import { NextRequest } from "next/server";
import { fail, ok, toBool } from "@/lib/api";
import { dbQuery } from "@/lib/db";

const defaultMenuItems = [
  { id: 1, category_id: 1, name: "Classic Waffle", description: "Fluffy waffle with syrup", price: 8.5, category_name: "Breakfast & Waffles" },
  { id: 2, category_id: 2, name: "Chicken Wings", description: "Crispy wings with sauce", price: 12, category_name: "Snacks & Meze" },
  { id: 3, category_id: 3, name: "Fresh Coffee", description: "Aromatic coffee blend", price: 3.5, category_name: "Beverages" },
  { id: 4, category_id: 4, name: "House Cocktail", description: "Signature cocktail", price: 9, category_name: "Cocktails & Spirits" }
];

export async function GET(req: NextRequest) {
  try {
    const categoryId = req.nextUrl.searchParams.get("category_id");
    const isActive = req.nextUrl.searchParams.get("is_active");
    const active = toBool(isActive, true);

    const result = categoryId
      ? await dbQuery`SELECT m.*, c.name AS category_name
                  FROM menu_items m
                  LEFT JOIN categories c ON c.id = m.category_id
                  WHERE m.is_active = ${active} AND m.category_id = ${Number(categoryId)}
                  ORDER BY m.sort_order, m.name`
      : await dbQuery`SELECT m.*, c.name AS category_name
                  FROM menu_items m
                  LEFT JOIN categories c ON c.id = m.category_id
                  WHERE m.is_active = ${active}
                  ORDER BY m.sort_order, m.name`;
    const data = result as Record<string, unknown>[];

    if (data.length === 0) {
      return ok({ data: defaultMenuItems, count: defaultMenuItems.length });
    }
    return ok({ data, count: data.length });
  } catch {
    return ok({ data: defaultMenuItems, count: defaultMenuItems.length });
  }
}

export async function POST(req: NextRequest) {
  try {
    const body = await req.json();
    if (!body?.name || body?.price === undefined || !body?.category_id) {
      return fail("Missing required fields: name, price, category_id");
    }
    const result = await dbQuery`INSERT INTO menu_items
      (category_id, name, description, price, image_url, is_vegetarian, is_vegan, is_gluten_free, allergens, sort_order, is_active)
      VALUES
      (${Number(body.category_id)}, ${body.name}, ${body.description ?? ""}, ${Number(body.price)}, ${body.image_url ?? ""}, ${toBool(body.is_vegetarian)}, ${toBool(body.is_vegan)}, ${toBool(body.is_gluten_free)}, ${body.allergens ?? ""}, ${Number(body.sort_order ?? 0)}, ${toBool(body.is_active, true)})
      RETURNING *`;
    return ok({ message: "Menu item added successfully", data: result });
  } catch (error) {
    return fail(`Failed to add menu item: ${(error as Error).message}`, 500);
  }
}

export async function PUT(req: NextRequest) {
  try {
    const body = await req.json();
    if (!body?.id) return fail("Missing menu item ID");
    const result = await dbQuery`UPDATE menu_items
      SET
        category_id = COALESCE(${body.category_id}, category_id),
        name = COALESCE(${body.name}, name),
        description = COALESCE(${body.description}, description),
        price = COALESCE(${body.price}, price),
        image_url = COALESCE(${body.image_url}, image_url),
        is_vegetarian = COALESCE(${body.is_vegetarian}, is_vegetarian),
        is_vegan = COALESCE(${body.is_vegan}, is_vegan),
        is_gluten_free = COALESCE(${body.is_gluten_free}, is_gluten_free),
        allergens = COALESCE(${body.allergens}, allergens),
        sort_order = COALESCE(${body.sort_order}, sort_order),
        is_active = COALESCE(${body.is_active}, is_active),
        updated_at = NOW()
      WHERE id = ${Number(body.id)}
      RETURNING *`;
    return ok({ message: "Menu item updated successfully", data: result });
  } catch (error) {
    return fail(`Failed to update menu item: ${(error as Error).message}`, 500);
  }
}

export async function DELETE(req: NextRequest) {
  try {
    const body = await req.json();
    if (!body?.id) return fail("Missing menu item ID");
    await dbQuery`DELETE FROM menu_items WHERE id = ${Number(body.id)}`;
    return ok({ message: "Menu item deleted successfully" });
  } catch (error) {
    return fail(`Failed to delete menu item: ${(error as Error).message}`, 500);
  }
}
