<article class="article-card">
    <a href="/article/{$article.id}" class="article-card__image-link">
        <img src="/assets/{$article.image}" alt="{$article.title}" class="article-card__image">
    </a>
    <h3 class="article-card__title">
        <a href="/article/{$article.id}">{$article.title}</a>
    </h3>
    <p class="article-card__description">{$article.description}</p>
    <time class="article-card__date" datetime="{$article.published_at}">
        {$article.published_at_formatted}
    </time>
</article>
