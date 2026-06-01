{extends file='layout.tpl'}

{block name=content}
    <h2 class="page-title">Latest articles</h2>

    {if $categories|@count > 0}
        {foreach $categories as $category}
            <section class="category-block">
                <h3 class="category-block__title">{$category.title}</h3>
                <p class="category-block__description">{$category.description}</p>

                <div class="category-block__articles">
                    {foreach $category.articles as $article}
                        {include file='partials/article-card.tpl' article=$article}
                    {/foreach}
                </div>

                <p class="category-block__actions">
                    <a href="/category/{$category.id}">All articles</a>
                </p>
            </section>
        {/foreach}
    {else}
        <p class="empty-state">No articles have been published yet.</p>
    {/if}
{/block}
