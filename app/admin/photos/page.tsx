"use client";

import { FormEvent, useEffect, useState } from "react";
import { AdminNav } from "@/components/admin-nav";

type Photo = { id: number; title: string | null; image_url: string; category_id: number | null };
type Category = { id: number; name: string };

export default function AdminPhotosPage() {
  const [photos, setPhotos] = useState<Photo[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);

  async function load() {
    const [photoRes, categoryRes] = await Promise.all([
      fetch("/api/photos"),
      fetch("/api/categories?is_active=1")
    ]);
    const photoData = await photoRes.json();
    const categoryData = await categoryRes.json();
    setPhotos(photoData.data ?? []);
    setCategories(categoryData.data ?? []);
  }

  useEffect(() => {
    load().catch(() => undefined);
  }, []);

  async function onCreate(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = new FormData(e.currentTarget);
    const file = form.get("file");
    if (!(file instanceof File) || file.size <= 0) return;

    const upload = new FormData();
    upload.set("file", file);
    upload.set("folder", "das-house/photos");
    const uploadRes = await fetch("/api/admin/upload", { method: "POST", body: upload });
    const uploadData = await uploadRes.json();

    await fetch("/api/photos", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        title: String(form.get("title") ?? ""),
        description: String(form.get("description") ?? ""),
        category_id: Number(form.get("category_id") ?? 0) || null,
        image_url: uploadData?.data?.url ?? "",
        external_id: uploadData?.data?.public_id ?? null,
        original_name: uploadData?.data?.original_name ?? null
      })
    });

    e.currentTarget.reset();
    await load();
  }

  async function onDelete(id: number) {
    await fetch("/api/photos", {
      method: "DELETE",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id })
    });
    await load();
  }

  async function setCategoryImage(photo: Photo, categoryId: number) {
    await fetch("/api/categories", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: categoryId, image_url: photo.image_url })
    });
  }

  return (
    <main>
      <h1>Photo Manager</h1>
      <AdminNav />
      <form className="card" onSubmit={onCreate}>
        <h3>Upload Photo</h3>
        <input name="title" placeholder="Title" />
        <textarea name="description" placeholder="Description" />
        <select name="category_id">
          <option value="">Category (optional)</option>
          {categories.map((category) => (
            <option value={category.id} key={category.id}>
              {category.name}
            </option>
          ))}
        </select>
        <input name="file" type="file" accept="image/*" required />
        <button type="submit">Upload</button>
      </form>

      <div className="card">
        <h3>Photos</h3>
        {photos.map((photo) => (
          <div key={photo.id} style={{ display: "flex", justifyContent: "space-between", gap: 12 }}>
            <div>
              {photo.title || "Untitled"}
              <div>
                <select onChange={(e) => setCategoryImage(photo, Number(e.target.value))} defaultValue="">
                  <option value="">Set as category image...</option>
                  {categories.map((category) => (
                    <option value={category.id} key={category.id}>
                      {category.name}
                    </option>
                  ))}
                </select>
              </div>
            </div>
            <button style={{ width: "auto" }} onClick={() => onDelete(photo.id)}>
              Delete
            </button>
          </div>
        ))}
      </div>
    </main>
  );
}
