<?php

declare(strict_types=1);

require_once __DIR__ . '/storage.php';

function campaigns_path(): string
{
    return __DIR__ . '/../data/campaigns.json';
}

function get_campaigns(): array
{
    return read_json(campaigns_path(), []);
}

function save_campaigns(array $campaigns): void
{
    write_json(campaigns_path(), $campaigns);
}

function create_campaign(string $name, string $template, array $contactIds): string
{
    $campaigns = get_campaigns();
    $id = uuid();

    $campaigns[] = [
        'id' => $id,
        'name' => $name,
        'template' => $template,
        'contact_ids' => $contactIds,
        'enabled' => true,
        'created_at' => now_local(),
    ];

    save_campaigns($campaigns);
    return $id;
}

function update_campaign_status(string $id, bool $enabled): void
{
    $campaigns = get_campaigns();
    foreach ($campaigns as &$campaign) {
        if ($campaign['id'] === $id) {
            $campaign['enabled'] = $enabled;
            $campaign['updated_at'] = now_local();
            break;
        }
    }
    unset($campaign);
    save_campaigns($campaigns);
}
