"use client";

import { FormEvent, useEffect, useState } from "react";
import { AdminNav } from "@/components/admin-nav";

type GiftItem = { id: number; name: string; description: string | null; image_url: string; active: boolean };

export default function AdminGiftShopPage() {
  const [items, setItems] = useState<GiftItem[]>([]);

  async function load() {
    const res = await fetch("/api/gift-shop?is_active=1");
    const data = await res.json();
    setItems(data.data ?? []);
  }

  useEffect(() => {
    load().catch(() => undefined);
  }, []);

  async function onCreate(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = new FormData(e.currentTarget);
    const file = form.get("file");
    let image_url = "";
    if (file instanceof File && file.size > 0) {
      const upload = new FormData();
      upload.set("file", file);
      upload.set("folder", "das-house/gift-shop");
      const uploadRes = await fetch("/api/admin/upload", { method: "POST", body: upload });
      const uploadData = await uploadRes.json();
      image_url = uploadData?.data?.url ?? "";
    }

    await fetch("/api/gift-shop", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        name: String(form.get("name") ?? ""),
        description: String(form.get("description") ?? ""),
        image_url
      })
    });
    e.currentTarget.reset();
    await load();
  }

  async function onDelete(id: number) {
    await fetch("/api/gift-shop", {
      method: "DELETE",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id })
    });
    await load();
  }

  return (
    <main>
      <h1>Gift Shop Manager</h1>
      <AdminNav />
      <form className="card" onSubmit={onCreate}>
        <h3>Add Gift Item</h3>
        <input name="name" placeholder="Name" required />
        <textarea name="description" placeholder="Description" />
        <input name="file" type="file" accept="image/*" required />
        <button type="submit">Save</button>
      </form>

      <div className="card">
        <h3>Items</h3>
        {items.map((item) => (
          <div key={item.id} style={{ display: "flex", justifyContent: "space-between", gap: 12 }}>
            <div>{item.name}</div>
            <button style={{ width: "auto" }} onClick={() => onDelete(item.id)}>
              Delete
            </button>
          </div>
        ))}
      </div>
    </main>
  );
}
