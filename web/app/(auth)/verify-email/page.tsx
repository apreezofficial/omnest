import type { Metadata } from "next";
import Link from "next/link";
import { Notice } from "@/components/ui/Notice";
import { api } from "@/lib/api";

export const metadata: Metadata = { title: "Confirm email" };

export default async function VerifyEmailPage({ searchParams }: PageProps<"/verify-email">) {
  const { token } = await searchParams;
  const res =
    typeof token === "string" && token !== ""
      ? await api("/auth/email/verify", { method: "POST", body: { token } })
      : null;

  return (
    <div className="flex flex-col gap-6">
      <h1 className="type-h2">Confirm email</h1>
      {res?.ok ? (
        <Notice tone="success">Your email is confirmed. Thanks!</Notice>
      ) : (
        <Notice tone="danger">
          {res && !res.ok ? res.error.message : "This link is incomplete."} You can send a new link from your dashboard.
        </Notice>
      )}
      <Link
        href="/dashboard"
        className="type-label inline-flex h-12 items-center justify-center rounded-md border-2 border-border-strong bg-primary px-5 text-on-primary shadow-hard hover:bg-primary-hover active:translate-y-[3px] active:shadow-none"
      >
        Go to dashboard
      </Link>
    </div>
  );
}
