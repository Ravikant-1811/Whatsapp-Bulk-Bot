<?php

if (php_sapi_name() !== 'cli') {
    die("❌ Run via CLI only\n");
}

set_time_limit(0);
ini_set('max_execution_time', 0);

require 'vendor/autoload.php';
require 'config/db.php';
require 'import_contacts.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/* ===========================
   CONFIG
=========================== */

$apiKey = '040ea68be9706cd2f7682a32eff516cf897609f905e53be15147e2bd2ea89df0';
$url    = 'https://www.wasenderapi.com/api/send-message';

$dailyLimit = 250;
$minDelay   = 10;
$maxDelay   = 25;
$breakAfter = 25;
$breakMin   = 180;
$breakMax   = 420;

/* ===========================
   MESSAGE TEMPLATE
=========================== */

$template = "Hi [[fullname]],

A quick question —

Are you satisfied with your current business growth,
or actively investing to move to the next level?

https://zeusinfinityservices.com";

/* ===========================
   IMPORT EXCEL
=========================== */

$excelPath = __DIR__ . '/Test.xlsx';

if (!file_exists($excelPath)) {
    die("❌ Excel file not found\n");
}

$contacts = readContacts($excelPath);

$insert = $pdo->prepare("
    INSERT IGNORE INTO contacts (name, number, business_type, status)
    VALUES (:name, :number, :type, 'pending')
");

foreach ($contacts as $c) {
    $insert->execute([
        'name'   => $c['name'],
        'number' => $c['number'],
        'type'   => $c['business_type'] ?? null
    ]);
}

echo "📥 Excel sync complete\n";

/* ===========================
   DAILY LIMIT
=========================== */

$sentToday = (int)$pdo->query("
    SELECT COUNT(*) FROM contacts
    WHERE DATE(last_sent_at) = CURDATE()
")->fetchColumn();

echo "📊 Sent today: {$sentToday} / {$dailyLimit}\n";

if ($sentToday >= $dailyLimit) {
    echo "🛑 Daily limit reached\n";
    exit;
}

$remaining = $dailyLimit - $sentToday;

/* ===========================
   FETCH PENDING
=========================== */

$stmt = $pdo->prepare("
    SELECT * FROM contacts
    WHERE status = 'pending'
    ORDER BY id ASC
    LIMIT :limit
");

$stmt->bindValue(':limit', $remaining, PDO::PARAM_INT);
$stmt->execute();

$batch = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$batch) {
    echo "✅ No pending contacts\n";
    exit;
}

/* ===========================
   HUMAN DELAY
=========================== */

function humanDelay($count, $min, $max, $breakAfter, $breakMin, $breakMax)
{
    if ($count > 0 && $count % $breakAfter === 0) {
        $delay = rand($breakMin, $breakMax);
        echo "🧠 Break {$delay}s\n";
        sleep($delay);
        return;
    }

    $delay = rand($min, $max);
    echo "⏳ Waiting {$delay}s\n";
    sleep($delay);
}

/* ===========================
   WORKER
=========================== */

$client  = new Client();
$sentNow = 0;

foreach ($batch as $contact) {

    $messageText = str_replace('[[fullname]]', $contact['name'], $template);

    retry:

    try {

        $response = $client->post($url, [
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'to' => $contact['number'],
                'poll' => [
                    'question' => $messageText,
                    'options' => [
                        'Actively Investing',
                        'Comfortable'
                    ],
                    'multiSelect' => false
                ]
            ]
        ]);

        echo "✅ Sent → {$contact['name']} ({$contact['number']})\n";

        $pdo->prepare("
            UPDATE contacts
            SET status = 'sent',
                last_sent_at = NOW()
            WHERE id = :id
        ")->execute(['id' => $contact['id']]);

        $pdo->prepare("
            INSERT INTO message_logs
            (contact_id, phone, message, response, status, created_at)
            VALUES (:cid, :phone, :msg, :res, 'sent', NOW())
        ")->execute([
            'cid'   => $contact['id'],
            'phone' => $contact['number'],
            'msg'   => $messageText,
            'res'   => (string)$response->getBody()
        ]);

        $sentNow++;
        humanDelay($sentNow, $minDelay, $maxDelay, $breakAfter, $breakMin, $breakMax);

    } catch (RequestException $e) {

        $errorMsg = $e->getMessage();
        echo "❌ Failed → {$contact['number']} | {$errorMsg}\n";

        $pdo->prepare("
            UPDATE contacts
            SET status = 'failed',
                retry_count = retry_count + 1,
                last_error = :err
            WHERE id = :id
        ")->execute([
            'id'  => $contact['id'],
            'err' => $errorMsg
        ]);

        if ($e->hasResponse() && $e->getResponse()->getStatusCode() === 429) {
            $wait = rand(40, 90);
            echo "⏳ Rate limit. Waiting {$wait}s\n";
            sleep($wait);
            goto retry;
        }
    }
}

echo "🎉 Done. Sent Now: {$sentNow}\n";
