"use client";

import { useCallback, useEffect, useRef, useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import { CheckCircle, QrCode } from "@phosphor-icons/react";
import { Button } from "@/components/ui/Button";
import { Card } from "@/components/ui/Card";
import { Notice } from "@/components/ui/Notice";
import { createPairingCode, listDevices, type PairingState } from "../../actions";

type Props = { childId: number; childName: string; initialDeviceIds: number[]; autoStart: boolean };

type View =
  | { kind: "idle" }
  | { kind: "code"; code: string; expiresAt: number; qrSvg: string }
  | { kind: "expired" }
  | { kind: "paired"; deviceName: string }
  | { kind: "error"; message: string };

const POLL_MS = 4000;

export function PairDevice({ childId, childName, initialDeviceIds, autoStart }: Props) {
  const router = useRouter();
  const [view, setView] = useState<View>({ kind: "idle" });
  const [pending, startTransition] = useTransition();
  const [now, setNow] = useState(() => Date.now());
  const known = useRef(new Set(initialDeviceIds));
  const started = useRef(false);

  const start = useCallback(() => {
    startTransition(async () => {
      const res: PairingState = await createPairingCode(childId);
      setNow(Date.now());
      setView(
        res.ok
          ? { kind: "code", code: res.code, expiresAt: new Date(res.expiresAt).getTime(), qrSvg: res.qrSvg }
          : { kind: "error", message: res.message },
      );
    });
  }, [childId]);

  useEffect(() => {
    if (autoStart && !started.current) {
      started.current = true;
      start();
    }
  }, [autoStart, start]);

  // While a code is showing: tick the countdown and watch for the new phone.
  useEffect(() => {
    if (view.kind !== "code") return;

    const tick = setInterval(() => {
      const t = Date.now();
      setNow(t);
      if (t >= view.expiresAt) setView({ kind: "expired" });
    }, 1000);

    const poll = setInterval(async () => {
      const devices = await listDevices(childId);
      const fresh = devices.find((d) => !known.current.has(d.id));
      if (fresh) {
        known.current.add(fresh.id);
        setView({ kind: "paired", deviceName: fresh.name });
        router.refresh();
      }
    }, POLL_MS);

    return () => {
      clearInterval(tick);
      clearInterval(poll);
    };
  }, [view, childId, router]);

  if (view.kind === "idle" || view.kind === "error") {
    return (
      <Card featured className="flex flex-col items-start gap-4 p-6">
        <h3 className="type-h4">Pair a phone</h3>
        <p className="max-w-[65ch] text-body-sm text-text-muted">
          Install Omnest on {childName}&apos;s Android phone, open it, and enter the code we show you here.
        </p>
        {view.kind === "error" && <Notice tone="danger">{view.message}</Notice>}
        <Button onClick={start} disabled={pending} icon={<QrCode size={20} weight="bold" aria-hidden />}>
          {pending ? "Getting a code..." : "Get pairing code"}
        </Button>
      </Card>
    );
  }

  if (view.kind === "paired") {
    return (
      <Card featured className="flex flex-col items-start gap-4 bg-green-100 p-6 dark:bg-green-900">
        <p className="flex items-center gap-2 type-h4">
          <CheckCircle size={28} weight="fill" aria-hidden className="text-green-700 dark:text-green-200" />
          {view.deviceName} is paired
        </p>
        <p className="text-body-sm">Next, finish the setup steps on the phone. Limits come in the next update.</p>
        <Button variant="secondary" onClick={() => setView({ kind: "idle" })}>
          Pair another phone
        </Button>
      </Card>
    );
  }

  if (view.kind === "expired") {
    return (
      <Card featured className="flex flex-col items-start gap-4 p-6">
        <h3 className="type-h4">That code expired</h3>
        <p className="text-body-sm text-text-muted">Codes last 10 minutes. Get a fresh one when the phone is ready.</p>
        <Button onClick={start} disabled={pending}>
          {pending ? "Getting a code..." : "Get a new code"}
        </Button>
      </Card>
    );
  }

  const remaining = Math.max(0, Math.round((view.expiresAt - now) / 1000));
  const mmss = `${Math.floor(remaining / 60)}:${String(remaining % 60).padStart(2, "0")}`;

  return (
    <Card featured className="flex flex-col gap-6 p-6 md:flex-row md:items-center md:gap-10 md:p-8">
      <div className="flex flex-1 flex-col gap-4">
        <h3 className="type-h4">Pair {childName}&apos;s phone</h3>
        <ol className="flex list-decimal flex-col gap-2 pl-5 text-body-sm text-text-muted">
          <li>Open Omnest on {childName}&apos;s phone.</li>
          <li>Tap <strong className="text-text">I have a code</strong>.</li>
          <li>Type the code below, or scan the QR code.</li>
        </ol>
        <p
          className="font-mono text-[2.5rem] leading-none font-bold tracking-[0.15em] text-text md:text-[3rem]"
          aria-label={`Pairing code ${view.code.split("").join(" ")}`}
        >
          {view.code.slice(0, 3)} {view.code.slice(3)}
        </p>
        <p className="text-body-sm text-text-muted" aria-live="polite">
          Expires in <span className="font-mono font-bold text-text">{mmss}</span>. Waiting for the phone...
        </p>
      </div>
      <div
        className="size-48 shrink-0 self-center overflow-hidden rounded-md border-2 border-border-strong bg-cream-50 p-2 [&_svg]:size-full"
        role="img"
        aria-label="QR code with the pairing code"
        // SVG is generated server-side by the qrcode library from our own payload.
        dangerouslySetInnerHTML={{ __html: view.qrSvg }}
      />
    </Card>
  );
}
