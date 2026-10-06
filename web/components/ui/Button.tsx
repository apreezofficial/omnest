import type { ButtonHTMLAttributes, ReactNode } from "react";
import { cn } from "@/lib/cn";

type Variant = "primary" | "accent" | "secondary" | "ghost" | "danger";
type Size = "sm" | "md" | "lg";

const variants: Record<Variant, string> = {
  primary: "bg-primary text-on-primary hover:bg-primary-hover",
  accent: "bg-accent text-on-accent hover:bg-accent-hover",
  secondary: "bg-surface text-text hover:bg-cream-200 dark:hover:bg-border",
  ghost: "bg-transparent text-primary hover:bg-green-100 dark:hover:bg-surface",
  danger: "bg-coral-600 text-cream-50 hover:brightness-95",
};

// Ghost has no border or shadow (§9 Buttons).
const bold =
  "border-2 border-border-strong shadow-hard active:translate-y-[3px] active:shadow-none " +
  "disabled:border-ink-300 disabled:bg-ink-300 disabled:text-ink-700 disabled:shadow-none disabled:translate-y-0";

const sizes: Record<Size, string> = {
  sm: "h-10",
  md: "h-12",
  lg: "h-14",
};

export type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: Variant;
  size?: Size;
  /** Pill shape, used by the Knock button. */
  pill?: boolean;
  icon?: ReactNode;
};

export function Button({
  variant = "primary",
  size = "md",
  pill = false,
  icon,
  className,
  children,
  type = "button",
  ...props
}: ButtonProps) {
  return (
    <button
      type={type}
      className={cn(
        "type-label inline-flex min-w-12 select-none items-center justify-center gap-2 px-5",
        "transition-[transform,box-shadow,background-color] duration-[120ms] ease-out",
        "disabled:cursor-not-allowed",
        pill ? "rounded-full" : "rounded-md",
        sizes[size],
        variants[variant],
        variant !== "ghost" && bold,
        className,
      )}
      {...props}
    >
      {icon}
      {children}
    </button>
  );
}
