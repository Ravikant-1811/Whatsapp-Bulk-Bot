<?php
$navItems = [
    'overview' => ['label' => 'Overview', 'href' => 'index.php'],
    'settings' => ['label' => 'Settings', 'href' => 'settings.php'],
    'contacts' => ['label' => 'Contacts', 'href' => 'contacts.php'],
    'campaigns' => ['label' => 'Campaigns', 'href' => 'campaigns.php'],
    'schedules' => ['label' => 'Schedules', 'href' => 'schedules.php'],
    'queue' => ['label' => 'Queue', 'href' => 'queue.php'],
    'safety' => ['label' => 'Safety', 'href' => 'safety.php'],
    'templates' => ['label' => 'Templates', 'href' => 'templates.php'],
    'reports' => ['label' => 'Reports', 'href' => 'reports.php'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo safe($pageTitle); ?> · WhatsApp Bulk Bot</title>
    <link rel="stylesheet" href="assets/style.css" />
</head>
<body>
    <div class="shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="logo">WB</div>
                <div>
                    <div class="brand-title">WhatsApp Bulk Bot</div>
                    <div class="brand-subtitle">Automation Suite</div>
                </div>
            </div>
            <nav class="nav">
                <?php foreach ($navItems as $key => $item): ?>
                    <a class="nav-link <?php echo $activePage === $key ? 'active' : ''; ?>" href="<?php echo $item['href']; ?>">
                        <?php echo $item['label']; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-footer">
                <div class="tiny">Status</div>
                <div class="status-row">
                    <span>Pending</span>
                    <strong><?php echo $stats['pending']; ?></strong>
                </div>
                <div class="status-row">
                    <span>Sent</span>
                    <strong><?php echo $stats['sent']; ?></strong>
                </div>
                <div class="status-row">
                    <span>Failed</span>
                    <strong><?php echo $stats['failed']; ?></strong>
                </div>
            </div>
        </aside>
        <div class="page">
