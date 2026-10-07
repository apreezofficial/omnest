"use client";

import Link from "next/link";
import { useActionState } from "react";
import { Input } from "@/components/ui/Input";
import { Notice } from "@/components/ui/Notice";
import { SubmitButton } from "@/components/ui/SubmitButton";
import { fieldError, initialFormState } from "@/lib/form";
import { login } from "../actions";

export function LoginForm({ next }: { next: string }) {
  const [state, action] = useActionState(login, initialFormState);

  return (
    <form action={action} className="flex flex-col gap-5" noValidate>
      <input type="hidden" name="next" value={next} />
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
      <div className="flex flex-col gap-2">
        <Input
          label="Password"
          name="password"
          type="password"
          autoComplete="current-password"
          required
          error={fieldError(state, "password")}
        />
        <Link href="/forgot-password" className="self-end text-body-sm font-bold text-primary underline-offset-4 hover:underline">
          Forgot password?
        </Link>
      </div>
      <SubmitButton size="lg" pendingLabel="Signing in...">
        Sign in
      </SubmitButton>
    </form>
  );
}
