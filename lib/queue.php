<?php

declare(strict_types=1);

require_once __DIR__ . '/storage.php';

function queue_path(): string
{
    return __DIR__ . '/../data/queue.json';
}

function get_queue(): array
{
    return read_json(queue_path(), []);
}

function save_queue(array $queue): void
{
    write_json(queue_path(), $queue);
}

function enqueue_messages(string $campaignId, array $contacts, string $template): int
{
    $queue = get_queue();

    foreach ($contacts as $contact) {
        $queue[] = [
            'id' => uuid(),
            'campaign_id' => $campaignId,
            'name' => $contact['name'] ?? '',
            'to' => $contact['number'],
            'template' => $template,
            'status' => 'pending',
            'attempt' => 0,
            'added_at' => now_local(),
        ];
    }

    save_queue($queue);
    return count($contacts);
}

function queue_stats(): array
{
    $queue = get_queue();

    $stats = [
        'pending' => 0,
        'sent' => 0,
        'failed' => 0,
    ];

    foreach ($queue as $item) {
        if (!isset($stats[$item['status']])) {
            continue;
        }
        $stats[$item['status']]++;
    }

    return $stats;
}
