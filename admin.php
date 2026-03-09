<?php
// Simple admin interface to add or toggle supplier status
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_company'])) {
        $stmt = $pdo->prepare('INSERT INTO companies (company_name, category, status, remarks) VALUES (?, ?, ?, ?)');
        $stmt->execute([$_POST['company_name'], $_POST['category'], $_POST['status'], $_POST['remarks']]);
        $companyId = $pdo->lastInsertId();
        // if officer data supplied, insert into officers table
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
    if (isset($_POST['toggle_status']) && isset($_POST['company_id'])) {
        $stmt = $pdo->prepare('UPDATE companies SET status = ? WHERE company_id = ?');
        $stmt->execute([$_POST['new_status'], $_POST['company_id']]);
    }
    if (isset($_POST['delete_company']) && isset($_POST['company_id'])) {
        $stmt = $pdo->prepare('DELETE FROM companies WHERE company_id = ?');
        $stmt->execute([$_POST['company_id']]);
    }
}

$companies = $pdo->query("SELECT c.company_id, c.company_name, c.category, c.status, c.remarks,
                                 o.officer_name, o.position, o.email, o.phone
                          FROM companies c
                          LEFT JOIN officers o ON o.company_id = c.company_id
                          ORDER BY c.company_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container">
    <h1 class="mb-4">Admin Dashboard</h1>

    <div class="mb-3">
        <a href="import.php" class="btn btn-secondary">Import Excel/CSV</a>
    </div>

    <h2>Add Supplier</h2>
    <form method="post" class="row g-3 mb-4">
        <input type="hidden" name="add_company" value="1">
        <div class="col-md-4">
            <input type="text" name="company_name" class="form-control" placeholder="Company Name" required>
        </div>
        <div class="col-md-4">
            <input type="text" name="officer_name" class="form-control" placeholder="Officer Name">
        </div>
        <div class="col-md-2">
            <input type="text" name="position" class="form-control" placeholder="Position">
        </div>
        <div class="col-md-4">
            <input type="email" name="officer_email" class="form-control" placeholder="Email">
        </div>
        <div class="col-md-2">
            <input type="text" name="officer_phone" class="form-control" placeholder="Contact number">
        </div>
        <div class="col-md-2">
            <select name="category" class="form-select">
                <option value="Equipment">Equipment</option>
                <option value="Distributor">Distributor</option>
                <option value="Development">Development</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
        </div>
        <div class="col-md-4">
            <input type="text" name="remarks" class="form-control" placeholder="Remarks">
        </div>
        <div class="col-12">
            <button class="btn btn-primary">Add Company</button>
        </div>
    </form>

    <h2>Existing Suppliers</h2>
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