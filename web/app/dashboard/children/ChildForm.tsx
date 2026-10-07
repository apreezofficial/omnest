"use client";

import { useActionState } from "react";
import { Input } from "@/components/ui/Input";
import { Notice } from "@/components/ui/Notice";
import { SubmitButton } from "@/components/ui/SubmitButton";
import type { Child } from "@/lib/api";
import { AGE_TIERS } from "@/lib/children";
import { cn } from "@/lib/cn";
import { fieldError, initialFormState, type FormState } from "@/lib/form";

type Props = {
  action: (state: FormState, form: FormData) => Promise<FormState>;
  child?: Child;
  submitLabel: string;
};

export function ChildForm({ action, child, submitLabel }: Props) {
  const [state, formAction] = useActionState(action, initialFormState);
  const value = (key: keyof Child & string) => state.values?.[key] ?? (child?.[key] as string | null | undefined) ?? "";
  const tierError = fieldError(state, "age_tier");
  const today = new Date().toISOString().slice(0, 10);

  return (
    <form action={formAction} className="flex flex-col gap-6" noValidate>
      {state.message && <Notice tone="danger">{state.message}</Notice>}

      <Input
        label="Child's name"
        name="name"
        required
        maxLength={60}
        autoComplete="off"
        defaultValue={value("name")}
        error={fieldError(state, "name")}
      />

      <fieldset className="flex flex-col gap-3" aria-describedby={tierError ? "age-tier-error" : undefined}>
        <legend className="type-label mb-2">Age group</legend>
        {AGE_TIERS.map((tier) => (
          <label
            key={tier.value}
            className={cn(
              "flex cursor-pointer items-start gap-3 rounded-md border-2 bg-surface p-4",
              "has-[:checked]:border-primary has-[:checked]:bg-green-100 dark:has-[:checked]:bg-green-900",
              "has-[:focus-visible]:outline-3 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-sky-600",
              tierError ? "border-coral-600" : "border-border-strong",
            )}
          >
            <input
              type="radio"
              name="age_tier"
              value={tier.value}
              defaultChecked={value("age_tier") === tier.value}
              className="mt-1 size-5 accent-green-700"
            />
            <span className="flex flex-col gap-1">
              <span className="type-label">{tier.label}</span>
              <span className="text-body-sm text-text-muted">{tier.hint}</span>
            </span>
          </label>
        ))}
        {tierError && (
          <p id="age-tier-error" className="text-body-sm text-danger">
            {tierError}
          </p>
        )}
      </fieldset>

      <Input
        label="Birth date (optional)"
        name="birth_date"
        type="date"
        max={today}
        defaultValue={value("birth_date")}
        helper="Helps us suggest limits as they grow. Only you see it."
        error={fieldError(state, "birth_date")}
      />

      <div>
        <SubmitButton size="lg" pendingLabel="Saving...">
          {submitLabel}
        </SubmitButton>
      </div>
    </form>
  );
}
