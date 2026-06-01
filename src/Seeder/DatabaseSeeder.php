<?php

declare(strict_types=1);

namespace App\Seeder;

use PDO;

final class DatabaseSeeder
{
    private const MARKER_CATEGORY_TITLE = 'Technology';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function run(bool $fresh = false): void
    {
        if ($fresh) {
            $this->clearTables();
        } elseif ($this->isAlreadySeeded()) {
            echo "Seed data already present. Run with --fresh to reset and re-seed.\n";

            return;
        }

        $this->ensurePlaceholderImages();

        $categoryIds = $this->insertCategories();
        $articleIds = $this->insertArticles();
        $linkCount = $this->insertArticleCategories($categoryIds, $articleIds);

        echo sprintf(
            "Seeded %d categories, %d articles, %d article-category links.\n",
            count($categoryIds),
            count($articleIds),
            $linkCount,
        );
    }

    private function isAlreadySeeded(): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM categories WHERE title = :title'
        );
        $statement->execute(['title' => self::MARKER_CATEGORY_TITLE]);

        return (int) $statement->fetchColumn() > 0;
    }

    private function clearTables(): void
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $this->pdo->exec('TRUNCATE TABLE article_categories');
        $this->pdo->exec('TRUNCATE TABLE articles');
        $this->pdo->exec('TRUNCATE TABLE categories');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        echo "Cleared article_categories, articles, and categories.\n";
    }

    private function ensurePlaceholderImages(): void
    {
        $uploadsDir = dirname(__DIR__, 2) . '/public/assets/uploads';

        if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
            throw new \RuntimeException('Could not create uploads directory: ' . $uploadsDir);
        }

        for ($index = 1; $index <= 16; $index++) {
            $filename = sprintf('article-%02d.svg', $index);
            $path = $uploadsDir . '/' . $filename;

            if (is_file($path)) {
                continue;
            }

            $label = sprintf('Article %d', $index);
            $svg = <<<SVG
                <svg xmlns="http://www.w3.org/2000/svg" width="640" height="360" viewBox="0 0 640 360">
                    <rect width="640" height="360" fill="#e8eef4"/>
                    <text x="320" y="190" text-anchor="middle" font-family="sans-serif" font-size="28" fill="#4a5568">{$label}</text>
                </svg>
                SVG;

            file_put_contents($path, $svg);
        }
    }

    /**
     * @return list<int> category ids in insertion order
     */
    private function insertCategories(): array
    {
        $categories = [
            ['title' => 'Technology', 'description' => 'Software, gadgets, and digital life.'],
            ['title' => 'Travel', 'description' => 'Destinations, trips, and travel tips.'],
            ['title' => 'Food', 'description' => 'Recipes, restaurants, and kitchen ideas.'],
            ['title' => 'Photography', 'description' => 'Cameras, composition, and visual storytelling.'],
        ];

        $statement = $this->pdo->prepare(
            'INSERT INTO categories (title, description) VALUES (:title, :description)'
        );

        $ids = [];

        foreach ($categories as $category) {
            $statement->execute($category);
            $ids[] = (int) $this->pdo->lastInsertId();
        }

        return $ids;
    }

    /**
     * @return list<int> article ids in insertion order (1-based index matches seed data keys)
     */
    private function insertArticles(): array
    {
        $articles = [
            [
                'image' => 'uploads/article-01.svg',
                'title' => 'Getting started with PHP 8.2',
                'description' => 'A short overview of modern PHP features for backend developers.',
                'text' => "PHP 8.2 brings readonly classes, null and false standalone types, and performance improvements.\n\nThis article walks through practical examples you can use in a small project without a framework.",
                'view_count' => 42,
                'published_at' => '2026-05-20 09:00:00',
            ],
            [
                'image' => 'uploads/article-02.svg',
                'title' => 'Building a blog without frameworks',
                'description' => 'Why plain PHP, PDO, and Smarty can be enough for a test assignment.',
                'text' => "Frameworks are powerful, but a minimal stack helps you understand routing, SQL, and templates.\n\nWe keep controllers thin and repositories responsible for queries.",
                'view_count' => 128,
                'published_at' => '2026-05-18 14:30:00',
            ],
            [
                'image' => 'uploads/article-03.svg',
                'title' => 'MySQL window functions for home page feeds',
                'description' => 'Fetch the latest N articles per category in one query.',
                'text' => "ROW_NUMBER() partitioned by category_id avoids N+1 queries on the home page.\n\nIndexes on published_at and the junction table keep the query fast.",
                'view_count' => 89,
                'published_at' => '2026-05-15 11:15:00',
            ],
            [
                'image' => 'uploads/article-04.svg',
                'title' => 'Smarty escaping and plain text articles',
                'description' => 'Store plain text in the database and escape output in templates.',
                'text' => "setEscapeHtml(true) ensures user-facing content is safe by default.\n\nArticle bodies stay readable without allowing raw HTML from the database.",
                'view_count' => 56,
                'published_at' => '2026-05-12 16:45:00',
            ],
            [
                'image' => 'uploads/article-05.svg',
                'title' => 'Docker Compose for local development',
                'description' => 'nginx, php-fpm, and MySQL in three services.',
                'text' => "A simple compose file mounts the project and initializes schema on first MySQL start.\n\nIntegration tests can run through docker compose exec php.",
                'view_count' => 201,
                'published_at' => '2026-05-10 08:20:00',
            ],
            [
                'image' => 'uploads/article-06.svg',
                'title' => 'Pagination and sorting on category pages',
                'description' => 'Ten articles per page with sort by date or views.',
                'text' => "Whitelist sort columns in the repository and normalize invalid query params in the controller.\n\nThis keeps SQL safe while remaining friendly to manual URL tweaks.",
                'view_count' => 73,
                'published_at' => '2026-05-08 13:00:00',
            ],
            [
                'image' => 'uploads/article-07.svg',
                'title' => 'Incrementing article view counts',
                'description' => 'A single UPDATE per page view with in-memory display adjustment.',
                'text' => "After incrementViewCount runs, the controller bumps the loaded row in memory.\n\nThat avoids an extra SELECT while still showing the updated count.",
                'view_count' => 310,
                'published_at' => '2026-05-05 10:30:00',
            ],
            [
                'image' => 'uploads/article-08.svg',
                'title' => 'Related articles by shared category',
                'description' => 'Show three related posts ordered by popularity and date.',
                'text' => "Articles sharing at least one category with the current post are candidates.\n\nExclude the current article and limit to three results.",
                'view_count' => 95,
                'published_at' => '2026-05-02 17:10:00',
            ],
            [
                'image' => 'uploads/article-09.svg',
                'title' => 'PDO prepared statements in repositories',
                'description' => 'Keep SQL out of controllers and bind all dynamic values.',
                'text' => "Repositories accept PDO only and return associative arrays.\n\nControllers format dates and assign Smarty variables.",
                'view_count' => 64,
                'published_at' => '2026-04-28 09:45:00',
            ],
            [
                'image' => 'uploads/article-10.svg',
                'title' => 'Explicit routing without a framework',
                'description' => 'Match paths with simple checks and regular expressions.',
                'text' => "The router parses the path, wires repositories, and dispatches to controllers.\n\n404 and 405 responses render the shared error template.",
                'view_count' => 142,
                'published_at' => '2026-04-25 15:20:00',
            ],
            [
                'image' => 'uploads/article-11.svg',
                'title' => 'Seeding sample data with PHP',
                'description' => 'Populate categories, articles, and links for manual testing.',
                'text' => "A DatabaseSeeder class uses PDO inserts and supports a --fresh reset.\n\nPlaceholder SVG images keep the demo lightweight.",
                'view_count' => 27,
                'published_at' => '2026-04-22 12:00:00',
            ],
            [
                'image' => 'uploads/article-12.svg',
                'title' => 'Weekend in Lisbon',
                'description' => 'Tiles, trams, and ocean views in two days.',
                'text' => "Start in Alfama, walk to Belém for pastéis de nata, and watch the sunset by the Tagus.\n\nPack comfortable shoes for the hills.",
                'view_count' => 178,
                'published_at' => '2026-05-19 07:30:00',
            ],
            [
                'image' => 'uploads/article-13.svg',
                'title' => 'Packing light for carry-on travel',
                'description' => 'A capsule packing list that fits overhead bins.',
                'text' => "Choose versatile layers, one pair of shoes, and travel-size toiletries.\n\nRolling clothes saves space and reduces wrinkles.",
                'view_count' => 134,
                'published_at' => '2026-05-14 18:00:00',
            ],
            [
                'image' => 'uploads/article-14.svg',
                'title' => 'Train routes through the Alps',
                'description' => 'Scenic rides between Switzerland and Italy.',
                'text' => "Book window seats on daylight trains and bring snacks for long segments.\n\nRegional passes can be cheaper than individual tickets.",
                'view_count' => 92,
                'published_at' => '2026-05-09 06:40:00',
            ],
            [
                'image' => 'uploads/article-15.svg',
                'title' => 'Remote work from a coastal café',
                'description' => 'Balancing time zones, Wi-Fi, and sightseeing.',
                'text' => "Test upload speeds in the morning and block focus hours before exploring.\n\nA power bank and noise-cancelling headphones help on travel days.",
                'view_count' => 256,
                'published_at' => '2026-05-16 11:50:00',
            ],
            [
                'image' => 'uploads/article-16.svg',
                'title' => 'Simple sourdough focaccia',
                'description' => 'Crispy edges and airy crumb with minimal kneading.',
                'text' => "Mix dough the night before, stretch and dimple in the morning, then bake until golden.\n\nFinish with olive oil and flaky salt.",
                'view_count' => 88,
                'published_at' => '2026-05-17 19:15:00',
            ],
            [
                'image' => 'uploads/article-01.svg',
                'title' => 'One-pot pasta for busy evenings',
                'description' => 'Dinner ready in under thirty minutes.',
                'text' => "Simmer pasta with garlic, tomatoes, and broth in one pan.\n\nStir in parmesan and basil before serving.",
                'view_count' => 61,
                'published_at' => '2026-05-13 20:30:00',
            ],
            [
                'image' => 'uploads/article-02.svg',
                'title' => 'Farmers market salad ideas',
                'description' => 'Seasonal vegetables with a lemon vinaigrette.',
                'text' => "Roast roots while the greens soak, then toss everything with mustard and lemon.\n\nAdd toasted seeds for crunch.",
                'view_count' => 47,
                'published_at' => '2026-05-07 12:45:00',
            ],
            [
                'image' => 'uploads/article-03.svg',
                'title' => 'Smart kitchen scales for baking',
                'description' => 'Why grams beat cups for consistent results.',
                'text' => "Weigh flour and water for bread, and use tare mode for incremental mixing.\n\nA scale pays for itself quickly.",
                'view_count' => 112,
                'published_at' => '2026-05-04 09:10:00',
            ],
            [
                'image' => 'uploads/article-04.svg',
                'title' => 'Meal prep lunches for the work week',
                'description' => 'Grain bowls that stay fresh for four days.',
                'text' => "Cook rice and roast vegetables on Sunday, then assemble bowls with different sauces.\n\nKeep dressing separate until serving.",
                'view_count' => 99,
                'published_at' => '2026-04-30 14:00:00',
            ],
        ];

        $statement = $this->pdo->prepare(
            <<<'SQL'
                INSERT INTO articles (image, title, description, text, view_count, published_at)
                VALUES (:image, :title, :description, :text, :view_count, :published_at)
                SQL
        );

        $ids = [];

        foreach ($articles as $article) {
            $statement->execute($article);
            $ids[] = (int) $this->pdo->lastInsertId();
        }

        return $ids;
    }

    /**
     * @param list<int> $categoryIds [technology, travel, food, photography]
     * @param list<int> $articleIds 1-based article index in seed order
     */
    private function insertArticleCategories(array $categoryIds, array $articleIds): int
    {
        $technologyId = $categoryIds[0];
        $travelId = $categoryIds[1];
        $foodId = $categoryIds[2];

        $links = [
            // Technology (articles 1-11) — 11 articles for pagination demo
            ...array_map(static fn (int $index): array => [
                'article_id' => $articleIds[$index - 1],
                'category_id' => $technologyId,
            ], range(1, 11)),
            // Travel (articles 12-14, 15 multi)
            ['article_id' => $articleIds[11], 'category_id' => $travelId],
            ['article_id' => $articleIds[12], 'category_id' => $travelId],
            ['article_id' => $articleIds[13], 'category_id' => $travelId],
            ['article_id' => $articleIds[14], 'category_id' => $travelId],
            // Food (articles 16-19, 20 multi with tech)
            ['article_id' => $articleIds[15], 'category_id' => $foodId],
            ['article_id' => $articleIds[16], 'category_id' => $foodId],
            ['article_id' => $articleIds[17], 'category_id' => $foodId],
            ['article_id' => $articleIds[18], 'category_id' => $foodId],
            ['article_id' => $articleIds[19], 'category_id' => $foodId],
            // Multi-category
            ['article_id' => $articleIds[14], 'category_id' => $technologyId], // Remote work
            ['article_id' => $articleIds[18], 'category_id' => $technologyId], // Smart kitchen scales
        ];

        $statement = $this->pdo->prepare(
            'INSERT INTO article_categories (article_id, category_id) VALUES (:article_id, :category_id)'
        );

        foreach ($links as $link) {
            $statement->execute($link);
        }

        return count($links);
    }
}
