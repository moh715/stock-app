<?php
require_once 'config.php';
$active = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Article - StockView</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .article-wrap {
            max-width: 780px;
            margin: 40px auto 80px;
            padding: 0 24px;
        }

        .article-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 28px;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            font-family: var(--font-main);
            transition: color 0.2s;
        }

        .article-back:hover {
            color: var(--accent);
        }

        .article-image {
            width: 100%;
            max-height: 420px;
            object-fit: cover;
            border-radius: var(--radius);
            display: block;
            margin-bottom: 28px;
        }

        .article-meta {
            display: flex;
            gap: 16px;
            align-items: center;
            margin-bottom: 16px;
        }

        .article-source {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .article-date {
            font-family: var(--font-mono);
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .article-title {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            line-height: 1.3;
            margin-bottom: 20px;
        }

        .article-description {
            font-size: 1.05rem;
            line-height: 1.75;
            color: var(--text-muted);
            margin-bottom: 32px;
        }

        .article-read-link {
            display: inline-block;
            padding: 12px 28px;
            background: var(--accent);
            color: white;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .article-read-link:hover {
            opacity: 0.85;
            text-decoration: none;
            color: white;
        }

        .article-not-found {
            text-align: center;
            padding: 80px 0;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

<?php include 'php/nav.php'; ?>

<div class="article-wrap" id="article-content">
    <div class="article-not-found">
        <div style="font-size:2rem;margin-bottom:12px">📰</div>
        <p>No article selected.</p>
        <a href="news.php" style="margin-top:16px;display:inline-block">Browse News</a>
    </div>
</div>

<?php include 'php/ai_chat.php'; ?>

<script src="js/app.js"></script>
<script>
    const raw     = sessionStorage.getItem('currentArticle');
    const wrap    = document.getElementById('article-content');

    if (!raw) {

    } else {
        const article = JSON.parse(raw);

        const image = article.urlToImage
            ? `<img class="article-image" src="${escHtml(article.urlToImage)}" alt="" onerror="this.style.display='none'">`
            : '';

        const description = article.description ?? article.content ?? 'No description available.';

        wrap.innerHTML = `
            <button class="article-back" onclick="history.back()">
                &larr; Back
            </button>
            ${image}
            <div class="article-meta">
                <span class="article-source">${escHtml(article.source?.name ?? '')}</span>
                <span class="article-date">${formatDate(article.publishedAt)}</span>
            </div>
            <h1 class="article-title">${escHtml(article.title ?? '')}</h1>
            <p class="article-description">${escHtml(description)}</p>
            <a
                class="article-read-link"
                href="${escHtml(article.url)}"
                target="_blank"
                rel="noopener noreferrer">
                Read full article &rarr;
            </a>
        `;
    }
</script>

</body>
</html>