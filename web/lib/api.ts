// Server-side client for the Omnest PHP API. Only import from server components and server actions:
// the parent's token lives in an httpOnly cookie and never reaches the browser.

const API_URL = (process.env.API_URL ?? "http://localhost:8000/api/v1").replace(/\/$/, "");

export type ApiError = {
  code: string;
  message: string;
  fields?: Record<string, string[]>;
};

export type ApiResult<T> = { ok: true; status: number; data: T } | { ok: false; status: number; error: ApiError };

type Options = {
  method?: "GET" | "POST" | "PUT" | "PATCH" | "DELETE";
  body?: unknown;
  token?: string | null;
};

export async function api<T>(path: string, { method = "GET", body, token }: Options = {}): Promise<ApiResult<T>> {
  const headers: Record<string, string> = { Accept: "application/json" };
  if (body !== undefined) headers["Content-Type"] = "application/json";
  if (token) headers.Authorization = `Bearer ${token}`;

  let res: Response;
  try {
    res = await fetch(`${API_URL}${path}`, {
      method,
      headers,
      body: body === undefined ? undefined : JSON.stringify(body),
      cache: "no-store",
    });
  } catch {
    return {
      ok: false,
      status: 0,
      error: { code: "network", message: "We couldn't reach Omnest. Check your connection and try again." },
    };
  }

  if (res.status === 204) return { ok: true, status: 204, data: undefined as T };

  const json = await res.json().catch(() => null);
  if (res.ok && json && "data" in json) return { ok: true, status: res.status, data: json.data as T };

  const err = json?.error;
  return {
    ok: false,
    status: res.status,
    error: {
      code: err?.code ?? "server_error",
      message: err?.message ?? "Something went wrong on our side.",
      fields: err?.details?.fields,
    },
  };
}

// Shapes returned by the API.
export type User = { id: number; name: string; email: string; email_verified: boolean; created_at: string };

export type AgeTier = "kid" | "preteen" | "teen";

export type Child = {
  id: number;
  name: string;
  age_tier: AgeTier;
  birth_date: string | null;
  avatar: string | null;
  device_count: number;
  created_at: string;
};

export type Device = {
  id: number;
  child_id: number;
  name: string;
  model: string | null;
  os_version: string | null;
  app_version: string | null;
  push_enabled: boolean;
  last_seen_at: string | null;
  paired_at: string;
};

export type PairingCode = { code: string; expires_at: string; qr_payload: string };

export type AppUsage = { package: string; label: string; seconds: number };

export type UsageDay = {
  date: string;
  is_today: boolean;
  timezone: string;
  total_seconds: number;
  apps: AppUsage[];
  last_synced_at: string | null;
};

export type UsageRange = {
  from: string;
  to: string;
  timezone: string;
  days: { date: string; total_seconds: number }[];
  total_seconds: number;
  average_seconds: number;
  top_apps: AppUsage[];
  last_synced_at: string | null;
};
