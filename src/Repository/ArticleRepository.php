<?php

declare(strict_types=1);

namespace App\Repository;

use InvalidArgumentException;
use PDO;

final class ArticleRepository
{
    public const PER_PAGE = 10;

    public const HOME_ARTICLES_PER_CATEGORY = 3;

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /**
     * Latest N articles per category in one query (MySQL 8 window function).
     * Returns flat rows including category_id; group by category_id in PHP if needed.
     *
     * @return list<array<string, mixed>>
     */
    public function findLatestPerCategory(int $limitPerCategory = self::HOME_ARTICLES_PER_CATEGORY): array
    {
        if ($limitPerCategory < 1) {
            throw new InvalidArgumentException('limitPerCategory must be at least 1');
        }

        $sql = <<<'SQL'
            SELECT category_id, id, image, title, description, published_at, view_count
            FROM (
                SELECT
                    ac.category_id,
                    a.id,
                    a.image,
                    a.title,
                    a.description,
                    a.published_at,
                    a.view_count,
                    ROW_NUMBER() OVER (
                        PARTITION BY ac.category_id
                        ORDER BY a.published_at DESC
                    ) AS rn
                FROM articles a
                INNER JOIN article_categories ac ON ac.article_id = a.id
            ) ranked
            WHERE rn <= :limit_per_category
            ORDER BY category_id ASC, published_at DESC
            SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->bindValue(':limit_per_category', $limitPerCategory, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Group findLatestPerCategory() rows by category_id for templates.
     *
     * @return array<int, list<array<string, mixed>>>
     */
    public function findLatestPerCategoryGrouped(int $limitPerCategory = self::HOME_ARTICLES_PER_CATEGORY): array
    {
        $grouped = [];

        foreach ($this->findLatestPerCategory($limitPerCategory) as $row) {
            $categoryId = (int) $row['category_id'];
            unset($row['category_id']);
            $grouped[$categoryId][] = $row;
        }

        return $grouped;
    }

    public function countByCategoryId(int $categoryId): int
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
                SELECT COUNT(DISTINCT a.id) AS total
                FROM articles a
                INNER JOIN article_categories ac ON ac.article_id = a.id
                WHERE ac.category_id = :category_id
                SQL
        );
        $statement->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findByCategoryId(
        int $categoryId,
        string $sort,
        string $order,
        int $page,
        int $perPage = self::PER_PAGE,
    ): array {
        if ($perPage < 1) {
            throw new InvalidArgumentException('perPage must be at least 1');
        }

        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $sortColumn = $this->resolveSortColumn($sort);
        $orderDirection = $this->resolveOrderDirection($order);

        $sql = <<<SQL
            SELECT DISTINCT
                a.id,
                a.image,
                a.title,
                a.description,
                a.published_at,
                a.view_count
            FROM articles a
            INNER JOIN article_categories ac ON ac.article_id = a.id
            WHERE ac.category_id = :category_id
            ORDER BY {$sortColumn} {$orderDirection}
            LIMIT :limit OFFSET :offset
            SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
                SELECT id, image, title, description, text, view_count, published_at
                FROM articles
                WHERE id = :id
                LIMIT 1
                SQL
        );
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function incrementViewCount(int $id): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE articles SET view_count = view_count + 1 WHERE id = :id'
        );
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * Related articles sharing at least one category with the given article.
     *
     * @return list<array<string, mixed>>
     */
    public function findRelatedByArticleId(int $articleId, int $limit = 3): array
    {
        if ($limit < 1) {
            throw new InvalidArgumentException('limit must be at least 1');
        }

        $sql = <<<'SQL'
            SELECT DISTINCT
                a.id,
                a.image,
                a.title,
                a.description,
                a.published_at,
                a.view_count
            FROM articles a
            INNER JOIN article_categories ac ON ac.article_id = a.id
            WHERE ac.category_id IN (
                SELECT category_id
                FROM article_categories
                WHERE article_id = :article_id
            )
            AND a.id != :exclude_article_id
            ORDER BY a.view_count DESC, a.published_at DESC
            LIMIT :limit
            SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->bindValue(':article_id', $articleId, PDO::PARAM_INT);
        $statement->bindValue(':exclude_article_id', $articleId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    private function resolveSortColumn(string $sort): string
    {
        return match ($sort) {
            'date' => 'a.published_at',
            'views' => 'a.view_count',
            default => throw new InvalidArgumentException('Invalid sort: ' . $sort),
        };
    }

    private function resolveOrderDirection(string $order): string
    {
        return match (strtolower($order)) {
            'asc' => 'ASC',
            'desc' => 'DESC',
            default => throw new InvalidArgumentException('Invalid order: ' . $order),
        };
    }
}
