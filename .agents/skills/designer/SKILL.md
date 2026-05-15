---
name: designer
description: 'Use when: designing UI, creating layouts, defining colors, choosing typography, building components, discussing visual design, theming, style, or branding. Always consult the design system before making any visual decisions.'
---

# Designer

## Quando usar
- Qualquer tarefa relacionada a design, UI, estilos, cores, temas, tipografia ou componentes visuais.

## Regra principal
**Sempre** consulte o arquivo [DESIGN.md](./DESIGN.md) antes de tomar qualquer decisão visual.  
Nunca invente cores, espaçamentos, fontes ou variantes que não estejam definidos nesse arquivo.

## Procedimento
1. Carregue o [DESIGN.md](./DESIGN.md) para obter o design system completo (cores, tokens, nome do projeto).
2. Use exclusivamente os valores definidos — nada fora do arquivo.
3. Se o usuário pedir algo que conflite com o design system, sinalize e proponha a alternativa mais próxima dentro do sistema.
  surface-container: '#edeeef'
  surface-container-high: '#e7e8e9'
  surface-container-highest: '#e1e3e4'
  on-surface: '#191c1d'
  on-surface-variant: '#45464e'
  inverse-surface: '#2e3132'
  inverse-on-surface: '#f0f1f2'
  outline: '#76777f'
  outline-variant: '#c6c6cf'
  surface-tint: '#515d82'
  primary: '#051436'
  on-primary: '#ffffff'
  primary-container: '#1c294b'
  on-primary-container: '#8490b9'
  inverse-primary: '#b9c5f0'
  secondary: '#006d41'
  on-secondary: '#ffffff'
  secondary-container: '#90f4b7'
  on-secondary-container: '#007144'
  tertiary: '#00143b'
  on-tertiary: '#ffffff'
  tertiary-container: '#002765'
  on-tertiary-container: '#5a8eff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dae2ff'
  primary-fixed-dim: '#b9c5f0'
  on-primary-fixed: '#0c1a3b'
  on-primary-fixed-variant: '#394669'
  secondary-fixed: '#93f7ba'
  secondary-fixed-dim: '#77da9f'
  on-secondary-fixed: '#002110'
  on-secondary-fixed-variant: '#00522f'
  tertiary-fixed: '#dae2ff'
  tertiary-fixed-dim: '#b1c5ff'
  on-tertiary-fixed: '#001946'
  on-tertiary-fixed-variant: '#00419e'
  background: '#f8f9fa'
  on-background: '#191c1d'
  surface-variant: '#e1e3e4'
  status-success: '#198754'
  status-info: '#0D6EFD'
  deep-navy: '#1C294B'
  surface-muted: '#F8F9FA'
typography:
  display-lg:
    fontFamily: IBM Plex Sans
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: IBM Plex Sans
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
  headline-md:
    fontFamily: IBM Plex Sans
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  headline-sm:
    fontFamily: IBM Plex Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-sm:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
  data-mono:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  base: 8px
  container-max: 1440px
  gutter: 24px
  margin-desktop: 40px
  margin-mobile: 16px
  stack-sm: 8px
  stack-md: 16px
  stack-lg: 32px
---

## Brand & Style

The design system is engineered for a high-stakes financial environment within the agricultural sector. It balances the rugged, mechanical nature of heavy machinery with the precision and reliability of modern fintech. The aesthetic is **Corporate / Modern** with a slight lean towards **Minimalism** to ensure that complex data remains the primary focus.

The target audience consists of operational managers and financial controllers who require a "no-nonsense" interface. The UI evokes a sense of stability, longevity, and industrial strength through structured layouts, high-contrast typography, and a purposeful use of space. High-density information is managed through clear visual hierarchies, ensuring that critical financial indicators are immediately accessible.

## Colors

The palette is anchored by **Deep Navy (#1C294B)**, providing a foundation of institutional trust and authority appropriate for a financial management tool. **Agricultural Green (#198754)** serves as a secondary brand color, subtly referencing the agricultural industry and growth. 

**Functional Blue (#0D6EFD)** is reserved for primary actions and interactive elements, ensuring high visibility. The background utilizes **Off-White (#F8F9FA)** to reduce eye strain during prolonged use while maintaining a clean, professional canvas. Text and iconography use varying shades of the primary navy to maintain a cohesive, monochromatic depth rather than using pure blacks.

## Typography

This design system employs a dual-font strategy. **IBM Plex Sans** is used for headlines and display elements; its industrial, engineered grotesque style reflects the precision of agricultural implements. **Inter** is utilized for body text, data tables, and UI labels due to its exceptional legibility and systematic feel.

For financial data, the system leverages Inter's tabular num features (`tnum`) to ensure that figures in tables and columns align vertically, facilitating easier comparison of accounts and inventories. Mobile typography scales down significantly to accommodate data-heavy tables, with `headline-lg` transitioning to `headline-md` on smaller viewports.

## Layout & Spacing

The layout follows a **Fixed Grid** model on desktop, centered within the viewport at a maximum width of 1440px to ensure line lengths remain readable. It utilizes a strict 12-column system with a 24px gutter. 

Spacing is based on an **8px linear scale**, ensuring consistent vertical rhythm across all components. For complex financial dashboards, "Density Tiers" are applied: 
- **Standard:** Default padding for general forms and settings.
- **Compact:** Reduced padding for data tables and list views to maximize visible information without sacrificing touch targets.

Mobile layouts reflow to a single column with 16px side margins, collapsing horizontal navigation into a persistent bottom bar or a side-drawer to prioritize content area.

## Elevation & Depth

To maintain a "robust" and "trustworthy" feel, the design system avoids excessive shadows, opting instead for **Tonal Layers** and **Low-Contrast Outlines**. 

Depth is primarily communicated through surface color shifts. The main background is the darkest neutral, while "containers" (cards, panels) are pure white. A subtle 1px border (`#E9ECEF`) is used to define boundaries. Where elevation is required—such as for modals or dropdown menus—a "Precision Shadow" is used: a sharp, low-blur offset that mimics the shadow of a physical tool on a workbench (e.g., `0px 2px 4px rgba(28, 41, 75, 0.08)`).

## Shapes

The shape language is **Soft (0.25rem)**. This slight rounding takes the edge off the industrial aesthetic, making the professional interface feel modern and accessible without becoming "playful." 

- **Primary Buttons & Inputs:** 4px (0.25rem) corner radius.
- **Large Containers (Cards):** 8px (0.5rem) corner radius.
- **Status Tags/Chips:** Full pill-shape to distinguish them from interactive buttons.

This geometric discipline reflects the machined edges of agricultural parts—precise, intentional, and functional.

## Components

### Buttons
Primary buttons use the Deep Navy background with white text for maximum "weight." Secondary buttons use a Functional Blue outline. Interaction states (hover/active) should involve a subtle darkening of the background color rather than a shift in size or shadow.

### Input Fields
Fields utilize a white background with a 1px border in a light grey. On focus, the border transitions to Functional Blue with a 2px "focus ring" using 20% opacity of the same blue. Labels are always persistent (never disappearing on focus) using `label-md`.

### Data Tables
Tables are the core of the financial system. They feature a "Zebra" stripe pattern on rows using `surface-muted`. Header rows are pinned and styled with a slightly darker neutral background and `label-md` typography.

### Cards
Cards are used to group financial summaries. They are flat white with a 1px border and no shadow. Headers within cards are separated by a subtle 1px horizontal rule.

### Chips & Badges
Used for status (e.g., "Paid," "Pending," "Overdue"). These utilize the `secondary` green for positive states and a derived brand red for alerts, always using a light background tint with dark text for high legibility.