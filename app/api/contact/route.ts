import { fail, ok } from "@/lib/api";
import { isMailConfigured, sendMail } from "@/lib/mail";

export async function POST(req: Request) {
  try {
    const body = await req.json();
    const name = String(body?.name ?? "").trim();
    const email = String(body?.email ?? "").trim();
    const message = String(body?.message ?? "").trim();

    if (!name || !email || !message) {
      return fail("name, email and message are required");
    }

    if (!isMailConfigured()) {
      return fail("Email delivery is not configured on the server.", 503, {
        code: "SMTP_NOT_CONFIGURED",
      });
    }

    await sendMail({
      subject: `Das House Contact: ${name}`,
      text: `Name: ${name}\nEmail: ${email}\n\n${message}`,
      replyTo: email,
    });

    return ok({ message: "Message sent successfully" });
  } catch (error) {
    return fail(`Failed to send message: ${(error as Error).message}`, 500);
  }
}
