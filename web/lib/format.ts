/** 8040 -> "2h 14m", 2700 -> "45m", 30 -> "<1m", 0 -> "0m". */
export function formatDuration(seconds: number): string {
  if (seconds <= 0) return "0m";
  if (seconds < 60) return "<1m";
  const totalMinutes = Math.floor(seconds / 60);
  const h = Math.floor(totalMinutes / 60);
  const m = totalMinutes % 60;
  if (h === 0) return `${m}m`;
  return m === 0 ? `${h}h` : `${h}h ${m}m`;
}

/** Spoken form for screen readers: "2 hours 14 minutes". */
export function durationLabel(seconds: number): string {
  const totalMinutes = Math.floor(seconds / 60);
  const h = Math.floor(totalMinutes / 60);
  const m = totalMinutes % 60;
  const parts = [];
  if (h > 0) parts.push(`${h} hour${h === 1 ? "" : "s"}`);
  if (m > 0 || h === 0) parts.push(`${m} minute${m === 1 ? "" : "s"}`);
  return parts.join(" ");
}

/** "2026-06-10" -> Date at local noon (avoids timezone day shifts when only the date matters). */
function dateOnly(iso: string): Date {
  return new Date(`${iso}T12:00:00`);
}

export function weekday(iso: string): string {
  return dateOnly(iso).toLocaleDateString("en-GB", { weekday: "short" });
}

/** "Wed 10 Jun" */
export function shortDate(iso: string): string {
  return dateOnly(iso).toLocaleDateString("en-GB", { weekday: "short", day: "numeric", month: "short" });
}

/** Clean y-axis maximum in seconds: 30-minute steps under 2h, whole hours above. */
export function niceMaxSeconds(max: number): { top: number; step: number } {
  if (max <= 0) return { top: 3600, step: 1800 };
  const step = max <= 2 * 3600 ? 1800 : max <= 6 * 3600 ? 3600 : 2 * 3600;
  return { top: Math.ceil(max / step) * step, step };
}
