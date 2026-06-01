<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class CategoryRepository
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /**
     * Categories that have at least one linked article, ordered by title.
     *
     * @return list<array<string, mixed>>
     */
    public function findWithArticles(): array
    {
        $sql = <<<'SQL'
            SELECT c.id, c.title, c.description
            FROM categories c
            INNER JOIN article_categories ac ON ac.category_id = c.id
            GROUP BY c.id, c.title, c.description
            ORDER BY c.title ASC
            SQL;

        $statement = $this->pdo->query($sql);

        return $statement->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, title, description FROM categories WHERE id = :id LIMIT 1'
        );
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }
}
