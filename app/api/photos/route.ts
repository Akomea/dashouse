import { NextRequest } from "next/server";
import { fail, ok, toBool } from "@/lib/api";
import { dbQuery } from "@/lib/db";

export async function GET(req: NextRequest) {
  try {
    const categoryId = req.nextUrl.searchParams.get("category_id");
    const result = categoryId
      ? await dbQuery`SELECT * FROM photos WHERE category_id = ${Number(categoryId)} ORDER BY sort_order, uploaded_at DESC`
      : await dbQuery`SELECT * FROM photos ORDER BY sort_order, uploaded_at DESC`;
    const data = result as Record<string, unknown>[];
    return ok({ data, count: data.length });
  } catch (error) {
    return fail(`Failed to fetch photos: ${(error as Error).message}`, 500);
  }
}

export async function POST(req: NextRequest) {
  try {
    const body = await req.json();
    if (!body?.image_url) return fail("image_url is required");
    const result = await dbQuery`INSERT INTO photos
      (external_id, title, description, original_name, image_url, thumbnail_url, category_id, category, is_active, sort_order, uploaded_at)
      VALUES
      (${body.external_id ?? null}, ${body.title ?? null}, ${body.description ?? null}, ${body.original_name ?? null}, ${body.image_url}, ${body.thumbnail_url ?? null}, ${body.category_id ?? null}, ${body.category ?? null}, ${toBool(body.is_active, true)}, ${Number(body.sort_order ?? 0)}, NOW())
      RETURNING *`;
    return ok({ message: "Photo created", data: result });
  } catch (error) {
    return fail(`Failed to create photo: ${(error as Error).message}`, 500);
  }
}

export async function PUT(req: NextRequest) {
  try {
    const body = await req.json();
    if (!body?.id) return fail("id is required");
    const result = await dbQuery`UPDATE photos
      SET
        title = COALESCE(${body.title}, title),
        description = COALESCE(${body.description}, description),
        category_id = COALESCE(${body.category_id}, category_id),
        category = COALESCE(${body.category}, category),
        image_url = COALESCE(${body.image_url}, image_url),
        thumbnail_url = COALESCE(${body.thumbnail_url}, thumbnail_url),
        is_active = COALESCE(${body.is_active}, is_active),
        sort_order = COALESCE(${body.sort_order}, sort_order),
        updated_at = NOW()
      WHERE id = ${Number(body.id)}
      RETURNING *`;
    return ok({ message: "Photo updated", data: result });
  } catch (error) {
    return fail(`Failed to update photo: ${(error as Error).message}`, 500);
  }
}

export async function DELETE(req: NextRequest) {
  try {
    const body = await req.json();
    if (!body?.id) return fail("id is required");
    await dbQuery`DELETE FROM photos WHERE id = ${Number(body.id)}`;
    return ok({ message: "Photo deleted" });
  } catch (error) {
    return fail(`Failed to delete photo: ${(error as Error).message}`, 500);
  }
}
