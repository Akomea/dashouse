import { cookies } from "next/headers";
import { getCookieName, verifySession } from "@/lib/auth";

export async function requireAdmin() {
  const jar = await cookies();
  const token = jar.get(getCookieName())?.value;
  const session = verifySession(token);
  if (!session) {
    return false;
  }
  return true;
}
