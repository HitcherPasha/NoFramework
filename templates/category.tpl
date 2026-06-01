{extends file='layout.tpl'}

{block name=content}
    <section class="category-page">
        <h2 class="category-page__title">{$category.title}</h2>
        <p class="category-page__description">{$category.description}</p>

        <nav class="category-page__sort" aria-label="Sort articles">
            <span>Sort:</span>
            <a href="/category/{$category.id}?sort=date&amp;order=desc"
               class="category-page__sort-link{if $sort == 'date' && $order == 'desc'} is-active{/if}">Newest</a>
            <a href="/category/{$category.id}?sort=date&amp;order=asc"
               class="category-page__sort-link{if $sort == 'date' && $order == 'asc'} is-active{/if}">Oldest</a>
            <a href="/category/{$category.id}?sort=views&amp;order=desc"
               class="category-page__sort-link{if $sort == 'views' && $order == 'desc'} is-active{/if}">Most viewed</a>
            <a href="/category/{$category.id}?sort=views&amp;order=asc"
               class="category-page__sort-link{if $sort == 'views' && $order == 'asc'} is-active{/if}">Least viewed</a>
        </nav>

        <p class="category-page__count">{$totalArticles} article{if $totalArticles != 1}s{/if}</p>

        <div class="category-page__articles">
            {if $articles|@count > 0}
                {foreach $articles as $article}
                    {include file='partials/article-card.tpl' article=$article}
                {/foreach}
            {else}
                <p class="category-page__empty">No articles in this category yet.</p>
            {/if}
        </div>

        {if $totalPages > 1}
            <nav class="category-page__pagination" aria-label="Pagination">
                {if $hasPreviousPage}
                    <a href="/category/{$category.id}?sort={$sort|escape:'url'}&amp;order={$order|escape:'url'}&amp;page={$previousPage}">Previous</a>
                {/if}
                <span class="category-page__pagination-info">Page {$currentPage} of {$totalPages}</span>
                {if $hasNextPage}
                    <a href="/category/{$category.id}?sort={$sort|escape:'url'}&amp;order={$order|escape:'url'}&amp;page={$nextPage}">Next</a>
                {/if}
            </nav>
        {/if}
    </section>
{/block}
