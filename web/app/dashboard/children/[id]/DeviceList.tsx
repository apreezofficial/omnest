"use client";

import { useState, useTransition } from "react";
import { BellSimpleSlash, DeviceMobile } from "@phosphor-icons/react";
import { Button } from "@/components/ui/Button";
import { Card } from "@/components/ui/Card";
import type { Device } from "@/lib/api";
import { timeAgo } from "@/lib/children";
import { unpairDevice } from "../../actions";

export function DeviceList({ childId, childName, devices }: { childId: number; childName: string; devices: Device[] }) {
  if (devices.length === 0) {
    return <p className="text-text-muted">No phone paired yet. Pair {childName}&apos;s phone below to start setting limits.</p>;
  }

  return (
    <ul className="flex flex-col gap-3">
      {devices.map((device) => (
        <li key={device.id}>
          <DeviceRow childId={childId} device={device} />
        </li>
      ))}
    </ul>
  );
}

function DeviceRow({ childId, device }: { childId: number; device: Device }) {
  const [confirming, setConfirming] = useState(false);
  const [pending, startTransition] = useTransition();

  return (
    <Card className="flex flex-wrap items-center gap-4">
      <span className="inline-flex size-12 shrink-0 items-center justify-center rounded-md bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200">
        <DeviceMobile size={24} weight="bold" aria-hidden />
      </span>
      <div className="flex min-w-0 flex-1 flex-col gap-1">
        <p className="type-label truncate">{device.name}</p>
        <p className="text-body-sm text-text-muted">
          {[device.model, device.os_version && `Android ${device.os_version}`].filter(Boolean).join(" · ")}
          {" · "}Last seen {timeAgo(device.last_seen_at)}
        </p>
        {!device.push_enabled && (
          <p className="flex items-center gap-1 text-caption text-text-subtle">
            <BellSimpleSlash size={16} weight="bold" aria-hidden />
            Instant updates not set up yet. Changes reach the phone on its next check-in.
          </p>
        )}
      </div>
      {confirming ? (
        <div className="flex gap-2">
          <Button variant="danger" size="sm" disabled={pending} onClick={() => startTransition(() => unpairDevice(childId, device.id))}>
            {pending ? "Unpairing..." : "Unpair"}
          </Button>
          <Button variant="secondary" size="sm" disabled={pending} onClick={() => setConfirming(false)}>
            Cancel
          </Button>
        </div>
      ) : (
        <Button variant="ghost" size="sm" onClick={() => setConfirming(true)}>
          Unpair
        </Button>
      )}
    </Card>
  );
}
