<?php
require_once 'config.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS ppmp_plans (
    ppmp_id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(255) NOT NULL,
    budget DECIMAL(14,2) DEFAULT NULL,
    procurement_method VARCHAR(100) DEFAULT NULL,
    implementation_year SMALLINT DEFAULT NULL,
    end_user_unit VARCHAR(255) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;");

try {
    $pdo->exec("ALTER TABLE ppmp_plans ADD COLUMN status VARCHAR(50) DEFAULT 'Pending'");
} catch (PDOException $e) {
    // already exists or not supported in this MySQL version
}


$editPlan = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ppmp'])) {
    if (!empty($_POST['ppmp_id'])) {
        $stmt = $pdo->prepare('UPDATE ppmp_plans SET project_name = ?, budget = ?, procurement_method = ?, implementation_year = ?, end_user_unit = ?, status = ? WHERE ppmp_id = ?');
        $stmt->execute([
            $_POST['project_name'] ?: null,
            $_POST['budget'] ?: null,
            $_POST['procurement_method'] ?: null,
            $_POST['implementation_year'] ?: null,
            $_POST['end_user_unit'] ?: null,
            $_POST['status'] ?: 'Pending',
            $_POST['ppmp_id'],
        ]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO ppmp_plans (project_name, budget, procurement_method, implementation_year, end_user_unit, status) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $_POST['project_name'] ?: null,
            $_POST['budget'] ?: null,
            $_POST['procurement_method'] ?: null,
            $_POST['implementation_year'] ?: null,
            $_POST['end_user_unit'] ?: null,
            $_POST['status'] ?: 'Pending',
        ]);
    }
    header('Location: ppmp.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ppmp']) && !empty($_POST['ppmp_id'])) {
    $stmt = $pdo->prepare('DELETE FROM ppmp_plans WHERE ppmp_id = ?');
    $stmt->execute([$_POST['ppmp_id']]);
    header('Location: ppmp.php');
    exit;
}

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editPlan = $pdo->prepare('SELECT * FROM ppmp_plans WHERE ppmp_id = ?');
    $editPlan->execute([$_GET['edit']]);
    $editPlan = $editPlan->fetch();
}

$plans = $pdo->query('SELECT * FROM ppmp_plans ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>PPMP Module</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"><style>body{background:url('images/PEZA-background.jpeg') no-repeat center center fixed;background-size:cover;} .container{background:rgba(255,255,255,0.95);padding:1rem;margin:20px;border-radius:4px;}</style></head><body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>PPMP Module</h1>
        <div>
            <a href="pmis_dashboard.php" class="btn btn-sm btn-outline-secondary">Dashboard</a>
            <a href="market_scoping.php" class="btn btn-sm btn-outline-primary">Market Scoping</a>
            <a href="rfq.php" class="btn btn-sm btn-outline-success">RFQ</a>
        </div>
    </div>
    <form method="post" class="row g-3 mb-4">
        <input type="hidden" name="save_ppmp" value="1">
        <input type="hidden" name="ppmp_id" value="<?= htmlspecialchars($editPlan['ppmp_id'] ?? '') ?>">
        <div class="col-md-12">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('ppmpForm').classList.toggle('d-none');">Toggle form</button>
        </div>
        <div id="ppmpForm" class="row g-3<?= $editPlan ? '' : '' ?>">
            <div class="col-md-6"><label class="form-label">Project Name</label><input type="text" name="project_name" class="form-control" required value="<?= htmlspecialchars($editPlan['project_name'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">End User Unit</label><input type="text" name="end_user_unit" class="form-control" value="<?= htmlspecialchars($editPlan['end_user_unit'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">Budget (PHP)</label><input type="number" min="0" step="0.01" name="budget" class="form-control" value="<?= htmlspecialchars($editPlan['budget'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">Procurement Method</label><select name="procurement_method" class="form-select"><option value="">Choose</option><option<?= (isset($editPlan['procurement_method']) && $editPlan['procurement_method'] == 'Shopping') ? ' selected' : '' ?>>Shopping</option><option<?= (isset($editPlan['procurement_method']) && $editPlan['procurement_method'] == 'RFQ') ? ' selected' : '' ?>>RFQ</option><option<?= (isset($editPlan['procurement_method']) && $editPlan['procurement_method'] == 'Negotiated Procurement') ? ' selected' : '' ?>>Negotiated Procurement</option><option<?= (isset($editPlan['procurement_method']) && $editPlan['procurement_method'] == 'Public Bidding') ? ' selected' : '' ?>>Public Bidding</option></select></div>
            <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option<?= (isset($editPlan['status']) && $editPlan['status'] == 'Pending') ? ' selected' : '' ?>>Pending</option><option<?= (isset($editPlan['status']) && $editPlan['status'] == 'Approved') ? ' selected' : '' ?>>Approved</option><option<?= (isset($editPlan['status']) && $editPlan['status'] == 'Rejected') ? ' selected' : '' ?>>Rejected</option></select></div>
            <div class="col-md-4"><label class="form-label">Implementation Year</label><input type="number" name="implementation_year" min="2020" max="2100" class="form-control" value="<?= htmlspecialchars($editPlan['implementation_year'] ?? '') ?>"></div>
            <div class="col-12">
                <button class="btn btn-primary"><?= $editPlan ? 'Update PPMP' : 'Save PPMP' ?></button>
                <?php if ($editPlan): ?>
                    <a href="ppmp.php" class="btn btn-secondary btn-sm">Cancel</a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <h2>PPMP Records</h2>
    <button type="button" class="btn btn-sm btn-outline-secondary mb-2" onclick="document.getElementById('ppmpRecords').classList.toggle('d-none');">Toggle records</button>
    <div id="ppmpRecords">
      <table class="table table-bordered table-striped"><thead><tr><th>Project</th><th>Unit</th><th>Budget</th><th>Method</th><th>Status</th><th>Year</th><th>Created</th><th>Actions</th></tr></thead><tbody>
      <?php foreach ($plans as $plan): ?>
      <tr>
          <td><?=htmlspecialchars($plan['project_name'])?></td>
          <td><?=htmlspecialchars($plan['end_user_unit'])?></td>
          <td><?= $plan['budget'] !== null ? '₱'.number_format($plan['budget'],2) : 'N/A' ?></td>
          <td><?=htmlspecialchars($plan['procurement_method'])?></td>
          <td><?=htmlspecialchars($plan['status'] ?? 'Pending')?></td>
          <td><?=htmlspecialchars($plan['implementation_year'])?></td>
          <td><?=htmlspecialchars($plan['created_at'])?></td>
          <td>
              <a href="ppmp.php?edit=<?= intval($plan['ppmp_id']) ?>" class="btn btn-sm btn-warning me-1">Edit</a>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete this plan?');">
                  <input type="hidden" name="ppmp_id" value="<?= intval($plan['ppmp_id']) ?>">
                  <input type="hidden" name="delete_ppmp" value="1">
                  <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
          </td>
      </tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
</div>
</body></html>