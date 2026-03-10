<?php
// Simple admin interface to add or toggle supplier status
require_once 'config.php';

// redirect to login if not authenticated
if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

// allow logout via query parameter
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// helpers for rendering inputs/selects with prefilled values
function inputField($name, $value = '', $placeholder = '', $type = 'text', $class = 'form-control', $autocomplete = 'on') {
    return "<input autocomplete=\"" . htmlspecialchars($autocomplete) . "\" type=\"" . htmlspecialchars($type) . "\" name=\"" . htmlspecialchars($name) . "\" class=\"" . htmlspecialchars($class) . "\" placeholder=\"" . htmlspecialchars($placeholder) . "\" value=\"" . htmlspecialchars($value) . "\">";
}

function selectField($name, $options, $selected = null, $class = 'form-select') {
    $html = "<select name=\"" . htmlspecialchars($name) . "\" class=\"" . htmlspecialchars($class) . "\">";
    foreach ($options as $val => $label) {
        $sel = ((string)$val === (string)$selected) ? ' selected' : '';
        $html .= "<option value=\"" . htmlspecialchars($val) . "\"{$sel}>" . htmlspecialchars($label) . "</option>";
    }
    $html .= "</select>";
    return $html;
}

// datalist helper allows suggestion examples for text inputs
function datalistField($name, $options, $value = '', $placeholder = '', $class = 'form-control', $autocomplete = 'on') {
    $listId = $name . '_list';
    $html = "<input autocomplete=\"" . htmlspecialchars($autocomplete) . "\" type=\"text\" name=\"" . htmlspecialchars($name) . "\" class=\"" . htmlspecialchars($class) . "\" placeholder=\"" . htmlspecialchars($placeholder) . "\" value=\"" . htmlspecialchars($value) . "\" list=\"" . htmlspecialchars($listId) . "\">";
    $html .= "<datalist id=\"" . htmlspecialchars($listId) . "\">";
    foreach ($options as $opt) {
        $html .= "<option value=\"" . htmlspecialchars($opt) . "\">";
    }
    $html .= "</datalist>";
    return $html;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_company'])) {
        $stmt = $pdo->prepare('INSERT INTO companies (company_name, category, status, remarks) VALUES (?, ?, ?, ?)');
        $stmt->execute([$_POST['company_name'], $_POST['category'], $_POST['status'], $_POST['remarks']]);
        $companyId = $pdo->lastInsertId();
        if (!empty($_POST['officer_name'])) {
            $stmt2 = $pdo->prepare('INSERT INTO officers (company_id, officer_name, position, email, phone) VALUES (?, ?, ?, ?, ?)');
            $stmt2->execute([
                $companyId,
                $_POST['officer_name'],
                $_POST['position'] ?: null,
                $_POST['officer_email'] ?: null,
                $_POST['officer_phone'] ?: null,
            ]);
        }
    }
    if (isset($_POST['update_company']) && isset($_POST['company_id'])) {
        $stmt = $pdo->prepare('UPDATE companies SET company_name=?, category=?, status=?, remarks=? WHERE company_id=?');
        $stmt->execute([$_POST['company_name'], $_POST['category'], $_POST['status'], $_POST['remarks'], $_POST['company_id']]);
        // update existing officer if present else insert
        if (!empty($_POST['officer_name'])) {
            // check if an officer exists for this company
            $chk = $pdo->prepare('SELECT officer_id FROM officers WHERE company_id=? LIMIT 1');
            $chk->execute([$_POST['company_id']]);
            if ($row = $chk->fetch()) {
                $upd = $pdo->prepare('UPDATE officers SET officer_name=?, position=?, email=?, phone=? WHERE officer_id=?');
                $upd->execute([$_POST['officer_name'], $_POST['position'] ?: null, $_POST['officer_email'] ?: null, $_POST['officer_phone'] ?: null, $row['officer_id']]);
            } else {
                $ins = $pdo->prepare('INSERT INTO officers (company_id, officer_name, position, email, phone) VALUES (?, ?, ?, ?, ?)');
                $ins->execute([$_POST['company_id'], $_POST['officer_name'], $_POST['position'] ?: null, $_POST['officer_email'] ?: null, $_POST['officer_phone'] ?: null]);
            }
        }
    }
    if (isset($_POST['toggle_status']) && isset($_POST['company_id'])) {
        $stmt = $pdo->prepare('UPDATE companies SET status = ? WHERE company_id = ?');
        $stmt->execute([$_POST['new_status'], $_POST['company_id']]);
    }
    if (isset($_POST['delete_company']) && isset($_POST['company_id'])) {
        $stmt = $pdo->prepare('DELETE FROM companies WHERE company_id = ?');
        $stmt->execute([$_POST['company_id']]);
    }
}

$editData = null;
if (isset($_GET['edit_company'])) {
    $id = (int)$_GET['edit_company'];
    $stmt = $pdo->prepare('SELECT c.company_id, c.company_name, c.category, c.status, c.remarks, o.officer_name, o.position, o.email, o.phone FROM companies c LEFT JOIN officers o ON o.company_id = c.company_id WHERE c.company_id = ? LIMIT 1');
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}

