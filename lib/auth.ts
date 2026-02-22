import crypto from "crypto";

const COOKIE_NAME = "admin_session";
const DAY_SECONDS = 60 * 60 * 24;

function getSecret() {
  const secret = process.env.SESSION_SECRET;
  if (!secret) {
    throw new Error("SESSION_SECRET is required");
  }
  return secret;
}

function base64UrlEncode(input: Buffer | string): string {
  return Buffer.from(input)
    .toString("base64")
    .replaceAll("+", "-")
    .replaceAll("/", "_")
    .replace(/=+$/g, "");
}

function base64UrlDecode(input: string): string {
  const normalized = input.replaceAll("-", "+").replaceAll("_", "/");
  const pad = normalized.length % 4;
  const withPad = pad === 0 ? normalized : normalized + "=".repeat(4 - pad);
  return Buffer.from(withPad, "base64").toString("utf8");
}

export function signSession(payload: Record<string, unknown>): string {
  const body = base64UrlEncode(JSON.stringify(payload));
  const signature = crypto
    .createHmac("sha256", getSecret())
    .update(body)
    .digest("base64url");
  return `${body}.${signature}`;
}

export function verifySession(token: string | undefined): Record<string, unknown> | null {
  if (!token || !token.includes(".")) {
    return null;
  }
  const [body, signature] = token.split(".");
  const expected = crypto.createHmac("sha256", getSecret()).update(body).digest("base64url");
  if (signature !== expected) {
    return null;
  }
  try {
    const parsed = JSON.parse(base64UrlDecode(body)) as Record<string, unknown>;
    const exp = typeof parsed.exp === "number" ? parsed.exp : 0;
    if (Date.now() / 1000 > exp) {
      return null;
    }
    return parsed;
  } catch {
    return null;
  }
}

export function createAdminToken() {
  const exp = Math.floor(Date.now() / 1000) + DAY_SECONDS;
  return signSession({ role: "admin", exp });
}

export function getCookieName() {
  return COOKIE_NAME;
}
