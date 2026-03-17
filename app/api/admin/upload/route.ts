import { fail, ok } from "@/lib/api";
import { getCloudinary } from "@/lib/cloudinary";

export async function POST(req: Request) {
  try {
    const form = await req.formData();
    const file = form.get("file");
    const folder = String(form.get("folder") ?? "das-house/uploads");
    if (!(file instanceof File) || file.size <= 0) {
      return fail("File is required");
    }

    const allowed = [
      "image/jpeg",
      "image/png",
      "image/gif",
      "image/webp",
      "application/pdf"
    ];
    if (!allowed.includes(file.type)) {
      return fail("Invalid file type");
    }

    const maxSizeBytes =
      file.type === "application/pdf" ? 20 * 1024 * 1024 : 5 * 1024 * 1024;
    if (file.size > maxSizeBytes) {
      const maxSizeMb = file.type === "application/pdf" ? 20 : 5;
      return fail(`File too large. Maximum size is ${maxSizeMb}MB`);
    }

    const arrayBuffer = await file.arrayBuffer();
    const buffer = Buffer.from(arrayBuffer);
    const dataUri = `data:${file.type};base64,${buffer.toString("base64")}`;

    const uploaded = await getCloudinary().uploader.upload(dataUri, {
      folder,
      resource_type: "auto"
    });
    return ok({
      data: {
        url: uploaded.secure_url,
        public_id: uploaded.public_id,
        original_name: file.name
      }
    });
  } catch (error) {
    return fail(`Upload failed: ${(error as Error).message}`, 500);
  }
}
