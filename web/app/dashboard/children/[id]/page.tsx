import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { ArrowLeft, PencilSimple } from "@phosphor-icons/react/dist/ssr";
import { Chip } from "@/components/ui/Chip";
import { api, type Child, type Device } from "@/lib/api";
import { tierLabel } from "@/lib/children";
import { requireUser } from "@/lib/session";
import { ChildAvatar } from "../../ChildAvatar";
import { Suspense } from "react";
import { Card } from "@/components/ui/Card";
import { DeviceList } from "./DeviceList";
import { PairDevice } from "./PairDevice";
import { RANGES, UsageOverview, type RangeDays } from "./UsageOverview";

export async function generateMetadata({ params }: PageProps<"/dashboard/children/[id]">): Promise<Metadata> {
  const { id } = await params;
  const { token } = await requireUser();
  const res = await api<Child>(`/children/${encodeURIComponent(id)}`, { token });
  return { title: res.ok ? res.data.name : "Child" };
}

export default async function ChildPage({ params, searchParams }: PageProps<"/dashboard/children/[id]">) {
  const [{ id }, { pair, range: rangeParam }] = await Promise.all([params, searchParams]);
  const range = (RANGES.find((r) => String(r) === rangeParam) ?? 7) as RangeDays;
  const { token } = await requireUser();

  const [childRes, devicesRes] = await Promise.all([
    api<Child>(`/children/${encodeURIComponent(id)}`, { token }),
    api<Device[]>(`/children/${encodeURIComponent(id)}/devices`, { token }),
  ]);
  if (!childRes.ok) notFound();
  const child = childRes.data;
  const devices = devicesRes.ok ? devicesRes.data : [];

  return (
    <>
      <Link href="/dashboard" className="type-label inline-flex min-h-12 items-center gap-2 self-start text-primary">
        <ArrowLeft size={20} weight="bold" aria-hidden />
        All children
      </Link>

      <div className="flex flex-wrap items-center gap-4">
        <ChildAvatar name={child.name} avatar={child.avatar} size="lg" />
        <div className="flex min-w-0 flex-1 flex-col gap-2">
          <h1 className="type-h1 truncate">{child.name}</h1>
          <div>
            <Chip tone="green">{tierLabel(child.age_tier)}</Chip>
          </div>
        </div>
        <Link
          href={`/dashboard/children/${child.id}/edit`}
          className="type-label inline-flex h-12 items-center gap-2 rounded-md border-2 border-border-strong bg-surface px-5 shadow-hard hover:bg-cream-200 active:translate-y-[3px] active:shadow-none dark:hover:bg-border"
        >
          <PencilSimple size={20} weight="bold" aria-hidden />
          Edit
        </Link>
      </div>

      <section className="flex flex-col gap-4" aria-labelledby="usage-heading">
        <h2 id="usage-heading" className="type-h3">
          Screen time
        </h2>
        <Suspense fallback={<Card className="h-64 animate-pulse" aria-busy="true" aria-label="Loading screen time" />}>
          <UsageOverview childId={child.id} childName={child.name} token={token} range={range} hasDevice={devices.length > 0} />
        </Suspense>
      </section>

      <section className="flex flex-col gap-4" aria-labelledby="phones-heading">
        <h2 id="phones-heading" className="type-h3">
          Phones
        </h2>
        <DeviceList childId={child.id} childName={child.name} devices={devices} />
        <PairDevice childId={child.id} childName={child.name} initialDeviceIds={devices.map((d) => d.id)} autoStart={pair === "1"} />
      </section>
    </>
  );
}
