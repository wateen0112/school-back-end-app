# Purple Theme + Sidebar Redesign — eduadmin-dashboard

## Scope
Apply a purple/indigo theme to the `eduadmin-dashboard` React app only. Update the sidebar to match the provided reference image and change the main brand color across the whole frontend.

## Design decisions
- **Sidebar background:** purple/indigo gradient (no curved tab effect).
- **Active nav item:** simple white rounded rectangle inside the sidebar, with dark purple/indigo text and icon.
- **Theme behavior:** hard-coded purple default; ignore school-specific backend theme returned by `ThemeContext`.
- **Global colors:** update the primary color scale so all existing `bg-blue-*` / `text-blue-*` / `border-blue-*` utilities across the app render in purple.

## Files to change
1. `eduadmin-dashboard/src/lib/theme.ts` — update `DEFAULT_THEME` to purple values.
2. `eduadmin-dashboard/src/context/ThemeContext.tsx` — ignore backend `theme` payload and always use `DEFAULT_THEME`; keep logo and school name.
3. `eduadmin-dashboard/src/index.css` — update `@theme` primary/accent tokens to the purple scale.
4. `eduadmin-dashboard/src/components/Sidebar.tsx` — apply gradient background, white active item pill with dark text/icon, and adjust hover/submenu/scroll colors to match.
5. Spot-fix any hardcoded blue hex/rgba values found during verification (e.g. `rgba(0,122,255,…)`).

## Color palette
- Sidebar gradient: `linear-gradient(180deg, #4F46E5 0%, #4338CA 50%, #3730A3 100%)` (indigo-600 → indigo-700 → indigo-800).
- Active item background: `#FFFFFF`.
- Active item text/icon: `#4338CA` (indigo-700).
- Primary accent: `#4F46E5` (indigo-600).
- Primary dark: `#312E81` (indigo-900) for headers/buttons.

## Verification
- Run `npm run build` inside `eduadmin-dashboard` with no TypeScript or build errors.
- Run `npm run lint` with no new lint errors.
- Visually confirm the sidebar shows the gradient and the active item is a white rounded pill with dark text.
