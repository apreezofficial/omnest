import type { Metadata, Viewport } from "next";
import { Bricolage_Grotesque, DM_Sans, JetBrains_Mono } from "next/font/google";
import "./globals.css";

const bricolage = Bricolage_Grotesque({
  variable: "--font-bricolage",
  subsets: ["latin"],
  weight: ["600", "700", "800"],
  display: "swap",
});

const dmSans = DM_Sans({
  variable: "--font-dm-sans",
  subsets: ["latin"],
  weight: ["400", "500", "700"],
  display: "swap",
});

const jetbrains = JetBrains_Mono({
  variable: "--font-jetbrains",
  subsets: ["latin"],
  weight: ["500", "700"],
  display: "swap",
});

export const metadata: Metadata = {
  title: {
    default: "Omnest: Limits that talk back",
    template: "%s · Omnest",
  },
  description:
    "Screen time limits for your child's Android phone. When time is up, they can Knock to ask for more, and you answer in one tap.",
};

export const viewport: Viewport = {
  themeColor: [
    { media: "(prefers-color-scheme: light)", color: "#FBF6EA" },
    { media: "(prefers-color-scheme: dark)", color: "#0F1712" },
  ],
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="en" className={`${bricolage.variable} ${dmSans.variable} ${jetbrains.variable} h-full antialiased`}>
      <body className="flex min-h-full flex-col bg-bg text-text">{children}</body>
    </html>
  );
}
