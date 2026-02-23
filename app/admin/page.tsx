"use client";

import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";

export default function AdminLoginPage() {
  const router = useRouter();
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  async function onSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setLoading(true);
    setError("");
    const form = new FormData(e.currentTarget);
    const username = String(form.get("username") ?? "");
    const password = String(form.get("password") ?? "");

    const res = await fetch("/api/admin/login", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ username, password })
    });
    const data = await res.json();
    setLoading(false);
    if (!data.success) {
      setError(data.error ?? "Login failed");
      return;
    }
    router.push("/admin/dashboard");
  }

  return (
    <div className="admin-login-card">
      <h1>Admin Login</h1>
      <p className="muted">Sign in with your admin credentials.</p>
      <form onSubmit={onSubmit}>
        <div className="admin-form-group">
          <label>Username</label>
          <input name="username" type="text" required autoComplete="username" />
        </div>
        <div className="admin-form-group">
          <label>Password</label>
          <input name="password" type="password" required autoComplete="current-password" />
        </div>
        <button type="submit" className="admin-btn admin-btn-primary" disabled={loading}>
          {loading ? "Logging in…" : "Login"}
        </button>
        {error ? <p className="error">{error}</p> : null}
      </form>
    </div>
  );
}
