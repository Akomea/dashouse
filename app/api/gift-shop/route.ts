import { NextRequest } from "next/server";
import { fail, ok, toBool } from "@/lib/api";
import { dbQuery } from "@/lib/db";

export async function GET(req: NextRequest) {
  try {
    const activeParam = req.nextUrl.searchParams.get("is_active");
    const hasFilter = activeParam !== null;
    const active = toBool(activeParam, true);
    const result = hasFilter
      ? await dbQuery`SELECT id, name, description, image_url, active, sort_order, created_at
                  FROM gift_shop_items
                  WHERE active = ${active}
                  ORDER BY sort_order, name`
      : await dbQuery`SELECT id, name, description, image_url, active, sort_order, created_at
                  FROM gift_shop_items
                  ORDER BY sort_order, name`;
    const data = result as Record<string, unknown>[];
    return ok({ data, count: data.length });
  } catch {
    return ok({ data: [], count: 0 });
  }
}

export async function POST(req: NextRequest) {
  try {
    const body = await req.json();
    if (!body?.name || !body?.image_url) {
      return fail("Missing required fields: name, image_url");
    }
    const result = await dbQuery`INSERT INTO gift_shop_items
      (name, description, image_url, filename, original_name, active, sort_order)
      VALUES
      (${body.name}, ${body.description ?? ""}, ${body.image_url}, ${body.filename ?? ""}, ${body.original_name ?? body.name}, ${toBool(body.active, true)}, ${Number(body.sort_order ?? 0)})
      RETURNING *`;
    return ok({ message: "Gift shop item added successfully", data: result });
  } catch (error) {
    return fail(`Failed to save gift shop item: ${(error as Error).message}`, 500);
  }
}

export async function PUT(req: NextRequest) {
  try {
    const body = await req.json();
    if (!body?.id) return fail("Missing item ID");

    const result = await dbQuery`UPDATE gift_shop_items
      SET
        name = COALESCE(${body.name}, name),
        description = COALESCE(${body.description}, description),
        active = COALESCE(${body.active}, active),
        sort_order = COALESCE(${body.sort_order}, sort_order),
        updated_at = NOW()
      WHERE id = ${Number(body.id)}
      RETURNING *`;
    return ok({ message: "Gift shop item updated successfully", data: result });
  } catch (error) {
    return fail(`Failed to update gift shop item: ${(error as Error).message}`, 500);
  }
}

export async function DELETE(req: NextRequest) {
  try {
    const body = await req.json();
    if (!body?.id) return fail("Missing item ID");
    await dbQuery`DELETE FROM gift_shop_items WHERE id = ${Number(body.id)}`;
    return ok({ message: "Gift shop item deleted successfully" });
  } catch (error) {
    return fail(`Failed to delete gift shop item: ${(error as Error).message}`, 500);
  }
}
