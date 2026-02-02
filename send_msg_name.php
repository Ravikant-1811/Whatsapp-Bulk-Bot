<?php
/**
 * NAME-BASED QUEUE + WORKER
 * Run ONLY via CLI:
 * php send_msg_name.php
 */

if (php_sapi_name() !== 'cli') {
    die("❌ Run this script from Command Line (CLI) only.\n");
}

set_time_limit(0);
ini_set('max_execution_time', 0);

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/* ===========================
   CONFIG
=========================== */

$apiKey = trim('97295e63d4521ada27c283dcc03e56e4f3894576ca5e185607453ad2c8d05bca');
$url    = 'https://www.wasenderapi.com/api/send-message';

$queueFile = __DIR__ . '/queue_name.json';

/* ===========================
   CONTACTS (NAME + NUMBER)
=========================== */

$contacts = [
    
];

/* ===========================
   MESSAGE TEMPLATE
=========================== */

$messageTemplate = "Hi [[fullname]],

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

foreach ($contacts as $contact) {

    $queue[] = [
        'name'      => $contact['name'],
        'to'        => $contact['number'],
        'template'  => $messageTemplate,
        'status'    => 'pending',
        'attempt'   => 0,
        'added_at'  => date('Y-m-d H:i:s')
    ];
}

file_put_contents($queueFile, json_encode($queue, JSON_PRETTY_PRINT));

echo "✅ " . count($contacts) . " name-based messages queued successfully.\n";

/* ===========================
   STEP 2: WORKER (SEND SAFELY)
=========================== */

$client = new Client();
$queue  = json_decode(file_get_contents($queueFile), true);

foreach ($queue as $index => $job) {

    if ($job['status'] !== 'pending') {
        continue;
    }

    // Personalize message
    $message = str_replace('[[fullname]]', $job['name'], $job['template']);

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
                'text' => $message
            ]
        ]);

        echo "✅ Sent to {$job['name']} ({$job['to']})\n";

        // Mark as sent
        $queue[$index]['status']  = 'sent';
        $queue[$index]['sent_at'] = date('Y-m-d H:i:s');
        file_put_contents($queueFile, json_encode($queue, JSON_PRETTY_PRINT));

        echo "⏳ Waiting 60 seconds (free plan)...\n";
        sleep(62);

    } catch (RequestException $e) {

    if ($e->hasResponse()) {
        $status = $e->getResponse()->getStatusCode();
        $body   = json_decode($e->getResponse()->getBody(), true);

        // ⏳ Rate limit → NOT an error
        if ($status === 429) {
            $wait = ($body['retry_after'] ?? 60) + rand(5, 10);
            echo "⏳ Rate limit reached. Waiting {$wait}s before retry...\n";
            sleep($wait);
            goto retry;
        }

        // 🚫 Invalid API key → STOP everything
        if ($status === 401) {
            echo "🚫 Invalid API key. Worker stopped.\n";
            exit;
        }
    }

    // ❌ REAL failure (network, invalid number, etc.)
    echo "❌ Failed permanently for {$job['name']} ({$job['to']})\n";

    $queue[$index]['status'] = 'failed';
    $queue[$index]['error']  = $e->getMessage();

    file_put_contents($queueFile, json_encode($queue, JSON_PRETTY_PRINT));
    }

};


echo "🎉 All name-based queued messages processed.\n";
