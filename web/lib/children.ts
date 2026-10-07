import type { AgeTier } from "@/lib/api";

export const AGE_TIERS: { value: AgeTier; label: string; hint: string }[] = [
  { value: "kid", label: "Kid", hint: "Up to 9. Stricter defaults, simple screens." },
  { value: "preteen", label: "Preteen", hint: "10 to 12. Balanced limits." },
  { value: "teen", label: "Teen", hint: "13 and up. Sees exactly what you see." },
];

export function tierLabel(tier: AgeTier): string {
  return AGE_TIERS.find((t) => t.value === tier)?.label ?? tier;
}

/** "5 min ago", "2 h ago", "3 days ago" for last-seen times. */
export function timeAgo(iso: string | null, now: Date = new Date()): string {
  if (!iso) return "never";
  const seconds = Math.max(0, Math.round((now.getTime() - new Date(iso).getTime()) / 1000));
  if (seconds < 60) return "just now";
  const minutes = Math.round(seconds / 60);
  if (minutes < 60) return `${minutes} min ago`;
  const hours = Math.round(minutes / 60);
  if (hours < 24) return `${hours} h ago`;
  const days = Math.round(hours / 24);
  return days === 1 ? "yesterday" : `${days} days ago`;
}
