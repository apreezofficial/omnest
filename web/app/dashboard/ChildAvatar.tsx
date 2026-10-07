import { cn } from "@/lib/cn";

// Flat tint fills from the palette, picked by avatar key or the child's name.
const fills = ["bg-green-200 text-green-900", "bg-sun-200 text-ink-900", "bg-sky-100 text-sky-600", "bg-coral-100 text-coral-600"];

export const AVATARS = ["green", "sun", "sky", "coral"] as const;

export function ChildAvatar({ name, avatar, size = "md" }: { name: string; avatar: string | null; size?: "md" | "lg" }) {
  const index = avatar && AVATARS.includes(avatar as (typeof AVATARS)[number])
    ? AVATARS.indexOf(avatar as (typeof AVATARS)[number])
    : [...name].reduce((sum, ch) => sum + ch.charCodeAt(0), 0) % fills.length;

  return (
    <span
      aria-hidden
      className={cn(
        "inline-flex shrink-0 items-center justify-center rounded-full border-2 border-border-strong font-display font-extrabold",
        size === "lg" ? "size-16 text-h3" : "size-12 text-h4",
        fills[index],
      )}
    >
      {name.trim().charAt(0).toUpperCase() || "?"}
    </span>
  );
}
