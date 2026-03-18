<?php
// ============================================================
//  alter.php  –  Admin management page
// ============================================================
require_once __DIR__ . '/db.php';

// --- Simple HTTP Basic Auth ----------------------------------
if (!isset($_SERVER['PHP_AUTH_USER'])
    || $_SERVER['PHP_AUTH_USER'] !== ADMIN_USER
    || $_SERVER['PHP_AUTH_PW']   !== ADMIN_PASS
) {
    header('WWW-Authenticate: Basic realm="Lobby Admin"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Access denied.';
    exit;
}

// Ensure photo directory exists
if (!is_dir(PHOTO_DIR)) {
    mkdir(PHOTO_DIR, 0755, true);
}

$action  = $_POST['action'] ?? $_GET['action'] ?? '';
$message = '';
$error   = '';

// ---- Handle form submissions --------------------------------

if ($action === 'save') {
    $order = (int)($_POST['display_order'] ?? 0);
    if ($order <= 0) {
        $error = 'Display Order must be a positive integer.';
    } else {
        // Handle optional photo upload
        $photo = $_POST['existing_photo'] ?? '';
        if (!empty($_FILES['photo']['tmp_name'])) {
            $file    = $_FILES['photo'];
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $mime    = mime_content_type($file['tmp_name']);
            if (!in_array($mime, $allowed, true)) {
                $error = 'Photo must be a JPEG, PNG, GIF, or WebP image.';
            } else {
                $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'emp_' . $order . '_' . time() . '.' . strtolower($ext);
                if (move_uploaded_file($file['tmp_name'], PHOTO_DIR . $filename)) {
                    // Remove old photo if different
                    if (!empty($photo) && $photo !== $filename && file_exists(PHOTO_DIR . $photo)) {
                        unlink(PHOTO_DIR . $photo);
                    }
                    $photo = $filename;
                } else {
                    $error = 'Photo upload failed. Check directory permissions.';
                }
            }
        }

        if (!$error) {
            upsert_employee([
                'display_order' => $order,
                'name'          => trim($_POST['name']  ?? ''),
                'photo'         => $photo,
                'phone'         => trim($_POST['phone'] ?? ''),
                'email'         => trim($_POST['email'] ?? ''),
                'active'        => $_POST['active'] ?? 0,
            ]);
            $message = 'Employee record saved.';
        }
    }
}

if ($action === 'delete') {
    $order = (int)($_GET['order'] ?? 0);
    if ($order > 0) {
        $emp = get_employee($order);
        if ($emp && !empty($emp['photo']) && file_exists(PHOTO_DIR . $emp['photo'])) {
            unlink(PHOTO_DIR . $emp['photo']);
        }
        delete_employee($order);
        $message = 'Employee deleted.';
    }
}

$employees   = get_all_employees();
$editing     = null;
$edit_order  = (int)($_GET['edit'] ?? 0);
if ($edit_order > 0) {
    $editing = get_employee($edit_order);
}
// "New" form pre-fill: suggest the next available order number
$next_order = 1;
if (!empty($employees)) {
    $orders = array_column($employees, 'display_order');
    $next_order = max($orders) + 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lobby Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-body">

<div class="admin-wrap">
    <header class="admin-header">
        <h1>Lobby Admin</h1>
        <a href="index.php" class="btn btn-secondary btn-sm" target="_blank">View Front End ↗</a>
    </header>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Employee list -->
    <section class="admin-section">
        <div class="section-header">
            <h2>Staff Members</h2>
            <a href="alter.php?action=new" class="btn btn-primary btn-sm">+ Add New</a>
        </div>

        <?php if (empty($employees)): ?>
            <p class="empty-state">No employees yet. Add one using the button above.</p>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Visible</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($employees as $emp): ?>
            <tr class="<?= $emp['active'] ? '' : 'row-inactive' ?>">
                <td><?= (int)$emp['display_order'] ?></td>
                <td>
                    <?php if (!empty($emp['photo']) && file_exists(PHOTO_DIR . $emp['photo'])): ?>
                        <img class="table-thumb"
                             src="photos/<?= htmlspecialchars($emp['photo'], ENT_QUOTES) ?>"
                             alt="">
                    <?php else: ?>
                        <div class="table-thumb-placeholder">
                            <?= htmlspecialchars(mb_substr($emp['name'], 0, 1), ENT_QUOTES) ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($emp['name']) ?></td>
                <td><?= htmlspecialchars($emp['phone']) ?></td>
                <td><?= htmlspecialchars($emp['email']) ?></td>
                <td><?= $emp['active'] ? '✓' : '—' ?></td>
                <td class="action-cell">
                    <a href="alter.php?edit=<?= (int)$emp['display_order'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                    <a href="alter.php?action=delete&order=<?= (int)$emp['display_order'] ?>"
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Delete this employee?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>

    <!-- Add / Edit form -->
    <?php
    $form_title  = 'Add New Employee';
    $form_order  = $next_order;
    $form_name   = '';
    $form_photo  = '';
    $form_phone  = '';
    $form_email  = '';
    $form_active = 1;

    if ($editing) {
        $form_title  = 'Edit Employee';
        $form_order  = (int)$editing['display_order'];
        $form_name   = $editing['name'];
        $form_photo  = $editing['photo'];
        $form_phone  = $editing['phone'];
        $form_email  = $editing['email'];
        $form_active = (int)$editing['active'];
    }
    $show_form = ($editing !== null || isset($_GET['action']) && $_GET['action'] === 'new');
    ?>

    <?php if ($show_form): ?>
    <section class="admin-section">
        <h2><?= $form_title ?></h2>
        <form method="POST" action="alter.php" enctype="multipart/form-data" class="employee-form" novalidate>
            <input type="hidden" name="action"         value="save">
            <input type="hidden" name="existing_photo" value="<?= htmlspecialchars($form_photo, ENT_QUOTES) ?>">

            <div class="form-row">
                <label for="f-order">Display Order <span class="req">*</span></label>
                <input id="f-order" name="display_order" type="number" min="1"
                       value="<?= $form_order ?>" required
                       <?= $editing ? 'readonly' : '' ?>>
                <small>This is also the unique ID. Cannot be changed after creation.</small>
            </div>

            <div class="form-row">
                <label for="f-name">Name <span class="req">*</span></label>
                <input id="f-name" name="name" type="text" maxlength="100"
                       value="<?= htmlspecialchars($form_name, ENT_QUOTES) ?>" required>
            </div>

            <div class="form-row">
                <label for="f-photo">Photo</label>
                <?php if (!empty($form_photo) && file_exists(PHOTO_DIR . $form_photo)): ?>
                    <img class="form-photo-preview"
                         src="photos/<?= htmlspecialchars($form_photo, ENT_QUOTES) ?>" alt="Current photo">
                    <small>Upload a new file to replace, or leave blank to keep current.</small>
                <?php endif; ?>
                <input id="f-photo" name="photo" type="file" accept="image/*">
            </div>

            <div class="form-row">
                <label for="f-phone">Cell Phone</label>
                <input id="f-phone" name="phone" type="tel" maxlength="20"
                       placeholder="+10005550100"
                       value="<?= htmlspecialchars($form_phone, ENT_QUOTES) ?>">
                <small>E.164 format recommended, e.g. +10005550100</small>
            </div>

            <div class="form-row">
                <label for="f-email">Email Address</label>
                <input id="f-email" name="email" type="email" maxlength="120"
                       value="<?= htmlspecialchars($form_email, ENT_QUOTES) ?>">
            </div>

<!-- Not yet implemented
            <div class="form-row form-row-check">
                <label class="check-label">
                    <input type="checkbox" name="active" value="1"
                           <?= $form_active ? 'checked' : '' ?>>
                    Show this button on the front panel
                </label>
            </div>
-->

            <div class="form-actions">
                <a href="alter.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </section>
    <?php endif; ?>

</div><!-- /.admin-wrap -->
</body>
</html>
