import type { Metadata } from "next";
import Link from "next/link";
import { CaretRight, DeviceMobile, Plus } from "@phosphor-icons/react/dist/ssr";
import { Card } from "@/components/ui/Card";
import { Chip } from "@/components/ui/Chip";
import { Notice } from "@/components/ui/Notice";
import { api, type Child, type UsageDay } from "@/lib/api";
import { tierLabel } from "@/lib/children";
import { formatDuration } from "@/lib/format";
import { requireUser } from "@/lib/session";
import { ChildAvatar } from "./ChildAvatar";

export const metadata: Metadata = { title: "Dashboard" };

const linkButton =
  "type-label inline-flex h-12 items-center justify-center gap-2 rounded-md border-2 border-border-strong bg-primary px-5 text-on-primary shadow-hard transition-[transform,box-shadow] duration-[120ms] hover:bg-primary-hover active:translate-y-[3px] active:shadow-none";

export default async function DashboardPage({ searchParams }: PageProps<"/dashboard">) {
  const { user, token } = await requireUser();
  const { welcome } = await searchParams;
  const res = await api<Child[]>("/children", { token });
  const children = res.ok ? res.data : [];
  const todays = await Promise.all(
    children.map((c) => (c.device_count > 0 ? api<UsageDay>(`/children/${c.id}/usage/day`, { token }) : null)),
  );
  const todayFor = (i: number) => {
    const r = todays[i];
    return r?.ok && r.data.last_synced_at ? r.data.total_seconds : null;
  };

  return (
    <>
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div className="flex flex-col gap-1">
          <h1 className="type-h1">Hi, {user.name.split(" ")[0]}</h1>
          <p className="text-body-lg text-text-muted">Your family at a glance.</p>
        </div>
        {children.length > 0 && (
          <Link href="/dashboard/children/new" className={linkButton}>
            <Plus size={20} weight="bold" aria-hidden />
            Add child
          </Link>
        )}
      </div>

      {welcome && <Notice tone="success">Your account is ready. Next: add a child and pair their phone.</Notice>}
      {!res.ok && <Notice tone="danger">{res.error.message}</Notice>}

      {res.ok && children.length === 0 ? (
        <Card featured className="flex flex-col items-start gap-4 p-6 md:p-8">
          <h2 className="type-h3">Add your first child</h2>
          <p className="max-w-[65ch] text-text-muted">
            Add a profile, then pair their Android phone with a 6-digit code. It takes about two minutes.
          </p>
          <Link href="/dashboard/children/new" className={linkButton}>
            <Plus size={20} weight="bold" aria-hidden />
            Add a child
          </Link>
        </Card>
      ) : (
        <ul className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {children.map((child, i) => (
            <li key={child.id}>
              <Link
                href={`/dashboard/children/${child.id}`}
                className="group block rounded-lg transition-transform duration-[120ms] active:translate-y-[3px]"
              >
                <Card featured className="flex items-center gap-4 group-hover:bg-cream-200 dark:group-hover:bg-border">
                  <ChildAvatar name={child.name} avatar={child.avatar} />
                  <div className="flex min-w-0 flex-1 flex-col gap-2">
                    <div className="flex items-baseline justify-between gap-2">
                      <h2 className="type-h4 truncate">{child.name}</h2>
                      {todayFor(i) !== null && (
                        <span className="shrink-0 text-body-sm text-text-muted">
                          <span className="font-mono font-bold text-text">{formatDuration(todayFor(i)!)}</span> today
                        </span>
                      )}
                    </div>
                    <div className="flex flex-wrap gap-2">
                      <Chip tone="green">{tierLabel(child.age_tier)}</Chip>
                      <Chip tone={child.device_count > 0 ? "sky" : "sun"}>
                        <DeviceMobile size={16} weight="bold" aria-hidden />
                        {child.device_count > 0
                          ? `${child.device_count} phone${child.device_count > 1 ? "s" : ""}`
                          : "No phone yet"}
                      </Chip>
                    </div>
                  </div>
                  <CaretRight size={24} weight="bold" aria-hidden className="shrink-0 text-text-subtle" />
                </Card>
              </Link>
            </li>
          ))}
        </ul>
      )}
    </>
  );
}
