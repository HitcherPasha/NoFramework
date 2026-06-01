CREATE TABLE categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    description TEXT NOT NULL
);

CREATE TABLE articles (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    image           VARCHAR(500) NOT NULL,
    title           VARCHAR(255) NOT NULL,
    description     VARCHAR(500) NOT NULL,
    text            MEDIUMTEXT NOT NULL,
    view_count      INT UNSIGNED NOT NULL DEFAULT 0,
    published_at    DATETIME NOT NULL,
    INDEX idx_articles_published_at (published_at),
    INDEX idx_articles_view_count (view_count)
);

CREATE TABLE article_categories (
    article_id   INT UNSIGNED NOT NULL,
    category_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (article_id, category_id),
    CONSTRAINT fk_ac_article
        FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    CONSTRAINT fk_ac_category
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_ac_category_article (category_id, article_id)
);
