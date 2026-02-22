import nodemailer from "nodemailer";
import { fail, ok } from "@/lib/api";

export async function POST(req: Request) {
  try {
    const body = await req.json();
    const name = String(body?.name ?? "").trim();
    const email = String(body?.email ?? "").trim();
    const message = String(body?.message ?? "").trim();

    if (!name || !email || !message) {
      return fail("name, email and message are required");
    }

    if (!process.env.SMTP_HOST || !process.env.SMTP_PORT || !process.env.SMTP_USER || !process.env.SMTP_PASS) {
      return fail("SMTP configuration is missing", 500);
    }

    const transporter = nodemailer.createTransport({
      host: process.env.SMTP_HOST,
      port: Number(process.env.SMTP_PORT),
      secure: Number(process.env.SMTP_PORT) === 465,
      auth: {
        user: process.env.SMTP_USER,
        pass: process.env.SMTP_PASS
      }
    });

    const recipient = process.env.CONTACT_TO_EMAIL || process.env.SMTP_USER;
    await transporter.sendMail({
      from: process.env.CONTACT_FROM_EMAIL || process.env.SMTP_USER,
      to: recipient,
      subject: `Das House Contact: ${name}`,
      text: `Name: ${name}\nEmail: ${email}\n\n${message}`
    });

    return ok({ message: "Message sent successfully" });
  } catch (error) {
    return fail(`Failed to send message: ${(error as Error).message}`, 500);
  }
}
