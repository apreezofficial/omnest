"use client";

import { useActionState } from "react";
import { EnvelopeSimple } from "@phosphor-icons/react";
import { initialFormState } from "@/lib/form";
import { resendVerification } from "../(auth)/actions";

export function VerifyEmailBanner({ email }: { email: string }) {
  const [state, action, pending] = useActionState(resendVerification, initialFormState);

  return (
    <div role="status" className="bg-sun-200 text-ink-900">
      <div className="mx-auto flex w-full max-w-[1152px] flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3 text-body-sm md:px-8">
        <EnvelopeSimple size={20} weight="bold" aria-hidden className="shrink-0" />
        <p className="flex-1">
          {state.done ? (
            <>New link sent to <strong>{email}</strong>.</>
          ) : (
            <>Confirm your email so we can reach you about your child&apos;s phone. Check <strong>{email}</strong>.</>
          )}
        </p>
        {!state.done && (
          <form action={action}>
            <button
              type="submit"
              disabled={pending}
              className="type-label min-h-12 rounded-md px-2 underline underline-offset-4 disabled:opacity-60"
            >
              {pending ? "Sending..." : "Send a new link"}
            </button>
          </form>
        )}
      </div>
    </div>
  );
}
