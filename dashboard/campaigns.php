<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/actions.php';

$pageTitle = 'Campaigns';
$activePage = 'campaigns';

include __DIR__ . '/partials/header.php';
?>

<header class="page-header">
    <div>
        <div class="kicker">Messaging</div>
        <h1>Campaigns</h1>
        <p>Create and manage messaging campaigns.</p>
    </div>
</header>

<?php include __DIR__ . '/partials/notice.php'; ?>

<section class="grid">
    <div class="card">
        <h2>Create Campaign</h2>
        <p class="muted">Choose contacts and craft a compliant message template.</p>
        <form method="post">
            <input type="hidden" name="action" value="create_campaign" />
            <label>Campaign Name</label>
            <input type="text" name="campaign_name" placeholder="Weekly Follow-up" required />

            <label>Template Library (optional)</label>
            <select name="template_id">
                <option value="">-- Select template --</option>
                <?php foreach ($templates as $template): ?>
                    <option value="<?php echo safe($template['id']); ?>"><?php echo safe($template['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <div class="hint">If you select a template, you can leave the textarea empty.</div>

            <label>Message Template</label>
            <textarea name="campaign_template" rows="6" placeholder="Hi [[fullname]], ..."></textarea>

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
        <h2>Campaign List</h2>
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
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
