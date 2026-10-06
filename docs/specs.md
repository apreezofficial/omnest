# Omnest - specs.md

Design system for the landing page, parent dashboard, and Android app.
One source of truth: if a value isn't here, add it here first, then use it.

## 1. Brand

- **Name:** Omnest (from Yoruba *ọmọ*, child + nest: a safe space for kids)
- **Tagline:** Limits that talk back.
- **Hook feature name:** **Knock**. The child taps Knock on the blocked screen, the parent answers.
- **Style:** Bold and flat. Warm, chunky, confident. No gradients, no glassmorphism, no soft blurry shadows.
- **Logo direction:** a simple nest/door mark built from thick rounded strokes, one accent dot (the knock). Works in one color.

### Voice and tone
- Plain words, short sentences. Talk like a friendly older sibling, not a security product.
- Parent-facing: calm, reassuring, practical.
- Child-facing: respectful, never scolding. "Time's up for today. Want to knock?" not "ACCESS DENIED".
- Avoid: surveillance, spy, monitor, track (use *see*, *guide*, *set limits*).

## 2. Color

### Core palette (light)

| Token | Hex | Use |
|---|---|---|
| `cream-50` | `#FFFDF7` | Cards, surfaces, inputs |
| `cream-100` | `#FBF6EA` | App / page background |
| `cream-200` | `#F3EBD6` | Subtle fills, hover on cream |
| `cream-300` | `#E6DCC0` | Borders, dividers |
| `green-900` | `#0F3524` | Deep text on green tints, footer bg |
| `green-700` | `#1F5B3A` | **Primary** (buttons, links, brand) |
| `green-600` | `#2A7449` | Primary hover |
| `green-200` | `#CFE5D3` | Primary tint (chips, highlights) |
| `green-100` | `#E5F1E6` | Success/positive backgrounds |
| `sun-500` | `#F5A524` | **Accent**: Knock button, approvals, key highlights |
| `sun-600` | `#DB8E10` | Accent hover/pressed |
| `sun-200` | `#FCE3B0` | Accent tint |
| `ink-900` | `#16211B` | Primary text, borders on bold elements |
| `ink-700` | `#3B4A41` | Secondary text |
| `ink-500` | `#6B7A70` | Muted text, placeholders |
| `ink-300` | `#B7C0B9` | Disabled |
| `coral-600` | `#C93C2E` | Danger / destructive / lock |
| `coral-100` | `#F9DAD5` | Danger tint |
| `sky-600` | `#2B6CB0` | Info |
| `sky-100` | `#DCEAF7` | Info tint |

### Semantic mapping
- `bg` = cream-100, `surface` = cream-50, `border` = cream-300, `border-strong` = ink-900
- `text` = ink-900, `text-muted` = ink-700, `text-subtle` = ink-500
- `primary` = green-700, `on-primary` = cream-50
- `accent` = sun-500, `on-accent` = ink-900 (never white on yellow)
- `success` = green-700 on green-100, `warning` = ink-900 on sun-200, `danger` = coral-600 on coral-100

### Dark mode

| Token | Hex |
|---|---|
| `bg` | `#0F1712` |
| `surface` | `#17221B` |
| `border` | `#2B3A31` |
| `border-strong` | `#E8E2D0` |
| `text` | `#F1ECDD` |
| `text-muted` | `#B9C2B9` |
| `primary` | `#5FBF85` (on-primary `#0F1712`) |
| `accent` | `#F5B544` (on-accent `#16211B`) |
| `danger` | `#FF7A6B` |

### Rules
- No gradients anywhere. Flat fills only.
- Text contrast: at least 4.5:1 (body), 3:1 (large text, UI borders).
- Accent is rare: one accent element per screen area, max. If everything is yellow, nothing is.
- Color is never the only signal (pair with icon or label).

## 3. Typography

| Role | Font | Fallback |
|---|---|---|
| Headings / display | **Bricolage Grotesque** (600, 700, 800) | system-ui, sans-serif |
| Body / UI | **DM Sans** (400, 500, 700) | system-ui, sans-serif |
| Codes / numbers | **JetBrains Mono** (500, 700) | ui-monospace, monospace |

Use for: pairing code, timers, usage numbers. Everything else is DM Sans.

Android: bundle only the weights above, subset to Latin, to keep the APK small.
Web: `next/font`, `display: swap`, same weights only.

