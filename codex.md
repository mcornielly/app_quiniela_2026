# CODEX Development Rules - Quiniela 2026

## 1) Stack and scope
- Backend: Laravel 12.
- Frontend: Inertia.js + Vue 3 (JavaScript, not TypeScript).
- UI: Tailwind CSS + Flowbite.
- Goal: move fast, keep quality high, and keep code consistent across the project.

## 2) Project structure is mandatory
- Respect the current folder structure and architecture.
- Do not move or rename files/folders unless explicitly requested.
- Follow existing module boundaries and naming patterns.
- New files must be created in the correct layer (Controller, Service, Component, etc.).

## 3) Safe-change policy
- Do not change layout structures, CSS styles, or visual behavior unless requested.
- Keep changes minimal and focused on the requested scope.
- Avoid side effects in unrelated files.
- If a change can impact existing behavior, confirm before applying broad refactors.

## 4) DRY and reuse first
- Do not duplicate logic.
- Reuse existing utilities, composables, services, and components.
- If logic repeats, extract reusable pieces.
- Prefer scalable component-based solutions over copy/paste blocks.

## 5) Vue 3 (JavaScript) conventions
- Use `<script setup>` style where already used.
- This project uses JavaScript, not TypeScript.
- Use the explicit form:
  - `const props = defineProps({...})`
- Do not use non-standard or abbreviated patterns that reduce readability.
- Keep naming consistent with existing files.

## 6) Inertia + page/component rules
- Keep data flow aligned with Inertia patterns.
- Use page props from server as source of truth when applicable.
- Avoid business logic overload inside page components.
- Push reusable UI behavior into components/composables when needed.

## 7) Laravel conventions
- Respect Laravel 12 conventions and existing project patterns.
- Keep controllers lean when possible.
- Move reusable/domain logic to dedicated classes/services as needed.
- Use simple PHPDoc only when it adds clarity (no verbose comments).

## 8) Style and formatting
- Preserve current code alignment/formatting style in each file.
- Keep PHPDoc simple and concise.
- Keep templates readable (clear spacing, clear attribute ordering).
- Do not introduce style churn in unrelated lines.

## 9) Frontend styling rules
- Use Tailwind/Flowbite following existing style patterns.
- Reuse existing utility class patterns before inventing new ones.
- Avoid unnecessary custom CSS when utility classes/components already solve it.

## 10) Quality gates before closing work
- Verify no duplicate code was introduced.
- Verify component reuse opportunities were considered.
- Verify the change stays within requested scope.
- Verify naming/structure consistency with nearby code.

## 11) Commit rules
- Commit messages must be in English.
- Use clear, short, action-oriented messages.
- Keep commits focused (one logical change per commit when possible).

## 12) Team principle
- Prioritize speed with maintainability.
- Prefer clear and homologated code over clever shortcuts.
- Build for scale: each new change should make future changes easier, not harder.

## 13) Data flow rules (critical)
- Server (Laravel) is the source of truth.
- Do not duplicate server state in frontend unless necessary.
- Avoid unnecessary local state when data comes from Inertia props.
- Always validate where data should live: backend vs frontend.

## 14) State management rules
- Keep local state minimal.
- Shared logic must be extracted into composables.
- Avoid mixing UI state with business logic.
- Prefer computed values over duplicated reactive data.

## 15) Error handling
- Do not silently fail.
- Always handle API/async errors.
- Show user-friendly feedback when applicable.
- Log relevant errors when debugging is needed.

## 16) Performance rules
- Avoid unnecessary re-renders in Vue.
- Use lazy loading where possible.
- Avoid heavy computations inside templates.
- Optimize loops and large datasets rendering.

## 17) Backend architecture rules
- Controllers must not contain business logic.
- Services should handle domain logic.
- Keep queries optimized and reusable.
- Avoid fat controllers and duplicated queries.

## 18) Backward compatibility
- Do not break existing features.
- Validate existing flows before modifying shared logic.
- If behavior changes, document it clearly.

## 19) Naming conventions
- Use consistent naming across backend and frontend.
- Avoid abbreviations unless already used in the project.
- Keep naming aligned with existing domain language.
