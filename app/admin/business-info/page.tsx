"use client";

import { FormEvent, useEffect, useState } from "react";

type BusinessInfo = {
  business_name: string;
  email?: string;
  phone?: string;
  address?: string;
  description?: string;
  website?: string;
  monday_open?: string;
  monday_close?: string;
  tuesday_open?: string;
  tuesday_close?: string;
  wednesday_open?: string;
  wednesday_close?: string;
  thursday_open?: string;
  thursday_close?: string;
  friday_open?: string;
  friday_close?: string;
  saturday_open?: string;
  saturday_close?: string;
  sunday_open?: string;
  sunday_close?: string;
};

const days = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"] as const;

export default function AdminBusinessInfoPage() {
  const [info, setInfo] = useState<BusinessInfo>({ business_name: "Das House" });
  const [message, setMessage] = useState<"" | "Saved" | string>("");
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch("/api/business-info")
      .then((r) => r.json())
      .then((d) => setInfo(d.data ?? { business_name: "Das House" }))
      .catch(() => undefined)
      .finally(() => setLoading(false));
  }, []);

  async function onSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setMessage("");
    const form = new FormData(e.currentTarget);
    const payload: Record<string, string | null> = {
      business_name: String(form.get("business_name") ?? ""),
      email: String(form.get("email") ?? ""),
      phone: String(form.get("phone") ?? ""),
      address: String(form.get("address") ?? ""),
      description: String(form.get("description") ?? ""),
      website: String(form.get("website") ?? "")
    };
    for (const day of days) {
      payload[`${day}_open`] = String(form.get(`${day}_open`) ?? "").trim() || null;
      payload[`${day}_close`] = String(form.get(`${day}_close`) ?? "").trim() || null;
    }
    const res = await fetch("/api/business-info", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    setMessage(data.success ? "Saved" : data.error ?? "Failed");
  }

  if (loading) return <p>Loading…</p>;

  return (
    <>
      <div className="admin-page-header">
        <h2>🏢 Business Info Manager</h2>
      </div>

      {message && (
        <div className={`admin-alert ${message === "Saved" ? "admin-alert-success" : "admin-alert-danger"}`}>
          {message}
        </div>
      )}

      <form onSubmit={onSubmit} className="admin-card">
        <h3 style={{ margin: "0 0 1rem" }}>Contact &amp; details</h3>
        <div className="admin-form-group">
          <label>Business name *</label>
          <input name="business_name" defaultValue={info.business_name} required />
        </div>
        <div className="admin-form-group">
          <label>Email</label>
          <input name="email" type="email" defaultValue={info.email ?? ""} placeholder="Email" />
        </div>
        <div className="admin-form-group">
          <label>Phone</label>
          <input name="phone" defaultValue={info.phone ?? ""} placeholder="Phone" />
        </div>
        <div className="admin-form-group">
          <label>Address</label>
          <textarea name="address" rows={2} defaultValue={info.address ?? ""} placeholder="Address" />
        </div>
        <div className="admin-form-group">
          <label>Website</label>
          <input name="website" type="url" defaultValue={info.website ?? ""} placeholder="Website" />
        </div>
        <div className="admin-form-group">
          <label>Description</label>
          <textarea name="description" rows={3} defaultValue={info.description ?? ""} placeholder="Description" />
        </div>

        <h3 style={{ margin: "1.5rem 0 1rem" }}>Operating hours</h3>
        <p style={{ margin: "0 0 1rem", fontSize: "0.9rem", color: "#6c757d" }}>Leave open/close empty for closed days.</p>
        <div style={{ display: "grid", gap: 12 }}>
          {days.map((day) => (
            <div key={day} style={{ display: "flex", flexWrap: "wrap", alignItems: "center", gap: 12 }}>
              <span style={{ minWidth: 100, textTransform: "capitalize" }}>{day}</span>
              <input
                name={`${day}_open`}
                type="time"
                defaultValue={info[`${day}_open`] ?? ""}
                style={{ width: "auto", minWidth: 100 }}
              />
              <span>–</span>
              <input
                name={`${day}_close`}
                type="time"
                defaultValue={info[`${day}_close`] ?? ""}
                style={{ width: "auto", minWidth: 100 }}
              />
            </div>
          ))}
        </div>

        <div style={{ marginTop: "1.5rem" }}>
          <button type="submit" className="admin-btn admin-btn-primary">Save</button>
        </div>
      </form>
    </>
  );
}
