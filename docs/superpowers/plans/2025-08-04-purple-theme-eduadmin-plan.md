# Purple Theme + Sidebar Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Apply a purple/indigo theme and a redesigned sidebar to the `eduadmin-dashboard` React app.

**Architecture:** Replace the runtime school theme with a hard-coded purple default, update the Tailwind v4 theme tokens so existing `blue-*` utilities render purple, and restyle the sidebar with a gradient background and a white rounded active item.

**Tech Stack:** React 19, TypeScript, Tailwind CSS v4, Vite, `clsx`/`tailwind-merge`, `lucide-react`.

## Global Constraints
- Scope is `eduadmin-dashboard` only.
- Ignore the backend school theme; use hard-coded purple defaults.
- Keep logo URL and school name from `ThemeContext`.
- Active nav item is a white rounded rectangle with dark purple/indigo text/icon (no curved tab effect).
- Sidebar background is a purple/indigo gradient.
- Build and lint must pass after changes.

---

## File Structure

| File | Responsibility |
|------|----------------|
| `eduadmin-dashboard/src/lib/theme.ts` | Default theme constants and color helpers. |
| `eduadmin-dashboard/src/context/ThemeContext.tsx` | Loads branding from backend and injects CSS variables; will ignore backend theme colors. |
| `eduadmin-dashboard/src/index.css` | Tailwind v4 `@theme` tokens; primary scale drives all `blue-*` utilities. |
| `eduadmin-dashboard/src/components/Sidebar.tsx` | Sidebar layout, nav items, active/hover/submenu styles. |

---

### Task 1: Update the default theme constants

**Files:**
- Modify: `eduadmin-dashboard/src/lib/theme.ts:3-7`

**Interfaces:**
- Produces: new hex values for `DEFAULT_THEME` consumed by `ThemeContext`.

- [ ] **Step 1: Change `DEFAULT_THEME` to purple/indigo**

```typescript
export const DEFAULT_THEME: SchoolTheme = {
  primary: '#4F46E5',
  primary_dark: '#312E81',
  accent: '#4F46E5',
};
```

- [ ] **Step 2: Verify no other constants need changing**

`primaryScale`, `mixHex`, `hexToRgba`, and `hexToRgb` remain unchanged; they derive values from the constants above.

- [ ] **Step 3: Commit**

```bash
git add eduadmin-dashboard/src/lib/theme.ts
git commit -m "feat(theme): set default purple/indigo theme"
```

---

### Task 2: Ignore backend theme colors in ThemeContext

**Files:**
- Modify: `eduadmin-dashboard/src/context/ThemeContext.tsx:32-39`

**Interfaces:**
- Consumes: `DEFAULT_THEME` from `lib/theme.ts`.
- Produces: `theme` object always returns `DEFAULT_THEME`; `logoUrl` and `schoolName` still come from backend.

- [ ] **Step 1: Replace the theme memo to always use the default**

```typescript
const theme = useMemo<SchoolTheme>(() => DEFAULT_THEME, []);
```

- [ ] **Step 2: Keep the CSS-variable effect so the rest of the app still reacts to the theme**

The existing `useEffect` at lines 41-52 can stay; it will now inject the purple default values into CSS custom properties.

- [ ] **Step 3: Run TypeScript check**

Run: `cd eduadmin-dashboard && npx tsc -b`
Expected: no errors.

- [ ] **Step 4: Commit**

```bash
git add eduadmin-dashboard/src/context/ThemeContext.tsx
git commit -m "feat(theme): ignore backend theme and use purple default"
```

---

### Task 3: Update Tailwind theme tokens in index.css

**Files:**
- Modify: `eduadmin-dashboard/src/index.css:6-80`

**Interfaces:**
- Consumes: new primary/accent colors from the design spec.
- Produces: updated CSS custom properties mapped to `blue-*`, `sky-*`, and `cyan-*` utilities.

- [ ] **Step 1: Replace primary dark and accent tokens**

