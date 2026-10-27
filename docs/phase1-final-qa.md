# Phase 1 Admin QA and completion checklist

This is a static code/configuration audit plus the checks that could run in this workspace. It is not a substitute for executing the Laravel suite or browser workflows.

## Module inventory

| Module | Code present | QA status |
|---|---|---|
| Authentication and profile/password recovery | Routes, controllers, views, tests | Not verified in this environment |
| Dashboard | Admin page and protected data endpoint | Not verified in this environment |
| Brands; cars/models; variants | CRUD controllers, models, tables, Admin pages, protected JSON routes | Not verified in this environment |
| Specifications and features | Category/definition CRUD and variant associations | Not verified in this environment |
| Categories, tags, FAQs | CRUD controllers, tables, Admin pages, protected JSON routes | Not verified in this environment |
| Settings | Grouped settings UI and protected read/update endpoints | Not verified in this environment |
| Admin users; roles and permissions | CRUD/assignment routes and permission middleware | Not verified in this environment |
| Blogs/articles and blog categories | Legacy UI paths and newer article CRUD/public API | Not verified in this environment; legacy write permission gates were corrected in this QA pass |
| Blog gallery | Legacy upload/read/delete paths | Not verified in this environment |
| Media Library | Upload, search/filter/pagination, metadata, replace and guarded delete | Not verified in this environment |
| Homepage management | Section configuration and public homepage endpoint | Not verified in this environment |
| Featured content/banners | CRUD and active public endpoint | Not verified in this environment |
| SEO metadata | Admin CRUD and public metadata endpoints | Not verified in this environment |
| Audit logs | Read-only Admin list/detail and protected JSON endpoints | Not verified in this environment |

No separate public CRUD/API modules were found for FAQ, catalog brands/cars/variants, or standalone public media metadata. Those are frontend API gaps, not missing Admin CRUD pages.

## Verification available here

- `npm run build`: passed with Vite 5.4.21.
- `git diff --check`: passed apart from Git's existing CRLF normalization notices.
- PHP feature tests, Laravel route inspection, migrations, seeders, and browser/network checks: **Not verified**. PHP and `vendor/bin/phpunit` are absent; the Docker daemon socket is inaccessible from this workspace.

## Public React API checklist

| Data | Current public contract | Readiness |
|---|---|---|
| Articles/blogs | `GET /api/articles`, `GET /api/articles/{slug}`; published-only, paginated list/search, category/tags/author/image/SEO data | Available; runtime contract not verified |
| Homepage | `GET /api/homepage`; enabled configured sections, selected cars/brands/categories, article cards, banners, SEO | Available; runtime contract not verified |
| Banners/featured content | `GET /api/featured-content`; active items, optional placement, bounded ordered list | Available; runtime contract not verified |
| SEO | `GET /api/seo/homepage`, `GET /api/seo/{type}/{slug}` for car, brand, category, article | Available; runtime contract not verified |
| Brands, cars/models, variants | Admin-only endpoints under `/api/admin/*`; no public catalog endpoints | Backend work required before catalog pages |
| Categories | Admin-only list/active endpoints; homepage may return selected car categories | Backend work required for a category index/filter page |
| Tags | Admin-only endpoints; tags are embedded in article responses | Standalone endpoint missing; embedded article tags available |
| FAQ | Admin-only endpoints; no public FAQ endpoint | Backend work required before public FAQ consumption |
| Media/images | Public file URLs are embedded in public content responses; Media API is Admin-protected | No standalone public media endpoint; URLs available for returned content |

Admin APIs use the web session and permission middleware; they are not public React catalog APIs. Public API URLs are not versioned. CORS origins must be set for the deployed public frontend. The configured CORS middleware has credentials disabled, appropriate for the current public GET surface.

## Production readiness checklist

- [ ] Set `APP_ENV=production`, `APP_DEBUG=false`, a unique `APP_KEY`, and the production `APP_URL`.
- [ ] Supply database, mail, and any external service credentials through deployment secrets; do not reuse the local Compose values.
- [ ] Set `CORS_ALLOWED_ORIGINS` to the exact deployed frontend origins; keep credentials disabled unless an authenticated cross-origin use case is designed.
- [ ] Use persistent, backed-up storage for `media`, `brand_media`, and `car_media` assets; verify web serving and URL generation behind the production host/CDN.
- [ ] Configure a real mail transport for password reset and verify delivery.
- [ ] Choose queue/cache/session drivers for the deployment. The example uses synchronous jobs and file storage; no scheduled task was identified as required by this Admin phase.
- [ ] Build assets with `npm ci && npm run build`; configure web server/PHP-FPM, TLS, writable `storage` and `bootstrap/cache`, and log retention/alerting.
- [ ] Run migrations and seed required permissions/roles/lookup data using a controlled release process; provision the initial Super Admin securely.
- [ ] Confirm upload limits at both PHP/web-server and application levels. SVG upload is rejected; AVIF acceptance depends on PHP's image detection support and was not runtime-tested here.

## Issues and next checks

- **High — Public API coverage:** catalog, FAQ, and standalone media metadata endpoints required by portions of the planned public frontend are absent. Define and implement those read contracts before building those frontend screens.
- **High — Runtime QA unavailable:** no PHP/vendor runtime and no Docker access meant the Laravel feature suite, DB migration/seed cycle, auth/RBAC API requests, media processing, and workflows A–G could not be executed.
- **High — Existing seeded account cleanup:** the checked-in seeder no longer creates a predictable admin account, but databases seeded by the old code may still contain that account. Verify and reset or remove it before deployment.
- **Medium — Browser QA unavailable:** responsive viewport checks, console/network checks, visual pagination/modal review, and duplicate-click behavior remain unverified.
- **Low — Legacy Blog paths:** these remain separate from the ArticleController implementation. Their route permissions and publish checks now match the seeded permission split, but behavior requires a runtime regression run.
- **Deployment configuration:** `.env.example` now defaults debug off and uses file sessions/cache plus a synchronous queue. Compose requires a DB password from the environment but remains local-development-only.
