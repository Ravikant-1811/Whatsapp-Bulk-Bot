<?php

declare(strict_types=1);

require_once __DIR__ . '/storage.php';

function contacts_path(): string
{
    return __DIR__ . '/../data/contacts.json';
}

function get_contacts(): array
{
    return read_json(contacts_path(), []);
}

function save_contacts(array $contacts): void
{
    write_json(contacts_path(), $contacts);
}

function add_contacts(array $newContacts): int
{
    $contacts = get_contacts();
    $count = 0;

    foreach ($newContacts as $contact) {
        if (empty($contact['number'])) {
            continue;
        }
        $contacts[] = [
            'id' => uuid(),
            'name' => $contact['name'] ?? '',
            'number' => $contact['number'],
            'added_at' => now_local(),
        ];
        $count++;
    }

    save_contacts($contacts);
    return $count;
}
