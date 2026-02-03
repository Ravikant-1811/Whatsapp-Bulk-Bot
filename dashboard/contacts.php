<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/actions.php';

$pageTitle = 'Contacts';
$activePage = 'contacts';

include __DIR__ . '/partials/header.php';
?>

<header class="page-header">
    <div>
        <div class="kicker">Audience</div>
        <h1>Contacts</h1>
        <p>Upload CSV or add contacts manually.</p>
    </div>
</header>

<?php include __DIR__ . '/partials/notice.php'; ?>

<section class="grid">
    <div class="card">
        <h2>Upload CSV</h2>
        <p class="muted">CSV columns: name, number</p>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_contacts" />
            <input type="file" name="contacts_csv" accept=".csv" />
            <button class="primary" type="submit">Upload Contacts</button>
        </form>
    </div>
    <div class="card">
        <h2>Manual Input</h2>
        <p class="muted">Add one contact at a time.</p>
        <form method="post">
            <input type="hidden" name="action" value="add_contact" />
            <label>Name</label>
            <input type="text" name="contact_name" placeholder="Name (optional)" />
            <label>Number</label>
            <input type="text" name="contact_number" placeholder="WhatsApp number" required />
            <button class="primary" type="submit">Add Contact</button>
        </form>
    </div>
    <div class="card">
        <h2>Bulk Manual Input</h2>
        <p class="muted">One contact per line.</p>
        <form method="post">
            <input type="hidden" name="action" value="bulk_contacts" />
            <textarea name="bulk_contacts" rows="6" placeholder="Name,Number&#10;Name,Number&#10;+919xxxxxxxxx"></textarea>
            <div class="hint">Use "name,number" or just "number".</div>
            <button class="primary" type="submit">Add Bulk Contacts</button>
        </form>
    </div>
    <div class="card">
        <h2>Total Contacts</h2>
        <div class="list">
            <div class="list-row">
                <span>Contacts</span>
                <strong><?php echo count($contacts); ?></strong>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
