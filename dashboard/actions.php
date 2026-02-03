<?php

declare(strict_types=1);

$notice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        $settings = get_settings();
        $settings['api_key'] = trim($_POST['api_key'] ?? $settings['api_key']);
        $settings['api_url'] = trim($_POST['api_url'] ?? $settings['api_url']);
        $settings['min_delay_seconds'] = (int)($_POST['min_delay_seconds'] ?? $settings['min_delay_seconds']);
        $settings['max_delay_seconds'] = (int)($_POST['max_delay_seconds'] ?? $settings['max_delay_seconds']);
        $settings['send_window_start'] = trim($_POST['send_window_start'] ?? $settings['send_window_start']);
        $settings['send_window_end'] = trim($_POST['send_window_end'] ?? $settings['send_window_end']);
        $settings['daily_limit'] = (int)($_POST['daily_limit'] ?? $settings['daily_limit']);
        save_settings($settings);
        $notice = 'Settings saved.';
    }

    if ($action === 'upload_contacts' && isset($_FILES['contacts_csv'])) {
        $file = $_FILES['contacts_csv'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $handle = fopen($file['tmp_name'], 'r');
            $newContacts = [];
            $rowIndex = 0;
            if ($handle) {
                while (($row = fgetcsv($handle)) !== false) {
                    $rowIndex++;
                    if ($rowIndex === 1) {
                        $joined = strtolower(implode(',', $row));
                        if (str_contains($joined, 'name') || str_contains($joined, 'number')) {
                            continue;
                        }
                    }
                    $name = trim($row[0] ?? '');
                    $number = trim($row[1] ?? '');
                    $number = ltrim($number, "\xEF\xBB\xBF");
                    if ($number !== '') {
                        $newContacts[] = ['name' => $name, 'number' => $number];
                    }
                }
                fclose($handle);
            }
            $count = add_contacts($newContacts);
            $notice = "{$count} contacts added from CSV.";
        }
    }

    if ($action === 'add_contact') {
        $name = trim($_POST['contact_name'] ?? '');
        $number = trim($_POST['contact_number'] ?? '');
        if ($number !== '') {
            $count = add_contacts([['name' => $name, 'number' => $number]]);
            $notice = "{$count} contact added.";
        }
    }

    if ($action === 'bulk_contacts') {
        $raw = trim($_POST['bulk_contacts'] ?? '');
        if ($raw !== '') {
            $lines = preg_split('/\r\n|\r|\n/', $raw);
            $newContacts = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $parts = array_map('trim', explode(',', $line, 2));
                $name = $parts[0] ?? '';
                $number = $parts[1] ?? '';
                if ($number === '') {
                    $number = $name;
                    $name = '';
                }
                if ($number !== '') {
                    $newContacts[] = ['name' => $name, 'number' => $number];
                }
            }
            $count = add_contacts($newContacts);
            $notice = "{$count} contacts added manually.";
        }
    }

    if ($action === 'create_campaign') {
        $name = trim($_POST['campaign_name'] ?? '');
        $template = trim($_POST['campaign_template'] ?? '');
        $templateId = $_POST['template_id'] ?? '';
        if ($template === '' && $templateId !== '') {
            foreach (get_templates() as $storedTemplate) {
                if ($storedTemplate['id'] === $templateId) {
                    $template = $storedTemplate['content'];
                    break;
                }
            }
        }
        $contactIds = $_POST['contact_ids'] ?? [];
        if ($name !== '' && $template !== '') {
            if (count($contactIds) === 0) {
                $contacts = get_contacts();
                $contactIds = array_map(fn($c) => $c['id'], $contacts);
            }
            create_campaign($name, $template, $contactIds);
            $notice = 'Campaign created.';
        }
    }

    if ($action === 'toggle_campaign') {
        $id = $_POST['campaign_id'] ?? '';
        $enabled = ($_POST['enabled'] ?? '0') === '1';
        update_campaign_status($id, $enabled);
        $notice = 'Campaign updated.';
    }

    if ($action === 'run_now') {
        $campaignId = $_POST['campaign_id'] ?? '';
        $campaigns = get_campaigns();
        $contacts = get_contacts();
        $campaign = null;
        foreach ($campaigns as $item) {
            if ($item['id'] === $campaignId) {
                $campaign = $item;
                break;
            }
        }
        if ($campaign) {
            $contactMap = [];
            foreach ($contacts as $contact) {
                $contactMap[$contact['id']] = $contact;
            }
            $campaignContacts = [];
            foreach ($campaign['contact_ids'] as $contactId) {
                if (isset($contactMap[$contactId])) {
                    $campaignContacts[] = $contactMap[$contactId];
                }
            }
            $count = enqueue_messages($campaign['id'], $campaignContacts, $campaign['template']);
            $notice = "Queued {$count} messages.";
        }
    }

    if ($action === 'create_schedule') {
        $campaignId = $_POST['schedule_campaign_id'] ?? '';
        $day = $_POST['day_of_week'] ?? 'mon';
        $time = $_POST['time'] ?? '09:00';
        $timezone = $_POST['timezone'] ?? date_default_timezone_get();
        if ($campaignId !== '') {
            create_schedule($campaignId, $day, $time, $timezone);
            $notice = 'Schedule created.';
        }
    }

    if ($action === 'toggle_schedule') {
        $id = $_POST['schedule_id'] ?? '';
        $enabled = ($_POST['enabled'] ?? '0') === '1';
        update_schedule_status($id, $enabled);
        $notice = 'Schedule updated.';
    }

    if ($action === 'create_template') {
        $name = trim($_POST['template_name'] ?? '');
        $content = trim($_POST['template_content'] ?? '');
        if ($name !== '' && $content !== '') {
            add_template($name, $content);
            $notice = 'Template saved.';
        }
    }

    if ($action === 'delete_template') {
        $id = $_POST['template_id'] ?? '';
        if ($id !== '') {
            delete_template($id);
            $notice = 'Template deleted.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
}
