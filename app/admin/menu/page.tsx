"use client";

import { FormEvent, useEffect, useState } from "react";
import { AdminNav } from "@/components/admin-nav";

type MenuItem = {
  id: number;
  name: string;
  price: number;
  category_id: number | null;
  description: string | null;
  is_active: boolean;
};

type Category = { id: number; name: string };

export default function AdminMenuPage() {
  const [items, setItems] = useState<MenuItem[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);

  async function load() {
    const [itemsRes, categoriesRes] = await Promise.all([
      fetch("/api/menu-items?is_active=1"),
      fetch("/api/categories?is_active=1")
    ]);
    const itemsData = await itemsRes.json();
    const categoriesData = await categoriesRes.json();
    setItems(itemsData.data ?? []);
    setCategories(categoriesData.data ?? []);
  }

  useEffect(() => {
    load().catch(() => undefined);
  }, []);

  async function create(formData: FormData) {
    await fetch("/api/menu-items", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        name: String(formData.get("name") ?? ""),
        price: Number(formData.get("price") ?? 0),
        category_id: Number(formData.get("category_id") ?? 0),
        description: String(formData.get("description") ?? "")
      })
    });
    await load();
  }

  async function remove(id: number) {
    await fetch("/api/menu-items", {
      method: "DELETE",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id })
    });
    await load();
  }

  async function onCreate(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    await create(formData);
    e.currentTarget.reset();
  }

  return (
    <main>
      <h1>Menu Manager</h1>
      <AdminNav />

      <form className="card" onSubmit={onCreate}>
        <h3>Create Menu Item</h3>
        <input name="name" placeholder="Name" required />
        <input name="price" placeholder="Price" type="number" step="0.01" required />
        <select name="category_id" required>
          <option value="">Select category</option>
          {categories.map((cat) => (
            <option value={cat.id} key={cat.id}>
              {cat.name}
            </option>
          ))}
        </select>
        <textarea name="description" placeholder="Description" />
        <button type="submit">Create</button>
      </form>

      <div className="card">
        <h3>Items</h3>
        {items.map((item) => (
          <div key={item.id} style={{ display: "flex", justifyContent: "space-between", gap: 12 }}>
            <div>
              {item.name} - €{item.price}
            </div>
            <button style={{ width: "auto" }} onClick={() => remove(item.id)}>
              Delete
            </button>
          </div>
        ))}
      </div>
    </main>
  );
}
