<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ArticleRepository;
use DateTimeImmutable;
use RuntimeException;
use Smarty\Smarty;

final class ArticleController
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly Smarty $smarty,
    ) {
    }

    public function show(int $articleId): void
    {
        $article = $this->articleRepository->findById($articleId);

        if ($article === null) {
            throw new RuntimeException('Article not found: ' . $articleId);
        }

        $this->articleRepository->incrementViewCount($articleId);
        $article['view_count'] = (int) $article['view_count'] + 1;

        $article['published_at_formatted'] = (new DateTimeImmutable((string) $article['published_at']))
            ->format('Y-m-d H:i');

        $relatedArticles = $this->articleRepository->findRelatedByArticleId($articleId);

        foreach ($relatedArticles as $index => $relatedArticle) {
            $relatedArticles[$index]['published_at_formatted'] = (new DateTimeImmutable(
                (string) $relatedArticle['published_at']
            ))->format('Y-m-d H:i');
        }

        $this->smarty->assign('pageTitle', $article['title']);
        $this->smarty->assign('article', $article);
        $this->smarty->assign('relatedArticles', $relatedArticles);
        $this->smarty->display('article.tpl');
    }
}
