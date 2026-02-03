<?php
/**
 * SINGLE FILE: QUEUE + WORKER
 * Run ONLY via CLI:
 * php send_msg_number.php
 */

if (php_sapi_name() !== 'cli') {
    die("❌ Run this script from Command Line (CLI) only.\n");
}

set_time_limit(0);
ini_set('max_execution_time', 0);

require 'vendor/autoload.php';
require __DIR__ . '/lib/settings.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/* ===========================
   CONFIG
=========================== */

$settings = get_settings();
$apiKey = trim($settings['api_key'] ?? '');
$url    = $settings['api_url'] ?? 'https://www.wasenderapi.com/api/send-message';

if ($apiKey === '') {
    echo "🚫 Missing API key. Set WASENDER_API_KEY env var or save it in dashboard settings.\n";
    exit(1);
}

$queueFile = __DIR__ . '/queue.json';

/* ===========================
   CONTACTS (SEND ALL AT ONCE)
=========================== */

$numbers = [
    '+918347141878',
    '+917096291214',
    '+919601102505',
];

$message = "Hi There,

Quick check before we share anything.

To generate real leads for local businesses today,
marketing usually needs:

• Monthly execution
• Some ad budget support

Are you currently open to investing to grow enquiries,
or just exploring ideas?

Reply:
1️⃣ Open to invest
2️⃣ Just exploring";

/* ===========================
   STEP 1: QUEUE ALL CONTACTS
=========================== */

$queue = file_exists($queueFile)
    ? json_decode(file_get_contents($queueFile), true)
    : [];

foreach ($numbers as $number) {
    $queue[] = [
        'to'       => $number,
        'message'  => $message,
        'status'   => 'pending',
        'attempt'  => 0,
        'added_at' => date('Y-m-d H:i:s')
    ];
}

file_put_contents($queueFile, json_encode($queue, JSON_PRETTY_PRINT));

echo "✅ " . count($numbers) . " messages queued successfully.\n";

/* ===========================
   STEP 2: WORKER (SEND SAFELY)
=========================== */

$client = new Client();

$queue = json_decode(file_get_contents($queueFile), true);

foreach ($queue as $index => $job) {

    if ($job['status'] !== 'pending') {
        continue;
    }

    retry:

    try {
        $client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'json' => [
                'to'   => $job['to'],
                'text' => $job['message']
            ]
        ]);

        echo "✅ Sent to {$job['to']}\n";

        $queue[$index]['status']  = 'sent';
        $queue[$index]['sent_at'] = date('Y-m-d H:i:s');
        file_put_contents($queueFile, json_encode($queue, JSON_PRETTY_PRINT));

        echo "⏳ Waiting 60 seconds (free plan)...\n";
        sleep(60);

    } catch (RequestException $e) {

        $queue[$index]['attempt']++;

        if ($e->hasResponse()) {
            $status = $e->getResponse()->getStatusCode();
            $body   = json_decode($e->getResponse()->getBody(), true);

            // 🚫 Invalid API key → stop everything
            if ($status === 401) {
                echo "🚫 Invalid API key. Stopping worker.\n";
                exit;
            }

            // ⏳ Rate limit → wait & retry
            if ($status === 429) {
                $wait = $body['retry_after'] ?? 60;
                echo "⚠️ Rate limit hit. Waiting {$wait}s...\n";
                sleep($wait);
                goto retry;
            }
        }

        echo "❌ Failed for {$job['to']}\n";
        $queue[$index]['status'] = 'failed';
        file_put_contents($queueFile, json_encode($queue, JSON_PRETTY_PRINT));
    }
}

echo "🎉 All queued messages processed.\n";
