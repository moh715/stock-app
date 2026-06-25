<?php

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

const GEMINI_MODEL      = 'gemini-2.5-flash-lite';
const GEMINI_API_V      = 'v1';
const GEMINI_MAX_TOKENS = 300;
const SYSTEM_PROMPT =
    'You are StockView AI, a professional financial analyst and stock market expert. ' .
    'You help users understand stocks, market trends, financial metrics, and investment concepts. ' .
    'Be concise, clear, and data-driven. ' .
    'Always remind users that this is not financial advice. ' .
    'If asked about a specific stock, share relevant facts you know about the company.';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

$body     = json_decode(file_get_contents('php://input'), true);
$messages = $body['messages'] ?? [];

if (empty($messages)) {
    echo json_encode(['error' => 'No messages provided']);
    exit;
}

$recent = array_slice($messages, -5);

$contents = [
    [
        'role'  => 'user',
        'parts' => [['text' => SYSTEM_PROMPT]],
    ],
    [
        'role'  => 'model',
        'parts' => [['text' => 'Understood. I am StockView AI, ready to help with stock market questions. Note: nothing I say is financial advice.']],
    ],
    ...$recent,
];

$url  = 'https://generativelanguage.googleapis.com/' . GEMINI_API_V . '/models/' . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY;
$opts = [
    'http' => [
        'method'        => 'POST',
        'header'        => 'Content-Type: application/json',
        'content'       => json_encode([
            'contents'           => $contents,
            'generationConfig'   => [
                'maxOutputTokens' => GEMINI_MAX_TOKENS,
            ],
        ]),
        'timeout'       => 30,
        'ignore_errors' => true,
    ],
];

$response = file_get_contents($url, false, stream_context_create($opts));

if ($response === false) {
    echo json_encode(['error' => 'Failed to reach Gemini API']);
    exit;
}

$data = json_decode($response, true);

if (isset($data['error'])) {
    echo json_encode(['error' => $data['error']['message'] ?? 'Unknown Gemini API error']);
    exit;
}

$reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Sorry, I could not get a response.';
echo json_encode(['reply' => $reply]);