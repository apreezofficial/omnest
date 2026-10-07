import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { ArrowLeft } from "@phosphor-icons/react/dist/ssr";
import { Card } from "@/components/ui/Card";
import { api, type Child } from "@/lib/api";
import { requireUser } from "@/lib/session";
import { updateChild } from "../../../actions";
import { ChildForm } from "../../ChildForm";
import { DeleteChild } from "./DeleteChild";

export const metadata: Metadata = { title: "Edit child" };

export default async function EditChildPage({ params }: PageProps<"/dashboard/children/[id]/edit">) {
  const { id } = await params;
  const { token } = await requireUser();
  const res = await api<Child>(`/children/${encodeURIComponent(id)}`, { token });
  if (!res.ok) notFound();
  const child = res.data;

  return (
    <div className="flex max-w-[560px] flex-col gap-6">
      <Link href={`/dashboard/children/${child.id}`} className="type-label inline-flex min-h-12 items-center gap-2 self-start text-primary">
        <ArrowLeft size={20} weight="bold" aria-hidden />
        Back to {child.name}
      </Link>
      <h1 className="type-h2">Edit {child.name}</h1>
      <Card className="p-6">
        <ChildForm action={updateChild.bind(null, child.id)} child={child} submitLabel="Save changes" />
      </Card>
      <DeleteChild childId={child.id} name={child.name} />
    </div>
  );
}