```css
@theme {
  /* Reference design system — purple/indigo theme */
  --color-primary-dark: #312E81;   /* indigo-900: sidebar, primary buttons, card headers */
  --color-primary-dark-600: #1E1B4B;
  --color-accent: #4F46E5;         /* indigo-600: active nav, focus, links */

  --color-primary-50: #EEF2FF;
  --color-primary-100: #E0E7FF;
  --color-primary-200: #C7D2FE;
  --color-primary-300: #A5B4FC;
  --color-primary-400: #818CF8;
  --color-primary-500: #6366F1;
  --color-primary-600: #4F46E5;
  --color-primary-700: #4338CA;
  --color-primary-800: #3730A3;
  --color-primary-900: #312E81;
```

- [ ] **Step 2: Leave the rest of the theme section unchanged**

Success, danger, warning, background, text, surface, border, font, spacing, shadow, and radius tokens do not need changes unless they reference the old blue accent.

- [ ] **Step 3: Verify blue/sky/cyan remapping block still uses primary variables**

Lines 57-80 already map `--color-blue-*`, `--color-sky-*`, and `--color-cyan-*` to the primary scale; they will now resolve to purple.

- [ ] **Step 4: Commit**

```bash
git add eduadmin-dashboard/src/index.css
git commit -m "feat(theme): update tailwind tokens to purple scale"
```

---

### Task 4: Redesign the Sidebar

**Files:**
- Modify: `eduadmin-dashboard/src/components/Sidebar.tsx:161-169`, `:303-311`, `:394-492`, and related hover/submenu styles

**Interfaces:**
- Consumes: new gradient and active colors.
- Produces: sidebar renders with gradient background and white active item.

- [ ] **Step 1: Add local purple gradient constants**

After the existing `THEME` memo, add a small static object for the sidebar gradient and active colors:

```typescript
const SIDEBAR = {
  gradient: 'linear-gradient(180deg, #4F46E5 0%, #4338CA 50%, #3730A3 100%)',
  activeBg: '#FFFFFF',
  activeText: '#4338CA',
  hoverBg: 'rgba(255, 255, 255, 0.12)',
  subtleText: 'rgba(255, 255, 255, 0.7)',
  divider: 'rgba(255, 255, 255, 0.15)',
};
```

- [ ] **Step 2: Apply gradient to the aside element**

Replace the inline `style` on the `<aside>` (currently line 310):

```tsx
style={{
  background: SIDEBAR.gradient,
  color: SIDEBAR.activeBg,
}}
```

- [ ] **Step 3: Update parent button active/hover styles**

In the parent `<button>` (around line 394-426), change the active styles:

```tsx
className={cn(
  'flex w-full items-center overflow-hidden rounded-full py-3 text-sm transition-[background-color,color,max-width]',
  timing,
  active
    ? 'font-semibold'
    : 'hover:bg-white/10',
  collapsed
    ? 'mx-auto h-11 w-[50px] justify-center gap-0 px-0'
    : 'gap-3 px-4'
)}
style={{
  backgroundColor: active ? SIDEBAR.activeBg : 'transparent',
  color: active ? SIDEBAR.activeText : THEME.textOnDark,
}}
```

Also update the chevron color:

```tsx
style={{ color: active ? SIDEBAR.activeText : SIDEBAR.subtleText }}
```

- [ ] **Step 4: Update direct nav link active/hover styles**

In the direct `NavLink` (around line 472-492), apply the same active colors:

```tsx
className={cn(
  'flex items-center overflow-hidden rounded-full text-sm transition-[background-color,color,width]',
  timing,
  active ? 'font-semibold' : 'hover:bg-white/10',
  collapsed
    ? 'mx-auto h-[50px] w-[50px] justify-center gap-0 px-0'
    : cn('gap-3', active ? 'px-5' : 'px-4')
)}
style={{
  backgroundColor: active ? SIDEBAR.activeBg : 'transparent',
  color: active ? SIDEBAR.activeText : THEME.textOnDark,
}}
```

- [ ] **Step 5: Update submenu styles**

For child `NavLink` items (around line 446-462):

