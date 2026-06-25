<?php

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

function news_cache_get(string $key, int $ttl_minutes): array|null {
    $db   = get_db();
    $stmt = $db->prepare(
        'SELECT data, fetched_at FROM news_cache WHERE cache_key = ?'
    );
    $stmt->execute([$key]);
    $row = $stmt->fetch();

    if (!$row) return null;

    $age_minutes    = (time() - strtotime($row['fetched_at'])) / 60;
    $data           = json_decode($row['data'], true);
    $data['_cache'] = $age_minutes <= $ttl_minutes ? 'fresh' : 'stale';
    return $data;
}

function news_cache_set(string $key, array $data): void {
    get_db()
        ->prepare(
            'INSERT INTO news_cache (cache_key, data, fetched_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE data = VALUES(data), fetched_at = NOW()'
        )
        ->execute([$key, json_encode($data)]);
}

function fetch_news(array $params): array {
    $endpoint = $params['_endpoint'];
    unset($params['_endpoint']);

    $params['apiKey']   = NEWS_API_KEY;
    $params['pageSize'] = 10;
    $params['language'] = 'en';

    $url  = 'https://newsapi.org/v2/' . $endpoint . '?' . http_build_query($params);
    $ctx  = stream_context_create([
        'http' => [
            'timeout'       => 10,
            'ignore_errors' => true,
            'header'        => 'User-Agent: StockViewApp/1.0',
        ],
    ]);
    $body = file_get_contents($url, false, $ctx);

    if ($body === false) return ['error' => 'Failed to reach NewsAPI'];

    $data = json_decode($body, true);
    if (!is_array($data)) return ['error' => 'Invalid response from NewsAPI: ' . $body];

    if (($data['status'] ?? '') !== 'ok') {
        return ['error' => $data['message'] ?? 'NewsAPI error'];
    }

    return ['articles' => $data['articles'] ?? []];
}


function build_stock_query(string $symbol, string $name): string {
    $clean_symbol = explode('/', $symbol)[0];
    $clean_name   = trim(explode('.', $name)[0]);
    return $clean_name . ' ' . $clean_symbol;
}


function get_news(string $cache_key, array $api_params, int $ttl_minutes): array {
    $cached = news_cache_get($cache_key, $ttl_minutes);

    if ($cached && $cached['_cache'] === 'fresh') {
        return $cached;
    }

    $fresh      = fetch_news($api_params);
    $api_failed = isset($fresh['error']);

    if (!$api_failed) {
        news_cache_set($cache_key, $fresh);
        $fresh['_cache'] = 'fresh';
        return $fresh;
    }

    if ($cached) return $cached; 

    return $fresh;
}


switch ($action) {

    case 'stock':
        $symbol = strtoupper(trim($_GET['symbol'] ?? ''));
        $name   = trim($_GET['name'] ?? $symbol);

        if (!$symbol) { echo json_encode(['error' => 'Missing symbol']); exit; }

        $result = get_news('stock_' . $symbol, [
            '_endpoint' => 'everything',
            'q'         => build_stock_query($symbol, $name),
            'sortBy'    => 'publishedAt',
        ], 60);

        echo json_encode($result);
        break;

    case 'general':
        $result = get_news('general', [
            '_endpoint' => 'top-headlines',
            'category'  => 'business',
            'country'   => 'us',
        ], 30);

        echo json_encode($result);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}