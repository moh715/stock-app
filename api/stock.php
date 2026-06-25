<?php

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

const CACHE_TTL_MINUTES = 10;

$action = $_GET['action'] ?? '';
$symbol = strtoupper(trim($_GET['symbol'] ?? ''));
$query  = trim($_GET['q'] ?? '');



function cache_get(string $symbol, string $type): array|null {
    $db   = get_db();
    $stmt = $db->prepare(
        'SELECT data, fetched_at FROM stock_cache
         WHERE symbol = ? AND type = ?'
    );
    $stmt->execute([$symbol, $type]);
    $row = $stmt->fetch();

    if (!$row) return null;

    $age_minutes    = (time() - strtotime($row['fetched_at'])) / 60;
    $data           = json_decode($row['data'], true);
    $data['_cache'] = $age_minutes <= CACHE_TTL_MINUTES ? 'fresh' : 'stale';
    return $data;
}

function cache_set(string $symbol, string $type, array $data): void {
    if (isset($data['error']) || ($data['status'] ?? '') === 'error') return;

    get_db()
        ->prepare(
            'INSERT INTO stock_cache (symbol, type, data, fetched_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE data = VALUES(data), fetched_at = NOW()'
        )
        ->execute([$symbol, $type, json_encode($data)]);
}


function fetch_twelve(string $endpoint, array $params): array {
    $params['apikey'] = TWELVE_DATA_KEY;
    $url  = 'https://api.twelvedata.com/' . $endpoint . '?' . http_build_query($params);
    $ctx  = stream_context_create(['http' => ['timeout' => 10]]);
    $body = @file_get_contents($url, false, $ctx);

    if ($body === false) return ['error' => 'Failed to reach Twelve Data API'];

    return json_decode($body, true) ?? ['error' => 'Invalid API response'];
}

function get_stock_data(string $symbol, string $type): array {
    $cached = cache_get($symbol, $type);

    if ($cached && $cached['_cache'] === 'fresh') {
        return $cached;
    }

    if ($type === 'quote') {
        $fresh = fetch_twelve('quote', ['symbol' => $symbol]);
    } else {
        $fresh = fetch_twelve('time_series', [
            'symbol'     => $symbol,
            'interval'   => '1day',
            'outputsize' => 30,
        ]);
    }

    $api_failed = isset($fresh['error']) || ($fresh['status'] ?? '') === 'error';

    if (!$api_failed) {
        cache_set($symbol, $type, $fresh);
        $fresh['_cache'] = 'fresh';
        return $fresh;
    }

    if ($cached) {
        return $cached;
    }

    return ['error' => $fresh['message'] ?? $fresh['error'] ?? 'Data unavailable'];
}


function save_stock_to_db(string $symbol, string $name): void {
    try {
        get_db()
            ->prepare('INSERT IGNORE INTO stocks (symbol, name) VALUES (?, ?)')
            ->execute([$symbol, $name]);
    } catch (PDOException) {}
}


switch ($action) {

    case 'quote':
        if (!$symbol) { echo json_encode(['error' => 'Missing symbol']); exit; }
        echo json_encode(get_stock_data($symbol, 'quote'));
        break;

    case 'time_series':
        if (!$symbol) { echo json_encode(['error' => 'Missing symbol']); exit; }
        echo json_encode(get_stock_data($symbol, 'time_series'));
        break;
    case 'batch':
        $raw     = trim($_GET['symbols'] ?? '');
        $symbols = array_filter(array_map('strtoupper', explode(',', $raw)));

        if (!$symbols) { echo json_encode(['error' => 'Missing symbols']); exit; }

        $result = [];
        foreach ($symbols as $sym) {
            $result[$sym] = [
                'quote'       => get_stock_data($sym, 'quote'),
                'time_series' => get_stock_data($sym, 'time_series'),
            ];
        }

        echo json_encode($result);
        break;

    case 'search':
        if (!$query) { echo json_encode(['error' => 'Missing query']); exit; }

        $db   = get_db();
        $like = '%' . $query . '%';

        $stmt = $db->prepare(
            'SELECT symbol, name FROM stocks WHERE symbol LIKE ? OR name LIKE ? LIMIT 8'
        );
        $stmt->execute([$like, $like]);
        $local = $stmt->fetchAll();

        if (!$local) {
            $api  = fetch_twelve('symbol_search', ['symbol' => $query, 'outputsize' => 5]);
            $hits = $api['data'] ?? [];

            foreach ($hits as $hit) {
                save_stock_to_db($hit['symbol'], $hit['instrument_name']);
            }

            $results = array_map(fn($h) => [
                'symbol' => $h['symbol'],
                'name'   => $h['instrument_name'],
            ], $hits);
        } else {
            $results = array_map(fn($r) => [
                'symbol' => $r['symbol'],
                'name'   => $r['name'],
            ], $local);
        }

        echo json_encode(['results' => $results]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}