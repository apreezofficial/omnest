import type { Metadata } from "next";
import Link from "next/link";
import { RegisterForm } from "./RegisterForm";

export const metadata: Metadata = { title: "Create account" };

export default function RegisterPage() {
  return (
    <div className="flex flex-col gap-6">
      <div className="flex flex-col gap-2">
        <h1 className="type-h2">Create your account</h1>
        <p className="text-text-muted">Set limits together. Your child can Knock when they need more time.</p>
      </div>
      <RegisterForm />
      <p className="text-center text-body-sm text-text-muted">
        Already have an account?{" "}
        <Link href="/login" className="font-bold text-primary underline-offset-4 hover:underline">
          Sign in
        </Link>
      </p>
    </div>
  );
}
