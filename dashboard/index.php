<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/settings.php';
require_once __DIR__ . '/../lib/contacts.php';
require_once __DIR__ . '/../lib/campaigns.php';
require_once __DIR__ . '/../lib/schedules.php';
require_once __DIR__ . '/../lib/queue.php';

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
}

$settings = get_settings();
$contacts = get_contacts();
$campaigns = get_campaigns();
$schedules = get_schedules();
$queue = get_queue();
$stats = queue_stats();

function safe(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$recentQueue = array_slice(array_reverse($queue), 0, 20);

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>WhatsApp Bulk Bot Dashboard</title>
    <link rel="stylesheet" href="assets/style.css" />
</head>
<body>
    <div class="page">
        <header class="hero">
            <div>
                <div class="kicker">WhatsApp Bulk Bot</div>
                <h1>Automation Dashboard</h1>
                <p>Manage campaigns, schedules, and compliant sending with safety-first throttling.</p>
            </div>
            <div class="hero-card">
                <div class="stat">
                    <div class="stat-label">Pending</div>
                    <div class="stat-value"><?php echo $stats['pending']; ?></div>
                </div>
                <div class="stat">
                    <div class="stat-label">Sent</div>
                    <div class="stat-value"><?php echo $stats['sent']; ?></div>
                </div>
                <div class="stat">
                    <div class="stat-label">Failed</div>
                    <div class="stat-value"><?php echo $stats['failed']; ?></div>
                </div>
            </div>
        </header>

        <?php if ($notice): ?>
            <div class="notice"><?php echo safe($notice); ?></div>
        <?php endif; ?>

        <section class="grid">
            <div class="card">
                <h2>Settings</h2>
                <p class="muted">Store API details and tune rate limits for compliant sending.</p>
                <form method="post">
                    <input type="hidden" name="action" value="save_settings" />
                    <label>API Key</label>
                    <input type="password" name="api_key" value="<?php echo safe($settings['api_key']); ?>" placeholder="WASENDER API key" />

                    <label>API URL</label>
                    <input type="text" name="api_url" value="<?php echo safe($settings['api_url']); ?>" />

                    <div class="row">
                        <div>
                            <label>Min Delay (sec)</label>
                            <input type="number" name="min_delay_seconds" value="<?php echo (int)$settings['min_delay_seconds']; ?>" />
                        </div>
                        <div>
                            <label>Max Delay (sec)</label>
                            <input type="number" name="max_delay_seconds" value="<?php echo (int)$settings['max_delay_seconds']; ?>" />
                        </div>
                    </div>

                    <div class="row">
                        <div>
                            <label>Send Window Start</label>
                            <input type="time" name="send_window_start" value="<?php echo safe($settings['send_window_start']); ?>" />
                        </div>
                        <div>
                            <label>Send Window End</label>
                            <input type="time" name="send_window_end" value="<?php echo safe($settings['send_window_end']); ?>" />
                        </div>
                    </div>

                    <label>Daily Limit</label>
                    <input type="number" name="daily_limit" value="<?php echo (int)$settings['daily_limit']; ?>" />

                    <button class="primary" type="submit">Save Settings</button>
                </form>
            </div>

            <div class="card">
                <h2>Contacts</h2>
                <p class="muted">Upload CSV with columns: name, number</p>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_contacts" />
                    <input type="file" name="contacts_csv" accept=".csv" />
                    <button class="primary" type="submit">Upload Contacts</button>
                </form>
                <div class="divider"></div>
                <form method="post">
                    <input type="hidden" name="action" value="add_contact" />
                    <label>Manual Input</label>
                    <input type="text" name="contact_name" placeholder="Name (optional)" />
                    <input type="text" name="contact_number" placeholder="WhatsApp number" required />
                    <button class="primary" type="submit">Add Contact</button>
                </form>
                <div class="divider"></div>
                <form method="post">
                    <input type="hidden" name="action" value="bulk_contacts" />
                    <label>Bulk Manual Input</label>
                    <textarea name="bulk_contacts" rows="5" placeholder="Name,Number&#10;Name,Number&#10;+919xxxxxxxxx"></textarea>
                    <div class="hint">One per line. Use "name,number" or just "number".</div>
                    <button class="primary" type="submit">Add Bulk Contacts</button>
                </form>
                <div class="list">
                    <div class="list-row">
                        <span>Total contacts</span>
                        <strong><?php echo count($contacts); ?></strong>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>Create Campaign</h2>
                <p class="muted">Choose contacts and craft a compliant message template.</p>
                <form method="post">
                    <input type="hidden" name="action" value="create_campaign" />
                    <label>Campaign Name</label>
                    <input type="text" name="campaign_name" placeholder="Weekly Follow-up" required />

                    <label>Message Template</label>
                    <textarea name="campaign_template" rows="6" placeholder="Hi [[fullname]], ..." required></textarea>

                    <label>Contacts (optional)</label>
                    <select name="contact_ids[]" multiple>
                        <?php foreach ($contacts as $contact): ?>
                            <option value="<?php echo safe($contact['id']); ?>">
                                <?php echo safe($contact['name'] ?: $contact['number']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="hint">Leave empty to include all contacts.</div>

                    <button class="primary" type="submit">Create Campaign</button>
                </form>
            </div>

            <div class="card">
                <h2>Schedules</h2>
                <p class="muted">Schedule weekly sends. Run `scheduler.php` every minute.</p>
                <form method="post">
                    <input type="hidden" name="action" value="create_schedule" />
                    <label>Campaign</label>
                    <select name="schedule_campaign_id" required>
                        <?php foreach ($campaigns as $campaign): ?>
                            <option value="<?php echo safe($campaign['id']); ?>"><?php echo safe($campaign['name']); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <div class="row">
                        <div>
                            <label>Day</label>
                            <select name="day_of_week">
                                <option value="mon">Monday</option>
                                <option value="tue">Tuesday</option>
                                <option value="wed">Wednesday</option>
                                <option value="thu">Thursday</option>
                                <option value="fri">Friday</option>
                                <option value="sat">Saturday</option>
                                <option value="sun">Sunday</option>
                            </select>
                        </div>
                        <div>
                            <label>Time</label>
                            <input type="time" name="time" value="09:00" />
                        </div>
                    </div>

                    <label>Timezone</label>
                    <input type="text" name="timezone" value="<?php echo safe(date_default_timezone_get()); ?>" />

                    <button class="primary" type="submit">Create Schedule</button>
                </form>

                <div class="list">
                    <?php foreach ($schedules as $schedule): ?>
                        <div class="list-row">
                            <span><?php echo safe($schedule['day_of_week']); ?> @ <?php echo safe($schedule['time']); ?></span>
                            <form method="post">
                                <input type="hidden" name="action" value="toggle_schedule" />
                                <input type="hidden" name="schedule_id" value="<?php echo safe($schedule['id']); ?>" />
                                <input type="hidden" name="enabled" value="<?php echo $schedule['enabled'] ? '0' : '1'; ?>" />
                                <button class="ghost" type="submit"><?php echo $schedule['enabled'] ? 'Disable' : 'Enable'; ?></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="grid">
            <div class="card">
                <h2>Campaigns</h2>
                <div class="list">
                    <?php foreach ($campaigns as $campaign): ?>
                        <div class="list-row">
                            <div>
                                <strong><?php echo safe($campaign['name']); ?></strong>
                                <div class="hint"><?php echo count($campaign['contact_ids']); ?> contacts</div>
                            </div>
                            <div class="actions">
                                <form method="post">
                                    <input type="hidden" name="action" value="run_now" />
                                    <input type="hidden" name="campaign_id" value="<?php echo safe($campaign['id']); ?>" />
                                    <button class="ghost" type="submit">Run Now</button>
                                </form>
                                <form method="post">
                                    <input type="hidden" name="action" value="toggle_campaign" />
                                    <input type="hidden" name="campaign_id" value="<?php echo safe($campaign['id']); ?>" />
                                    <input type="hidden" name="enabled" value="<?php echo $campaign['enabled'] ? '0' : '1'; ?>" />
                                    <button class="ghost" type="submit"><?php echo $campaign['enabled'] ? 'Disable' : 'Enable'; ?></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card">
                <h2>Recent Queue</h2>
                <div class="list">
                    <?php foreach ($recentQueue as $job): ?>
                        <div class="list-row">
                            <div>
                                <strong><?php echo safe($job['to']); ?></strong>
                                <div class="hint"><?php echo safe($job['status']); ?> · <?php echo safe($job['added_at'] ?? ''); ?></div>
                            </div>
                            <div class="pill <?php echo safe($job['status']); ?>"><?php echo safe($job['status']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="card">
            <h2>Compliance & Safety</h2>
            <p class="muted">To reduce account risk and respect platform rules, use opt-in contacts, keep daily limits conservative, and avoid spamming.</p>
            <div class="list">
                <div class="list-row"><span>Randomized delays</span><strong><?php echo (int)$settings['min_delay_seconds']; ?>-<?php echo (int)$settings['max_delay_seconds']; ?>s</strong></div>
                <div class="list-row"><span>Send window</span><strong><?php echo safe($settings['send_window_start']); ?>-<?php echo safe($settings['send_window_end']); ?></strong></div>
                <div class="list-row"><span>Daily cap</span><strong><?php echo (int)$settings['daily_limit']; ?></strong></div>
            </div>
        </section>
    </div>
</body>
</html>
