<?php
require_once 'config.php';
$active = '';

$symbol = strtoupper(trim($_GET['symbol'] ?? ''));
$name   = trim($_GET['name'] ?? $symbol);

if (!$symbol) {
    header('Location: index.php');
    exit;
}

$is_pinned = false;
if (is_logged_in()) {
    $db   = get_db();
    $stmt = $db->prepare(
        'SELECT 1 FROM stock_user su
         JOIN stocks s ON s.id = su.stock_id
         WHERE su.user_id = ? AND s.symbol = ?'
    );
    $stmt->execute([current_user_id(), $symbol]);
    $is_pinned = (bool) $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($symbol) ?> — StockView</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https:fcdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/charts.js"></script>
</head>
<body>

<?php include 'php/nav.php'; ?>

<div class="container">

    <div class="stock-detail-header">
        <div class="stock-detail-title">
            <h1 id="detail-name"><?= htmlspecialchars($name) ?></h1>
            <div class="symbol"><?= htmlspecialchars($symbol) ?></div>
        </div>
        <div class="stock-detail-price">
            <div class="price" id="detail-price">—</div>
            <span class="trend-badge" id="detail-trend"></span>
            <?php if (is_logged_in()): ?>
                <br>
                <button
                    id="pin-btn"
                    class="btn-pin <?= $is_pinned ? 'pinned' : '' ?>"
                    onclick="togglePin()"
                    style="margin-top:10px"
                >
                    <?= $is_pinned ? 'Pinned' : 'Pin' ?>
                </button>
            <?php else: ?>
                <br>
                <a href="login.php" class="btn btn-outline" style="margin-top:10px;display:inline-block">Login to Pin</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="chart-large-wrap">
        <div id="chart-spinner" class="spinner">Loading chart</div>
        <canvas id="large-chart" style="display:none"></canvas>
    </div>

    <div class="stock-meta-grid" id="meta-grid"></div>

    <div id="news-section">
        <div class="news-section-title">Latest News</div>
        <div id="news-container"><div class="spinner">Loading news</div></div>
    </div>

</div>

<?php include 'php/ai_chat.php'; ?>

<script src="js/app.js"></script>
<script src="charts.js"></script>
<script>
    const SYMBOL   = <?= json_encode($symbol) ?>;
    const NAME     = <?= json_encode($name) ?>;
    let   isPinned = <?= $is_pinned ? 'true' : 'false' ?>;


    async function loadStockDetail() {
        try {
            const [quote, ts] = await Promise.all([
                fetchQuote(SYMBOL),
                fetchTimeSeries(SYMBOL),
            ]);

            renderPrice(quote);
            renderMeta(quote);
            renderChart(ts);

        } catch (e) {
            document.getElementById('chart-spinner').textContent = 'Failed to load data.';
        }
    }

    function renderPrice(quote) {
        const price = parseFloat(quote.close ?? 0).toFixed(2);
        document.getElementById('detail-price').textContent = '$' + price;

        const open    = parseFloat(quote.open  ?? 0);
        const close   = parseFloat(quote.close ?? 0);
        const isUp    = close >= open;
        const badge   = document.getElementById('detail-trend');
        badge.textContent  = isUp ? '▲ Up' : '▼ Down';
        badge.className    = 'trend-badge ' + (isUp ? 'trend-up' : 'trend-down');
    }

    function renderMeta(quote) {
        const fields = [
            { label: 'Currency',          key: 'currency'           },
            { label: 'Exchange',          key: 'exchange'           },
            { label: 'Exchange Timezone', key: 'exchange_timezone'  },
            { label: 'MIC Code',          key: 'mic_code'           },
            { label: 'Type',              key: 'type'               },
            { label: 'Open',              key: 'open'               },
            { label: 'High',              key: 'high'               },
            { label: 'Low',               key: 'low'                },
            { label: 'Previous Close',    key: 'previous_close'     },
            { label: 'Volume',            key: 'volume'             },
        ];

        const grid = document.getElementById('meta-grid');
        grid.innerHTML = fields
            .filter(f => quote[f.key] != null)
            .map(f => `
                <div class="stock-meta-item">
                    <div class="meta-label">${escHtml(f.label)}</div>
                    <div class="meta-value">${escHtml(String(quote[f.key]))}</div>
                </div>
            `).join('');
    }

    function renderChart(ts) {
        const spinner = document.getElementById('chart-spinner');
        const canvas  = document.getElementById('large-chart');

        const chartData = buildChartData(ts);

        if (!chartData) {
            spinner.textContent = 'Chart data unavailable.';
            return;
        }

        spinner.style.display = 'none';
        canvas.style.display  = 'block';
        createLargeChart(canvas, chartData.labels, chartData.prices);
    }

    async function togglePin() {
        const btn    = document.getElementById('pin-btn');
        const action = isPinned ? 'unpin' : 'pin';

        btn.disabled = true;

        try {
            const res  = await fetch('api/pin.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ symbol: SYMBOL, name: NAME, action }),
            });
            const data = await res.json();

            if (data.error) {
                alert(data.error);
                return;
            }

            isPinned         = data.pinned;
            btn.textContent  = isPinned ? 'Pinned' : 'Pin';
            btn.className    = 'btn-pin' + (isPinned ? ' pinned' : '');

        } catch (e) {
            alert('Failed to update pin. Please try again.');
        } finally {
            btn.disabled = false;
        }
    }


    document.addEventListener('DOMContentLoaded', () => {
        loadStockDetail();
        loadNews();
    });


    async function loadNews() {
        const container = document.getElementById('news-container');

        try {
            const res  = await fetch(
                `api/news.php?action=stock&symbol=${encodeURIComponent(SYMBOL)}&name=${encodeURIComponent(NAME)}`
            );
            const data = await res.json();

            if (data.error) {
                container.innerHTML = `<p style="color:crimson;padding-bottom:40px">News error: ${escHtml(data.error)}</p>`;
                return;
            }

            if (!data.articles?.length) {
                container.innerHTML = '<p style="color:var(--text-muted);padding-bottom:40px">No news found.</p>';
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
            container.innerHTML = `<p style="color:crimson;padding-bottom:40px">Failed to load news: ${escHtml(e.message)}</p>`;
        }
    }
</script>

</body>
</html>