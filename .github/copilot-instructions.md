You are working on the VICZ Implementos internal management system.

This system combines:
- Financial control
- Accounts payable
- Accounts receivable
- Work orders / services
- Delivery forecasting
- Commercial proposals
- Cash-flow forecasting
- Alerts
- Reports

Mandatory design rule:
Before implementing any UI, UX, layout, page, form, table, modal, dashboard, card, chart, navigation, empty state, loading state, error state, or visual component, locate and read the existing `skill.md` and `designer.md` files in the repository.

Treat `skill.md` and `designer.md` as the source of truth for:
- Visual style
- Layout structure
- Colors
- Typography
- Spacing
- Components
- Responsiveness
- UX behavior
- Design patterns
- Accessibility
- Interaction style

Do not invent a new visual language.
Do not create components that conflict with `skill.md` or `designer.md`.
If this prompt conflicts with `skill.md` or `designer.md`, prioritize `skill.md` and `designer.md` for design decisions.

Use clear English names in code.
Keep business logic separate from UI.
Keep financial calculations isolated in reusable functions.
Respect user permissions at both UI and backend/server level.