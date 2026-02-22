import { NextResponse } from "next/server";
import { createAdminToken, getCookieName } from "@/lib/auth";

export async function POST(req: Request) {
  const body = await req.json().catch(() => ({}));
  const username = String(body?.username ?? "");
  const password = String(body?.password ?? "");
  if (!process.env.ADMIN_USER || !process.env.ADMIN_PASSWORD) {
    return NextResponse.json(
      { success: false, error: "ADMIN_USER and ADMIN_PASSWORD must be configured" },
      { status: 500 }
    );
  }

  if (username !== process.env.ADMIN_USER || password !== process.env.ADMIN_PASSWORD) {
    return NextResponse.json({ success: false, error: "Invalid username or password" }, { status: 401 });
  }

  const res = NextResponse.json({ success: true, message: "Logged in" });
  res.cookies.set(getCookieName(), createAdminToken(), {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    path: "/",
    maxAge: 60 * 60 * 24
  });
  return res;
}
