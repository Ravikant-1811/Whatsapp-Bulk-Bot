<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/actions.php';

$pageTitle = 'Safety';
$activePage = 'safety';

include __DIR__ . '/partials/header.php';
?>

<header class="page-header">
    <div>
        <div class="kicker">Compliance</div>
        <h1>Safety & Compliance</h1>
        <p>Use opt-in lists and conservative limits to reduce risk.</p>
    </div>
</header>

<?php include __DIR__ . '/partials/notice.php'; ?>

<section class="grid">
    <div class="card">
        <h2>Safety Rules</h2>
        <div class="list">
            <div class="list-row"><span>Randomized delays</span><strong><?php echo (int)$settings['min_delay_seconds']; ?>-<?php echo (int)$settings['max_delay_seconds']; ?>s</strong></div>
            <div class="list-row"><span>Send window</span><strong><?php echo safe($settings['send_window_start']); ?>-<?php echo safe($settings['send_window_end']); ?></strong></div>
            <div class="list-row"><span>Daily cap</span><strong><?php echo (int)$settings['daily_limit']; ?></strong></div>
        </div>
    </div>
    <div class="card">
        <h2>Best Practices</h2>
        <ul class="plain-list">
            <li>Send only to contacts who have opted in.</li>
            <li>Keep message frequency consistent and respectful.</li>
            <li>Review failures and remove invalid numbers.</li>
            <li>Warm up new accounts gradually.</li>
        </ul>
    </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
