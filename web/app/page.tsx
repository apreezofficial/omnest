import Link from "next/link";
import { Logo } from "@/components/Logo";
import { Button } from "@/components/ui/Button";

// Holding page until the full landing page (Phase 7).
export default function Home() {
  return (
    <main className="mx-auto flex w-full max-w-[1152px] flex-1 flex-col justify-center gap-8 px-5 py-16 md:px-8">
      <Logo />
      <div className="flex max-w-[65ch] flex-col gap-4">
        <h1 className="type-display text-text">Limits that talk back.</h1>
        <p className="text-body-lg text-text-muted">
          Screen time limits for your child&apos;s Android phone. When time is up, they tap{" "}
          <strong className="text-text">Knock</strong> to ask for more, and you answer in one tap.
        </p>
      </div>
      <div className="flex flex-wrap items-center gap-4">
        <Button variant="primary" size="lg" disabled>
          Waitlist opens soon
        </Button>
        <Link href="/login" className="type-label inline-flex min-h-12 items-center px-2 text-primary underline-offset-4 hover:underline">
          Parent sign in
        </Link>
      </div>
    </main>
  );
}
