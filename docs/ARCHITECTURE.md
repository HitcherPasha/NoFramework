# Blog Application Architecture

**Canonical architecture and design reference for this project.**  
All implementation must follow the rules and structure defined here unless explicitly changed here.

**Implementation status, roadmap progress, and remaining work** live in [`PROJECT_STATE.md`](PROJECT_STATE.md)—not in this file.

Temporary reports under `tmp/` are **not** authoritative. They exist only for external review.


---

## Table of contents

1. [Project goals and assignment requirements](#1-project-goals-and-assignment-requirements)
2. [Development workflow rules](#2-development-workflow-rules)
3. [Project structure](#3-project-structure)
4. [Technology stack](#4-technology-stack)
5. [Docker architecture](#5-docker-architecture)
6. [Database schema](#6-database-schema)
7. [Bootstrap layer](#7-bootstrap-layer)
8. [Routing and request flow](#8-routing-and-request-flow)
9. [Controllers and repositories](#9-controllers-and-repositories)
10. [Smarty integration](#10-smarty-integration)
11. [Pages and behavior defaults](#11-pages-and-behavior-defaults)
12. [Data access patterns](#12-data-access-patterns)
13. [Seeder](#13-seeder)
14. [Coding rules and constraints](#14-coding-rules-and-constraints)
15. [Anti-goals](#15-anti-goals)


---

## 1. Project goals and assignment requirements

Build a **simple, fully working blog website** as a PHP Full Stack Developer test assignment.

### Core constraints

| Area | Requirement |
|------|-------------|
| Language | Pure **PHP 8.1+**, no frameworks |
| Database | **MySQL** |
| Templates | **Smarty** via Composer |
| Style | Simple, readable, structured code—**do not overengineer** |
| Delivery | Progressive, reviewable steps |

### Domain model

**Categories**

- `title`
- `description`

**Articles**

- `image`
- `title`
- `description` (short teaser)
- `text` (full body, **plain text only**)
- one or more categories (many-to-many)
- `view_count`
- `published_at` (publication date)

### Required pages

| Page | Behavior |
|------|----------|
| **Home** | Categories that have at least one article; **3 latest posts per category** by `published_at`; **“All articles”** link per category |
| **Category** | Category title and description; paginated article list; sort by **views** or **publication date** |
| **Article** | Full article data; **increment view count** on view; **3 related articles** |

### Additional requirements

- **PHP PDO seeder** (`bin/seed.php`)—no SQL-only seed files
- **Docker** (nginx + php-fpm + mysql)—implemented
- **SCSS** optional/secondary; compiled CSS may be committed to avoid Node in Docker


---

## 2. Development workflow rules

These rules apply to all contributors and automation assisting the project:

| Rule | Detail |
|------|--------|
| **No git operations by agents** | Never create commits, never push, never run git commands |
| **Manual review** | All changes are reviewed and committed manually by the project owner |
| **Architecture doc** | `docs/ARCHITECTURE.md` — structure, rules, and design decisions only |
| **Status doc** | `docs/PROJECT_STATE.md` — current implementation progress and next steps |
| **Temporary proposals** | Store in `tmp/proposals/` (e.g. `ROUTER_PROPOSAL.txt`)—not authoritative |
| **Temporary reviews** | Store in `tmp/reviews/` (e.g. `COMMIT_3_REVIEW.txt`)—not authoritative |
| **Command reports** | Store in `tmp/command-reports/`—one self-contained file per command session; do **not** append to a shared log |
| **Legacy root reports** | Do not create new `*_PROPOSAL.txt` / `*_REVIEW.txt` in project root |
| **COMMAND_RUN_LOG.txt** | **Deprecated**—do not update; use `tmp/command-reports/` instead |
| **Spec changes** | Update `docs/ARCHITECTURE.md` explicitly when decisions change |
| **Large outputs** | Continue creating separate text files when output is large, using the `tmp/` paths above |

---

## 3. Project structure

```
project-root/
├── docs/
│   └── ARCHITECTURE.md          # this file (canonical)
├── tmp/                         # gitignored — agent proposals, reviews, command reports
│   ├── proposals/
│   ├── reviews/
│   └── command-reports/
├── public/
│   ├── index.php                # front controller
│   └── assets/
│       ├── css/                 # compiled CSS (from SCSS)
│       ├── img/
│       └── uploads/             # article images (seeder)
├── src/
│   ├── bootstrap.php
│   ├── Config.php
│   ├── Database.php
│   ├── Router.php
│   ├── Controller/
│   ├── Repository/
│   └── Seeder/
├── templates/
│   ├── layout.tpl, home.tpl, category.tpl, article.tpl, error.tpl
│   └── partials/
├── database/
│   └── schema.sql               # DDL only
├── bin/
│   └── seed.php
├── scss/
├── docker/
│   ├── php/Dockerfile
│   └── nginx/default.conf
├── var/smarty/                  # compile/cache (gitignored)
├── docker-compose.yml
├── composer.json
├── .env.example
└── README.md                    # setup guide (when added)
```

- **Web root:** `public/` only  
- **Namespace:** `App\` → `src/` (PSR-4)  
- **No** `src/` files web-accessible


---

## 4. Technology stack

| Layer | Choice |
|-------|--------|
| PHP | 8.1+ (Docker image: **8.2-fpm-alpine**) |
| DB | MySQL **8.0** |
| Templates | Smarty **5.x** (`smarty/smarty` ^5.0) |
| HTTP | nginx:alpine → php-fpm |
| Config | Custom `App\Config`—**no phpdotenv** |
| Data access | Plain **PDO** in repository classes |
| CSS | SCSS → `public/assets/css/main.css` (secondary) |

### Composer dependencies

**Direct require (only):**

- `php` >= 8.1  
- `smarty/smarty` ^5.0  

**Autoload:** `App\` => `src/`

- Commit `composer.lock` to the repo  
- Ignore `vendor/` in git  
- Run `composer install` after clone


---

## 5. Docker architecture

### Services (exactly three)

| Service | Image / build | Role |
|---------|---------------|------|
| **nginx** | `nginx:alpine` | HTTP, static files, FastCGI to PHP |
| **php** | Build `docker/php/Dockerfile` | PHP-FPM, application code |
| **mysql** | `mysql:8.0` | Database |

**Not allowed:** redis, node, mailhog, phpmyadmin, adminer, supervisor, cron, or any other service.

### PHP Dockerfile (`docker/php/Dockerfile`)

```dockerfile
FROM php:8.2-fpm-alpine
RUN docker-php-ext-install pdo_mysql
```

- Install **only** `pdo_mysql`  
- No other extensions unless this document is updated

### nginx (`docker/nginx/default.conf`)

- Document root: `/var/www/html/public`  
- Front controller: `try_files $uri $uri/ /index.php?$query_string`  
- PHP: `fastcgi_pass php:9000`

### docker-compose.yml (approved layout)

- **nginx:** port `8080:80`; mount project `.:/var/www/html:ro`; mount nginx config  
- **php:** build `./docker/php`; mount `.:/var/www/html`; env `DB_*`, `APP_DEBUG`  
- **mysql:** database `blog`, user `blog`; named volume `mysql_data`; port `3306:3306` optional for host tools  

**Networking:** PHP connects to host `mysql`; nginx connects to `php:9000`.

### Schema initialization

Mount `database/schema.sql` on first MySQL volume creation:

```yaml
./database/schema.sql:/docker-entrypoint-initdb.d/01-schema.sql
```

Runs only on **first** creation of `mysql_data`. Re-init: `docker compose down -v` then `up`.

### Environment

- Docker php service sets `DB_HOST=mysql` (overrides local `.env` `127.0.0.1` via getenv precedence)  
- Copy `.env.example` → `.env` for local non-Docker use


---

## 6. Database schema

**File:** `database/schema.sql` (structure only—no seed data)

### Tables

```sql
CREATE TABLE categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    description TEXT NOT NULL
);

CREATE TABLE articles (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    image           VARCHAR(500) NOT NULL,
    title           VARCHAR(255) NOT NULL,
    description     VARCHAR(500) NOT NULL,
    text            MEDIUMTEXT NOT NULL,
    view_count      INT UNSIGNED NOT NULL DEFAULT 0,
    published_at    DATETIME NOT NULL,
    INDEX idx_articles_published_at (published_at),
    INDEX idx_articles_view_count (view_count)
);

CREATE TABLE article_categories (
    article_id   INT UNSIGNED NOT NULL,
    category_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (article_id, category_id),
    CONSTRAINT fk_ac_article
        FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    CONSTRAINT fk_ac_category
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_ac_category_article (category_id, article_id)
);
```

### Field semantics

| Field | Meaning |
|-------|---------|
| `articles.image` | Relative path under `public/assets/`, e.g. `uploads/article-1.jpg` |
| `articles.description` | Short teaser (plain text) |
| `articles.text` | Full body (plain text, escaped in Smarty) |
| `articles.published_at` | Sorting and display |
| `article_categories` | Many-to-many links |

### Index rationale

| Index | Purpose |
|-------|---------|
| `idx_articles_published_at` | Home “latest”, category `sort=date`, related tie-break |
| `idx_articles_view_count` | Category `sort=views`, related articles primary sort |
| `idx_ac_category_article` | Category-scoped lists and joins (`category_id` first) |
| PK `(article_id, category_id)` | Uniqueness; article-centric lookups |

### Foreign keys

| FK | ON DELETE CASCADE because |
|----|---------------------------|
| `fk_ac_article` | Junction rows are meaningless without the article |
| `fk_ac_category` | Unlink articles when category removed; articles remain |

### Omitted by design

- Slugs  
- `created_at` / `updated_at` on categories/articles  
- SQL seed file  
- FULLTEXT indexes  


---

## 7. Bootstrap layer

**Files:** `src/Config.php`, `src/Database.php`, `src/bootstrap.php`

### `App\Config`

- Loads `{PROJECT_ROOT}/.env` if readable (line-based `KEY=VALUE`, `#` comments, quoted values)  
- **Resolution order:** non-empty `getenv($key)` → `.env` file → default / exception  
- **Required for DB** (via `require()`): `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`  
- **Optional:** `DB_PORT` (default **3306**), `APP_DEBUG` (default **false**)  
- `isDebug()`: true for `1`, `true`, `yes` (case-insensitive)

### `App\Database`

- **`Database::createConnection(Config $config): PDO`**  
- Creates a **new** PDO instance per call—**no singleton**, no static PDO storage  
- Bootstrap calls it **once**; the instance lives in the bootstrap return array  
- DSN: `mysql:host=…;port=…;dbname=…;charset=utf8mb4`  
- PDO options:
  - `ATTR_ERRMODE` => `EXCEPTION`
  - `ATTR_DEFAULT_FETCH_MODE` => `ASSOC`
  - `ATTR_EMULATE_PREPARES` => `false`
- Wraps `PDOException` in `RuntimeException` with chained previous exception

### `src/bootstrap.php`

**Initialization order:**

1. `PROJECT_ROOT` = `dirname(__DIR__)`  
2. Require `vendor/autoload.php` (fail with composer install hint)  
3. `Config::load(PROJECT_ROOT)`  
4. Minimal error handlers (`set_exception_handler`, `register_shutdown_function` for fatals)  
5. `$pdo = Database::createConnection($config)`  
6. Initialize Smarty (template/compile/cache dirs, `setEscapeHtml(true)`, caching off)  
7. Return array:

```php
return [
    'config' => $config,
    'pdo'    => $pdo,
    'smarty' => $smarty,
];
```

**Error handling (minimal):**

- Uncaught exceptions → HTTP 500; debug shows escaped trace, production shows generic message  
- Fatals → shutdown handler, same debug/production split  
- No logging framework, no Whoops, no separate error subsystem  
- **404** handled in Router/controllers, not bootstrap


---

## 8. Routing and request flow

### Routes (ID-based only—no slugs)

| Method | Path | Handler |
|--------|------|---------|
| GET | `/` | `HomeController::index` |
| GET | `/category/{id}` | `CategoryController::show` |
| GET | `/article/{id}` | `ArticleController::show` |

### Category query parameters

| Param | Values | Default |
|-------|--------|---------|
| `sort` | `date`, `views` | `date` |
| `order` | `asc`, `desc` | `desc` |
| `page` | integer | `1` |

- Router passes raw query values to `CategoryController::show()`; path `{id}` must be a positive integer  
- Invalid or missing category/article resource → **404** (`error.tpl`)  
- Invalid query values are **normalized in the controller**, not rejected:
  - invalid `sort` → `date`
  - invalid `order` → `desc`
  - `page` &lt; 1 or non-numeric → `1`

### Request flow

```
public/index.php
  → require src/bootstrap.php  →  ['config', 'pdo', 'smarty']
  → new Router($pdo, $smarty)->dispatch(REQUEST_URI, REQUEST_METHOD)
  → Controller action
  → Repository (PDO)
  → Smarty display
```

### `public/index.php`

```php
$app = require dirname(__DIR__) . '/src/bootstrap.php';

$router = new App\Router(
    $app['pdo'],
    $app['smarty'],
);

$router->dispatch(
    $_SERVER['REQUEST_URI'] ?? '/',
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
);
```

### `App\Router`

- **Constructor:** `PDO`, `Smarty\Smarty` only—no DI container  
- **No** route collections, middleware pipeline, or framework-style abstractions  
- Parse path with `parse_url()`; query string does not affect route matching  
- Match routes with explicit checks and simple regex for numeric IDs  
- Instantiate `CategoryRepository` / `ArticleRepository` with `$pdo`; wire controllers with repositories + Smarty  
- **404:** unknown path, invalid ID, or controller `RuntimeException` (missing resource) → `error.tpl`  
- **405:** non-GET on a known route pattern → `error.tpl` and optional `Allow: GET`


---

## 9. Controllers and repositories

### Repositories

- Classes in `App\Repository\`  
- Receive **`PDO` only** in constructor  
- **All SQL** lives in repositories—no queries in controllers  
- `CategoryRepository`, `ArticleRepository`

### Controllers

- Classes in `App\Controller\`  
- **Thin**—orchestrate repositories and Smarty  
- **Do not** instantiate repositories internally  
- **Receive repositories via constructor** (injected by Router)

**Example wiring (conceptual):**

```text
Router
  → new CategoryRepository($pdo)
  → new ArticleRepository($pdo)
  → new HomeController($categoryRepository, $articleRepository, $smarty)
```

### Layer rules

| Layer | May use |
|-------|---------|
| Controller | Repositories, Smarty, HTTP/query input |
| Repository | PDO only |
| bootstrap | Config, Database, Smarty |


---

## 10. Smarty integration

Initialized in `src/bootstrap.php` and returned as `$app['smarty']`.

### Directories

| Setting | Path |
|---------|------|
| Templates | `{PROJECT_ROOT}/templates` |
| Compile | `{PROJECT_ROOT}/var/smarty/compile/` |
| Cache | `{PROJECT_ROOT}/var/smarty/cache/` |

- `caching = false` for development simplicity  
- Compile dir required; gitignore compiled output under `var/`

### Escaping

- Article content is **plain text** stored in DB  
- Enable **default HTML escaping** for template variables (Smarty 5 API)  
- Do not wrap user content in `{literal}`  
- No raw HTML from database

### Templates

| File | Purpose |
|------|---------|
| `layout.tpl` | Base layout |
| `error.tpl` | 404 / errors |
| `home.tpl`, `category.tpl`, `article.tpl` | Page templates |
| `partials/*` | Reusable fragments (e.g. article card) |


---

## 11. Pages and behavior defaults

| Setting | Value |
|---------|-------|
| Pagination (category) | **10** per page |
| Default sort | `published_at` **DESC** (`sort=date`) |
| Home categories | Only categories with ≥ 1 article |
| Home articles per category | **3** latest by `published_at` |
| “All articles” link | `/category/{id}` |
| View count | Increment on each article page load |
| Related articles | Same category(ies), exclude current, `ORDER BY view_count DESC, published_at DESC`, **LIMIT 3** |
| Multi-category article | May appear in multiple home category blocks |


---

## 12. Data access patterns

### Home page (avoid N+1)

**Two queries + PHP grouping** (preferred):

1. Categories with at least one article (`INNER JOIN` + `GROUP BY`)  
2. Articles per category—MySQL 8 **window function** `ROW_NUMBER() PARTITION BY category_id ORDER BY published_at DESC`, keep `rn <= 3`

**Alternative:** second query returns links ordered by date; PHP takes first 3 distinct article IDs per category.

### Category page

- `COUNT(*)` for pagination  
- `SELECT` with `JOIN article_categories`, `WHERE category_id = ?`, `ORDER BY` date or views, `LIMIT offset, per_page`

### Article page

1. `SELECT` article by id  
2. `UPDATE view_count = view_count + 1 WHERE id = ?`  
3. Bump `view_count` in memory on the loaded row (avoid immediate reload SELECT)  
4. Related articles query (see section 11)


---

## 13. Seeder

**Files:** `bin/seed.php`, `src/Seeder/DatabaseSeeder.php`

- Use PDO only—**no** `seed.sql`  
- `bin/seed.php` requires `bootstrap.php`, uses `$app['pdo']`  
- `DatabaseSeeder::run(bool $fresh = false)`  
- **`--fresh`:** truncate `article_categories`, `articles`, `categories` (FK-safe order); do not drop schema  
- **Without `--fresh`:** skip if seed marker data already exists; print message to use `--fresh` to reset  
- Insert categories, articles, `article_categories` links via prepared statements  
- Placeholder images under `public/assets/uploads/` (e.g. lightweight SVG)  
- Schema comes from `schema.sql` / Docker init—not from the seeder


---

## 14. Coding rules and constraints

- **PHP 8.1+** features OK (`declare(strict_types=1)` in new files)  
- **Final classes** for infrastructure where extension is not planned (`Config`, `Database`)  
- **No frameworks**, no ORM, no DI container  
- **No** `phpdotenv` or extra Composer packages without updating this doc  
- **No** AJAX/API/admin UI unless spec changes  
- **No** HTML in article `text`  
- **ID URLs only**  
- Keep diffs small and readable  
- Comments only for non-obvious logic  
- Repositories: prepared statements only  
- Controllers: no SQL strings  
- **Avoid `foreach` by reference** in application code unless there is a strong reason; prefer index-based updates or building a new array  
- **Avoid reloading data immediately after a simple atomic UPDATE** when the changed value can be safely adjusted in memory  
- **Controller/page verification:** for changes that depend on database behavior, run Docker-based integration checks whenever possible; if a DB check is skipped, the report must explain exactly why  


---

## 15. Anti-goals

Intentionally **not** building:

| Anti-goal | Reason |
|-----------|--------|
| Service container / Pimple / Symfony DI | Unnecessary for small blog |
| Custom “framework” (Kernel, EventDispatcher) | Assignment is plain PHP |
| ORM (Eloquent, Doctrine) | PDO + repositories is enough |
| Slug-based URLs | IDs are sufficient |
| SQL seed files | Approved: PHP seeder |
| phpdotenv | Custom `Config` is enough |
| Redis, queues, caching layers | Out of scope |
| phpMyAdmin, Mailhog, Node in Compose | Docker stays 3 services |
| Database singleton | Explicit `createConnection`; one PDO per bootstrap in app array |
| Controllers creating repositories | Router injects repositories |
| Complex error subsystem | Minimal handlers in bootstrap |
| Admin panel / authentication | Not in assignment |
| Over-abstracted base classes | Add only when duplication is real |


---

*For implementation progress and remaining work, see [`PROJECT_STATE.md`](PROJECT_STATE.md).*
