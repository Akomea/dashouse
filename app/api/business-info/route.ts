import { NextRequest } from "next/server";
import { fail, ok } from "@/lib/api";
import { dbQuery } from "@/lib/db";

const defaultInfo = {
  business_name: "Das House",
  email: "info@dashouse.at",
  phone: "+43 677 634 238 81",
  address: "Austria, Vienna\nGumpendorfer strasse 51",
  description: "",
  website: "",
  monday_open: "",
  monday_close: "",
  tuesday_open: "10:00",
  tuesday_close: "23:30",
  wednesday_open: "10:00",
  wednesday_close: "23:30",
  thursday_open: "10:00",
  thursday_close: "23:30",
  friday_open: "10:00",
  friday_close: "01:00",
  saturday_open: "10:00",
  saturday_close: "01:00",
  sunday_open: "10:00",
  sunday_close: "19:00"
};

const days = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"] as const;

/** Normalize DB row for the frontend: TIME comes as "HH:mm:ss", input expects "HH:mm". */
function normalizeRow(row: Record<string, unknown> | null | undefined): Record<string, unknown> {
  const base = row ?? defaultInfo;
  const out: Record<string, unknown> = {
    business_name: base.business_name ?? defaultInfo.business_name,
    email: base.email ?? defaultInfo.email,
    phone: base.phone ?? defaultInfo.phone,
    address: base.address ?? defaultInfo.address,
    description: base.description ?? defaultInfo.description,
    website: base.website ?? defaultInfo.website
  };
  for (const day of days) {
    const open = base[`${day}_open`];
    const close = base[`${day}_close`];
    out[`${day}_open`] = normalizeTime(open);
    out[`${day}_close`] = normalizeTime(close);
  }
  return out;
}

function normalizeTime(v: unknown): string {
  if (v == null) return "";
  const s = String(v).trim();
  if (!s) return "";
  // "10:00:00" or "01:00:00" -> "10:00" / "01:00"
  if (s.length >= 5) return s.slice(0, 5);
  return s;
}

export async function GET() {
  try {
    const result = (await dbQuery`SELECT * FROM business_info WHERE id = 1`) as Record<string, unknown>[];
    const row = result[0];
    return ok({ data: normalizeRow(row ?? null) });
  } catch (err) {
    console.error("business-info GET:", err);
    return ok({ data: defaultInfo });
  }
}

export async function POST(req: NextRequest) {
  return upsertBusinessInfo(req);
}

export async function PUT(req: NextRequest) {
  return upsertBusinessInfo(req);
}

export async function PATCH(req: NextRequest) {
  return upsertBusinessInfo(req);
}

async function upsertBusinessInfo(req: NextRequest) {
  try {
    const body = await req.json();
    if (!body?.business_name) {
      return fail("Business name is required");
    }

    await dbQuery`INSERT INTO business_info (id, business_name, email, phone, address, description, website, social_media,
      monday_open, monday_close, tuesday_open, tuesday_close, wednesday_open, wednesday_close,
      thursday_open, thursday_close, friday_open, friday_close, saturday_open, saturday_close, sunday_open, sunday_close, updated_at)
      VALUES
      (1, ${body.business_name}, ${body.email ?? null}, ${body.phone ?? null}, ${body.address ?? null}, ${body.description ?? null}, ${body.website ?? null}, ${body.social_media ?? null},
      ${body.monday_open ?? null}, ${body.monday_close ?? null}, ${body.tuesday_open ?? null}, ${body.tuesday_close ?? null}, ${body.wednesday_open ?? null}, ${body.wednesday_close ?? null},
      ${body.thursday_open ?? null}, ${body.thursday_close ?? null}, ${body.friday_open ?? null}, ${body.friday_close ?? null}, ${body.saturday_open ?? null}, ${body.saturday_close ?? null}, ${body.sunday_open ?? null}, ${body.sunday_close ?? null}, NOW())
      ON CONFLICT (id) DO UPDATE SET
        business_name = EXCLUDED.business_name,
        email = EXCLUDED.email,
        phone = EXCLUDED.phone,
        address = EXCLUDED.address,
        description = EXCLUDED.description,
        website = EXCLUDED.website,
        social_media = EXCLUDED.social_media,
        monday_open = EXCLUDED.monday_open,
        monday_close = EXCLUDED.monday_close,
        tuesday_open = EXCLUDED.tuesday_open,
        tuesday_close = EXCLUDED.tuesday_close,
        wednesday_open = EXCLUDED.wednesday_open,
        wednesday_close = EXCLUDED.wednesday_close,
        thursday_open = EXCLUDED.thursday_open,
        thursday_close = EXCLUDED.thursday_close,
        friday_open = EXCLUDED.friday_open,
        friday_close = EXCLUDED.friday_close,
        saturday_open = EXCLUDED.saturday_open,
        saturday_close = EXCLUDED.saturday_close,
        sunday_open = EXCLUDED.sunday_open,
        sunday_close = EXCLUDED.sunday_close,
        updated_at = NOW()`;

    return ok({ message: "Business information updated successfully" });
  } catch (error) {
    return fail(`Failed to update business information: ${(error as Error).message}`, 500);
  }
}
