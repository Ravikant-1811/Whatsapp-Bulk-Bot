<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/actions.php';

$pageTitle = 'Schedules';
$activePage = 'schedules';

include __DIR__ . '/partials/header.php';

$campaignMap = [];
foreach ($campaigns as $campaign) {
    $campaignMap[$campaign['id']] = $campaign['name'];
}
?>

<header class="page-header">
    <div>
        <div class="kicker">Automation</div>
        <h1>Schedules</h1>
        <p>Set weekly automation for campaigns.</p>
    </div>
</header>

<?php include __DIR__ . '/partials/notice.php'; ?>

<section class="grid">
    <div class="card">
        <h2>Create Schedule</h2>
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
    </div>

    <div class="card">
        <h2>Active Schedules</h2>
        <div class="list">
            <?php foreach ($schedules as $schedule): ?>
                <div class="list-row">
                        <div>
                            <strong><?php echo safe($schedule['day_of_week']); ?> @ <?php echo safe($schedule['time']); ?></strong>
                            <div class="hint">Campaign: <?php echo safe($campaignMap[$schedule['campaign_id']] ?? 'Unknown'); ?></div>
                        </div>
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

<?php include __DIR__ . '/partials/footer.php'; ?>
