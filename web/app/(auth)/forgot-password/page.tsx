import type { Metadata } from "next";
import Link from "next/link";
import { ForgotForm } from "./ForgotForm";

export const metadata: Metadata = { title: "Forgot password" };

export default function ForgotPasswordPage() {
  return (
    <div className="flex flex-col gap-6">
      <div className="flex flex-col gap-2">
        <h1 className="type-h2">Forgot your password?</h1>
        <p className="text-text-muted">Enter your email and we&apos;ll send you a link to choose a new one.</p>
      </div>
      <ForgotForm />
      <Link href="/login" className="text-center text-body-sm font-bold text-primary underline-offset-4 hover:underline">
        Back to sign in
      </Link>
    </div>
  );
}
