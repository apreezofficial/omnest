import type { Metadata } from "next";
import Link from "next/link";
import { ArrowLeft } from "@phosphor-icons/react/dist/ssr";
import { Card } from "@/components/ui/Card";
import { createChild } from "../../actions";
import { ChildForm } from "../ChildForm";

export const metadata: Metadata = { title: "Add a child" };

export default function NewChildPage() {
  return (
    <div className="flex max-w-[560px] flex-col gap-6">
      <Link href="/dashboard" className="type-label inline-flex min-h-12 items-center gap-2 self-start text-primary">
        <ArrowLeft size={20} weight="bold" aria-hidden />
        Back
      </Link>
      <h1 className="type-h2">Add a child</h1>
      <Card className="p-6">
        <ChildForm action={createChild} submitLabel="Add child" />
      </Card>
    </div>
  );
}
