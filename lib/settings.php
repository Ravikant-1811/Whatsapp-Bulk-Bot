<?php

declare(strict_types=1);

require_once __DIR__ . '/storage.php';

function settings_path(): string
{
    return __DIR__ . '/../data/settings.json';
}

function get_settings(): array
{
    $defaults = require __DIR__ . '/config.php';
    $stored = read_json(settings_path(), []);
    return array_merge($defaults, $stored);
}

function save_settings(array $settings): void
{
    write_json(settings_path(), $settings);
}
