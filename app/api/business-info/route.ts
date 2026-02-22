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

export async function GET() {
  try {
    const result = (await dbQuery`SELECT * FROM business_info WHERE id = 1`) as Record<string, unknown>[];
    return ok({ data: result[0] ?? defaultInfo });
  } catch {
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
