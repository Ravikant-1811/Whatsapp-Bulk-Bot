<?php
/**
 * Worker: send queued messages safely.
 * Run via CLI: php worker.php
 */

if (php_sapi_name() !== 'cli') {
    die("❌ Run this script from Command Line (CLI) only.\n");
}

set_time_limit(0);
ini_set('max_execution_time', 0);

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lib/settings.php';
require_once __DIR__ . '/lib/queue.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

$settings = get_settings();
$apiKey = $settings['api_key'] ?? '';
if ($apiKey === '') {
    echo "🚫 Missing API key. Set WASENDER_API_KEY env var or save it in dashboard settings.\n";
    exit(1);
}

$apiUrl = $settings['api_url'] ?? 'https://www.wasenderapi.com/api/send-message';

$minDelay = (int)($settings['min_delay_seconds'] ?? 45);
$maxDelay = (int)($settings['max_delay_seconds'] ?? 90);
$sendWindowStart = $settings['send_window_start'] ?? '09:00';
$sendWindowEnd = $settings['send_window_end'] ?? '20:00';
$dailyLimit = (int)($settings['daily_limit'] ?? 200);

$dailyFile = __DIR__ . '/data/daily.json';
$daily = read_json($dailyFile, ['date' => date('Y-m-d'), 'sent' => 0]);
if (($daily['date'] ?? '') !== date('Y-m-d')) {
    $daily = ['date' => date('Y-m-d'), 'sent' => 0];
}

$client = new Client();
$queue = get_queue();

$nowTime = date('H:i');
if ($nowTime < $sendWindowStart || $nowTime > $sendWindowEnd) {
    echo "⏳ Outside send window ({$sendWindowStart}-{$sendWindowEnd}). Exiting.\n";
    exit(0);
}

foreach ($queue as $index => $job) {
    if ($job['status'] !== 'pending') {
        continue;
    }

    if ($daily['sent'] >= $dailyLimit) {
        echo "🛑 Daily limit reached ({$dailyLimit}). Stopping.\n";
        break;
    }

    $message = str_replace('[[fullname]]', $job['name'] ?? '', $job['template'] ?? '');

    retry:

    try {
        $client->post($apiUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => [
                'to' => $job['to'],
                'text' => $message,
            ],
        ]);

        echo "✅ Sent to {$job['to']}\n";

        $queue[$index]['status'] = 'sent';
        $queue[$index]['sent_at'] = now_local();
        $queue[$index]['attempt'] = ($queue[$index]['attempt'] ?? 0) + 1;
        save_queue($queue);

        $daily['sent']++;
        write_json($dailyFile, $daily);

        $sleep = rand($minDelay, $maxDelay);
        echo "⏳ Waiting {$sleep} seconds...\n";
        sleep($sleep);
    } catch (RequestException $e) {
        $queue[$index]['attempt'] = ($queue[$index]['attempt'] ?? 0) + 1;

        if ($e->hasResponse()) {
            $status = $e->getResponse()->getStatusCode();
            $body = json_decode((string)$e->getResponse()->getBody(), true);

            if ($status === 401) {
                echo "🚫 Invalid API key. Worker stopped.\n";
                exit(1);
            }

            if ($status === 429) {
                $wait = (int)($body['retry_after'] ?? 60);
                $wait = max($wait, 30);
                echo "⚠️ Rate limit hit. Waiting {$wait}s...\n";
                sleep($wait);
                goto retry;
            }
        }

        echo "❌ Failed for {$job['to']}\n";
        $queue[$index]['status'] = 'failed';
        $queue[$index]['error'] = $e->getMessage();
        save_queue($queue);
    }
}

echo "🎉 Queue processed.\n";
