import { NextRequest } from "next/server";
import { fail, ok } from "@/lib/api";
import { requireAdmin } from "@/lib/admin-guard";
import { dbQuery } from "@/lib/db";
import { ensureAdoptionTable } from "@/lib/adoption-store";

export async function GET() {
  try {
    if (!(await requireAdmin())) {
      return fail("Unauthorized", 401);
    }
    await ensureAdoptionTable();
    const data = await dbQuery`
      SELECT id, full_name, email, phone, locale, payload, status, created_at
      FROM adoption_applications
      ORDER BY created_at DESC
      LIMIT 200
    `;
    return ok({ data });
  } catch (error) {
    return fail(`Failed to load applications: ${(error as Error).message}`, 500);
  }
}

export async function PATCH(req: NextRequest) {
  try {
    if (!(await requireAdmin())) {
      return fail("Unauthorized", 401);
    }
    const body = await req.json();
    const id = Number(body?.id);
    const status = String(body?.status ?? "").trim();
    if (!id || !["new", "reviewed", "accepted", "declined"].includes(status)) {
      return fail("id and valid status are required");
    }
    await ensureAdoptionTable();
    const result = await dbQuery`
      UPDATE adoption_applications
      SET status = ${status}
      WHERE id = ${id}
      RETURNING id, status
    `;
    return ok({ data: result });
  } catch (error) {
    return fail(`Failed to update application: ${(error as Error).message}`, 500);
  }
}
