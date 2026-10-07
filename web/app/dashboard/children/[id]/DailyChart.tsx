"use client";

import { useState } from "react";
import { cn } from "@/lib/cn";
import { durationLabel, formatDuration, niceMaxSeconds, shortDate, weekday } from "@/lib/format";

type Day = { date: string; total_seconds: number };

const PLOT_HEIGHT = 160; // px

/**
 * Daily screen time as columns: one series, so one color (primary) and no legend (the heading names it).
 * Columns are <= 24px with 4px rounded tops on a shared baseline; hairline solid gridlines; per-column
 * hover/focus tooltip on a full-height hit target; the same numbers are in the table below.
 */
export function DailyChart({ days, todayIso }: { days: Day[]; todayIso: string }) {
  const [active, setActive] = useState<number | null>(null);
  const max = Math.max(0, ...days.map((d) => d.total_seconds));
  const { top, step } = niceMaxSeconds(max);
  const ticks = Array.from({ length: Math.round(top / step) + 1 }, (_, i) => i * step);
  const dense = days.length > 14;
  const labelEvery = dense ? 5 : days.length > 7 ? 2 : 1;

  return (
    <div className="flex flex-col gap-2">
      <div className="relative flex" style={{ height: PLOT_HEIGHT }}>
        {/* Y axis labels: muted text, clean steps */}
        <div className="relative w-12 shrink-0 text-caption text-text-subtle" aria-hidden>
          {ticks.map((t) => (
            <span
              key={t}
              className="absolute right-2 -translate-y-1/2 tabular-nums"
              style={{ top: PLOT_HEIGHT - (t / top) * PLOT_HEIGHT }}
            >
              {formatDuration(t)}
            </span>
          ))}
        </div>

        <div className="relative flex-1">
          {/* Gridlines: 1px solid, recessive */}
          {ticks.map((t) => (
            <div
              key={t}
              aria-hidden
              className="absolute inset-x-0 border-t border-border"
              style={{ top: PLOT_HEIGHT - (t / top) * PLOT_HEIGHT }}
            />
          ))}

          <ol className="absolute inset-0 flex items-end" aria-label="Screen time per day">
            {days.map((d, i) => {
              const h = (d.total_seconds / top) * PLOT_HEIGHT;
              const isToday = d.date === todayIso;
              return (
                <li key={d.date} className="relative flex h-full flex-1 justify-center">
                  <button
                    type="button"
                    className="group flex h-full w-full items-end justify-center rounded-sm"
                    aria-label={`${shortDate(d.date)}${isToday ? " (today)" : ""}: ${durationLabel(d.total_seconds)}`}
                    onPointerEnter={() => setActive(i)}
                    onPointerLeave={() => setActive((a) => (a === i ? null : a))}
                    onFocus={() => setActive(i)}
                    onBlur={() => setActive((a) => (a === i ? null : a))}
                  >
                    <span
                      className={cn(
                        "block w-[60%] max-w-6 rounded-t-[4px] bg-primary transition-opacity duration-[120ms]",
                        active !== null && active !== i && "opacity-40",
                      )}
                      style={{ height: d.total_seconds > 0 ? Math.max(2, h) : 0 }}
                    />
                  </button>
                  {active === i && (
                    <div
                      role="tooltip"
                      className="pointer-events-none absolute z-10 -translate-y-full rounded-md bg-ink-900 px-3 py-2 text-caption whitespace-nowrap text-cream-50 dark:bg-cream-50 dark:text-ink-900"
                      style={{ bottom: Math.max(2, h) + 8 }}
                    >
                      <span className="block">{shortDate(d.date)}{isToday ? " · today" : ""}</span>
                      <span className="block font-mono text-body-sm font-bold">{formatDuration(d.total_seconds)}</span>
                    </div>
                  )}
                </li>
              );
            })}
          </ol>
        </div>
      </div>

      {/* X axis */}
      <div className="flex pl-12 text-caption text-text-subtle" aria-hidden>
        {days.map((d, i) => (
          <span key={d.date} className={cn("flex-1 text-center", d.date === todayIso && "font-bold text-text")}>
            {(days.length - 1 - i) % labelEvery === 0 ? (dense ? d.date.slice(8).replace(/^0/, "") : weekday(d.date)) : ""}
          </span>
        ))}
      </div>
    </div>
  );
}
