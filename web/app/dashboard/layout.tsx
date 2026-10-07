import Link from "next/link";
import { SignOut } from "@phosphor-icons/react/dist/ssr";
import { Logo } from "@/components/Logo";
import { requireUser } from "@/lib/session";
import { logout } from "../(auth)/actions";
import { VerifyEmailBanner } from "./VerifyEmailBanner";

export default async function DashboardLayout({ children }: LayoutProps<"/dashboard">) {
  const { user } = await requireUser();

  return (
    <div className="flex flex-1 flex-col">
      <header className="border-b-2 border-border-strong bg-surface">
        <div className="mx-auto flex h-16 w-full max-w-[1152px] items-center justify-between gap-4 px-5 md:px-8">
          <Link href="/dashboard" aria-label="Dashboard home" className="rounded-sm">
            <Logo />
          </Link>
          <div className="flex items-center gap-2">
            <span className="hidden text-body-sm text-text-muted md:inline">{user.name}</span>
            <form action={logout}>
              <button
                type="submit"
                className="type-label inline-flex h-12 items-center gap-2 rounded-md px-3 text-primary hover:bg-green-100 dark:hover:bg-surface"
              >
                <SignOut size={20} weight="bold" aria-hidden />
                Sign out
              </button>
            </form>
          </div>
        </div>
      </header>
      {!user.email_verified && <VerifyEmailBanner email={user.email} />}
      <main className="mx-auto flex w-full max-w-[1152px] flex-1 flex-col gap-8 px-5 py-8 md:px-8 md:py-12">{children}</main>
    </div>
  );
}
