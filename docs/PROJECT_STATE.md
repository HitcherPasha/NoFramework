# Project State

**Purpose:** Handoff document for developers and AI assistants continuing this codebase.  
**This is the only document for implementation status, roadmap progress, and remaining work.**

| Document | Contains |
|----------|----------|
| [`ARCHITECTURE.md`](ARCHITECTURE.md) | Architecture, design rules, constraints, layer responsibilities |
| **This file** | What is implemented, what is pending, how to run and verify |

---

## Project Overview

### Assignment

Build a **simple, fully working blog** as a PHP Full Stack Developer test task:

- Pure PHP 8.1+, **no frameworks**
- **MySQL** database
- **Smarty** templating
- Three public pages: **home**, **category** (sort + pagination), **article** (view count + related posts)
- **PHP PDO seeder** (no SQL seed file)
- **Docker** (nginx + php-fpm + mysql)
- SCSS/CSS secondary

### Main technologies

| Layer | Technology |
|-------|------------|
| Language | PHP 8.1+ (Docker: 8.2-fpm-alpine) |
| Database | MySQL 8.0 |
| Templates | Smarty 5.x (`smarty/smarty` ^5.0) |
| HTTP | nginx → php-fpm |
| Config | Custom `App\Config` (no phpdotenv) |
| Data access | PDO in repository classes |
| Containers | Docker Compose (3 services only) |

### Architecture summary (as implemented)

```
public/index.php          → bootstrap → Router::dispatch()
src/bootstrap.php         → config, PDO, Smarty
src/Router.php            → routes, 404/405, wires controllers
Controllers               → repositories + Smarty via constructor
Repositories              → all SQL; associative arrays
templates/                → home, category, article, layout, error, partials
database/schema.sql       → DDL (Docker init on first volume)
bin/seed.php              → DatabaseSeeder (--fresh supported)
```

**Request flow:**  
`index.php` → `bootstrap.php` → `Router::dispatch()` → controller → repositories → Smarty.

---

## Current Status

| Area | Status | Notes |
|------|--------|-------|
| Docker | **Done** | nginx, php-fpm, mysql; port 8080; schema init mount |
| Database schema | **Done** | `database/schema.sql`; 3 tables |
| Config | **Done** | `App\Config`; `.env` / getenv |
| Database connection | **Done** | `App\Database::createConnection()` — no singleton |
| Bootstrap | **Done** | Autoload, handlers, PDO, Smarty |
| Repositories | **Done** | Category + Article repositories |
| Smarty | **Done** | bootstrap, layout.tpl, error.tpl, var/smarty/ |
| Home page | **Done** | HomeController, home.tpl, article-card.tpl |
| Category page | **Done** | CategoryController, category.tpl (inline sort + pagination) |
| Article page | **Done** | ArticleController, article.tpl; view_count + related |
| Router | **Done** | `src/Router.php`; 404/405 via error.tpl |
| public/index.php | **Done** | Wired to bootstrap + Router |
| Seeder | **Done** | `bin/seed.php`, `DatabaseSeeder`, sample data + SVG placeholders |
| SCSS / CSS | **Done** | `scss/main.scss`, `public/assets/css/main.css` (manual compile) |
| README | **Pending** | Setup/usage guide not written |

**In progress:** None (owner reviews and commits manually).

### Runnable today

After Docker is up and the database is seeded, the blog is available in the browser:

```bash
docker compose up -d
docker compose exec -T php php bin/seed.php --fresh
```

Open **http://localhost:8080/** — home, category, and article pages work via Router.

- Re-run `php bin/seed.php` without `--fresh` skips if data already exists
- Empty database shows home empty state until seed is run
- Styles served from `/assets/css/main.css` (static file via nginx)

---

## Implementation Roadmap

| # | Deliverable | Status |
|---|-------------|--------|
| 1 | Project scaffold | **Done** |
| 2 | Docker (nginx, php-fpm, mysql) | **Done** |
| 3 | Database schema | **Done** |
| 4 | Config, Database, bootstrap | **Done** |
| 5 | Repositories | **Done** |
| 6 | Smarty + base layout | **Done** |
| 7 | Home page | **Done** |
| 8 | Category page (sort, pagination) | **Done** |
| 9 | Article page (views, related) | **Done** |
| 10 | Router + `public/index.php` | **Done** |
| 11 | PDO seeder | **Done** |
| 12 | SCSS / `main.css` | **Done** |
| 13 | README | **Pending** |

---

## Implemented Files

### Infrastructure

