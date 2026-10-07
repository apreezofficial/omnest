import type { Metadata } from "next";
import Link from "next/link";
import { Notice } from "@/components/ui/Notice";
import { LoginForm } from "./LoginForm";

export const metadata: Metadata = { title: "Sign in" };

export default async function LoginPage({ searchParams }: PageProps<"/login">) {
  const params = await searchParams;
  const next = typeof params.next === "string" ? params.next : "/dashboard";

  return (
    <div className="flex flex-col gap-6">
      <div className="flex flex-col gap-2">
        <h1 className="type-h2">Welcome back</h1>
        <p className="text-text-muted">Sign in to see your family&apos;s phones.</p>
      </div>
      {params.reset && <Notice tone="success">Password changed. Sign in with your new password.</Notice>}
      {params.expired && <Notice tone="info">You were signed out. Please sign in again.</Notice>}
      <LoginForm next={next} />
      <p className="text-center text-body-sm text-text-muted">
        New to Omnest?{" "}
        <Link href="/register" className="font-bold text-primary underline-offset-4 hover:underline">
          Create an account
        </Link>
      </p>
    </div>
  );
}
