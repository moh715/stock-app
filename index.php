<?php
require_once 'config.php';
$active = 'index';

$trending_stocks = [
    ['symbol' => 'AAPL',  'name' => 'Apple Inc.'],
    ['symbol' => 'MSFT',  'name' => 'Microsoft Corp.'],
    ['symbol' => 'GOOGL', 'name' => 'Alphabet Inc.'],
    ['symbol' => 'AMZN',  'name' => 'Amazon.com Inc.'],
    ['symbol' => 'TSLA',  'name' => 'Tesla Inc.'],
    ['symbol' => 'NVDA',  'name' => 'NVIDIA Corp.'],
    ['symbol' => 'META',  'name' => 'Meta Platforms'],
    ['symbol' => 'NFLX',  'name' => 'Netflix Inc.'],
    ['symbol' => 'BTC/USD', 'name' => 'Bitcoin']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trending — StockView</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/charts.js"></script>
</head>
<body>

<?php include 'php/nav.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>Trending Stocks</h1>
        <p>Today's most-watched stocks at a glance</p>
    </div>

    <div id="grid-container"></div>
</div>

<?php include 'php/ai_chat.php'; ?>

<script src="js/app.js"></script>
<script>
    const STOCKS = <?= json_encode($trending_stocks) ?>;

    document.addEventListener('DOMContentLoaded', () => {
        populateGrid(document.getElementById('grid-container'), STOCKS);
    });
</script>

</body>
</html>