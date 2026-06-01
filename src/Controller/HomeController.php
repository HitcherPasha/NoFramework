<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Smarty\Smarty;

final class HomeController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly ArticleRepository $articleRepository,
        private readonly Smarty $smarty,
    ) {
    }

    public function index(): void
    {
        $categories = $this->categoryRepository->findWithArticles();

        if ($categories !== []) {
            $articlesByCategory = $this->articleRepository->findLatestPerCategoryGrouped();

            foreach ($categories as &$category) {
                $categoryId = (int) $category['id'];
                $articles = $articlesByCategory[$categoryId] ?? [];

                foreach ($articles as &$article) {
                    $article['published_at_formatted'] = date(
                        'Y-m-d H:i',
                        strtotime((string) $article['published_at'])
                    );
                }
                unset($article);

                $category['articles'] = $articles;
            }
            unset($category);
        }

        $this->smarty->assign('pageTitle', 'Home');
        $this->smarty->assign('categories', $categories);
        $this->smarty->display('home.tpl');
    }
}
