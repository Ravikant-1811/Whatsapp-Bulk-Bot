<?php
/**
 * Scheduler: enqueue campaigns on weekly schedule.
 * Run via CLI every minute: php scheduler.php
 */

if (php_sapi_name() !== 'cli') {
    die("❌ Run this script from Command Line (CLI) only.\n");
}

require __DIR__ . '/lib/contacts.php';
require __DIR__ . '/lib/campaigns.php';
require __DIR__ . '/lib/schedules.php';
require __DIR__ . '/lib/queue.php';
require __DIR__ . '/lib/storage.php';

$now = new DateTimeImmutable('now');
$day = strtolower($now->format('D')); // mon, tue, wed...
$time = $now->format('H:i');

$schedules = get_schedules();
$campaigns = get_campaigns();
$contacts = get_contacts();

$campaignMap = [];
foreach ($campaigns as $campaign) {
    $campaignMap[$campaign['id']] = $campaign;
}

$contactMap = [];
foreach ($contacts as $contact) {
    $contactMap[$contact['id']] = $contact;
}

$updated = false;

foreach ($schedules as &$schedule) {
    if (!($schedule['enabled'] ?? true)) {
        continue;
    }

    $scheduleDay = strtolower($schedule['day_of_week'] ?? '');
    $scheduleTime = $schedule['time'] ?? '';

    if ($scheduleDay !== $day || $scheduleTime !== $time) {
        continue;
    }

    $lastRun = $schedule['last_run_at'] ?? null;
    $alreadyRanToday = $lastRun && substr($lastRun, 0, 10) === $now->format('Y-m-d');
    if ($alreadyRanToday) {
        continue;
    }

    $campaignId = $schedule['campaign_id'] ?? '';
    if (!isset($campaignMap[$campaignId])) {
        continue;
    }

    $campaign = $campaignMap[$campaignId];
    if (!($campaign['enabled'] ?? true)) {
        continue;
    }

    $campaignContacts = [];
    foreach ($campaign['contact_ids'] as $contactId) {
        if (isset($contactMap[$contactId])) {
            $campaignContacts[] = $contactMap[$contactId];
        }
    }

    $count = enqueue_messages($campaignId, $campaignContacts, $campaign['template']);
    echo "✅ Enqueued {$count} messages for campaign {$campaign['name']}\n";

    $schedule['last_run_at'] = now_local();
    $updated = true;
}

unset($schedule);

if ($updated) {
    save_schedules($schedules);
}
