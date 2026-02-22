import { fail, ok } from "@/lib/api";

export async function POST(req: Request) {
  try {
    const body = await req.json();
    const email = String(body?.email ?? "").trim();
    if (!email) return fail("Email is required");

    // Provider integration hook: keep the API shape stable while moving away from PHP include handlers.
    // If provider keys are configured, call the provider API here.
    return ok({
      message: "Newsletter subscription accepted",
      data: {
        email,
        provider: process.env.NEWSLETTER_PROVIDER || "manual"
      }
    });
  } catch (error) {
    return fail(`Failed to subscribe: ${(error as Error).message}`, 500);
  }
}
