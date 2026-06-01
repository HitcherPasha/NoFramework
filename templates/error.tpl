{extends file='layout.tpl'}

{block name=content}
    <section class="error">
        <h2>{$errorTitle|default:'Page not found'}</h2>
        <p>{$errorMessage|default:'The page you requested does not exist.'}</p>
        <p><a href="/">Back to home</a></p>
    </section>
{/block}