| File | Role |
|------|------|
| `composer.json` | smarty/smarty; PSR-4 `App\` → `src/` |
| `composer.lock` | Locked dependencies |
| `.env.example` | DB_* and APP_DEBUG template |
| `.gitignore` | vendor, .env, tmp/, uploads, var/smarty compile/cache |
| `public/index.php` | Front controller → bootstrap → Router |
| `src/Config.php` | Load .env / getenv; DB accessors |
| `src/Database.php` | `createConnection(Config): PDO` |
| `src/bootstrap.php` | App bootstrap; returns config, pdo, smarty |
| `src/Router.php` | Route matching, 404/405, controller wiring |
| `bin/seed.php` | CLI seeder entrypoint |

### Repositories

| File | Role |
|------|------|
| `src/Repository/CategoryRepository.php` | `findWithArticles()`, `findById()` |
| `src/Repository/ArticleRepository.php` | Home, category, article, views, related |

### Controllers

| File | Role |
|------|------|
| `src/Controller/HomeController.php` | `index()` |
| `src/Controller/CategoryController.php` | `show()` — sort, pagination |
| `src/Controller/ArticleController.php` | `show()` — views, related |

### Seeder

| File | Role |
|------|------|
| `src/Seeder/DatabaseSeeder.php` | PDO seed; `--fresh` truncate + insert |
| `public/assets/uploads/.gitkeep` | Uploads directory placeholder |

### Templates

| File | Role |
|------|------|
| `templates/layout.tpl` | Base layout |
| `templates/error.tpl` | 404 / 405 |
| `templates/home.tpl` | Home |
| `templates/category.tpl` | Category (inline sort + pagination) |
| `templates/article.tpl` | Article detail + related |
| `templates/partials/article-card.tpl` | Teaser card |

### Database & Docker

| File | Role |
|------|------|
| `database/schema.sql` | categories, articles, article_categories |
| `docker-compose.yml` | nginx, php, mysql |
| `docker/php/Dockerfile` | php:8.2-fpm-alpine + pdo_mysql |
| `docker/nginx/default.conf` | public root; try_files → index.php |

### Styles

| File | Role |
|------|------|
| `scss/main.scss` | SCSS source (edit here; recompile to CSS manually) |
| `public/assets/css/main.css` | Compiled stylesheet linked from `layout.tpl` |

### Not yet present

- `README.md`

---

## Page Implementation Summary

### Home — Done

- `HomeController::index()` — 2 SQL queries when categories exist
- **Debt:** `strtotime()`/`date()` and `foreach` by reference (see below)

### Category — Done

- Invalid `sort` → `date`; invalid `order` → `desc`; `page` &lt; 1 → `1`
- Missing category → `RuntimeException` → Router 404
- `DateTimeImmutable`; index-based foreach for article dates
- 3 SQL queries per request

### Article — Done

- View count: `incrementViewCount()` + in-memory bump
- Related articles (limit 3)
- 3 SQL queries per successful request

### Router — Done

- GET `/`, `/category/{id}`, `/article/{id}`
- Query params passed to category controller; path-only matching
- 404 / 405 → `error.tpl`

### Seeder — Done

- 4 categories (Photography empty — hidden on home)
- 20 articles, 22 links, 2 multi-category articles
- Technology has 11 articles (pagination demo)

---

## Known Technical Debt

| Item | Notes |
|------|-------|
| README missing | Setup not documented in repo yet |
| **HomeController** | Still `strtotime()`/`date()` and reference foreach |
| No shared `renderNotFound()` | 404 logic in Router only |
| Bootstrap 500 handlers | Plain text/HTML; not `error.tpl` |
| `depends_on` only in Docker | MySQL may be briefly unavailable on first request |
| View count | Increments every page load; no bot filtering |
| No automated test suite | Ad hoc Docker/curl checks in reviews |
| Smarty compile output | Gitignored under `var/smarty/compile/` |

---

## Next Recommended Steps

1. **README** — Docker, composer, seed, URLs, defaults
2. **Optional polish** — align `HomeController` with `DateTimeImmutable` and index-based foreach
3. **After SCSS edits** — manually sync changes to `public/assets/css/main.css` (no build tooling in repo)

---

## Context Handoff

### Implemented

- Docker stack, schema, config, bootstrap, repositories, Smarty
- Home, category, article pages (controllers + templates)
- Router + `public/index.php`
- PDO seeder with `--fresh` and idempotent default run

### Remaining

- README

### Rules (do not break)

1. Design changes → update **ARCHITECTURE.md**
2. Status/progress → update **PROJECT_STATE.md** only
3. No frameworks, ORM, or DI container
4. SQL only in repositories; Router injects dependencies into controllers
5. ID-based URLs; Smarty escaping; plain text articles
6. No git operations by automation; reports under **tmp/**

### Quick verification

```bash
docker compose up -d
docker compose exec -T php php bin/seed.php --fresh

curl -sS -o /dev/null -w "%{http_code}\n" http://localhost:8080/
curl -sS -o /dev/null -w "%{http_code}\n" http://localhost:8080/category/1
curl -sS -o /dev/null -w "%{http_code}\n" "http://localhost:8080/category/1?sort=views&order=desc&page=1"
curl -sS -o /dev/null -w "%{http_code}\n" http://localhost:8080/article/1
```

### Review references

| Topic | File |
|-------|------|
| Router | `tmp/reviews/ROUTER_REVIEW.txt` |
| Seeder | `tmp/reviews/SEEDER_REVIEW.txt` |
| Article page | `tmp/reviews/ARTICLE_PAGE_REVIEW.txt` |

---

*Last updated: after SCSS/CSS styling.*
