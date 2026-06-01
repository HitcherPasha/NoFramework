# NoFramework Blog

A simple blog application built as a **PHP Full Stack Developer test assignment**.

The project uses **plain PHP** (no framework), **MySQL**, **Smarty** templates, and **Docker** for local development. It demonstrates routing, PDO data access, pagination, sorting, and a small layered structure without over-engineering.

## Features

* **Home page** — categories with articles; three latest posts per category
* **Category page** — article list with pagination (10 per page)
* **Sorting** — by publication date or view count (`sort`, `order` query parameters)
* **Article page** — full article with view counter and related articles
* **View counter** — incremented on each article page view
* **Related articles** — up to three posts from shared categories
* **PDO seeder** — sample categories, articles, and links (`bin/seed.php`)
* **Docker environment** — nginx, PHP-FPM, and MySQL via Docker Compose

## Technology stack

| Component   | Version / tool                        |
| ----------- | ------------------------------------- |
| PHP         | 8.2 (FPM in Docker; `>=8.1` required) |
| MySQL       | 8.0                                   |
| Smarty      | 5.x                                   |
| HTTP        | nginx → php-fpm                       |
| Data access | PDO (repositories)                    |
| Containers  | Docker Compose                        |

## Quick start

### Prerequisites

* Docker with Docker Compose
* Git

### Run locally

```bash
git clone git@github.com:HitcherPasha/NoFramework.git
cd NoFramework

docker compose up -d

docker compose exec -T php composer install

docker compose exec -T php php bin/seed.php --fresh
```

Open in your browser:

**http://localhost:8080**

The database schema is applied automatically on the first MySQL container start (`database/schema.sql`). The seeder fills sample data for manual testing.

Optional: copy `.env.example` to `.env` if you run PHP outside Docker. Inside Compose, database settings are provided by the `php` service environment.

## Routes

| Method | Path             | Description                                              |
| ------ | ---------------- | -------------------------------------------------------- |
| GET    | `/`              | Home page                                                |
| GET    | `/category/{id}` | Category articles (pagination and sort via query string) |
| GET    | `/article/{id}`  | Article detail                                           |

**Category query parameters** (optional):

| Parameter | Values          | Default |
| --------- | --------------- | ------- |
| `sort`    | `date`, `views` | `date`  |
| `order`   | `asc`, `desc`   | `desc`  |
| `page`    | integer         | `1`     |

Examples:

* `/category/1?sort=views&order=desc&page=1`
* `/category/1?sort=date&order=asc&page=2`

## Query Parameter Handling

Category query parameters are normalized to safe defaults instead of causing request failures.

| Input            | Result       |
| ---------------- | ------------ |
| `?sort=unknown`  | `sort=date`  |
| `?order=unknown` | `order=desc` |
| `?page=0`        | `page=1`     |
| `?page=-10`      | `page=1`     |

Invalid category or article IDs still return **404 Not Found**.

This behavior was chosen to make the application more resilient to invalid user input while still returning proper errors for missing resources.

## Seeder

```bash
# Inside Docker (recommended)
docker compose exec -T php php bin/seed.php
docker compose exec -T php php bin/seed.php --fresh

# Or on host, if PHP and DB are configured
php bin/seed.php
php bin/seed.php --fresh
```

| Command                    | Behavior                                              |
| -------------------------- | ----------------------------------------------------- |
| `php bin/seed.php`         | Inserts sample data only if not already present       |
| `php bin/seed.php --fresh` | Clears categories, articles, and links, then re-seeds |

## Project structure (overview)

```text
public/           Web root (index.php, assets)
src/              Application code (Router, controllers, repositories, seeder)
templates/        Smarty templates
database/         SQL schema (DDL)
bin/              CLI scripts (seed.php)
docker/           nginx and PHP image config
scss/             SCSS source (compiled to public/assets/css/main.css)
docs/             Architecture and project state documentation
```

Request flow:

```text
public/index.php
  → bootstrap
  → Router
  → controller
  → repositories
  → Smarty
```

## Additional documentation

| Document              | Purpose                                         |
| --------------------- | ----------------------------------------------- |
| docs/ARCHITECTURE.md  | Architecture, design rules, and constraints     |
| docs/PROJECT_STATE.md | Current implementation status and handoff notes |

## License

Test assignment repository — use and evaluate as provided by the author.
