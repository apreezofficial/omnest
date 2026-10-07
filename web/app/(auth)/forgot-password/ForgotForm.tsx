"use client";

import { useActionState } from "react";
import { Input } from "@/components/ui/Input";
import { Notice } from "@/components/ui/Notice";
import { SubmitButton } from "@/components/ui/SubmitButton";
import { fieldError, initialFormState } from "@/lib/form";
import { forgotPassword } from "../actions";

export function ForgotForm() {
  const [state, action] = useActionState(forgotPassword, initialFormState);

  if (state.done) {
    return (
      <Notice tone="success">
        If <strong>{state.values?.email}</strong> has an Omnest account, a reset link is on its way. It works for 1 hour.
      </Notice>
    );
  }

  return (
    <form action={action} className="flex flex-col gap-5" noValidate>
      {state.message && <Notice tone="danger">{state.message}</Notice>}
      <Input
        label="Email"
        name="email"
        type="email"
        autoComplete="email"
        required
        defaultValue={state.values?.email}
        error={fieldError(state, "email")}
      />
      <SubmitButton size="lg" pendingLabel="Sending...">
        Send reset link
      </SubmitButton>
    </form>
  );
}
