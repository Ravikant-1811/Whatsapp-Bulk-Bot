<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/actions.php';

$pageTitle = 'Reports';
$activePage = 'reports';

include __DIR__ . '/partials/header.php';

$sent = $stats['sent'];
$failed = $stats['failed'];
$totalProcessed = max(1, $sent + $failed);
$successRate = round(($sent / $totalProcessed) * 100, 1);
?>

<header class="page-header">
    <div>
        <div class="kicker">Analytics</div>
        <h1>Reports</h1>
        <p>High-level performance stats.</p>
    </div>
</header>

<?php include __DIR__ . '/partials/notice.php'; ?>

<section class="grid">
    <div class="card">
        <h2>Summary</h2>
        <div class="list">
            <div class="list-row"><span>Total contacts</span><strong><?php echo count($contacts); ?></strong></div>
            <div class="list-row"><span>Campaigns</span><strong><?php echo count($campaigns); ?></strong></div>
            <div class="list-row"><span>Schedules</span><strong><?php echo count($schedules); ?></strong></div>
            <div class="list-row"><span>Sent today</span><strong><?php echo (int)($daily['sent'] ?? 0); ?></strong></div>
        </div>
    </div>
    <div class="card">
        <h2>Delivery Health</h2>
        <div class="list">
            <div class="list-row"><span>Sent</span><strong><?php echo $sent; ?></strong></div>
            <div class="list-row"><span>Failed</span><strong><?php echo $failed; ?></strong></div>
            <div class="list-row"><span>Success rate</span><strong><?php echo $successRate; ?>%</strong></div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
