import { fail, ok } from "@/lib/api";

export async function POST(req: Request) {
  try {
    const form = await req.formData();
    const name = String(form.get("name") ?? form.get("widget-contact-form-name") ?? "");
    const email = String(form.get("email") ?? form.get("widget-contact-form-email") ?? "");
    const message = String(form.get("message") ?? form.get("widget-contact-form-message") ?? "");

    if (!name || !email || !message) {
      return fail("Missing required form fields");
    }
    return ok({
      alert: "success",
      message:
        "We have successfully received your message and will get back to you as soon as possible."
    });
  } catch (error) {
    return fail(`Unexpected error: ${(error as Error).message}`, 500);
  }
}
