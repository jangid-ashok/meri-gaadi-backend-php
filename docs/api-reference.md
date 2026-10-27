# Meri Gaadi API Reference

This documents the current Phase 1 API surface. The project has no versioned API prefix and uses Laravel routes/controllers, Eloquent models, request validation, and a shared `Controller::paginateRequest` helper. Existing URLs and response shapes are retained for current Admin consumers.

## Authentication and access

- Public routes are unauthenticated `GET` endpoints listed below.
- Admin APIs use `/api/admin/*`, Laravel's `web` session middleware, the `admin` guard middleware, and per-route `permission:*` checks. Browser mutations use the existing session/CSRF flow.
- Admin sign-in/out use the existing web session routes (`POST /login`, `POST /logout`); there is no separate API token login endpoint.
- `GET /api/user` uses Sanctum authentication. Admin session context is available through `GET /api/admin/me`.
- No API versioning is configured.
- The API middleware applies a shared 60 requests/minute throttle, keyed by authenticated user when available and otherwise by IP.
- CORS allows methods and headers broadly, but origins come from the comma-separated `CORS_ALLOWED_ORIGINS` environment variable (local defaults are ports 3000 and 5173). Credentials are disabled. Set the deployed public frontend origin explicitly; Admin session cookies remain same-origin/CSRF protected.

## Current response conventions

- Most JSON endpoints wrap their result in `data`.
- Paginated list responses use Laravel's paginator JSON under `data`, including `data.data`, `data.current_page`, `data.per_page`, `data.total`, and `data.last_page`.
- `per_page` is accepted only from `10`, `15`, `20`, `50`, or `100`; it defaults to `15`. Unsupported values fall back to `15`. `page` uses Laravel pagination.
- Successful creation returns HTTP `201`; deletion usually returns `204`; validation uses Laravel's JSON `422` response; authorization uses `403`; unauthenticated API access uses `401`.
- Validation responses use Laravel's `{message, errors}` format when the client requests JSON. Unexpected errors use Laravel's exception handler and production debug configuration.
- These conventions are not wrapped in a common `success`/`message` envelope. Do not assume every response contains those fields.

## Public endpoints

| Method | Endpoint | Notes |
|---|---|---|
| GET | `/api/articles` | Published articles; supports `search`, `page`, and `per_page`; returns article resources with category, tags, author, image URL, and SEO metadata. |
| GET | `/api/articles/{slug}` | Published article detail by slug; inactive related categories/tags are omitted. |
| GET | `/api/brands` | Active brands; supports `search`, `sort=name\|founded_year`, `direction`, `page`, and `per_page`. |
| GET | `/api/brands/{slug}` | Active brand details and SEO metadata. |
| GET | `/api/cars` | Active cars whose brand is active; supports `search`, `brand` (brand slug), `body_type`, `fuel_type`, `transmission_type`, `sort=name\|launch_year`, `direction`, `page`, and `per_page`. |
| GET | `/api/cars/{brandSlug}/{carSlug}` | Active car details scoped to an active brand; includes active variants and SEO metadata. This is the canonical detail lookup because car slugs are unique only within a brand. |
| GET | `/api/cars/{brandSlug}/{carSlug}/variants/{variantSlug}` | Active variant with its public car, active specifications/features, and car SEO metadata. |
| GET | `/api/categories` | Active categories; supports `search`, `category_type`, `parent_id` (integer or `root`), `page`, and `per_page`. |
| GET | `/api/categories/{slug}` | Active category and active children. |
| GET | `/api/tags` | Active tags; supports `search`, `page`, and `per_page`. |
| GET | `/api/tags/{slug}` | Active tag details. |
| GET | `/api/faqs` | Active FAQs; supports `category` (slug), `category_type`, `page`, and `per_page`; omits entries attached to inactive categories. |
| GET | `/api/homepage` | Enabled configured homepage sections and SEO metadata. |
| GET | `/api/featured-content` | Active featured items; optional `placement`; returns at most 100 ordered results. |
| GET | `/api/seo/homepage` | Public homepage SEO metadata with fallback. |
| GET | `/api/seo/{type}/{slug}` | Public unique car, brand, category, or article SEO metadata with fallback. A colliding car slug returns 404 rather than selecting an arbitrary brand. |
| GET | `/api/seo/car/{brandSlug}/{carSlug}` | Brand-scoped car SEO lookup. |

All listed endpoints are unauthenticated read APIs. Collections use the existing nested paginator format under `data` (`data.data`, `data.current_page`, etc.). Public serializers expose selected fields and usable `*_url` image URLs; there is no standalone public media API because frontend images are provided on the relevant content resources. Public content excludes inactive/draft records according to each resource's existing status rules. No public settings endpoint is exposed.

## Public API readiness

**Provisional — not frozen.** Focused contract tests have been added, but the PHP/Laravel test suite could not be run in the current environment. Treat these contracts as implementation-ready for review, not verified stable contracts, until the suite passes on PHP 8.4.

