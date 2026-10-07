"use client";

import { useActionState } from "react";
import { Input } from "@/components/ui/Input";
import { Notice } from "@/components/ui/Notice";
import { SubmitButton } from "@/components/ui/SubmitButton";
import { fieldError, initialFormState } from "@/lib/form";
import { register } from "../actions";

export function RegisterForm() {
  const [state, action] = useActionState(register, initialFormState);

  return (
    <form action={action} className="flex flex-col gap-5" noValidate>
      {state.message && <Notice tone="danger">{state.message}</Notice>}
      <Input
        label="Your name"
        name="name"
        autoComplete="name"
        required
        maxLength={80}
        defaultValue={state.values?.name}
        error={fieldError(state, "name")}
      />
      <Input
        label="Email"
        name="email"
        type="email"
        autoComplete="email"
        required
        defaultValue={state.values?.email}
        error={fieldError(state, "email")}
      />
      <Input
        label="Password"
        name="password"
        type="password"
        autoComplete="new-password"
        required
        minLength={8}
        helper="At least 8 characters."
        error={fieldError(state, "password")}
      />
      <SubmitButton size="lg" pendingLabel="Creating account...">
        Create account
      </SubmitButton>
    </form>
  );
}
