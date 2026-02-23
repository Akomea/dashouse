"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";

type Setting = {
  id: number;
  setting_key: string;
  setting_value: string | null;
  setting_type: string | null;
  description: string | null;
};

export default function AdminSettingsPage() {
  const [settings, setSettings] = useState<Setting[]>([]);

  const load = useCallback(async () => {
    const res = await fetch("/api/settings");
    const data = await res.json();
    setSettings(data.data ?? []);
  }, []);

  useEffect(() => {
    load().catch(() => undefined);
  }, [load]);

  async function onSave(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = new FormData(e.currentTarget);
    const res = await fetch("/api/settings", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        setting_key: form.get("setting_key"),
        setting_value: form.get("setting_value"),
        setting_type: form.get("setting_type"),
        description: form.get("description")
      })
    });
    const data = await res.json();
    if (!data.success) return;
    e.currentTarget.reset();
    load();
  }

  return (
    <>
      <div className="admin-page-header">
        <h2>⚙ Settings</h2>
      </div>

      <div className="admin-card">
        <h3 style={{ margin: "0 0 0.5rem" }}>Admin Panel</h3>
        <p style={{ margin: 0, fontSize: "0.9rem", color: "#6c757d" }}>
          Login uses environment variables <code>ADMIN_USER</code> and <code>ADMIN_PASSWORD</code>. To change the password, update these in your environment (e.g. <code>.env</code>) and redeploy.
        </p>
      </div>

      <div className="admin-card">
        <h3 style={{ margin: "0 0 1rem" }}>Add or update setting</h3>
        <form onSubmit={onSave}>
          <div className="admin-form-group">
            <label>Key *</label>
            <input name="setting_key" placeholder="setting_key" required />
          </div>
          <div className="admin-form-group">
            <label>Value</label>
            <input name="setting_value" placeholder="setting_value" />
          </div>
          <div className="admin-form-group">
            <label>Type</label>
            <input name="setting_type" placeholder="text or boolean" defaultValue="text" />
          </div>
          <div className="admin-form-group">
            <label>Description</label>
            <textarea name="description" placeholder="description" rows={2} />
          </div>
          <button type="submit" className="admin-btn admin-btn-primary">Save setting</button>
        </form>
      </div>

      <div className="admin-card">
        <h3 style={{ margin: "0 0 1rem" }}>Current settings</h3>
        {settings.length === 0 ? (
          <p style={{ margin: 0, color: "#6c757d" }}>No settings yet.</p>
        ) : (
          <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
            {settings.map((setting) => (
              <div key={setting.id}>
                <strong>{setting.setting_key}</strong>: {setting.setting_value ?? ""}
              </div>
            ))}
          </div>
        )}
      </div>
    </>
  );
}
