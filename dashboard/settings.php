<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/actions.php';

$pageTitle = 'Settings';
$activePage = 'settings';

include __DIR__ . '/partials/header.php';
?>

<header class="page-header">
    <div>
        <div class="kicker">Configuration</div>
        <h1>Settings</h1>
        <p>Store API keys and tune delivery settings.</p>
    </div>
</header>

<?php include __DIR__ . '/partials/notice.php'; ?>

<section class="grid">
    <div class="card">
        <h2>API & Limits</h2>
        <p class="muted">Keep daily limits and delays conservative.</p>
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
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
