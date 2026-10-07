import Link from "next/link";
import { ArrowsClockwise, HourglassMedium } from "@phosphor-icons/react/dist/ssr";
import { Card } from "@/components/ui/Card";
import { Notice } from "@/components/ui/Notice";
import { api, type UsageDay, type UsageRange } from "@/lib/api";
import { timeAgo } from "@/lib/children";
import { cn } from "@/lib/cn";
import { durationLabel, formatDuration, shortDate } from "@/lib/format";
import { DailyChart } from "./DailyChart";
import { TopApps } from "./TopApps";

export const RANGES = [7, 14, 30] as const;
export type RangeDays = (typeof RANGES)[number];

type Props = { childId: number; childName: string; token: string; range: RangeDays; hasDevice: boolean };

export async function UsageOverview({ childId, childName, token, range, hasDevice }: Props) {
  const [dayRes, rangeRes] = await Promise.all([
    api<UsageDay>(`/children/${childId}/usage/day`, { token }),
    api<UsageRange>(`/children/${childId}/usage/range?days=${range}`, { token }),
  ]);

  if (!dayRes.ok || !rangeRes.ok) {
    const err = !dayRes.ok ? dayRes.error : !rangeRes.ok ? rangeRes.error : null;
    return <Notice tone="danger">{err?.message ?? "Couldn't load screen time."}</Notice>;
  }

  const today = dayRes.data;
  const report = rangeRes.data;
  const synced = today.last_synced_at;

  if (!synced) {
    return (
      <Card className="flex items-start gap-4 p-6">
        <HourglassMedium size={28} weight="bold" aria-hidden className="mt-1 shrink-0 text-text-muted" />
        <div className="flex flex-col gap-1">
          <h3 className="type-h4">No screen time yet</h3>
          <p className="max-w-[65ch] text-body-sm text-text-muted">
            {hasDevice
              ? `Numbers appear after ${childName}'s phone checks in. That happens every 15 minutes once Usage access is turned on.`
              : `Pair ${childName}'s phone below to start seeing screen time.`}
          </p>
        </div>
      </Card>
    );
  }

  return (
    <div className="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
      {/* Today: the one hero number on this page */}
      <Card featured className="flex flex-col gap-6 p-6">
        <div className="flex flex-col gap-1">
          <p className="type-label text-text-muted">Screen time today</p>
          <p className="font-mono text-[3rem] leading-none font-bold text-text" aria-label={durationLabel(today.total_seconds)}>
            {formatDuration(today.total_seconds)}
          </p>
          <p className="flex items-center gap-1 text-caption text-text-subtle">
            <ArrowsClockwise size={14} weight="bold" aria-hidden />
            Updated {timeAgo(synced)}
          </p>
        </div>
        {today.apps.length > 0 ? (
          <div className="flex flex-col gap-3">
            <h4 className="type-label">Most used today</h4>
            <TopApps apps={today.apps} />
          </div>
        ) : (
          <p className="text-body-sm text-text-muted">No apps used yet today.</p>
        )}
      </Card>

      {/* Range */}
      <Card className="flex flex-col gap-6 p-6">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div className="flex flex-col gap-1">
            <h3 className="type-h4">Daily screen time</h3>
            <p className="text-body-sm text-text-muted">
              Average <strong className="font-mono text-text">{formatDuration(report.average_seconds)}</strong> a day ·{" "}
              {shortDate(report.from)} to {shortDate(report.to)}
            </p>
          </div>
          <nav aria-label="Time range" className="flex rounded-md border-2 border-border-strong p-0.5">
            {RANGES.map((r) => (
              <Link
                key={r}
                href={`?range=${r}`}
                scroll={false}
                aria-current={r === range ? "page" : undefined}
                className={cn(
                  "type-label inline-flex h-10 min-w-12 items-center justify-center rounded-sm px-3",
                  r === range ? "bg-primary text-on-primary" : "text-text hover:bg-cream-200 dark:hover:bg-border",
                )}
              >
                {r}d
              </Link>
            ))}
          </nav>
        </div>

        <DailyChart days={report.days} todayIso={today.date} />

        {report.top_apps.length > 0 && (
          <div className="flex flex-col gap-3">
            <h4 className="type-label">Top apps, last {range} days</h4>
            <TopApps apps={report.top_apps} />
          </div>
        )}

        <details className="text-body-sm">
          <summary className="type-label inline-flex min-h-12 cursor-pointer items-center text-primary">Show as a table</summary>
          <table className="mt-2 w-full border-collapse">
            <caption className="sr-only">Screen time per day, {report.timezone}</caption>
            <thead>
              <tr className="border-b border-border text-left text-text-muted">
                <th scope="col" className="py-2 font-medium">Day</th>
                <th scope="col" className="py-2 text-right font-medium">Screen time</th>
              </tr>
            </thead>
            <tbody>
              {[...report.days].reverse().map((d) => (
                <tr key={d.date} className="border-b border-border last:border-0">
                  <td className="py-2">{shortDate(d.date)}</td>
                  <td className="py-2 text-right font-mono tabular-nums">{formatDuration(d.total_seconds)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </details>
      </Card>
    </div>
  );
}
