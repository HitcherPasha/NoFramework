<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$pageTitle|default:'Blog'}</title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
    <header>
        <h1><a href="/">Blog</a></h1>
    </header>
    <main>
        {block name=content}{/block}
    </main>
</body>
</html>
