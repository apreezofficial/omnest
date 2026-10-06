import type { HTMLAttributes } from "react";
import { cn } from "@/lib/cn";

type Tone = "green" | "sun" | "coral" | "sky";

const tones: Record<Tone, string> = {
  green: "bg-green-200 text-green-900",
  sun: "bg-sun-200 text-ink-900",
  coral: "bg-coral-100 text-coral-600",
  sky: "bg-sky-100 text-sky-600",
};

type ChipProps = HTMLAttributes<HTMLSpanElement> & { tone?: Tone };

export function Chip({ tone = "green", className, ...props }: ChipProps) {
  return (
    <span
      className={cn("type-label inline-flex h-8 items-center gap-1 rounded-full px-3 text-caption", tones[tone], className)}
      {...props}
    />
  );
}
