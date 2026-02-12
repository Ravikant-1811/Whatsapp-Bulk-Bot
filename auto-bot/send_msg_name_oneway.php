<?php
/**
 * NAME-BASED DAILY BATCH WORKER (MYSQL · RESUMABLE · SAFE)
 * Run via CLI only:
 * php send_msg_name.php
 */

if (php_sapi_name() !== 'cli') {
    die("❌ Run via CLI only\n");
}

set_time_limit(0);
ini_set('max_execution_time', 0);

require 'vendor/autoload.php';
require 'config/db.php';        // PDO connection ($pdo)
require 'import_contacts.php';  // Excel reader

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

$today = date('Y-m-d');

/* ===========================
   MESSAGE TEMPLATE
=========================== */

$template = "Hi [[fullname]],

A quick question — Are you satisfied with your current business growth,
or actively investing to move to the next level?

Reply:
1️⃣ Actively investing
2️⃣ Comfortable where I am

https://zeusinfinityservices.com";

/* ===========================
   STEP 1: IMPORT EXCEL (ONLY NEW)
=========================== */

$excelPath = __DIR__ . '/Test.xlsx';

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
   STEP 2: COUNT TODAY'S SENDS
=========================== */

$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM contacts
    WHERE DATE(last_sent_at) = CURDATE()
");
$countStmt->execute();

$sentToday = (int) $countStmt->fetchColumn();

echo "📊 Sent today: {$sentToday} / {$dailyLimit}\n";

$remaining = $dailyLimit - $sentToday;

if ($remaining <= 0) {
    echo "🛑 Daily limit reached. Resume tomorrow.\n";
    exit;
}

/* ===========================
   STEP 3: FETCH SAFE BATCH
   (pending + retryable failed)
=========================== */

$stmt = $pdo->prepare("
    SELECT *
    FROM contacts
    WHERE status IN ('pending','failed')
      AND retry_count < 3
      AND (locked_at IS NULL OR locked_at < NOW() - INTERVAL 1 HOUR)
    ORDER BY id ASC
    LIMIT :limit
");
$stmt->bindValue(':limit', $remaining, PDO::PARAM_INT);
$stmt->execute();

$batch = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$batch) {
    echo "✅ No eligible contacts left.\n";
    exit;
}

/* ===========================
   HELPER: HUMAN DELAY
=========================== */

function humanDelay($count, $min, $max, $breakAfter, $breakMin, $breakMax) {
    if ($count > 0 && $count % $breakAfter === 0) {
        $d = rand($breakMin, $breakMax);
        echo "🧠 Human break: {$d}s\n";
        sleep($d);
        return;
    }

    $d = rand($min, $max);
    echo "⏳ Waiting {$d}s\n";
    sleep($d);
}

/* ===========================
   STEP 4: WORKER
=========================== */

$client  = new Client();
$sentNow = 0;

foreach ($batch as $contact) {

    // Lock row (prevents parallel runs)
    $pdo->prepare("
        UPDATE contacts
        SET locked_at = NOW()
        WHERE id = :id
    ")->execute(['id' => $contact['id']]);

    $message = str_replace('[[fullname]]', $contact['name'], $template);

    retry:

    try {
        $client->post($url, [
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'to'   => $contact['number'],
                'text' => $message
            ]
        ]);

        echo "✅ Sent → {$contact['name']} ({$contact['number']})\n";

        // Mark as sent
        $pdo->prepare("
            UPDATE contacts
            SET status = 'sent',
                last_sent_at = NOW(),
                locked_at = NULL
            WHERE id = :id
        ")->execute(['id' => $contact['id']]);

        // Log success
        $pdo->prepare("
            INSERT INTO message_logs (contact_id, status)
            VALUES (:id, 'sent')
        ")->execute(['id' => $contact['id']]);

        $sentNow++;
        humanDelay($sentNow, $minDelay, $maxDelay, $breakAfter, $breakMin, $breakMax);

    } catch (RequestException $e) {

        $code = $e->hasResponse()
            ? $e->getResponse()->getStatusCode()
            : 0;

        // Rate limit → wait & retry same number
        if ($code === 429) {
            $wait = rand(40, 90);
            echo "⏳ Rate limit hit. Waiting {$wait}s\n";
            sleep($wait);
            goto retry;
        }

        if ($code === 401) {
            echo "🚫 Invalid API key. Stopping worker.\n";
            exit;
        }

        echo "❌ Failed → {$contact['number']}\n";

        // Mark failed + increment retry
        $pdo->prepare("
            UPDATE contacts
            SET status = 'failed',
                retry_count = retry_count + 1,
                locked_at = NULL
            WHERE id = :id
        ")->execute(['id' => $contact['id']]);

        // Log failure
        $pdo->prepare("
            INSERT INTO message_logs (contact_id, status, response)
            VALUES (:id, 'failed', :err)
        ")->execute([
            'id'  => $contact['id'],
            'err' => $e->getMessage()
        ]);
    }
}

echo "🎉 Done. Total sent today: " . ($sentToday + $sentNow) . "\n";
