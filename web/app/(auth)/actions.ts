"use server";

import { redirect } from "next/navigation";
import { api, type User } from "@/lib/api";
import { fromApiError, str, type FormState } from "@/lib/form";
import { clearToken, getToken, setToken } from "@/lib/session";

type AuthResponse = { user: User; token: string };

/** Only allow redirects back into the dashboard (no open redirects). */
function safeNext(next: string): string {
  return next.startsWith("/dashboard") && !next.startsWith("//") ? next : "/dashboard";
}

export async function login(_: FormState, form: FormData): Promise<FormState> {
  const email = str(form, "email");
  const res = await api<AuthResponse>("/auth/login", {
    method: "POST",
    body: { email, password: form.get("password"), client: "web" },
  });
  if (!res.ok) return fromApiError(res.error, { email });

  await setToken(res.data.token);
  redirect(safeNext(str(form, "next")));
}

export async function register(_: FormState, form: FormData): Promise<FormState> {
  const values = { name: str(form, "name"), email: str(form, "email") };
  const res = await api<AuthResponse>("/auth/register", {
    method: "POST",
    body: { ...values, password: form.get("password"), client: "web" },
  });
  if (!res.ok) return fromApiError(res.error, values);

  await setToken(res.data.token);
  redirect("/dashboard?welcome=1");
}

export async function forgotPassword(_: FormState, form: FormData): Promise<FormState> {
  const email = str(form, "email");
  const res = await api("/auth/password/forgot", { method: "POST", body: { email } });
  if (!res.ok) return fromApiError(res.error, { email });

  return { done: true, values: { email } };
}

export async function resetPassword(_: FormState, form: FormData): Promise<FormState> {
  const res = await api("/auth/password/reset", {
    method: "POST",
    body: { token: str(form, "token"), password: form.get("password") },
  });
  if (!res.ok) return fromApiError(res.error);

  // Every session was signed out by the reset, including this browser's.
  await clearToken();
  redirect("/login?reset=1");
}

export async function resendVerification(): Promise<FormState> {
  const res = await api("/auth/email/resend", { method: "POST", token: await getToken() });
  return res.ok ? { done: true } : fromApiError(res.error);
}

export async function logout(): Promise<void> {
  const token = await getToken();
  if (token) await api("/auth/logout", { method: "POST", token });
  await clearToken();
  redirect("/login");
}