```tsx
className={cn(
  'relative block border-s-2 px-3 py-2 text-sm leading-[1.25] transition-[color,border-color,background-color] duration-200 ease-out hover:translate-x-0 motion-reduce:transition-none motion-reduce:hover:translate-x-0',
  childActiveRow
    ? 'border-transparent font-medium'
    : 'border-transparent'
)}
style={{
  borderColor: childActiveRow ? SIDEBAR.activeBg : 'transparent',
  color: childActiveRow ? SIDEBAR.activeBg : SIDEBAR.subtleText,
  backgroundColor: childActiveRow ? 'rgba(255,255,255,0.12)' : 'transparent',
  borderRadius: '0 8px 8px 0',
}}
```

- [ ] **Step 6: Update hover/subtle colors throughout**

Replace remaining references to `rgba(0,136,204,…)` or `THEME.accent` in the sidebar with `SIDEBAR.activeBg` or `SIDEBAR.subtleText`:

- Collapse toggle button border: `border: '1px solid rgba(255,255,255,0.25)'`.
- Scroll hint buttons border: `borderColor: 'rgba(255,255,255,0.25)'`.
- Brand divider: `borderColor: SIDEBAR.divider`.
- Footer divider: `borderColor: SIDEBAR.divider`.

- [ ] **Step 7: Run TypeScript check**

Run: `cd eduadmin-dashboard && npx tsc -b`
Expected: no errors.

- [ ] **Step 8: Commit**

```bash
git add eduadmin-dashboard/src/components/Sidebar.tsx
git commit -m "feat(sidebar): purple gradient background and white active item"
```

---

### Task 5: Spot-fix leftover hardcoded blue values

**Files:**
- Modify: any files under `eduadmin-dashboard/src` that still contain `rgba(0,122,255,…)`, `rgba(0,136,204,…)`, or `#0088CC`

**Interfaces:**
- Consumes: new primary/accent color tokens.
- Produces: consistent purple appearance across pages.

- [ ] **Step 1: Search for hardcoded blue values**

Run:
```bash
cd eduadmin-dashboard
grep -R "rgba(0,122,255" src/ || true
grep -R "rgba(0,136,204" src/ || true
grep -R "#0088CC" src/ || true
grep -R "#1E2942" src/ || true
```

- [ ] **Step 2: Replace each occurrence with a purple equivalent**

Use the closest mapped Tailwind class when possible, e.g.:
- `bg-blue-50` → already maps to primary-50 (purple tint).
- `text-blue-600` → already maps to primary-600 (purple).
- `rgba(0,122,255,0.35)` → `rgba(79,70,229,0.35)` (indigo-600 at 35%).
- `rgba(0,122,255,0.12)` → `rgba(79,70,229,0.12)`.
- `rgba(0,136,204,0.5)` → `rgba(79,70,229,0.5)`.

- [ ] **Step 3: Run TypeScript check**

Run: `cd eduadmin-dashboard && npx tsc -b`
Expected: no errors.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "feat(theme): replace leftover hardcoded blue values with purple"
```

---

### Task 6: Build and lint verification

**Files:**
- None (verification only).

- [ ] **Step 1: Run full build**

Run: `cd eduadmin-dashboard && npm run build`
Expected: exits with code 0.

- [ ] **Step 2: Run linter**

Run: `cd eduadmin-dashboard && npm run lint`
Expected: exits with code 0 (or only pre-existing warnings).

- [ ] **Step 3: Optional manual visual check**

Run: `cd eduadmin-dashboard && npm run dev`
Open the local URL and confirm:
- Sidebar has a purple gradient.
- Active nav item is a white rounded pill with dark purple text/icon.
- Buttons, links, and focus rings across the app are purple.

- [ ] **Step 4: Commit if any final fixes were needed**

```bash
git add -A
git commit -m "fix(theme): build/lint fixes after purple theme rollout"
```

---

## Self-Review

1. **Spec coverage:**
   - Purple gradient sidebar → Task 4.
   - White rounded active item with dark text/icon → Task 4.
   - Ignore backend theme → Task 2.
   - Change main color across the whole frontend → Tasks 1, 2, 3, 5.
   - Build/lint passes → Task 6.

2. **Placeholder scan:** No TBD, TODO, or vague steps.

3. **Type consistency:** `DEFAULT_THEME` shape stays the same; `ThemeContext` still returns `SchoolTheme`; `Sidebar` uses local `SIDEBAR` object without changing component props.
