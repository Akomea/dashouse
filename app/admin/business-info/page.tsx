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
  const [formKey, setFormKey] = useState(0);

  async function loadInfo() {
    const r = await fetch("/api/business-info");
    const d = await r.json();
    if (!r.ok) throw new Error(d.error ?? "Failed to load");
    setInfo((d.data ?? { business_name: "Das House" }) as BusinessInfo);
    setFormKey((k) => k + 1);
  }

  useEffect(() => {
    loadInfo()
      .catch(() => setMessage("Could not load business info from server."))
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
      address: String(form.get("address") ?? "")
    };
    for (const day of days) {
      payload[`${day}_open`] = String(form.get(`${day}_open`) ?? "").trim() || null;
      payload[`${day}_close`] = String(form.get(`${day}_close`) ?? "").trim() || null;
    }
    try {
      const res = await fetch("/api/business-info", {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        setMessage("Saved");
        await loadInfo();
      } else {
        setMessage(data.error ?? "Failed to save");
      }
    } catch (err) {
      setMessage(err instanceof Error ? err.message : "Failed to save");
    }
  }

  if (loading) return <p>Loading…</p>;

  return (
    <>
      <div className="admin-page-header">
        <h2>Business Info Manager</h2>
      </div>

      {message && (
        <div className={`admin-alert ${message === "Saved" ? "admin-alert-success" : "admin-alert-danger"}`}>
          {message}
        </div>
      )}

      <form key={formKey} onSubmit={onSubmit} className="admin-card">
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

        <h3 style={{ margin: "1.5rem 0 1rem" }}>Operating hours</h3>
        <p style={{ margin: "0 0 1rem", fontSize: "0.9rem", color: "#6c757d" }}>Leave open/close empty for closed days.</p>
        <div style={{ display: "grid", gap: 12 }}>
          {days.map((day) => {
            const openVal = info[`${day}_open`] ?? "";
            const closeVal = info[`${day}_close`] ?? "";
            const isClosed = !String(openVal).trim() && !String(closeVal).trim();
            return (
              <div key={day} className="admin-hours-row" style={{ display: "flex", flexWrap: "wrap", alignItems: "center", gap: 12 }}>
                <span style={{ minWidth: 100, textTransform: "capitalize" }}>{day}</span>
                <input
                  name={`${day}_open`}
                  type="time"
                  defaultValue={openVal}
                  style={{ width: "auto", minWidth: 100 }}
                />
                <span>–</span>
                <input
                  name={`${day}_close`}
                  type="time"
                  defaultValue={closeVal}
                  style={{ width: "auto", minWidth: 100 }}
                />
                {isClosed && (
                  <span className="admin-hours-closed" style={{ marginLeft: "auto", display: "flex", alignItems: "center", gap: 6 }}>
                    <span aria-hidden>×</span>
                    <span>Closed</span>
                  </span>
                )}
              </div>
            );
          })}
        </div>

        <div style={{ marginTop: "1.5rem" }}>
          <button type="submit" className="admin-btn admin-btn-primary">Save</button>
        </div>
      </form>
    </>
  );
}
