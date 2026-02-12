<?php

$raw = file_get_contents("php://input");
file_put_contents('webhook_debug.txt', $raw . PHP_EOL . "-------------------" . PHP_EOL, FILE_APPEND);

require 'vendor/autoload.php';
require 'config/db.php';

use GuzzleHttp\Client;

/* ===========================
   PARSE INPUT
=========================== */

$input = json_decode($raw, true);

if (!$input) exit;

/* ===========================
   ONLY HANDLE POLL RESULTS
=========================== */

if (($input['event'] ?? '') !== 'poll.results') {
    exit;
}

$pollResults = $input['data']['pollResult'] ?? [];

if (!$pollResults) exit;

/* ===========================
   FIND SELECTED OPTION
=========================== */

$selectedOption = null;
$voterNumber    = null;

foreach ($pollResults as $result) {
    if (!empty($result['voters'])) {
        $selectedOption = $result['name'];
        $voterNumber = explode('@', $result['voters'][0])[0];
        break;
    }
}

if (!$selectedOption || !$voterNumber) exit;

/* ===========================
   FIND CONTACT IN DB
=========================== */

$stmt = $pdo->prepare("SELECT * FROM contacts WHERE number = :num");
$stmt->execute(['num' => $voterNumber]);
$contact = $stmt->fetch();

if (!$contact) {
    file_put_contents('webhook_debug.txt', "Contact not found\n", FILE_APPEND);
    exit;
}

/* ===========================
   LOG INCOMING POLL
=========================== */

$pdo->prepare("
    INSERT INTO reply_logs
    (contact_id, direction, message, button_id, created_at)
    VALUES (:cid, 'incoming', :msg, :opt, NOW())
")->execute([
    'cid' => $contact['id'],
    'msg' => 'Poll Vote',
    'opt' => $selectedOption
]);

/* ===========================
   SEND FOLLOW UP
=========================== */

$apiKey = '040ea68be9706cd2f7682a32eff516cf897609f905e53be15147e2bd2ea89df0';
$url    = 'https://www.wasenderapi.com/api/send-message';

function sendMessage($number, $text, $apiKey, $url) {
    $client = new Client();

    try {
        $response = $client->post($url, [
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json'
            ],
            'json' => [
                'to'   => $number,
                'text' => $text
            ]
        ]);

        file_put_contents(
            'webhook_debug.txt',
            "Follow-up sent to {$number}\n",
            FILE_APPEND
        );

    } catch (Exception $e) {
        file_put_contents(
            'webhook_debug.txt',
            "Follow-up FAILED: " . $e->getMessage() . "\n",
            FILE_APPEND
        );
    }
}

/* ===========================
   AUTOMATION LOGIC
=========================== */

if ($selectedOption === "Actively Investing") {

    $pdo->prepare("
        UPDATE contacts
        SET status = 'interested'
        WHERE id = :id
    ")->execute(['id' => $contact['id']]);

    sendMessage(
        $voterNumber,
        "🚀 Amazing!\n\nWould you like:\n1️⃣ A quick 15-min strategy call\n2️⃣ A case study?",
        $apiKey,
        $url
    );
}

elseif ($selectedOption === "Comfortable") {

    $pdo->prepare("
        UPDATE contacts
        SET status = 'not_interested'
        WHERE id = :id
    ")->execute(['id' => $contact['id']]);

    sendMessage(
        $voterNumber,
        "No worries 👍\n\nShould we revisit this in 3 months?",
        $apiKey,
        $url
    );
}
