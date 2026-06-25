<?php
require_once 'config.php';
$active = 'news';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News — StockView</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'php/nav.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>Market News</h1>
        <p>Latest business and stock market headlines</p>
    </div>

    <div id="news-container"><div class="spinner">Loading news</div></div>
</div>

<?php include 'php/ai_chat.php'; ?>
<script src="js/app.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', async () => {
        const container = document.getElementById('news-container');

        try {
            const res  = await fetch('api/news.php?action=general');
            const data = await res.json();

            if (data.error) {
                showError(container, 'News error: ' + data.error);
                return;
            }

            if (!data.articles?.length) {
                showEmpty(container, 'No news available right now.');
                return;
            }

            container.innerHTML = '';
            const grid = document.createElement('div');
            grid.className = 'news-grid';

            for (const article of data.articles) {
                grid.appendChild(buildNewsCard(article));
            }

            container.appendChild(grid);

        } catch (e) {
            showError(container, 'Failed to load news: ' + e.message);
        }
    });
</script>

</body>
</html>