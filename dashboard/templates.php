<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/actions.php';

$pageTitle = 'Templates';
$activePage = 'templates';

include __DIR__ . '/partials/header.php';
?>

<header class="page-header">
    <div>
        <div class="kicker">Library</div>
        <h1>Templates</h1>
        <p>Save reusable messages for faster campaign creation.</p>
    </div>
</header>

<?php include __DIR__ . '/partials/notice.php'; ?>

<section class="grid">
    <div class="card">
        <h2>Create Template</h2>
        <form method="post">
            <input type="hidden" name="action" value="create_template" />
            <label>Template Name</label>
            <input type="text" name="template_name" placeholder="Follow-up Script" required />
            <label>Template Content</label>
            <textarea name="template_content" rows="6" placeholder="Hi [[fullname]], ..." required></textarea>
            <button class="primary" type="submit">Save Template</button>
        </form>
    </div>
    <div class="card">
        <h2>Saved Templates</h2>
        <div class="list">
            <?php foreach ($templates as $template): ?>
                <div class="list-row">
                    <div>
                        <strong><?php echo safe($template['name']); ?></strong>
                        <div class="hint"><?php echo safe(substr($template['content'], 0, 60)); ?>...</div>
                    </div>
                    <form method="post">
                        <input type="hidden" name="action" value="delete_template" />
                        <input type="hidden" name="template_id" value="<?php echo safe($template['id']); ?>" />
                        <button class="ghost" type="submit">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
