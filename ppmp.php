<?php
require_once 'config.php';

function normalizeCurrency($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    return (float) str_replace([',', ' '], '', $value);
}

$pdo->exec("CREATE TABLE IF NOT EXISTS ppmp_plans (
    ppmp_id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(255) NOT NULL,
    category VARCHAR(50) DEFAULT 'Other',
    budget DECIMAL(14,2) DEFAULT NULL,
    procurement_method VARCHAR(100) DEFAULT NULL,
    implementation_year SMALLINT DEFAULT NULL,
    end_user_unit VARCHAR(255) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;");

try {
    $pdo->exec("ALTER TABLE ppmp_plans ADD COLUMN category VARCHAR(50) DEFAULT 'Other'");
    $pdo->exec("ALTER TABLE ppmp_plans ADD COLUMN status VARCHAR(50) DEFAULT 'Pending'");
} catch (PDOException $e) {
    // already exists or not supported in this MySQL version
}

$validCategories = ['ICT Equipment', 'Office Supplies', 'Infrastructure', 'Maintenance', 'Other'];

$editPlan = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ppmp'])) {
    // Validate category
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    if (empty($category) || !in_array($category, $validCategories, true)) {
        $category = 'Other';
    }
    
    if (!empty($_POST['ppmp_id'])) {
        $stmt = $pdo->prepare('UPDATE ppmp_plans SET project_name = ?, category = ?, budget = ?, procurement_method = ?, implementation_year = ?, end_user_unit = ?, status = ? WHERE ppmp_id = ?');
        $stmt->execute([
            $_POST['project_name'] ?: null,
            $category,
            normalizeCurrency($_POST['budget'] ?? null),
            $_POST['procurement_method'] ?: null,
            $_POST['implementation_year'] ?: null,
            $_POST['end_user_unit'] ?: null,
            $_POST['status'] ?: 'Pending',
            $_POST['ppmp_id'],
        ]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO ppmp_plans (project_name, category, budget, procurement_method, implementation_year, end_user_unit, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $_POST['project_name'] ?: null,
            $category,
            normalizeCurrency($_POST['budget'] ?? null),
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
            <div class="col-md-3"><label class="form-label">Category</label><select name="category" class="form-select" required><option value="ICT Equipment"<?= (!isset($editPlan['category']) || $editPlan['category'] == 'ICT Equipment') ? ' selected' : '' ?>>ICT Equipment</option><option value="Office Supplies"<?= (isset($editPlan['category']) && $editPlan['category'] == 'Office Supplies') ? ' selected' : '' ?>>Office Supplies</option><option value="Infrastructure"<?= (isset($editPlan['category']) && $editPlan['category'] == 'Infrastructure') ? ' selected' : '' ?>>Infrastructure</option><option value="Maintenance"<?= (isset($editPlan['category']) && $editPlan['category'] == 'Maintenance') ? ' selected' : '' ?>>Maintenance</option><option value="Other"<?= (isset($editPlan['category']) && $editPlan['category'] == 'Other') ? ' selected' : '' ?>>Other</option></select></div>
            <div class="col-md-3"><label class="form-label">Budget (PHP)</label><input type="text" inputmode="decimal" pattern="^[0-9,]+(\.[0-9]{2})?$" placeholder="0,000,000.00" name="budget" class="form-control formatted-money" value="<?= htmlspecialchars($editPlan['budget'] ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label">Procurement Method</label><select name="procurement_method" class="form-select"><option value="">Choose</option><option value="Shopping"<?= (isset($editPlan['procurement_method']) && $editPlan['procurement_method'] == 'Shopping') ? ' selected' : '' ?>>Shopping</option><option value="RFQ"<?= (isset($editPlan['procurement_method']) && $editPlan['procurement_method'] == 'RFQ') ? ' selected' : '' ?>>RFQ</option><option value="Negotiated Procurement"<?= (isset($editPlan['procurement_method']) && $editPlan['procurement_method'] == 'Negotiated Procurement') ? ' selected' : '' ?>>Negotiated Procurement</option><option value="Public Bidding"<?= (isset($editPlan['procurement_method']) && $editPlan['procurement_method'] == 'Public Bidding') ? ' selected' : '' ?>>Public Bidding</option></select></div>
            <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select" required><option value="Pending"<?= (!isset($editPlan['status']) || $editPlan['status'] == 'Pending') ? ' selected' : '' ?>>Pending</option><option value="Approved"<?= (isset($editPlan['status']) && $editPlan['status'] == 'Approved') ? ' selected' : '' ?>>Approved</option><option value="Rejected"<?= (isset($editPlan['status']) && $editPlan['status'] == 'Rejected') ? ' selected' : '' ?>>Rejected</option></select></div>
            <div class="col-md-3"><label class="form-label">Implementation Year</label><input type="number" name="implementation_year" min="2020" max="2100" class="form-control" value="<?= htmlspecialchars($editPlan['implementation_year'] ?? '') ?>"></div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary"><?= $editPlan ? 'Update PPMP' : 'Save PPMP' ?></button>
                <?php if ($editPlan): ?>
                    <a href="ppmp.php" class="btn btn-secondary btn-sm">Cancel</a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <h2>PPMP Records</h2>
    <button type="button" class="btn btn-sm btn-outline-secondary mb-2" onclick="document.getElementById('ppmpRecords').classList.toggle('d-none');">Toggle records</button>
    <div id="ppmpRecords">
      <table class="table table-bordered table-striped"><thead><tr><th>Project</th><th>Category</th><th>Unit</th><th>Budget</th><th>Method</th><th>Status</th><th>Year</th><th>Created</th><th>Actions</th></tr></thead><tbody>
      <?php foreach ($plans as $plan): ?>
      <tr>
          <td><?=htmlspecialchars($plan['project_name'])?></td>
          <td><?=htmlspecialchars($plan['category'] ?? 'Other')?></td>
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
<script>
(function() {
    function unformat(value) {
        return value.replace(/,/g, '').trim();
    }
    function format(value) {
        value = unformat(value);
        if (value === '' || isNaN(value)) {
            return '';
        }
        return Number(value).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    document.querySelectorAll('.formatted-money').forEach(function(input) {
        if (input.value) {
            input.value = format(input.value);
        }
        input.addEventListener('focus', function() {
            this.value = unformat(this.value);
        });
        input.addEventListener('blur', function() {
            this.value = format(this.value);
        });
    });
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            this.querySelectorAll('.formatted-money').forEach(function(input) {
                input.value = unformat(input.value);
            });
        });
    });
})();
</script>
</body></html>