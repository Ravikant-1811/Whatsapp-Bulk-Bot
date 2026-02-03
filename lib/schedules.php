<?php

declare(strict_types=1);

require_once __DIR__ . '/storage.php';

function schedules_path(): string
{
    return __DIR__ . '/../data/schedules.json';
}

function get_schedules(): array
{
    return read_json(schedules_path(), []);
}

function save_schedules(array $schedules): void
{
    write_json(schedules_path(), $schedules);
}

function create_schedule(string $campaignId, string $dayOfWeek, string $time, string $timezone): string
{
    $schedules = get_schedules();
    $id = uuid();

    $schedules[] = [
        'id' => $id,
        'campaign_id' => $campaignId,
        'day_of_week' => $dayOfWeek,
        'time' => $time,
        'timezone' => $timezone,
        'enabled' => true,
        'last_run_at' => null,
        'created_at' => now_local(),
    ];

    save_schedules($schedules);
    return $id;
}

function update_schedule_status(string $id, bool $enabled): void
{
    $schedules = get_schedules();
    foreach ($schedules as &$schedule) {
        if ($schedule['id'] === $id) {
            $schedule['enabled'] = $enabled;
            $schedule['updated_at'] = now_local();
            break;
        }
    }
    unset($schedule);
    save_schedules($schedules);
}
