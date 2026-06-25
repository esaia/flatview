# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

All commands run from the repo root unless noted.

```bash
composer dev          # Run everything: php serve + queue:listen + pail logs + vite (concurrently)
composer setup        # First-time: install, .env, key:gen, migrate, npm install, build
composer test         # Clears config then runs `php artisan test`
php artisan test --filter=ProfileTest        # Run a single test class/method
vendor/bin/pint       # PHP code style (Laravel Pint)

npm run dev           # Vite dev server for the public site (resources/js)
npm run build         # Production build of the public site
```

### Building the IREP editor (separate frontend)

The Vue editor lives in the `filament-irep` package and has its **own** build, independent of the root Vite:

```bash
cd packages/filament-irep
npm run build         # Compiles src → resources/dist/irep-admin.{js,css}
npm run dev           # Same, in --watch mode
# Then publish the compiled assets to public/vendor/filament-irep:
php artisan vendor:publish --tag=filament-irep-assets --force
```

The admin Blade page loads these published assets directly (cache-busted by file mtime), **not** through Laravel Mix/Vite. Editing editor source requires a rebuild + republish to take effect.

## Architecture

Three distinct frontends share one Laravel 13 backend:

1. **Public marketing site** — Inertia + Vue 3 SPA. Pages in `resources/js/Pages/*.vue`, entry `resources/js/app.js`. Routes in `routes/web.php` render Inertia pages (some via thin controllers: `HomeController`, `AboutController`, `ContactController`). Tailwind v4.

2. **Filament v5 admin panel** (`/admin`) — the CMS. Configured in `app/Providers/Filament/AdminPanelProvider.php`. Manages site content via Resources/Pages in `app/Filament/` backed by models like `HomepageSlide`, `MenuItem`, `HomepageSetting`, `AboutStat`, `AboutGalleryImage`.

3. **IREP editor** (`packages/filament-irep`) — the core product: an interactive real-estate visual editor. A standalone Vue 3 + Pinia + TypeScript SPA mounted inside a single Filament page (`IrepAdminPage` → `irep-admin-page.blade.php` mounts `#irep-vue-app`). It is a path-repository Composer package (`irep-plugin/filament-irep`, namespace `IrepPlugin\FilamentIrep\`), registered both as a Filament plugin (`FilamentIrepPlugin::make()`) and a Laravel service provider.

### IREP backend API pattern

The editor does **not** use REST or Inertia. It POSTs to a single endpoint `/admin/irep-ajax` (route name `irep.handle`, configurable via `config/filament-irep.php` → `ajax_path`) with an `action` field. `IrepController::handle()` dispatches via a static `$map` array (`'irep_get_projects' => 'getProjects'`, etc.) — a WordPress-style action router. When adding editor backend functionality, add an entry to `$map` and a corresponding method, both in `packages/filament-irep/src/Http/Controllers/IrepController.php`.

The IREP data model is a project hierarchy: `Project` hasMany `Block`, `Floor`, `Flat`, `Type`, `Tooltip`, `ProjectMeta`; `Reservation` is hasManyThrough `Flat`. Settings are key/value rows in the `Setting` model. The package ships its own migrations (`loadMigrationsFrom`), so its tables are created by the app's normal `php artisan migrate`.

### Cross-cutting

- **Shared Inertia props** (`app/Http/Middleware/HandleInertiaRequests.php`): `auth.user`, `flash.success`/`flash.error`, and `menuItems` (active `MenuItem` rows ordered by `sort_order`) are available on every public page. The floating nav menu reads `page.props.menuItems`.
- **Uploaded images** are served from `/storage/...` (public disk symlink); IREP uploads live under `/storage/irep/`. Slide/menu image fields store the path relative to the disk.
- **Media library picker** (`app/Filament/Support/MediaLibrary.php`) is the "Choose from library" hint action attached to Filament `FileUpload` fields. **Rule: the picker must always be scoped to the target field's own upload directory** — it reads the FileUpload's `->directory(...)` and only lists images from that folder (e.g. `services-gallery`), never the whole public disk. Any new admin upload component that uses `MediaLibrary::pickerAction()` must set a `->directory()` so its library stays folder-scoped.
- **Tests** use an in-memory SQLite database (`phpunit.xml`); no separate test DB setup needed.
</content>
</invoke>
