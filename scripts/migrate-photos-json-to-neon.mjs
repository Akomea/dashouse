import fs from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { neon } from "@neondatabase/serverless";

const databaseUrl = process.env.DATABASE_URL;
if (!databaseUrl) {
  throw new Error("DATABASE_URL is required");
}

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const photosPath = path.resolve(__dirname, "../data/photos.json");
const photos = JSON.parse(await fs.readFile(photosPath, "utf8"));
const sql = neon(databaseUrl);

for (const photo of photos) {
  await sql`INSERT INTO photos
    (external_id, title, description, original_name, image_url, category_id, is_active, uploaded_at)
    VALUES
    (${photo.id ?? null}, ${photo.title ?? null}, ${photo.description ?? null}, ${photo.original_name ?? null}, ${photo.path}, ${photo.category_id ? Number(photo.category_id) : null}, ${Boolean(photo.active)}, ${photo.uploaded_at ?? new Date().toISOString()})
    ON CONFLICT DO NOTHING`;
}

console.log(`Migrated ${photos.length} photos from data/photos.json`);
