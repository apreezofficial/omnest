import type { Metadata } from "next";
import Link from "next/link";
import { Notice } from "@/components/ui/Notice";
import { ResetForm } from "./ResetForm";

export const metadata: Metadata = { title: "Choose a new password" };

export default async function ResetPasswordPage({ searchParams }: PageProps<"/reset-password">) {
  const { token } = await searchParams;

  return (
    <div className="flex flex-col gap-6">
      <h1 className="type-h2">Choose a new password</h1>
      {typeof token === "string" && token !== "" ? (
        <ResetForm token={token} />
      ) : (
        <Notice tone="danger">
          This link is incomplete.{" "}
          <Link href="/forgot-password" className="font-bold underline">
            Ask for a new one
          </Link>
          .
        </Notice>
      )}
    </div>
  );
}
