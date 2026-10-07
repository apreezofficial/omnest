"use client";

import Link from "next/link";
import { useActionState } from "react";
import { Input } from "@/components/ui/Input";
import { Notice } from "@/components/ui/Notice";
import { SubmitButton } from "@/components/ui/SubmitButton";
import { fieldError, initialFormState } from "@/lib/form";
import { resetPassword } from "../actions";

export function ResetForm({ token }: { token: string }) {
  const [state, action] = useActionState(resetPassword, initialFormState);

  return (
    <form action={action} className="flex flex-col gap-5" noValidate>
      <input type="hidden" name="token" value={token} />
      {state.message && (
        <Notice tone="danger">
          {state.message}{" "}
          <Link href="/forgot-password" className="font-bold underline">
            Get a new link
          </Link>
        </Notice>
      )}
      <Input
        label="New password"
        name="password"
        type="password"
        autoComplete="new-password"
        required
        minLength={8}
        helper="At least 8 characters. You'll be signed out on all devices."
        error={fieldError(state, "password")}
      />
      <SubmitButton size="lg" pendingLabel="Saving...">
        Save new password
      </SubmitButton>
    </form>
  );
}
