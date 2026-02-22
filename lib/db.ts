import { neon } from "@neondatabase/serverless";

function getClient() {
  const connectionString = process.env.DATABASE_URL;
  if (!connectionString) {
    throw new Error("DATABASE_URL is required");
  }
  return neon(connectionString);
}

export async function dbQuery<T = unknown[]>(
  strings: TemplateStringsArray,
  ...values: unknown[]
): Promise<T> {
  const client = getClient() as unknown as (
    tpl: TemplateStringsArray,
    ...params: unknown[]
  ) => Promise<T>;
  return client(strings, ...values);
}

export async function pingDb(): Promise<boolean> {
  const result = await dbQuery<{ ok: number }[]>`SELECT 1 as ok`;
  return Array.isArray(result) && result.length > 0;
}
