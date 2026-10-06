import { cn } from "@/lib/cn";

type LogoProps = {
  className?: string;
  /** Single-color version (dot uses currentColor too). */
  mono?: boolean;
  withWordmark?: boolean;
};

/** Door arch sitting in a nest, with the accent "knock" dot as the handle. */
export function LogoMark({ className, mono = false }: Omit<LogoProps, "withWordmark">) {
  return (
    <svg viewBox="0 0 48 48" fill="none" aria-hidden className={cn("size-8", className)}>
      <path
        d="M14 38V22a10 10 0 0 1 20 0v16"
        stroke="currentColor"
        strokeWidth="5"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
      <path d="M6 33c8 10 28 10 36 0" stroke="currentColor" strokeWidth="5" strokeLinecap="round" />
      <circle cx="28.5" cy="27" r="3.5" fill={mono ? "currentColor" : "var(--accent)"} />
    </svg>
  );
}

export function Logo({ className, mono, withWordmark = true }: LogoProps) {
  return (
    <span className={cn("inline-flex items-center gap-2 text-primary", className)}>
      <LogoMark mono={mono} />
      {withWordmark && <span className="font-display text-h4 font-extrabold tracking-[-0.02em] text-text">Omnest</span>}
    </span>
  );
}
