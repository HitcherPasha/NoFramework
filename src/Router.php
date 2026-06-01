<?php

declare(strict_types=1);

namespace App;

use App\Controller\ArticleController;
use App\Controller\CategoryController;
use App\Controller\HomeController;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use PDO;
use RuntimeException;
use Smarty\Smarty;

final class Router
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly Smarty $smarty,
    ) {
    }

    public function dispatch(string $requestUri, string $requestMethod): void
    {
        $path = parse_url($requestUri, PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : '/';

        if ($requestMethod !== 'GET') {
            if ($this->isKnownRoute($path)) {
                $this->renderError(
                    405,
                    'Method not allowed',
                    'This resource only supports GET requests.',
                    true,
                );

                return;
            }

            $this->renderError(404, 'Page not found', 'The page you requested does not exist.');

            return;
        }

        if ($path === '/') {
            $this->dispatchHome();

            return;
        }

        if (preg_match('#^/category/(\d+)$#', $path, $matches) === 1) {
            $this->dispatchCategory($requestUri, (int) $matches[1]);

            return;
        }

        if (preg_match('#^/article/(\d+)$#', $path, $matches) === 1) {
            $this->dispatchArticle((int) $matches[1]);

            return;
        }

        $this->renderError(404, 'Page not found', 'The page you requested does not exist.');
    }

    private function dispatchHome(): void
    {
        $categoryRepository = new CategoryRepository($this->pdo);
        $articleRepository = new ArticleRepository($this->pdo);
        $controller = new HomeController($categoryRepository, $articleRepository, $this->smarty);

        try {
            $controller->index();
        } catch (RuntimeException $e) {
            $this->renderError(404, 'Page not found', $e->getMessage());
        }
    }

    private function dispatchCategory(string $requestUri, int $categoryId): void
    {
        if ($categoryId < 1) {
            $this->renderError(404, 'Page not found', 'The page you requested does not exist.');

            return;
        }

        $query = $this->parseQuery($requestUri);
        $sort = isset($query['sort']) ? (string) $query['sort'] : 'date';
        $order = isset($query['order']) ? (string) $query['order'] : 'desc';
        $page = isset($query['page']) ? (int) $query['page'] : 1;

        $categoryRepository = new CategoryRepository($this->pdo);
        $articleRepository = new ArticleRepository($this->pdo);
        $controller = new CategoryController($categoryRepository, $articleRepository, $this->smarty);

        try {
            $controller->show($categoryId, $page, $sort, $order);
        } catch (RuntimeException $e) {
            $this->renderError(404, 'Page not found', $e->getMessage());
        }
    }

    private function dispatchArticle(int $articleId): void
    {
        if ($articleId < 1) {
            $this->renderError(404, 'Page not found', 'The page you requested does not exist.');

            return;
        }

        $articleRepository = new ArticleRepository($this->pdo);
        $controller = new ArticleController($articleRepository, $this->smarty);

        try {
            $controller->show($articleId);
        } catch (RuntimeException $e) {
            $this->renderError(404, 'Page not found', $e->getMessage());
        }
    }

    /**
     * @return array<string, string>
     */
    private function parseQuery(string $requestUri): array
    {
        $queryString = parse_url($requestUri, PHP_URL_QUERY);

        if (!is_string($queryString) || $queryString === '') {
            return [];
        }

        $query = [];
        parse_str($queryString, $query);

        return is_array($query) ? $query : [];
    }

    private function isKnownRoute(string $path): bool
    {
        if ($path === '/') {
            return true;
        }

        if (preg_match('#^/category/\d+$#', $path) === 1) {
            return true;
        }

        return preg_match('#^/article/\d+$#', $path) === 1;
    }

    private function renderError(
        int $statusCode,
        string $title,
        string $message,
        bool $setAllowGet = false,
    ): void {
        http_response_code($statusCode);

        if ($setAllowGet) {
            header('Allow: GET');
        }

        $this->smarty->assign('pageTitle', $title);
        $this->smarty->assign('errorTitle', $title);
        $this->smarty->assign('errorMessage', $message);
        $this->smarty->assign('statusCode', $statusCode);
        $this->smarty->display('error.tpl');
    }
}
