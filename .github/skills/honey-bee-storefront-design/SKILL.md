---
name: honey-bee-storefront-design
description: >
  Implement the Honey Bee artisan soap storefront UI using the Stitch design system ("The Luminous Alchemist").
  Use when: building any page or component for client-honey-bee storefront, implementing product listings,
  product detail pages, navigation, hero sections, cards, buttons, typography, or adapting the Stitch
  design reference into Next.js + Tailwind CSS code.
argument-hint: 'Specify page/component: "homepage", "shop", "product-detail", "our-story", "navigation", "card", "button", or "layout"'
applyTo: 'client-honey-bee/**'
---

# Honey Bee Storefront — Design System Skill

## Purpose

Guide for implementing the **Honey Bee artisan soap storefront** using the Stitch-designed "Luminous Alchemist" design system in **Next.js 14+ with Tailwind CSS v4**.

This design is **not a generic template**. It is a digital atelier with a distinct editorial luxury aesthetic inspired by Ayurvedic slow-made craftsmanship.

## Full Design Reference

**ALWAYS read this first**: `client-honey-bee/src/design-system/HONEY-BEE-DESIGN-SYSTEM.md`

HTML prototypes with all Tailwind classes: `client-honey-bee/src/design-system/design-reference/stitch/`

## CRITICAL: Tailwind v4 Configuration

**This repo uses Tailwind CSS v4. There is NO `tailwind.config.ts` file.**

All theme tokens are defined in `src/app/globals.css` inside a single `@theme {}` block:

```css
/* globals.css */
@import "tailwindcss";

@theme {
  --color-primary: #7b5800;
  --color-background: #fcf9f4;
  /* ... all tokens here */
}

@layer utilities {
  .honey-glow { background: linear-gradient(135deg, #7b5800 0%, #d59f2b 100%); }
  .botanical-glass { background: rgba(252,249,244,0.8); backdrop-filter: blur(20px); }
  .sunlight-shadow { box-shadow: 0 12px 40px rgba(28,28,25,0.05); }
  .hero-overlay { background: linear-gradient(to right, rgba(252,249,244,0.9), rgba(252,249,244,0.4) 50%, transparent); }
  .label-caps { font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.1em; text-transform: uppercase; }
}
```

The `@theme` block makes tokens available as Tailwind utilities (`text-primary`, `bg-background`, etc.) **and** as CSS vars (`var(--color-primary)`).

## Quick Reference

### Fonts (Load in layout.tsx)

```tsx
import { Noto_Serif, Manrope } from 'next/font/google';

const notoSerif = Noto_Serif({
  subsets: ['latin'],
  weight: ['400', '700'],
  style: ['normal', 'italic'],
  variable: '--font-headline',
});

const manrope = Manrope({
  subsets: ['latin'],
  weight: ['300', '400', '500', '600', '700', '800'],
  variable: '--font-body',
});
```

### Icons

**Use Material Symbols Outlined — NOT Heroicons.**

Load via `<link>` in `layout.tsx` and render as:
```tsx
<span
  className="material-symbols-outlined"
  style={{ fontVariationSettings: "'wght' 200" }}
>
  shopping_bag
</span>
```

Do NOT import `@heroicons/react` — it is not installed.

### Core Color Rules

```
text-[#1c1c19]           ← ALL "black" text (NEVER #000000)
text-[#7b5800]           ← Brand amber primary
bg-[#fcf9f4]             ← Page canvas (background)
bg-[#ffffff]             ← Cards (surface-container-lowest)
bg-[#f0ede8]             ← Grouped sections (surface-container)
bg-[#e0e5cc]             ← Botanical sections (secondary-container)
```

### Special CSS Classes

```css
/* All defined in globals.css @layer utilities */
.honey-glow      → amber gradient CTA background
.botanical-glass → frosted glass nav (rgba + backdrop-blur)
.sunlight-shadow → warm soft box-shadow for cards
.hero-overlay    → left-to-right warm fade for hero images
.label-caps      → 11px / 500 / 0.1em / uppercase — all labels/tags
```

---

## Component Patterns

### Navigation Bar (botanical-glass)

Header uses `botanical-glass` sticky nav with:
- Left: brand mark as `font-headline text-2xl text-[#7b5800]`
- Centre: `navLinks` array mapped to `Link` with `label-caps` + `pb-0.5 border-b` active indicator
- Right: Material Symbols cart/account + mobile toggle SVG

```tsx
<header className="botanical-glass sticky top-0 z-50 w-full">
  <div className="flex justify-between items-center px-6 md:px-20 py-5">
    <Link href="/" className="font-headline text-2xl text-[#7b5800] tracking-tight">
      Honey Bee
    </Link>
    <nav className="hidden md:flex items-center gap-10">
      {navLinks.map(link => (
        <Link key={link.href} href={link.href}
          className={`label-caps text-[#5c614d] hover:text-[#7b5800] transition-colors pb-0.5 ${
            pathname === link.href ? 'border-b border-[#7b5800] text-[#7b5800]' : ''
          }`}>
          {link.label}
        </Link>
      ))}
    </nav>
    {/* Icons: Material Symbols wght 200 */}
  </div>
</header>
```

### Hero Section

