import { NextResponse } from "next/server";

export function ok(data: unknown, init?: ResponseInit) {
  return NextResponse.json({ success: true, ...(typeof data === "object" && data ? data : { data }) }, init);
}

export function fail(error: string, status = 400) {
  return NextResponse.json({ success: false, error }, { status });
}

export function toBool(value: unknown, fallback = false) {
  if (typeof value === "boolean") return value;
  if (typeof value === "string") {
    const lower = value.toLowerCase();
    if (lower === "true" || lower === "1") return true;
    if (lower === "false" || lower === "0") return false;
  }
  return fallback;
}
