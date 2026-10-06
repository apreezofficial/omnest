import type { Metadata } from "next";
import { HandPalm, Check } from "@phosphor-icons/react/dist/ssr";
import { Logo, LogoMark } from "@/components/Logo";
import { Button } from "@/components/ui/Button";
import { Card } from "@/components/ui/Card";
import { Chip } from "@/components/ui/Chip";
import { Input } from "@/components/ui/Input";

export const metadata: Metadata = {
  title: "Style guide",
  robots: { index: false },
};

const swatches = [
  ["cream-50", "bg-cream-50"],
  ["cream-100", "bg-cream-100"],
  ["cream-200", "bg-cream-200"],
  ["cream-300", "bg-cream-300"],
  ["green-100", "bg-green-100"],
  ["green-200", "bg-green-200"],
  ["green-600", "bg-green-600"],
  ["green-700", "bg-green-700"],
  ["green-900", "bg-green-900"],
  ["sun-200", "bg-sun-200"],
  ["sun-500", "bg-sun-500"],
  ["sun-600", "bg-sun-600"],
  ["ink-300", "bg-ink-300"],
  ["ink-500", "bg-ink-500"],
  ["ink-700", "bg-ink-700"],
  ["ink-900", "bg-ink-900"],
  ["coral-100", "bg-coral-100"],
  ["coral-600", "bg-coral-600"],
  ["sky-100", "bg-sky-100"],
  ["sky-600", "bg-sky-600"],
] as const;

function Section({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <section className="flex flex-col gap-6">
      <h2 className="type-h3">{title}</h2>
      {children}
    </section>
  );
}

/** Internal page to check tokens and components against docs/specs.md. */
export default function StyleGuide() {
  return (
    <main className="mx-auto flex w-full max-w-[1152px] flex-col gap-16 px-5 py-16 md:px-8">
      <header className="flex flex-col gap-4">
        <Logo />
        <h1 className="type-h1">Style guide</h1>
      </header>

      <Section title="Logo">
        <div className="flex items-center gap-6">
          <LogoMark className="size-16 text-primary" />
          <LogoMark className="size-16 text-text" mono />
          <Logo />
        </div>
      </Section>

      <Section title="Color">
        <div className="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-5">
          {swatches.map(([name, cls]) => (
            <div key={name} className="flex items-center gap-3">
              <span className={`size-12 shrink-0 rounded-md border border-border ${cls}`} />
              <code className="font-mono text-caption">{name}</code>
            </div>
          ))}
        </div>
      </Section>

      <Section title="Type">
        <div className="flex flex-col gap-3">
          <p className="type-display">Display</p>
          <p className="type-h1">Heading 1</p>
          <p className="type-h2">Heading 2</p>
          <p className="type-h3">Heading 3</p>
          <p className="type-h4">Heading 4</p>
          <p className="text-body-lg">Body large. Plain words, short sentences.</p>
          <p className="text-body">Body. Talk like a friendly older sibling.</p>
          <p className="text-body-sm text-text-muted">Body small, muted.</p>
          <p className="type-label">Label</p>
          <p className="text-caption text-text-subtle">Caption</p>
          <p className="font-mono text-h2 font-bold tracking-widest">482 913</p>
        </div>
      </Section>

      <Section title="Buttons">
        <div className="flex flex-wrap items-center gap-4">
          <Button>Primary</Button>
          <Button variant="secondary">Secondary</Button>
          <Button variant="ghost">Ghost</Button>
          <Button variant="danger">Lock phone</Button>
          <Button disabled>Disabled</Button>
          <Button size="sm">Small</Button>
          <Button size="lg">Large</Button>
        </div>
        <Button variant="accent" pill className="h-16 w-full max-w-80" icon={<HandPalm size={24} weight="bold" />}>
          Knock for 15 more minutes
        </Button>
      </Section>

      <Section title="Inputs">
        <div className="grid max-w-md gap-6">
          <Input label="Email" type="email" placeholder="you@example.com" helper="We'll never share it." />
          <Input label="Password" type="password" error="Use at least 8 characters." />
        </div>
      </Section>

      <Section title="Cards and chips">
        <div className="grid gap-6 md:grid-cols-2">
          <Card>
            <h3 className="type-h4">Quiet card</h3>
            <p className="text-body-sm text-text-muted">1px border, no shadow.</p>
          </Card>
          <Card featured>
            <h3 className="type-h4">Featured card</h3>
            <p className="text-body-sm text-text-muted">2px border with the hard shadow.</p>
          </Card>
        </div>
        <div className="flex flex-wrap gap-3">
          <Chip tone="green">
            <Check size={16} weight="bold" aria-hidden /> Approved
          </Chip>
          <Chip tone="sun">Waiting</Chip>
          <Chip tone="coral">Locked</Chip>
          <Chip tone="sky">Teen</Chip>
        </div>
      </Section>
    </main>
  );
}
