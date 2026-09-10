import { dbQuery } from "@/lib/db";
import type { AdoptPayload } from "@/lib/adopt";

export async function ensureAdoptionTable() {
  await dbQuery`
    CREATE TABLE IF NOT EXISTS adoption_applications (
      id BIGSERIAL PRIMARY KEY,
      full_name VARCHAR(200) NOT NULL,
      email VARCHAR(200) NOT NULL,
      phone VARCHAR(50) NOT NULL,
      locale VARCHAR(8) DEFAULT 'de',
      payload JSONB NOT NULL,
      status VARCHAR(32) DEFAULT 'new',
      created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
    )
  `;
}

export async function saveAdoptionApplication(data: AdoptPayload) {
  await ensureAdoptionTable();
  const result = await dbQuery`
    INSERT INTO adoption_applications (full_name, email, phone, locale, payload, status)
    VALUES (
      ${data.fullName},
      ${data.email},
      ${data.phone},
      ${data.locale ?? "de"},
      ${JSON.stringify(data)}::jsonb,
      ${"new"}
    )
    RETURNING id, created_at
  `;
  const rows = result as { id: number; created_at: string }[];
  return rows[0];
}
