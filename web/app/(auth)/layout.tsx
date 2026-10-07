import Link from "next/link";
import { Logo } from "@/components/Logo";

export default function AuthLayout({ children }: LayoutProps<"/">) {
  return (
    <main className="flex flex-1 flex-col items-center px-5 py-12 md:py-16">
      <Link href="/" className="mb-8 rounded-sm" aria-label="Omnest home">
        <Logo />
      </Link>
      <div className="w-full max-w-[440px] rounded-xl border-2 border-border-strong bg-surface p-6 shadow-hard-lg md:p-8">
        {children}
      </div>
    </main>
  );
}
