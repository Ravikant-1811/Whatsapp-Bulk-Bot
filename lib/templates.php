<?php

declare(strict_types=1);

require_once __DIR__ . '/storage.php';

function templates_path(): string
{
    return __DIR__ . '/../data/templates.json';
}

function get_templates(): array
{
    return read_json(templates_path(), []);
}

function save_templates(array $templates): void
{
    write_json(templates_path(), $templates);
}

function add_template(string $name, string $content): string
{
    $templates = get_templates();
    $id = uuid();

    $templates[] = [
        'id' => $id,
        'name' => $name,
        'content' => $content,
        'created_at' => now_local(),
    ];

    save_templates($templates);
    return $id;
}

function delete_template(string $id): void
{
    $templates = get_templates();
    $templates = array_values(array_filter($templates, fn($t) => $t['id'] !== $id));
    save_templates($templates);
}
