<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/settings.php';
require_once __DIR__ . '/../lib/contacts.php';
require_once __DIR__ . '/../lib/campaigns.php';
require_once __DIR__ . '/../lib/schedules.php';
require_once __DIR__ . '/../lib/queue.php';
require_once __DIR__ . '/../lib/storage.php';
require_once __DIR__ . '/../lib/templates.php';

$settings = get_settings();
$contacts = get_contacts();
$campaigns = get_campaigns();
$schedules = get_schedules();
$queue = get_queue();
$templates = get_templates();
$stats = queue_stats();
$recentQueue = array_slice(array_reverse($queue), 0, 20);

$dailyFile = __DIR__ . '/../data/daily.json';
$daily = read_json($dailyFile, ['date' => date('Y-m-d'), 'sent' => 0]);
if (($daily['date'] ?? '') !== date('Y-m-d')) {
    $daily = ['date' => date('Y-m-d'), 'sent' => 0];
}

function safe(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
