import type { HTMLAttributes } from "react";
import { cn } from "@/lib/cn";

type CardProps = HTMLAttributes<HTMLDivElement> & {
  /** Featured/actionable: 2px bold border + hard shadow. Default is quiet. */
  featured?: boolean;
};

export function Card({ featured = false, className, ...props }: CardProps) {
  return (
    <div
      className={cn(
        "rounded-lg bg-surface p-5 lg:p-6",
        featured ? "border-2 border-border-strong shadow-hard" : "border border-border",
        className,
      )}
      {...props}
    />
  );
}
