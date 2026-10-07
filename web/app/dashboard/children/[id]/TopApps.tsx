import type { AppUsage } from "@/lib/api";
import { durationLabel, formatDuration } from "@/lib/format";

/** Per-app time as horizontal bars: label + value in text colors, bar in the single series color. */
export function TopApps({ apps, limit = 5 }: { apps: AppUsage[]; limit?: number }) {
  const shown = apps.slice(0, limit);
  const max = Math.max(1, ...shown.map((a) => a.seconds));

  return (
    <ul className="flex flex-col gap-4">
      {shown.map((app) => (
        <li key={app.package} className="flex flex-col gap-1">
          <div className="flex items-baseline justify-between gap-3">
            <span className="truncate text-body-sm font-medium text-text" title={app.package}>
              {app.label}
            </span>
            <span className="shrink-0 font-mono text-body-sm font-bold text-text" aria-label={durationLabel(app.seconds)}>
              {formatDuration(app.seconds)}
            </span>
          </div>
          <div className="h-2 rounded-full bg-cream-200 dark:bg-border" aria-hidden>
            <div className="h-full rounded-full bg-primary" style={{ width: `${Math.max(2, (app.seconds / max) * 100)}%` }} />
          </div>
        </li>
      ))}
    </ul>
  );
}
