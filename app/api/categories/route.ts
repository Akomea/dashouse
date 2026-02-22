import { NextRequest } from "next/server";
import { fail, ok, toBool } from "@/lib/api";
import { getCloudinary } from "@/lib/cloudinary";
import { dbQuery } from "@/lib/db";

const defaultCategories = [
  { id: 1, name: "Breakfast & Waffles", description: null, image_url: "/demos/burger/images/others/burger.png", sort_order: 0 },
  { id: 2, name: "Snacks & Meze", description: null, image_url: "/demos/burger/images/others/snacks.png", sort_order: 1 },
  { id: 3, name: "Beverages", description: null, image_url: "/demos/burger/images/others/beverage.png", sort_order: 2 },
  { id: 4, name: "Cocktails & Spirits", description: null, image_url: "/demos/burger/images/others/beverage-1.png", sort_order: 3 }
];

async function uploadImage(file: File) {
  const arrayBuffer = await file.arrayBuffer();
  const buffer = Buffer.from(arrayBuffer);
  const dataUri = `data:${file.type};base64,${buffer.toString("base64")}`;
  return getCloudinary().uploader.upload(dataUri, {
    folder: "das-house/categories"
  });
}

export async function GET(req: NextRequest) {
  try {
    const isActive = req.nextUrl.searchParams.get("is_active");
    const active = toBool(isActive, true);
    const data = (await dbQuery`SELECT *
                           FROM categories
                           WHERE is_active = ${active}
                           ORDER BY sort_order, name`) as Record<string, unknown>[];
    if (data.length === 0) {
      return ok({ data: defaultCategories, count: defaultCategories.length });
    }
    return ok({ data, count: data.length });
  } catch {
    return ok({ data: defaultCategories, count: defaultCategories.length });
  }
}

export async function POST(req: NextRequest) {
  try {
    const contentType = req.headers.get("content-type") || "";
    let body: Record<string, FormDataEntryValue> = {};
    let imageUrl = "";

    if (contentType.includes("multipart/form-data")) {
      const form = await req.formData();
      body = Object.fromEntries(form.entries());
      const image = form.get("image");
      if (image instanceof File && image.size > 0) {
        const upload = await uploadImage(image);
        imageUrl = upload.secure_url;
      }
    } else {
      body = (await req.json()) as Record<string, FormDataEntryValue>;
    }

    if (!body.name || String(body.name).trim() === "") {
      return fail("Category name is required");
    }

    const result = await dbQuery`INSERT INTO categories
      (name, description, image_url, sort_order, is_active)
      VALUES
      (${String(body.name)}, ${String(body.description ?? "")}, ${String(body.image_url ?? imageUrl)}, ${Number(body.sort_order ?? 0)}, ${toBool(body.is_active, true)})
      RETURNING *`;

    return ok({ message: "Category added successfully", data: result });
  } catch (error) {
    return fail(`Failed to add category: ${(error as Error).message}`, 500);
  }
}

export async function PUT(req: NextRequest) {
  try {
    const body = await req.json();
    if (!body?.id) return fail("Category ID is required");

    const result = await dbQuery`UPDATE categories
      SET
        name = COALESCE(${body.name}, name),
        description = COALESCE(${body.description}, description),
        image_url = COALESCE(${body.image_url}, image_url),
        sort_order = COALESCE(${body.sort_order}, sort_order),
        is_active = COALESCE(${body.is_active}, is_active),
        updated_at = NOW()
      WHERE id = ${Number(body.id)}
      RETURNING *`;

    return ok({ message: "Category updated successfully", data: result });
  } catch (error) {
    return fail(`Failed to update category: ${(error as Error).message}`, 500);
  }
}

export async function DELETE(req: NextRequest) {
  try {
    const body = await req.json();
    if (!body?.id) return fail("Category ID is required");

    const menuItems = (await dbQuery`SELECT id FROM menu_items WHERE category_id = ${Number(body.id)} LIMIT 1`) as Record<string, unknown>[];
    if (menuItems.length > 0) {
      const result = await dbQuery`UPDATE categories
                               SET is_active = false, updated_at = NOW()
                               WHERE id = ${Number(body.id)}
                               RETURNING *`;
      return ok({ message: "Category deactivated (has menu items)", data: result });
    }

    const result = await dbQuery`DELETE FROM categories WHERE id = ${Number(body.id)} RETURNING id`;
    return ok({ message: "Category deleted successfully", data: result });
  } catch (error) {
    return fail(`Failed to delete category: ${(error as Error).message}`, 500);
  }
}

export async function PATCH(req: NextRequest) {
  try {
    const form = await req.formData();
    const id = form.get("id");
    const image = form.get("image");

    if (!id) return fail("Category ID is required");
    if (!(image instanceof File) || image.size <= 0) return fail("Image file is required");

    const upload = await uploadImage(image);
    const result = await dbQuery`UPDATE categories
                             SET image_url = ${upload.secure_url}, updated_at = NOW()
                             WHERE id = ${Number(id)}
                             RETURNING *`;
    return ok({
      message: "Category image updated successfully",
      image_url: upload.secure_url,
      data: result
    });
  } catch (error) {
    return fail(`Image upload failed: ${(error as Error).message}`, 500);
  }
}
