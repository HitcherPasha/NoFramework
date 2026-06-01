<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use DateTimeImmutable;
use RuntimeException;
use Smarty\Smarty;

final class CategoryController
{
    private const ALLOWED_SORTS = ['date', 'views'];

    private const ALLOWED_ORDERS = ['asc', 'desc'];

    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly ArticleRepository $articleRepository,
        private readonly Smarty $smarty,
    ) {
    }

    public function show(
        int $categoryId,
        int $page = 1,
        string $sort = 'date',
        string $order = 'desc',
    ): void {
        $category = $this->categoryRepository->findById($categoryId);

        if ($category === null) {
            throw new RuntimeException('Category not found: ' . $categoryId);
        }

        if ($page < 1) {
            $page = 1;
        }

        if (!in_array($sort, self::ALLOWED_SORTS, true)) {
            $sort = 'date';
        }

        if (!in_array($order, self::ALLOWED_ORDERS, true)) {
            $order = 'desc';
        }

        $totalArticles = $this->articleRepository->countByCategoryId($categoryId);
        $perPage = ArticleRepository::PER_PAGE;
        $totalPages = $totalArticles > 0 ? (int) ceil($totalArticles / $perPage) : 1;

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $articles = $this->articleRepository->findByCategoryId($categoryId, $sort, $order, $page);

        foreach ($articles as $index => $article) {
            $articles[$index]['published_at_formatted'] = (new DateTimeImmutable((string) $article['published_at']))
                ->format('Y-m-d H:i');
        }

        $this->smarty->assign('pageTitle', $category['title']);
        $this->smarty->assign('category', $category);
        $this->smarty->assign('articles', $articles);
        $this->smarty->assign('totalArticles', $totalArticles);
        $this->smarty->assign('currentPage', $page);
        $this->smarty->assign('totalPages', $totalPages);
        $this->smarty->assign('sort', $sort);
        $this->smarty->assign('order', $order);
        $this->smarty->assign('hasPreviousPage', $page > 1);
        $this->smarty->assign('hasNextPage', $page < $totalPages);
        $this->smarty->assign('previousPage', $page - 1);
        $this->smarty->assign('nextPage', $page + 1);
        $this->smarty->display('category.tpl');
    }
}