```tsx
<section className="relative min-h-[850px] flex items-end pb-24 overflow-hidden">
  <div className="absolute inset-0">
    <Image src={heroImage} alt="..." fill className="object-cover" priority />
    <div className="absolute inset-0 hero-overlay" />
  </div>
  <div className="relative z-10 px-6 md:px-20 max-w-2xl">
    <p className="label-caps text-[#5c614d] mb-4">HANDCRAFTED INTENTION</p>
    <h1 className="font-headline text-5xl md:text-6xl lg:text-7xl text-[#1c1c19] leading-[1.05] mb-6">
      Artisan Cold-Process Soaps
    </h1>
    <p className="text-base text-[#5c614d] leading-relaxed max-w-md mb-10">
      Slow-made in small batches using traditional Ayurvedic wisdom.
    </p>
    <div className="flex flex-wrap gap-4">
      <button className="honey-glow text-white font-label tracking-wider rounded-xl px-8 py-4">
        EXPLORE SHOP
      </button>
      <button className="text-[#1c1c19] underline underline-offset-4 font-label tracking-wider hover:text-[#7b5800]">
        VIEW RITUALS
      </button>
    </div>
  </div>
</section>
```

### Product Card (Artisan Card)

```tsx
<div className="bg-white rounded-xl sunlight-shadow overflow-hidden">
  <div className="aspect-[4/5] overflow-hidden">
    <Image src={product.image} alt={product.name} width={400} height={500}
      className="w-full h-full object-cover hover:scale-105 transition-transform duration-500" />
  </div>
  <div className="p-5">
    {product.badge && (
      <span className="honey-glow text-white label-caps rounded-full px-3 py-1 mb-3 inline-block">
        {product.badge}
      </span>
    )}
    <div className="flex justify-between items-baseline mb-1">
      <h3 className="font-headline text-xl text-[#1c1c19]">{product.name}</h3>
      <span className="font-semibold text-[#7b5800]">${product.price}</span>
    </div>
    <p className="label-caps text-[#5c614d] mb-3">{product.fragrance}</p>
    <div className="flex gap-2 flex-wrap">
      {product.tags.map(tag => (
        <span key={tag} className="rounded-full bg-[#f0ede8] label-caps px-3 py-1 text-[#5c614d]">
          {tag}
        </span>
      ))}
    </div>
  </div>
</div>
```

### Dark Brand Band ("Nurse's Promise")

```tsx
<section className="bg-[#7b5800] py-20 px-8 md:px-20 text-center">
  <p className="label-caps text-white/60 mb-6">THE NURSE'S PROMISE</p>
  <blockquote className="font-headline text-2xl md:text-3xl italic text-white max-w-3xl mx-auto leading-relaxed">
    "{quote}"
  </blockquote>
  <cite className="label-caps text-white/70 mt-6 block not-italic">{attribution}</cite>
</section>
```

---

## Layout Rules

| Context | Horizontal Padding |
|---------|-------------------|
| Mobile | `px-6` |
| Desktop | `px-6 md:px-20` (80px) |

**NO visible borders between sections** — use background colour shifts only.  
**NO HR dividers** in lists — use vertical spacing.  
**Cards**: always `rounded-xl`, never visible border, `sunlight-shadow` only.  
**Hero**: `items-end pb-24` (copy at bottom-left), `min-h-[850px]`.

---

## What NEVER to Do

- ❌ `text-black` or `text-[#000000]` → use `text-[#1c1c19]`
- ❌ Import `@heroicons/react` → use Material Symbols Outlined (`<span className="material-symbols-outlined">`)
- ❌ `border-b border-gray-200` between sections → change background color instead
- ❌ `shadow-lg` with black/grey → use `.sunlight-shadow` only
- ❌ Flat color primary button → always `.honey-glow` gradient
- ❌ `font-sans` for headlines → always `font-headline` (Noto Serif)
- ❌ `rounded-md` for cards → always `rounded-xl` (1.5rem artisan radius)
- ❌ Client-side only data fetching → use `generateStaticParams` + `fetch` in Server Components
- ❌ Tailwind config file → all tokens are in `@theme {}` block in `globals.css`
- ❌ `tailwind.config.ts` → does not exist in this repo

---

## Page Structure Quick Reference

| Page | Key Sections |
|------|-------------|
| `/` Homepage | botanical-glass nav → Hero (min-h-850, items-end) → 4-icon features row → Collections grid (3-col) → Current Favourites (artisan cards) → Story teaser (2-col + pull-quote) → Dark CTA band → Footer |
| `/shop` | Nav → Editorial title → 2-col (filters sidebar + product grid) → Load more → Footer |
| `/shop/[slug]` | Nav → 2-col (gallery + info) → Nurse's Note → Ingredients → Cold Process → Usage Ritual → Testimonials → Related → Footer |
| `/our-story` | Nav → Hero (founder) → Our Process → Radical Sourcing → Nurse's Promise band → CTA → Footer |

---

## Checklist for Any New Component

- [ ] Uses `font-headline` (Noto Serif) for all headings
- [ ] Uses `label-caps` utility class for labels, tags, eyebrows
- [ ] Text color is `#1c1c19`, never `#000000`
- [ ] No visible border lines between sections
- [ ] Cards use `rounded-xl` + white bg + `sunlight-shadow`
- [ ] Primary CTA uses `.honey-glow` gradient
- [ ] Navigation uses `.botanical-glass` frosted effect
- [ ] Horizontal padding is at least `px-6 md:px-20` on desktop
- [ ] No `grey`/`black` box shadows
- [ ] Icons are Material Symbols Outlined (NOT Heroicons)
- [ ] All images use `next/image` with `priority` on above-fold images
- [ ] Page uses SSG via `generateStaticParams` or Server Components for SEO

