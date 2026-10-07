import { cache } from "react";
import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { api, type User } from "@/lib/api";

export const SESSION_COOKIE = "omnest_session";

export async function getToken(): Promise<string | null> {
  return (await cookies()).get(SESSION_COOKIE)?.value ?? null;
}

/** Call from server actions only (cookies can't be written during render). */
export async function setToken(token: string): Promise<void> {
  (await cookies()).set(SESSION_COOKIE, token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    path: "/",
    maxAge: 60 * 60 * 24 * 30, // matches the API's 30-day sliding expiry
  });
}

export async function clearToken(): Promise<void> {
  (await cookies()).delete(SESSION_COOKIE);
}

/**
 * Current parent, or redirect to /login. Use at the top of dashboard pages.
 * Wrapped in cache() so layout + page share one /auth/me call per request.
 */
export const requireUser = cache(async (): Promise<{ user: User; token: string }> => {
  const token = await getToken();
  if (!token) redirect("/login");

  const res = await api<User>("/auth/me", { token });
  if (!res.ok) {
    // Expired or revoked: the logout route clears the stale cookie.
    redirect(res.status === 401 ? "/logout?expired=1" : "/login");
  }

  return { user: res.data, token };
});