$companies = $pdo->query('SELECT c.company_id, c.company_name, `c`.`category`, c.status, c.remarks,
                                 o.officer_name, o.position, o.email, o.phone
                          FROM companies c
                          LEFT JOIN officers o ON o.company_id = c.company_id
                          ORDER BY c.company_name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            /* place your chosen photo in scms/images/ and update the filename below */
            background: url('images/PEZA-background.jpeg') no-repeat center center fixed;
            background-size: cover;
        }
        .container {
            background-color: rgba(255,255,255,0.9);
            padding: 1rem;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="m-0">Admin Dashboard</h1>
        <a href="admin.php?action=logout" class="btn btn-outline-secondary btn-sm">Logout</a>
    </div>

    <div class="mb-3">
        <a href="import.php" class="btn btn-secondary">Import Excel/CSV</a>
    </div>

    <h2><?= $editData ? 'Edit Supplier' : 'Add Supplier' ?></h2>
    <form method="post" class="row g-3 mb-4">
        <?php if ($editData): ?><input type="hidden" name="update_company" value="1">
        <input type="hidden" name="company_id" value="<?= $editData['company_id'] ?>"><?php else: ?><input type="hidden" name="add_company" value="1"><?php endif; ?>
        <div class="col-md-4">
            <?= inputField('company_name', $editData['company_name'] ?? '', 'Company Name', 'text', 'form-control', 'off') ?>
        </div>
        <div class="col-md-4">
            <?= inputField('officer_name', $editData['officer_name'] ?? '', 'Officer Name', 'text', 'form-control', 'off') ?>
        </div>
        <div class="col-md-2">
            <?= datalistField('position', ['Sales Manager','Director','Engineer'], $editData['position'] ?? '', 'Position (e.g. Sales Manager)') ?>
        </div>
        <div class="col-md-4">
            <?= inputField('officer_email', $editData['email'] ?? '', 'Email', 'email', 'form-control', 'off') ?>
        </div>
        <div class="col-md-2">
            <?= inputField('officer_phone', $editData['phone'] ?? '', 'Contact number', 'text', 'form-control', 'off') ?>
        </div>
        <div class="col-md-2">
            <?= selectField('category', ['Equipment'=>'Equipment','Distributor'=>'Distributor','Development'=>'Development'], $editData['category'] ?? '') ?>
        </div>
        <div class="col-md-2">
            <?= selectField('status', ['Active'=>'Active','Inactive'=>'Inactive'], $editData['status'] ?? '') ?>
        </div>
        <div class="col-md-4">
            <?= inputField('remarks', $editData['remarks'] ?? '', 'Remarks', 'text', 'form-control', 'off') ?>
        </div>
        <div class="col-12">
            <button class="btn btn-primary"><?= $editData ? 'Update Company' : 'Add Company' ?></button>
            <?php if ($editData): ?><a href="admin.php" class="btn btn-secondary ms-2">Cancel</a><?php endif; ?>
        </div>
    </form>

    <h2>Existing Suppliers <a href="index.php" class="ms-3" title="Back to directory">🏠</a></h2>
    <table class="table table-bordered">
        <thead><tr><th>Company</th><th>Officer</th><th>Position</th><th>Email</th><th>Phone</th><th>Category</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($companies as $c): 
            $rowClass = '';
            if ($c['status'] === 'Active') {
                $rowClass = 'table-success';
            } elseif ($c['status'] === 'Inactive') {
                $rowClass = 'table-danger';
            }
        ?>
            <tr class="<?= $rowClass ?>">
                <td><?=htmlspecialchars($c['company_name'])?></td>
                <td><?=htmlspecialchars($c['officer_name'])?></td>
                <td><?=htmlspecialchars($c['position'])?></td>
                <td><?=htmlspecialchars($c['email'])?></td>
                <td><?=htmlspecialchars($c['phone'])?></td>
                <td><?=htmlspecialchars($c['category'])?></td>
                <td><span class="badge <?= $c['status'] === 'Active' ? 'bg-success' : 'bg-danger' ?>"><?=htmlspecialchars($c['status'])?></span></td>
                <td>
                    <a href="admin.php?edit_company=<?=$c['company_id']?>" class="btn btn-sm btn-info">Edit</a>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="company_id" value="<?=$c['company_id']?>">
                        <input type="hidden" name="toggle_status" value="1">
                        <input type="hidden" name="new_status" value="<?= $c['status'] === 'Active' ? 'Inactive' : 'Active' ?>">
                        <button class="btn btn-sm btn-warning">Toggle</button>
                    </form>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this supplier?');">
                        <input type="hidden" name="company_id" value="<?=$c['company_id']?>">
                        <input type="hidden" name="delete_company" value="1">
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p><a href="index.php">Back to directory</a></p>
</div>
</body>
</html>