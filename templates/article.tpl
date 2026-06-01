{extends file='layout.tpl'}

{block name=content}
    <article class="article-page">
        <h2 class="article-page__title">{$article.title}</h2>

        <img src="/assets/{$article.image}" alt="{$article.title}" class="article-page__image">

        <p class="article-page__description">{$article.description}</p>

        <div class="article-page__text">{$article.text}</div>

        <p class="article-page__meta">
            <time class="article-page__date" datetime="{$article.published_at}">
                {$article.published_at_formatted}
            </time>
            <span class="article-page__views">{$article.view_count} view{if $article.view_count != 1}s{/if}</span>
        </p>

        {if $relatedArticles|@count > 0}
            <section class="article-page__related">
                <h3 class="article-page__related-title">Related articles</h3>
                <ul class="article-page__related-list">
                    {foreach $relatedArticles as $relatedArticle}
                        <li class="article-page__related-item">
                            <a href="/article/{$relatedArticle.id}" class="article-page__related-link">
                                {$relatedArticle.title}
                            </a>
                            <time class="article-page__related-date" datetime="{$relatedArticle.published_at}">
                                {$relatedArticle.published_at_formatted}
                            </time>
                        </li>
                    {/foreach}
                </ul>
            </section>
        {/if}
    </article>
{/block}