### Type scale (web, rem / px)

| Token | Size | Line height | Weight | Font |
|---|---|---|---|---|
| `display` | 4rem / 64 | 1.02 | 800 | Bricolage |
| `h1` | 3rem / 48 | 1.08 | 800 | Bricolage |
| `h2` | 2.25rem / 36 | 1.12 | 700 | Bricolage |
| `h3` | 1.5rem / 24 | 1.2 | 700 | Bricolage |
| `h4` | 1.25rem / 20 | 1.3 | 600 | Bricolage |
| `body-lg` | 1.125rem / 18 | 1.6 | 400 | DM Sans |
| `body` | 1rem / 16 | 1.6 | 400 | DM Sans |
| `body-sm` | 0.875rem / 14 | 1.5 | 400 | DM Sans |
| `label` | 0.875rem / 14 | 1.2 | 700 | DM Sans |
| `caption` | 0.75rem / 12 | 1.4 | 500 | DM Sans |

Mobile web: `display` 2.5rem, `h1` 2.25rem, `h2` 1.75rem, `h3` 1.25rem.
Letter spacing: headings `-0.02em`, labels `0.01em`, body `0`.

### Type scale (Android, sp)

| Token | Size | Weight | Font |
|---|---|---|---|
| `display` | 40 | 800 | Bricolage |
| `headline` | 28 | 700 | Bricolage |
| `title` | 20 | 700 | Bricolage |
| `subtitle` | 16 | 600 | Bricolage |
| `body` | 16 | 400 | DM Sans |
| `body-sm` | 14 | 400 | DM Sans |
| `label` | 14 | 700 | DM Sans |
| `caption` | 12 | 500 | DM Sans |
| `code` | 32 | 700 | JetBrains Mono |

Minimum text size anywhere: 12sp/px. Support system font scaling up to 130%.

## 4. Spacing

Base unit: **4px** (4dp on Android). Use only these steps.

| Token | Value |
|---|---|
| `space-1` | 4 |
| `space-2` | 8 |
| `space-3` | 12 |
| `space-4` | 16 |
| `space-5` | 20 |
| `space-6` | 24 |
| `space-8` | 32 |
| `space-10` | 40 |
| `space-12` | 48 |
| `space-16` | 64 |
| `space-24` | 96 |

Conventions:
- Screen/page horizontal padding: 20 (mobile), 32 (tablet), container-centered (desktop)
- Card padding: 20 (24 on desktop)
- Gap between related items: 8-12; between groups: 24; between sections: 64 (mobile) / 96 (desktop)
- Landing page section vertical padding: 64 mobile, 96 desktop

## 5. Radius

| Token | Value | Use |
|---|---|---|
| `radius-sm` | 8 | Chips, small tags, inputs' inner elements |
| `radius-md` | 12 | Buttons, inputs |
| `radius-lg` | 20 | Cards |
| `radius-xl` | 28 | Large cards, sheets, blocked-screen panel |
| `radius-full` | 9999 | Avatars, pills, toggles, the Knock button |

