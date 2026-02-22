import { spawn } from "node:child_process";
import path from "node:path";
import { fileURLToPath } from "node:url";

const databaseUrl = process.env.DATABASE_URL;
if (!databaseUrl) {
  throw new Error("DATABASE_URL is required");
}

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const schemaPath = path.resolve(__dirname, "../neon/schema.sql");

await new Promise((resolve, reject) => {
  const child = spawn(
    "psql",
    [databaseUrl, "-v", "ON_ERROR_STOP=1", "-f", schemaPath],
    { stdio: "inherit" }
  );

  child.on("error", reject);
  child.on("close", (code) => {
    if (code === 0) resolve();
    else reject(new Error(`psql exited with code ${code}`));
  });
});

console.log("Neon schema applied via psql.");
