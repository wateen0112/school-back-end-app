---
name: School Management Design System
colors:
  surface: '#f3fcf1'
  surface-dim: '#d4dcd2'
  surface-bright: '#f3fcf1'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eef6eb'
  surface-container: '#e8f0e5'
  surface-container-high: '#e2ebe0'
  surface-container-highest: '#dce5da'
  on-surface: '#161d17'
  on-surface-variant: '#3d4a3e'
  inverse-surface: '#2b322b'
  inverse-on-surface: '#ebf3e8'
  outline: '#6c7b6d'
  outline-variant: '#bbcbbb'
  surface-tint: '#006d37'
  primary: '#006d37'
  on-primary: '#ffffff'
  primary-container: '#2ecc71'
  on-primary-container: '#005027'
  inverse-primary: '#4ae183'
  secondary: '#006397'
  on-secondary: '#ffffff'
  secondary-container: '#5cb8fd'
  on-secondary-container: '#00476e'
  tertiary: '#98472a'
  on-tertiary: '#ffffff'
  tertiary-container: '#ff9875'
  on-tertiary-container: '#772e14'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#6bfe9c'
  primary-fixed-dim: '#4ae183'
  on-primary-fixed: '#00210c'
  on-primary-fixed-variant: '#005228'
  secondary-fixed: '#cce5ff'
  secondary-fixed-dim: '#92ccff'
  on-secondary-fixed: '#001d31'
  on-secondary-fixed-variant: '#004b73'
  tertiary-fixed: '#ffdbd0'
  tertiary-fixed-dim: '#ffb59d'
  on-tertiary-fixed: '#390c00'
  on-tertiary-fixed-variant: '#793015'
  background: '#f3fcf1'
  on-background: '#161d17'
  surface-variant: '#dce5da'
typography:
  display-lg:
    fontFamily: Cairo
    fontSize: 30px
    fontWeight: '700'
    lineHeight: 38px
  headline-md:
    fontFamily: Cairo
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
  title-lg:
    fontFamily: Cairo
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  title-md:
    fontFamily: Cairo
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
  body-lg:
    fontFamily: Cairo
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Cairo
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-lg:
    fontFamily: Cairo
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
  label-sm:
    fontFamily: Cairo
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  base: 8px
  container-margin: 16px
  gutter: 12px
  stack-sm: 8px
  stack-md: 16px
  stack-lg: 24px
---

## Brand & Style
The brand personality is authoritative yet approachable, striking a balance between institutional reliability and modern efficiency. This design system focuses on a **Corporate / Modern** aesthetic tailored for educational environments. The UI is designed to feel organized and trustworthy, reducing the cognitive load for administrators, teachers, and parents who interact with complex data daily.

The style leverages a clean, card-based architecture to compartmentalize information into digestible units. High legibility and a clear visual hierarchy are prioritized to ensure the system remains accessible under high-stress school environments. The interface adopts a "Safe & Productive" atmosphere, emphasizing clarity over decoration.

## Colors
This design system utilizes a palette rooted in "Trust and Vitality." 
- **Emerald Green (#2ECC71)**: Serves as the primary action color, symbolizing growth, success, and positive confirmation (e.g., "Present," "Approved").
- **Trust Blue (#3498DB)**: Acts as the secondary brand color for navigational elements, information headers, and administrative functions.
- **Surface & Background**: A light gray (#F8F9FA) is used for the main background to reduce glare, while pure white (#FFFFFF) is reserved for cards to create a distinct layering effect.
- **Typography Colors**: Deep Slate (#2C3E50) is used for primary text to ensure high contrast, while Medium Gray (#7F8C8D) is used for secondary metadata.

## Typography
Cairo is selected for its exceptional legibility in both Arabic and English scripts. It features a wide x-height and clear letterforms that maintain clarity even at smaller sizes on mobile displays.

For the primary RTL (Arabic) layout, font-weight is used strategically to differentiate between headers and content, as Arabic script often requires slightly heavier weights to appear visually balanced with Latin counterparts. Leading (line-height) is set generously to prevent crowded text blocks, particularly in multi-step forms and student reports.

## Layout & Spacing
The layout follows a fluid grid system optimized for mobile viewports. It utilizes a **16px outer margin** (Safe Area) and an **8px base unit** for all spacing and padding.

Elements are organized in a single-column stack on mobile, shifting to a multi-column card layout on tablets. In RTL mode, the horizontal axis is completely mirrored: sidebars appear on the right, and the "Next" buttons in multi-step forms are placed on the bottom-left to follow the natural reading flow. Padding within cards is strictly maintained at 16px to ensure "thumb-friendly" interaction zones.

## Elevation & Depth
Hierarchy is established through **Tonal Layers** and **Ambient Shadows**. The background sits at the lowest elevation. Cards sit on top of the background with a subtle, diffused shadow (0px 4px 12px, 5% opacity, #000000) to indicate interactivity.

Floating Action Buttons (FABs) or sticky footers used for multi-step forms utilize a higher elevation shadow (0px 8px 24px, 10% opacity) to signify they are the primary interaction point. Avoid using heavy borders; use subtle 1px light-gray outlines only when cards need to be placed against white surfaces.

## Shapes
The design system employs a **Soft (0.5rem/8px)** roundedness for all primary containers, including cards, input fields, and buttons. This 8px radius provides a modern, approachable feel while maintaining the professional structure required for an educational tool.

- **Standard Elements**: 8px (Cards, Buttons, Inputs).
- **Status Badges**: Fully rounded (Pill-shaped) to distinguish them from interactive buttons.
- **Selection Controls**: 4px (Checkboxes) to maintain a classic, recognizable form.

## Components
- **Buttons**: Minimum height of 48px for touch accessibility. Primary buttons use Emerald Green with white text; secondary buttons use a light blue tint or outline.
- **Status Badges**: Used for attendance and status.
    - *Present/Active*: Emerald Green background (15% opacity) with Emerald Green text.
    - *Absent/Inactive*: Red tint background with Red text.
- **Cards**: The primary container for student profiles, class schedules, and grades. They must include a white fill, 8px radius, and a subtle shadow.
- **Multi-step Forms**: Features a top-aligned progress indicator showing the number of steps. The navigation (Previous/Next) is fixed at the bottom for easy thumb access.
- **Input Fields**: Labeled clearly above the field. In RTL, text alignment and icons (like search or calendar) are mirrored to the right side of the container.
- **Lists**: High-contrast rows with 16px vertical padding, separated by subtle dividers, featuring chevron icons (mirrored in RTL) to indicate drill-down actions.