| Resource | Endpoint | Status | Pagination / filters | Images | Contract test |
|---|---|---|---|---|---|
| Homepage | `GET /api/homepage` | READY (existing) | Configured sections | Section image URLs | Existing coverage; runtime unverified |
| Brands | `GET /api/brands`, `/api/brands/{slug}` | READY (added) | Paged; search/sort | `logo_url` | Added; runtime unverified |
| Cars | `GET /api/cars` | READY (added) | Paged; brand/body/fuel/transmission/search/sort | `image_url`, brand `logo_url` | Added; runtime unverified |
| Car detail | `GET /api/cars/{brandSlug}/{carSlug}` | READY (added) | Scoped detail | `image_url`, brand `logo_url` | Added; runtime unverified |
| Variant detail | `GET /api/cars/{brandSlug}/{carSlug}/variants/{variantSlug}` | READY (added) | Scoped detail | Car image URL | Added; runtime unverified |
| Blogs | `GET /api/articles` | READY (existing; filters tightened) | Paged; search/category/tag | Featured image URL | Added; runtime unverified |
| Blog detail | `GET /api/articles/{slug}` | READY (existing) | Slug detail | Featured image URL | Existing coverage; runtime unverified |
| Categories | `GET /api/categories`, `/api/categories/{slug}` | READY (added) | Paged; type/parent/search | Not applicable | Added; runtime unverified |
| Tags | `GET /api/tags`, `/api/tags/{slug}` | READY (added) | Paged; search | Not applicable | Added; runtime unverified |
| FAQ | `GET /api/faqs` | READY (added) | Paged; category/type | Not applicable | Added; runtime unverified |
| Banners / featured | `GET /api/featured-content` | READY (existing) | Placement; capped at 100 | Image URLs where configured | Existing coverage; runtime unverified |
| SEO | `/api/seo/homepage`, `/api/seo/{type}/{slug}`, `/api/seo/car/{brandSlug}/{carSlug}` | READY (existing + scoped car lookup) | Slug | SEO image URLs | Added scoped collision test; runtime unverified |
| Standalone media | — | NOT REQUIRED | — | Embedded URLs above | — |

## Admin endpoints

All endpoints in this table are under `/api/admin` and require an active admin session plus the listed permission. `{id}` parameters use Laravel route model binding.

| Resource | Methods and paths | Permission family |
|---|---|---|
| Audit logs | `GET /audit-logs`, `GET /audit-logs/{id}` | `audit_logs.view` |
| Dashboard | `GET /dashboard` | `dashboard.view` |
| Brands | `GET, POST /brands`; `GET, PUT, PATCH, DELETE /brands/{id}` | `brands.*` |
| Cars / models | `GET, POST /cars`; `GET, PUT, PATCH, DELETE /cars/{id}` | `cars.*` |
| Variants | `GET, POST /variants`; `GET, PUT, PATCH, DELETE /variants/{id}`; `GET /variants/models` | `variants.*` |
| Variant specifications | `GET, POST /variants/{id}/specifications`; `PUT, DELETE /variants/{id}/specifications/{specification}` | `specifications.*` |
| Variant features | `GET, POST /variants/{id}/features`; `PUT, DELETE /variants/{id}/features/{feature}` | `features.*` |
| Specification categories / definitions | Collection CRUD at `/specification-categories` and `/specification-definitions`; `GET /specification-definitions/category` | `specification_categories.*`, `specification_definitions.*` |
| Feature categories / features | Collection CRUD at `/feature-categories` and `/features`; `GET /features/category` | `feature_categories.*`, `features.*` |
| Categories | Collection CRUD at `/categories`; `GET /categories/active` | `categories.*` |
| Tags | Collection CRUD at `/tags`; `GET /tags/active` | `tags.*` |
| FAQs | Collection CRUD at `/faqs`; `GET /faqs/active` | `faqs.*` |
| Settings | `GET /settings`, `GET /settings/{group}`, `PUT, PATCH /settings` | `settings.*` |
| Admin users | `GET, POST /users`; `GET, PUT, PATCH, DELETE /users/{id}`; `PATCH /users/{id}/status` | `users.*` |
| Roles / permissions | CRUD `/roles`; `GET /permissions`; `GET, PUT /admins/{id}/roles`; `GET /me` | `roles.*` |
| Articles | Collection CRUD at `/articles`; `PATCH /articles/{id}/status` | `articles.*` |
| Media | `GET, POST /media`; `GET, PUT, PATCH, DELETE /media/{id}`; `POST /media/{id}/replace` | `media.*` |
| Homepage configuration | `GET, PUT /homepage`; `GET /homepage/options/{type}` | `homepage.*` |
| Featured content | Collection CRUD at `/featured-content` | `featured_content.*` |
| SEO metadata | Collection CRUD at `/seo`; `GET /seo/entities/{type}` | `seo.*` |

Collection CRUD means `GET` and `POST` on the collection, plus `GET`, `PUT`/`PATCH`, and `DELETE` on an individual resource, subject to the route table in `routes/api.php`.

## Query parameters

- Search uses `search` where implemented.
- Pagination uses `page` and `per_page` with the shared values described above.
- Many ordered Admin lists currently use `sort` and `direction`; sort columns are allowlisted in their controllers. This parameter pair is retained for compatibility. Not every list exposes sorting.
- Filters are resource-specific and use fields such as `status`, `brand_id`, `category_id`, `placement`, `format`, and `date_from`/`date_to`; consult the respective controller validation rules.

## Compatibility notes and known gaps

- `/api/admin/dashboard` returns `statistics` and `recent_activity` at the top level. This is an existing tested contract and has not been wrapped in `data`.
- `/api/admin/variants/{id}/features` and `/specifications` accept a JSON list and return the updated association list under `data`. Empty batches now return Laravel's standard validation error shape; batches are limited to 100 rows and referenced records are loaded in one batched query.
- For those two bulk endpoints, empty or oversized invalid batches now return the same Laravel validation shape as other invalid payloads; clients should read the field errors rather than rely on the previous message-only body.
- `/api/featured-content` keeps its existing array response and caps ordered active results at 100. Homepage hero/promotion items are capped at the configured section limit, with a maximum of 50.
- Admin CRUD endpoints still have mixed serialization: some return explicit resources while others serialize Eloquent models, so fields can differ by resource. Avoid relying on unlisted model columns; normalize these resources in a planned frontend contract change.
- No route, method, version prefix, existing response envelope, or pagination default was renamed in this cleanup.
