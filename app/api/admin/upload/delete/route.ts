import { fail, ok } from "@/lib/api";
import { getCloudinary } from "@/lib/cloudinary";

export async function POST(req: Request) {
  try {
    const body = await req.json();
    const publicId = String(body?.public_id ?? "").trim();
    if (!publicId) {
      return fail("public_id is required");
    }

    const result = await getCloudinary().uploader.destroy(publicId, {
      resource_type: "raw",
      invalidate: true
    });

    return ok({ data: result });
  } catch (error) {
    return fail(`Delete failed: ${(error as Error).message}`, 500);
  }
}
