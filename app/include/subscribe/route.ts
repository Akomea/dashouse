import { fail, ok } from "@/lib/api";

export async function POST(req: Request) {
  try {
    const form = await req.formData();
    const email = String(form.get("widget-subscribe-form-email") ?? "");
    if (!email) {
      return fail("Email is required");
    }
    return ok({
      alert: "success",
      message: "You have been successfully subscribed to our Email List."
    });
  } catch (error) {
    return fail(`Subscription failed: ${(error as Error).message}`, 500);
  }
}
