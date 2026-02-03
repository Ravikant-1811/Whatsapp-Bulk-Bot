<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/actions.php';

$pageTitle = 'Overview';
$activePage = 'overview';

include __DIR__ . '/partials/header.php';
?>

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

<?php include __DIR__ . '/partials/notice.php'; ?>

<section class="grid">
    <div class="card">
        <h2>Today</h2>
        <p class="muted">Quick performance snapshot.</p>
        <div class="list">
            <div class="list-row"><span>Sent today</span><strong><?php echo (int)($daily['sent'] ?? 0); ?></strong></div>
            <div class="list-row"><span>Contacts</span><strong><?php echo count($contacts); ?></strong></div>
            <div class="list-row"><span>Active campaigns</span><strong><?php echo count(array_filter($campaigns, fn($c) => $c['enabled'] ?? true)); ?></strong></div>
        </div>
    </div>
    <div class="card">
        <h2>Quick Actions</h2>
        <p class="muted">Jump straight into the most common tasks.</p>
        <div class="quick-actions">
            <a class="quick-link" href="contacts.php">Add Contacts</a>
            <a class="quick-link" href="campaigns.php">Create Campaign</a>
            <a class="quick-link" href="schedules.php">Set Weekly Schedule</a>
            <a class="quick-link" href="queue.php">Review Queue</a>
        </div>
    </div>
    <div class="card">
        <h2>Safety Guardrails</h2>
        <p class="muted">Current throttling rules in place.</p>
        <div class="list">
            <div class="list-row"><span>Randomized delays</span><strong><?php echo (int)$settings['min_delay_seconds']; ?>-<?php echo (int)$settings['max_delay_seconds']; ?>s</strong></div>
            <div class="list-row"><span>Send window</span><strong><?php echo safe($settings['send_window_start']); ?>-<?php echo safe($settings['send_window_end']); ?></strong></div>
            <div class="list-row"><span>Daily cap</span><strong><?php echo (int)$settings['daily_limit']; ?></strong></div>
        </div>
    </div>
</section>

<section class="grid">
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
