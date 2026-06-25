<?php
require_once 'config.php';
require_login();

$active = 'pinned';
$uid    = current_user_id();

$db    = get_db();
$stmt  = $db->prepare(
    'SELECT s.symbol, s.name
     FROM stocks s
     JOIN stock_user su ON su.stock_id = s.id
     WHERE su.user_id = ?'
);
$stmt->execute([$uid]);
$pinned = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pinned — StockView</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/charts.js"></script>
</head>
<body>

<?php include 'php/nav.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>Pinned Stocks</h1>
        <p>Your personal watchlist</p>
    </div>

    <div id="grid-container"></div>
</div>

<?php include 'php/ai_chat.php'; ?>

<script src="js/app.js"></script>
<script>
    const PINNED = <?= json_encode($pinned) ?>;

    document.addEventListener('DOMContentLoaded', () => {
        if (!PINNED.length) {
            showEmpty(
                document.getElementById('grid-container'),
                'You have not pinned any stocks yet. Browse stocks and click Pin!'
            );
            return;
        }
        populateGrid(document.getElementById('grid-container'), PINNED);
    });
</script>

</body>
</html>