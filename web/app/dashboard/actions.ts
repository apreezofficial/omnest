"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import QRCode from "qrcode";
import { api, type Child, type Device, type PairingCode } from "@/lib/api";
import { fromApiError, str, type FormState } from "@/lib/form";
import { getToken } from "@/lib/session";

function childInput(form: FormData) {
  const birth = str(form, "birth_date");
  return {
    name: str(form, "name"),
    age_tier: str(form, "age_tier"),
    birth_date: birth === "" ? null : birth,
    avatar: str(form, "avatar") || null,
  };
}

function formValues(input: ReturnType<typeof childInput>): Record<string, string> {
  return {
    name: input.name,
    age_tier: input.age_tier,
    birth_date: input.birth_date ?? "",
    avatar: input.avatar ?? "",
  };
}

export async function createChild(_: FormState, form: FormData): Promise<FormState> {
  const input = childInput(form);
  const res = await api<Child>("/children", { method: "POST", body: input, token: await getToken() });
  if (!res.ok) return fromApiError(res.error, formValues(input));

  revalidatePath("/dashboard");
  // Straight to pairing: that's the next thing a parent needs to do.
  redirect(`/dashboard/children/${res.data.id}?pair=1`);
}

export async function updateChild(childId: number, _: FormState, form: FormData): Promise<FormState> {
  const input = childInput(form);
  const res = await api<Child>(`/children/${childId}`, { method: "PATCH", body: input, token: await getToken() });
  if (!res.ok) return fromApiError(res.error, formValues(input));

  revalidatePath("/dashboard");
  redirect(`/dashboard/children/${childId}`);
}

export async function deleteChild(childId: number): Promise<void> {
  await api(`/children/${childId}`, { method: "DELETE", token: await getToken() });
  revalidatePath("/dashboard");
  redirect("/dashboard");
}

export type PairingState =
  | { ok: true; code: string; expiresAt: string; qrSvg: string }
  | { ok: false; message: string };

export async function createPairingCode(childId: number): Promise<PairingState> {
  const res = await api<PairingCode>(`/children/${childId}/pairing-codes`, { method: "POST", token: await getToken() });
  if (!res.ok) return { ok: false, message: res.error.message };

  const qrSvg = await QRCode.toString(res.data.qr_payload, {
    type: "svg",
    margin: 1,
    errorCorrectionLevel: "M",
    color: { dark: "#16211B", light: "#FFFDF7" },
  });

  return { ok: true, code: res.data.code, expiresAt: res.data.expires_at, qrSvg };
}

export async function listDevices(childId: number): Promise<Device[]> {
  const res = await api<Device[]>(`/children/${childId}/devices`, { token: await getToken() });
  return res.ok ? res.data : [];
}

export async function unpairDevice(childId: number, deviceId: number): Promise<void> {
  await api(`/devices/${deviceId}`, { method: "DELETE", token: await getToken() });
  revalidatePath(`/dashboard/children/${childId}`);
}
