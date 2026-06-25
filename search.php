<?php
require_once 'config.php';
$active = 'search';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search — StockView</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'php/nav.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>Search Stocks</h1>
        <p>Find any stock by name or symbol</p>
    </div>

    <div class="search-bar">
        <input
            type="text"
            id="search-input"
            placeholder="e.g. Apple, AAPL, Tesla…"
            autocomplete="off"
        >
        <button class="btn btn-primary" onclick="doSearch()">Search</button>
    </div>

    <div id="grid-container"></div>
</div>

<?php include 'php/ai_chat.php'; ?>

<script src="js/app.js"></script>
<script>
    const input = document.getElementById('search-input');
    const grid  = document.getElementById('grid-container');

    input.addEventListener('keydown', e => {
        if (e.key === 'Enter') doSearch();
    });

    async function doSearch() {
        const q = input.value.trim();
        if (!q) return;

        showSpinner(grid);

        try {
            const res  = await fetch(`api/stock.php?action=search&q=${encodeURIComponent(q)}`);
            const data = await res.json();

            if (data.error) { showError(grid, data.error); return; }

            const results = data.results ?? [];

            if (!results.length) {
                showEmpty(grid, `No stocks found for "${escHtml(q)}".`);
                return;
            }

            renderResultList(results);

        } catch (e) {
            showError(grid, 'Search failed. Please try again.');
        }
    }

    function renderResultList(results) {
        const list = document.createElement('div');
        list.className = 'search-result-list';

        for (const r of results) {
            const row = document.createElement('div');
            row.className = 'search-result-row';
            row.innerHTML = `
                <div>
                    <span class="search-result-name">${escHtml(r.name)}</span>
                    <span class="search-result-symbol">${escHtml(r.symbol)}</span>
                </div>
                <span class="search-result-arrow">→</span>
            `;
            row.addEventListener('click', () => {
                window.location.href =
                    `stock.php?symbol=${encodeURIComponent(r.symbol)}&name=${encodeURIComponent(r.name)}`;
            });
            list.appendChild(row);
        }

        grid.innerHTML = '';
        grid.appendChild(list);
    }
</script>

</body>
</html>