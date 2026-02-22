import { NextResponse } from "next/server";
import { getCookieName } from "@/lib/auth";

export async function POST() {
  const res = NextResponse.json({ success: true, message: "Logged out" });
  res.cookies.set(getCookieName(), "", {
    httpOnly: true,
    path: "/",
    maxAge: 0
  });
  return res;
}