Rule: nested radius = outer radius minus padding (don't put radius-lg inside radius-lg with the same value).

## 6. Borders, shadows, elevation

The look is **flat with hard edges**, not soft and blurry.

- Border width: **2px** on interactive/bold elements (buttons, inputs, cards that are actionable), **1px** for quiet dividers.
- Bold border color: `ink-900`. Quiet border color: `cream-300`.
- Hard shadow (the signature): `0 3px 0 0 ink-900` on buttons and key cards. Pressed: shadow removed, element translates down 3px.
- Large hard shadow (hero cards): `0 6px 0 0 ink-900`.
- No blur shadows. No glow. No inner shadows.
- Android: draw hard shadow as an offset solid shape behind the element (not `elevation`).
- Dark mode: border and shadow color = `#E8E2D0`, or drop the shadow and keep a 2px border if it feels heavy.

## 7. Layout

| Breakpoint | Min width | Container max | Columns |
|---|---|---|---|
| `sm` | 0 | 100% | 4 |
| `md` | 768 | 720 | 8 |
| `lg` | 1024 | 960 | 12 |
| `xl` | 1280 | 1152 | 12 |

- Mobile-first. Design for 360px width first (common Nigerian Android size), then scale up.
- Touch targets: minimum **48x48** (dp/px). Space between targets: 8 minimum.
- Max line length for reading text: 65 characters.

## 8. Iconography

- One family: **Phosphor Icons**, *Bold* weight for UI, *Fill* for active states.
- Sizes: 16, 20, 24, 32. Default 24. Stroke follows the family, don't mix sets.
- Icons sit on `ink-900` or `green-700`; never on a busy fill.

## 9. Components

### Buttons
| Variant | Fill | Text | Border | Shadow |
|---|---|---|---|---|
| Primary | green-700 | cream-50 | 2px ink-900 | 0 3px 0 ink-900 |
| Accent (Knock) | sun-500 | ink-900 | 2px ink-900 | 0 3px 0 ink-900 |
| Secondary | cream-50 | ink-900 | 2px ink-900 | 0 3px 0 ink-900 |
| Ghost | transparent | green-700 | none | none |
| Danger | coral-600 | cream-50 | 2px ink-900 | 0 3px 0 ink-900 |

- Height: 48 default, 56 large, 40 small. Radius: `radius-md` (Knock button: `radius-full`).
- Padding: 20 horizontal. Label: `label` style.
- States: hover (one shade darker), pressed (shadow gone, translateY 3px), focus (3px outline `sky-600`, offset 2), disabled (`ink-300` fill, no shadow).

### Inputs
- Height 52, radius-md, 2px `ink-900` border, `cream-50` fill, 16px text (prevents iOS/Android zoom).
- Focus: border `green-700` + 3px `green-200` ring.
- Error: border `coral-600`, message below in `coral-600` with icon.
- Label above the field (`label` style), helper text below (`caption`, ink-500).
- Pairing code input: 6 boxes, JetBrains Mono, 56x64 each.

### Cards
- Fill `cream-50`, radius-lg, 1px `cream-300` border (quiet) or 2px `ink-900` + hard shadow (featured/actionable).
- Padding 20-24.

### Chips / tags
- Height 32, radius-full, padding 12, `caption`/`label` size, tint fill (green-200, sun-200, coral-100, sky-100) with matching dark text.

### Toggles and checkboxes
- Toggle: 52x32, radius-full, 2px ink-900 border, on = green-700, off = cream-200.
- Checkbox: 24x24, radius-sm, 2px ink-900.

### Bottom sheet / modal
- radius-xl top corners, cream-50 fill, 2px ink-900 top border, drag handle 40x4 `cream-300`.
- Scrim: `ink-900` at 50% opacity (flat, no blur).

### Toast / snackbar
- ink-900 fill, cream-50 text, radius-md, 16 padding, 4s default.

### Progress and time ring
- Track `cream-300`, fill `green-700`; under 15% time left = `sun-500`; at 0 = `coral-600`.
- Stroke width 12 (ring) or 10 height (bar), rounded caps.

### Signature: the Knock screen (child blocked screen)
- Full-screen `cream-100` background.
- Centered door/nest illustration (flat shapes, green + cream + one sun-500 dot).
- Headline (`headline`): "Time's up for today."
- Subtext (`body`): "Want a little more? Ask your parent."
- Knock button: accent, full-width (max 320), radius-full, 64 tall, label "Knock for 15 more minutes".
- Small secondary text button under it: "Choose a different time".
- Waiting state: button becomes disabled with pulsing dot, text "Waiting for your parent...".
- Approved: green-100 panel, check icon, "Approved! You have 15 more minutes."
- Denied: cream-200 panel, "Not this time. You can try again later."

## 10. Motion

| Token | Duration | Easing | Use |
|---|---|---|---|
| `fast` | 120ms | ease-out | Press, hover, toggles |
| `base` | 200ms | ease-in-out | Fades, color changes |
| `slow` | 320ms | cubic-bezier(0.2, 0.8, 0.2, 1) | Sheets, page/section reveals |

- Knock button: on tap, a quick 2-beat "knock" wiggle (translateX, 120ms x2).
- Scroll reveals on landing: fade + 16px rise, once, 320ms.
- Respect `prefers-reduced-motion` / Android animator-duration-scale: disable non-essential motion.

## 11. Imagery and illustration

- Flat vector illustration only, thick rounded shapes, palette colors only, no outlines thinner than 2px.
- Photos (if any): real families, warm light, rounded `radius-lg`; no stock "hacker" or padlock imagery.
- App mockups: real screens in a simple flat phone frame, cream background.

## 12. Accessibility

- Contrast: 4.5:1 body, 3:1 large text and UI edges.
- Focus rings always visible on web; never remove outlines.
- Minimum touch target 48.
- Support font scaling to 130%, screen readers (labels on every icon button), reduced motion.
- Don't rely on color alone; pair with icon/text.

## 13. Implementation tokens

### Tailwind

The web app uses Tailwind v4, which is configured in CSS. The live tokens are in
`web/app/globals.css` (`@theme` block). They match the values below, plus semantic
tokens (`bg`, `surface`, `text`, `primary`, `accent`, ...) that switch for dark mode.
Tailwind's default palette, shadows and breakpoints are turned off, so only spec values exist.
Headings use `type-display`, `type-h1` ... `type-h4` and `type-label` utilities (font, weight, tracking, mobile size together).

Reference (v3-style equivalent):
```ts
import type { Config } from "tailwindcss";

export default {
  content: ["./app/**/*.{ts,tsx}", "./components/**/*.{ts,tsx}"],
  theme: {
    extend: {
      colors: {
        cream: { 50: "#FFFDF7", 100: "#FBF6EA", 200: "#F3EBD6", 300: "#E6DCC0" },
        green: { 100: "#E5F1E6", 200: "#CFE5D3", 600: "#2A7449", 700: "#1F5B3A", 900: "#0F3524" },
        sun: { 200: "#FCE3B0", 500: "#F5A524", 600: "#DB8E10" },
        ink: { 300: "#B7C0B9", 500: "#6B7A70", 700: "#3B4A41", 900: "#16211B" },
        coral: { 100: "#F9DAD5", 600: "#C93C2E" },
        sky: { 100: "#DCEAF7", 600: "#2B6CB0" },
      },
      fontFamily: {
        display: ["var(--font-bricolage)", "system-ui", "sans-serif"],
        sans: ["var(--font-dm-sans)", "system-ui", "sans-serif"],
        mono: ["var(--font-jetbrains)", "ui-monospace", "monospace"],
      },
      borderRadius: { sm: "8px", md: "12px", lg: "20px", xl: "28px" },
      boxShadow: {
        hard: "0 3px 0 0 #16211B",
        "hard-lg": "0 6px 0 0 #16211B",
      },
    },
  },
} satisfies Config;
```

### Compose

Live code: `android/app/src/main/kotlin/com/omnest/child/ui/theme/` (`Color.kt`, `Type.kt`, `Dimens.kt`, `Theme.kt`).
Screens read semantic colors via `OmnestTheme.colors` and type via `OmnestTheme.type`.

Outline:
```kotlin
object OmnestColors {
    val Cream50 = Color(0xFFFFFDF7); val Cream100 = Color(0xFFFBF6EA)
    val Cream200 = Color(0xFFF3EBD6); val Cream300 = Color(0xFFE6DCC0)
    val Green700 = Color(0xFF1F5B3A); val Green600 = Color(0xFF2A7449)
    val Green200 = Color(0xFFCFE5D3); val Green100 = Color(0xFFE5F1E6)
    val Sun500 = Color(0xFFF5A524); val Sun200 = Color(0xFFFCE3B0)
    val Ink900 = Color(0xFF16211B); val Ink700 = Color(0xFF3B4A41)
    val Ink500 = Color(0xFF6B7A70); val Coral600 = Color(0xFFC93C2E)
}

object OmnestSpacing { val s1 = 4.dp; val s2 = 8.dp; val s3 = 12.dp; val s4 = 16.dp
    val s5 = 20.dp; val s6 = 24.dp; val s8 = 32.dp; val s12 = 48.dp }

object OmnestShapes { val sm = RoundedCornerShape(8.dp); val md = RoundedCornerShape(12.dp)
    val lg = RoundedCornerShape(20.dp); val xl = RoundedCornerShape(28.dp) }
```

## 14. Do / Don't

**Do**
- Use the tokens above for every color, space, radius, and font size.
- Keep one accent element per area; let green carry the brand.
- Give every screen empty, loading, and error states in the same style.

**Don't**
- Add gradients, blur shadows, or new colors ad hoc.
- Mix icon families or corner radii inside one component.
- Use white text on yellow, or yellow text on cream.
- Use scary security imagery (locks with red glows, spy eyes, shields everywhere).