import { NextResponse, type NextRequest } from "next/server";
import { SESSION_COOKIE } from "@/lib/session";

/** Clears a stale session cookie (token expired or revoked) and sends the parent to sign in. */
export function GET(request: NextRequest) {
  const url = new URL("/login", request.url);
  if (request.nextUrl.searchParams.has("expired")) url.searchParams.set("expired", "1");

  const res = NextResponse.redirect(url);
  res.cookies.delete(SESSION_COOKIE);
  return res;
}
