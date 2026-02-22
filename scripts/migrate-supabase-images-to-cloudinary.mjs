import { neon } from "@neondatabase/serverless";
import { v2 as cloudinary } from "cloudinary";

const databaseUrl = process.env.DATABASE_URL;
if (!databaseUrl) throw new Error("DATABASE_URL is required");
if (!process.env.CLOUDINARY_CLOUD_NAME || !process.env.CLOUDINARY_API_KEY || !process.env.CLOUDINARY_API_SECRET) {
  throw new Error("CLOUDINARY_* variables are required");
}

cloudinary.config({
  cloud_name: process.env.CLOUDINARY_CLOUD_NAME,
  api_key: process.env.CLOUDINARY_API_KEY,
  api_secret: process.env.CLOUDINARY_API_SECRET
});

const sql = neon(databaseUrl);

async function migrateTable(tableName, idColumn = "id") {
  const rows = await sql.query(`SELECT ${idColumn}, image_url FROM ${tableName} WHERE image_url IS NOT NULL AND image_url <> ''`);
  let migrated = 0;
  for (const row of rows) {
    const imageUrl = row.image_url;
    if (!String(imageUrl).includes("supabase.co/storage")) continue;
    const uploaded = await cloudinary.uploader.upload(imageUrl, {
      folder: `das-house/${tableName}`
    });
    await sql.query(`UPDATE ${tableName} SET image_url = $1, updated_at = NOW() WHERE ${idColumn} = $2`, [
      uploaded.secure_url,
      row[idColumn]
    ]);
    migrated += 1;
  }
  console.log(`${tableName}: migrated ${migrated} rows`);
}

await migrateTable("categories");
await migrateTable("menu_items");
await migrateTable("gift_shop_items");
await migrateTable("photos");
