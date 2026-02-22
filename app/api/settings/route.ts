import { NextRequest } from "next/server";
import { fail, ok } from "@/lib/api";
import { dbQuery } from "@/lib/db";

export async function GET() {
  try {
    const data = (await dbQuery`SELECT * FROM settings ORDER BY setting_key`) as Record<string, unknown>[];
    return ok({ data, count: data.length });
  } catch (error) {
    return fail(`Failed to fetch settings: ${(error as Error).message}`, 500);
  }
}

export async function PUT(req: NextRequest) {
  try {
    const body = await req.json();
    if (!body?.setting_key) return fail("setting_key is required");
    const result = await dbQuery`INSERT INTO settings (setting_key, setting_value, setting_type, description, updated_at)
      VALUES (${body.setting_key}, ${body.setting_value ?? ""}, ${body.setting_type ?? "text"}, ${body.description ?? ""}, NOW())
      ON CONFLICT (setting_key) DO UPDATE SET
        setting_value = EXCLUDED.setting_value,
        setting_type = EXCLUDED.setting_type,
        description = EXCLUDED.description,
        updated_at = NOW()
      RETURNING *`;
    return ok({ message: "Setting saved", data: result });
  } catch (error) {
    return fail(`Failed to save setting: ${(error as Error).message}`, 500);
  }
}
