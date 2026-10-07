import type { ApiError } from "@/lib/api";

/** State returned by server actions to forms using useActionState. */
export type FormState = {
  message?: string;
  fields?: Record<string, string[]>;
  values?: Record<string, string>;
  done?: boolean;
};

export const initialFormState: FormState = {};

export function str(form: FormData, key: string): string {
  const v = form.get(key);
  return typeof v === "string" ? v.trim() : "";
}

/** Turns an API error into form state, keeping what the user typed (never passwords). */
export function fromApiError(error: ApiError, values: Record<string, string> = {}): FormState {
  return {
    message: error.fields ? undefined : error.message,
    fields: error.fields,
    values,
  };
}

export function fieldError(state: FormState, name: string): string | undefined {
  return state.fields?.[name]?.[0];
}
