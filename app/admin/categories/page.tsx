"use client";

import { FormEvent, useEffect, useState } from "react";
import { AdminNav } from "@/components/admin-nav";

type Category = { id: number; name: string; image_url: string | null; is_active: boolean };

export default function AdminCategoriesPage() {
  const [categories, setCategories] = useState<Category[]>([]);

  async function load() {
    const res = await fetch("/api/categories?is_active=1");
    const data = await res.json();
    setCategories(data.data ?? []);
  }

  useEffect(() => {
    load().catch(() => undefined);
  }, []);

  async function onCreate(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = new FormData(e.currentTarget);
    await fetch("/api/categories", {
      method: "POST",
      body: form
    });
    e.currentTarget.reset();
    await load();
  }

  async function onDelete(id: number) {
    await fetch("/api/categories", {
      method: "DELETE",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id })
    });
    await load();
  }

  return (
    <main>
      <h1>Category Manager</h1>
      <AdminNav />

      <form className="card" onSubmit={onCreate}>
        <h3>Create Category</h3>
        <input name="name" placeholder="Category name" required />
        <textarea name="description" placeholder="Description" />
        <input name="image" type="file" accept="image/*" />
        <button type="submit">Save</button>
      </form>

      <div className="card">
        <h3>Categories</h3>
        {categories.map((category) => (
          <div key={category.id} style={{ display: "flex", justifyContent: "space-between", gap: 12 }}>
            <div>
              <strong>{category.name}</strong>
              {category.image_url ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img src={category.image_url} alt={category.name} style={{ display: "block", height: 48, marginTop: 6 }} />
              ) : null}
            </div>
            <button style={{ width: "auto" }} onClick={() => onDelete(category.id)}>
              Delete
            </button>
          </div>
        ))}
      </div>
    </main>
  );
}
