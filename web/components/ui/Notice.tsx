import type { ReactNode } from "react";
import { CheckCircle, Info, WarningCircle } from "@phosphor-icons/react/dist/ssr";
import { cn } from "@/lib/cn";

type Tone = "success" | "info" | "warning" | "danger";

const tones: Record<Tone, string> = {
  success: "bg-green-100 text-green-900 dark:bg-green-900 dark:text-green-100",
  info: "bg-sky-100 text-ink-900",
  warning: "bg-sun-200 text-ink-900",
  danger: "bg-coral-100 text-coral-600",
};

const icons: Record<Tone, ReactNode> = {
  success: <CheckCircle size={20} weight="bold" aria-hidden className="shrink-0" />,
  info: <Info size={20} weight="bold" aria-hidden className="shrink-0" />,
  warning: <WarningCircle size={20} weight="bold" aria-hidden className="shrink-0" />,
  danger: <WarningCircle size={20} weight="bold" aria-hidden className="shrink-0" />,
};

/** Inline message panel. Always pairs color with an icon (specs §12). */
export function Notice({ tone = "info", children, className }: { tone?: Tone; children: ReactNode; className?: string }) {
  return (
    <div
      role={tone === "danger" ? "alert" : "status"}
      className={cn("flex items-start gap-3 rounded-md p-4 text-body-sm", tones[tone], className)}
    >
      {icons[tone]}
      <div className="min-w-0">{children}</div>
    </div>
  );
}
