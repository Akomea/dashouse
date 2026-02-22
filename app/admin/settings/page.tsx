"use client";

import { FormEvent, useEffect, useState } from "react";
import { AdminNav } from "@/components/admin-nav";

type Setting = {
  id: number;
  setting_key: string;
  setting_value: string | null;
  setting_type: string | null;
  description: string | null;
};

export default function AdminSettingsPage() {
  const [settings, setSettings] = useState<Setting[]>([]);

  async function load() {
    const res = await fetch("/api/settings");
    const data = await res.json();
    setSettings(data.data ?? []);
  }

  useEffect(() => {
    load().catch(() => undefined);
  }, []);

  async function onSave(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = new FormData(e.currentTarget);
    await fetch("/api/settings", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        setting_key: form.get("setting_key"),
        setting_value: form.get("setting_value"),
        setting_type: form.get("setting_type"),
        description: form.get("description")
      })
    });
    e.currentTarget.reset();
    await load();
  }

  return (
    <main>
      <h1>Settings</h1>
      <AdminNav />
      <form className="card" onSubmit={onSave}>
        <input name="setting_key" placeholder="setting_key" required />
        <input name="setting_value" placeholder="setting_value" />
        <input name="setting_type" placeholder="setting_type (text/boolean)" defaultValue="text" />
        <textarea name="description" placeholder="description" />
        <button type="submit">Save setting</button>
      </form>

      <div className="card">
        <h3>Current settings</h3>
        {settings.map((setting) => (
          <div key={setting.id}>
            <strong>{setting.setting_key}</strong>: {setting.setting_value ?? ""}
          </div>
        ))}
      </div>
    </main>
  );
}
