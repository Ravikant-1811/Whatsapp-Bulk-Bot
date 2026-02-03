<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/actions.php';

$pageTitle = 'Queue';
$activePage = 'queue';

include __DIR__ . '/partials/header.php';
?>

<header class="page-header">
    <div>
        <div class="kicker">Delivery</div>
        <h1>Queue</h1>
        <p>Track pending and sent messages.</p>
    </div>
</header>

<?php include __DIR__ . '/partials/notice.php'; ?>

<section class="grid">
    <div class="card">
        <h2>Queue Stats</h2>
        <div class="list">
            <div class="list-row"><span>Pending</span><strong><?php echo $stats['pending']; ?></strong></div>
            <div class="list-row"><span>Sent</span><strong><?php echo $stats['sent']; ?></strong></div>
            <div class="list-row"><span>Failed</span><strong><?php echo $stats['failed']; ?></strong></div>
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

<?php include __DIR__ . '/partials/footer.php'; ?>
