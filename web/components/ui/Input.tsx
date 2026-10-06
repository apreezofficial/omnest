import { useId, type InputHTMLAttributes } from "react";
import { WarningCircle } from "@phosphor-icons/react/dist/ssr";
import { cn } from "@/lib/cn";

type InputProps = InputHTMLAttributes<HTMLInputElement> & {
  label: string;
  helper?: string;
  error?: string;
};

export function Input({ label, helper, error, className, id, ...props }: InputProps) {
  const generated = useId();
  const inputId = id ?? generated;
  const describedBy = error ? `${inputId}-error` : helper ? `${inputId}-helper` : undefined;

  return (
    <div className="flex flex-col gap-2">
      <label htmlFor={inputId} className="type-label text-text">
        {label}
      </label>
      <input
        id={inputId}
        aria-invalid={error ? true : undefined}
        aria-describedby={describedBy}
        className={cn(
          // 16px text prevents mobile zoom on focus (§9 Inputs).
          "h-[52px] rounded-md border-2 bg-surface px-4 text-body text-text placeholder:text-text-subtle",
          "outline-none transition-[border-color,box-shadow] duration-[120ms]",
          "focus:border-primary focus:ring-[3px] focus:ring-green-200 dark:focus:ring-green-900",
          error ? "border-coral-600" : "border-border-strong",
          className,
        )}
        {...props}
      />
      {error ? (
        <p id={`${inputId}-error`} className="flex items-center gap-1 text-body-sm text-danger">
          <WarningCircle size={16} weight="bold" aria-hidden />
          {error}
        </p>
      ) : helper ? (
        <p id={`${inputId}-helper`} className="text-caption text-text-subtle">
          {helper}
        </p>
      ) : null}
    </div>
  );
}
