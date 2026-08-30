---
paths:
  - 'resources/js/actions/**,resources/js/routes/**'
---

# Routes

## Always pass --with-form to wayfinder:generate
vite.config uses the `wayfinder` plugin with `formVariants: true`, so several pages (auth, settings/profile, settings/security, two-factor components) rely on the `.form` property Wayfinder attaches to generated route/action objects.

Running `php artisan wayfinder:generate` manually WITHOUT `--with-form` regenerates every file without that property and breaks TypeScript compilation across those pages (TS2339: Property 'form' does not exist).

Always run `php artisan wayfinder:generate --with-form --no-interaction` when regenerating by hand (e.g. after adding a new resource route). The Vite dev server regenerates correctly on its own since it reads the `formVariants: true` option from vite.config.ts.
