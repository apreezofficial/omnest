import { NextResponse, type NextRequest } from "next/server";

const SESSION_COOKIE = "omnest_session";
const AUTH_PAGES = ["/login", "/register", "/forgot-password"];

/**
 * Cheap gate on cookie presence only. The real check (token valid?) happens in
 * requireUser() when a dashboard page renders.
 */
export function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl;
  const signedIn = request.cookies.has(SESSION_COOKIE);

  if (pathname.startsWith("/dashboard") && !signedIn) {
    const url = new URL("/login", request.url);
    url.searchParams.set("next", pathname);
    return NextResponse.redirect(url);
  }

  if (AUTH_PAGES.includes(pathname) && signedIn) {
    return NextResponse.redirect(new URL("/dashboard", request.url));
  }

  return NextResponse.next();
}

export const config = {
  matcher: ["/dashboard/:path*", "/login", "/register", "/forgot-password"],
};
