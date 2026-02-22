"use client";

import { FormEvent, useEffect, useState } from "react";
import { AdminNav } from "@/components/admin-nav";

type BusinessInfo = {
  business_name: string;
  email?: string;
  phone?: string;
  address?: string;
  description?: string;
  website?: string;
};

export default function AdminBusinessInfoPage() {
  const [info, setInfo] = useState<BusinessInfo>({ business_name: "Das House" });
  const [message, setMessage] = useState("");

  useEffect(() => {
    fetch("/api/business-info")
      .then((r) => r.json())
      .then((d) => setInfo(d.data ?? { business_name: "Das House" }))
      .catch(() => undefined);
  }, []);

  async function onSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setMessage("");
    const form = new FormData(e.currentTarget);
    const payload = {
      business_name: String(form.get("business_name") ?? ""),
      email: String(form.get("email") ?? ""),
      phone: String(form.get("phone") ?? ""),
      address: String(form.get("address") ?? ""),
      description: String(form.get("description") ?? ""),
      website: String(form.get("website") ?? "")
    };
    const res = await fetch("/api/business-info", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    setMessage(data.success ? "Saved" : data.error ?? "Failed");
  }

  return (
    <main>
      <h1>Business Info Manager</h1>
      <AdminNav />
      <form className="card" onSubmit={onSubmit}>
        <input name="business_name" defaultValue={info.business_name} required />
        <input name="email" defaultValue={info.email ?? ""} placeholder="Email" />
        <input name="phone" defaultValue={info.phone ?? ""} placeholder="Phone" />
        <input name="address" defaultValue={info.address ?? ""} placeholder="Address" />
        <input name="website" defaultValue={info.website ?? ""} placeholder="Website" />
        <textarea name="description" defaultValue={info.description ?? ""} placeholder="Description" />
        <button type="submit">Save</button>
        {message ? <p>{message}</p> : null}
      </form>
    </main>
  );
}
