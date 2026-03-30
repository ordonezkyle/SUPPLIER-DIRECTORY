<?php
require_once 'config.php';

// ensure table exists and has correct fields
$pdo->exec("CREATE TABLE IF NOT EXISTS market_scoping (
    scoping_id INT AUTO_INCREMENT PRIMARY KEY,
    procuring_entity VARCHAR(255) NOT NULL DEFAULT 'Philippine Economic Zone Authority',
    end_user_unit VARCHAR(255) DEFAULT NULL,
    representative_name VARCHAR(255) DEFAULT NULL,
    designation VARCHAR(255) DEFAULT NULL,
    project_name VARCHAR(255) NOT NULL,
    estimated_budget DECIMAL(14,2) DEFAULT NULL,
    period_from_month TINYINT DEFAULT NULL,
    period_from_year SMALLINT DEFAULT NULL,
    period_to_month TINYINT DEFAULT NULL,
    period_to_year SMALLINT DEFAULT NULL,
    expected_delivery_month TINYINT DEFAULT NULL,
    expected_delivery_year SMALLINT DEFAULT NULL,
    supplier_id INT DEFAULT NULL,
    quotation DECIMAL(13,2) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    analysis TEXT DEFAULT NULL,
    report_link VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES companies(company_id) ON DELETE SET NULL
) ENGINE=InnoDB;");

try {
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN status VARCHAR(50) DEFAULT 'Pending'");
} catch (PDOException $e) {
    // already exists or not supported in this MySQL version
}


$pdo->exec("ALTER TABLE market_scoping ADD COLUMN IF NOT EXISTS procuring_entity VARCHAR(255) NOT NULL DEFAULT 'Philippine Economic Zone Authority';");
$pdo->exec("ALTER TABLE market_scoping ADD COLUMN IF NOT EXISTS end_user_unit VARCHAR(255) DEFAULT NULL;");
$pdo->exec("ALTER TABLE market_scoping ADD COLUMN IF NOT EXISTS representative_name VARCHAR(255) DEFAULT NULL;");
$pdo->exec("ALTER TABLE market_scoping ADD COLUMN IF NOT EXISTS designation VARCHAR(255) DEFAULT NULL;");
$pdo->exec("ALTER TABLE market_scoping ADD COLUMN IF NOT EXISTS estimated_budget DECIMAL(14,2) DEFAULT NULL;");
$pdo->exec("ALTER TABLE market_scoping ADD COLUMN IF NOT EXISTS period_from_month TINYINT DEFAULT NULL;");
$pdo->exec("ALTER TABLE market_scoping ADD COLUMN IF NOT EXISTS period_from_year SMALLINT DEFAULT NULL;");
$pdo->exec("ALTER TABLE market_scoping ADD COLUMN IF NOT EXISTS period_to_month TINYINT DEFAULT NULL;");
$pdo->exec("ALTER TABLE market_scoping ADD COLUMN IF NOT EXISTS period_to_year SMALLINT DEFAULT NULL;");
$pdo->exec("ALTER TABLE market_scoping ADD COLUMN IF NOT EXISTS expected_delivery_month TINYINT DEFAULT NULL;");
$pdo->exec("ALTER TABLE market_scoping ADD COLUMN IF NOT EXISTS expected_delivery_year SMALLINT DEFAULT NULL;");

$errors = [];
$editScoping = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_scoping']) || isset($_POST['update_scoping'])) {
        if (trim($_POST['project_name']) === '') {
            $errors[] = 'Project name is required.';
        }
        if (trim($_POST['end_user_unit']) === '') {
            $errors[] = 'End User Unit is required.';
        }
        if (trim($_POST['representative_name']) === '') {
            $errors[] = 'Representative Name is required.';
        }
        if (!$errors) {
            if (!empty($_POST['scoping_id']) && isset($_POST['update_scoping'])) {
                $stmt = $pdo->prepare('UPDATE market_scoping SET procuring_entity=?, end_user_unit=?, representative_name=?, designation=?, project_name=?, estimated_budget=?, period_from_month=?, period_from_year=?, period_to_month=?, period_to_year=?, expected_delivery_month=?, expected_delivery_year=?, supplier_id=?, quotation=?, status=?, analysis=?, report_link=? WHERE scoping_id=?');
                $stmt->execute([
                    $_POST['procuring_entity'] ?: 'Philippine Economic Zone Authority',
                    $_POST['end_user_unit'] ?: null,
                    $_POST['representative_name'] ?: null,
                    $_POST['designation'] ?: null,
                    $_POST['project_name'],
                    $_POST['estimated_budget'] ?: null,
                    $_POST['period_from_month'] ?: null,
                    $_POST['period_from_year'] ?: null,
                    $_POST['period_to_month'] ?: null,
                    $_POST['period_to_year'] ?: null,
                    $_POST['expected_delivery_month'] ?: null,
                    $_POST['expected_delivery_year'] ?: null,
                    $_POST['supplier_id'] ?: null,
                    $_POST['quotation'] ?: null,
                    $_POST['status'] ?: 'Pending',
                    $_POST['analysis'] ?: null,
                    $_POST['report_link'] ?: null,
                    $_POST['scoping_id'],
                ]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO market_scoping (procuring_entity, end_user_unit, representative_name, designation, project_name, estimated_budget, period_from_month, period_from_year, period_to_month, period_to_year, expected_delivery_month, expected_delivery_year, supplier_id, quotation, status, analysis, report_link) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    $_POST['procuring_entity'] ?: 'Philippine Economic Zone Authority',
                    $_POST['end_user_unit'] ?: null,
                    $_POST['representative_name'] ?: null,
                    $_POST['designation'] ?: null,
                    $_POST['project_name'],
                    $_POST['estimated_budget'] ?: null,
                    $_POST['period_from_month'] ?: null,
                    $_POST['period_from_year'] ?: null,
                    $_POST['period_to_month'] ?: null,
                    $_POST['period_to_year'] ?: null,
                    $_POST['expected_delivery_month'] ?: null,
                    $_POST['expected_delivery_year'] ?: null,
                    $_POST['supplier_id'] ?: null,
                    $_POST['quotation'] ?: null,
                    $_POST['status'] ?: 'Pending',
                    $_POST['analysis'] ?: null,
                    $_POST['report_link'] ?: null,
                ]);
            }
            header('Location: market_scoping.php');
            exit;
        }
    }
    if (isset($_POST['delete_scoping']) && isset($_POST['scoping_id'])) {
        $stmt = $pdo->prepare('DELETE FROM market_scoping WHERE scoping_id = ?');
        $stmt->execute([$_POST['scoping_id']]);
        header('Location: market_scoping.php');
        exit;
    }
}

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM market_scoping WHERE scoping_id = ?');
    $stmt->execute([$_GET['edit']]);
    $editScoping = $stmt->fetch();
}

$companies = $pdo->query('SELECT company_id, company_name FROM companies ORDER BY company_name')->fetchAll();
$scopings = $pdo->query('SELECT m.scoping_id,m.procuring_entity,m.end_user_unit,m.representative_name,m.designation,m.project_name,m.estimated_budget,m.period_from_month,m.period_from_year,m.period_to_month,m.period_to_year,m.expected_delivery_month,m.expected_delivery_year,m.quotation,m.analysis,m.report_link,m.created_at,c.company_name
    FROM market_scoping m
    LEFT JOIN companies c ON c.company_id=m.supplier_id
    ORDER BY m.created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Market Scoping | PEZA SCMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>body{background:url('images/PEZA-background.jpeg') no-repeat center center fixed;background-size:cover;}.container{background-color:rgba(255,255,255,0.95);padding:1rem;border-radius:4px;margin-top:20px;margin-bottom:20px;}</style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="m-0">Market Scoping Module</h1>
        <div>
            <a href="admin.php" class="btn btn-success btn-sm me-2">Admin</a>
            <a href="index.php" class="btn btn-secondary btn-sm">Directory</a>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger"><ul><?php foreach ($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="post" class="row g-3 mb-4">
        <input type="hidden" name="add_scoping" value="1">
        <input type="hidden" name="update_scoping" value="<?= $editScoping ? '1' : '' ?>">
        <input type="hidden" name="scoping_id" value="<?= htmlspecialchars($editScoping['scoping_id'] ?? '') ?>">
        <div class="col-12 mb-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('scopingForm').classList.toggle('d-none');">Toggle form</button>
            <?php if ($editScoping): ?>
                <a href="market_scoping.php" class="btn btn-sm btn-secondary">Cancel edit</a>
            <?php endif; ?>
        </div>
        <div id="scopingForm" class="row g-3<?= $editScoping ? '' : '' ?>">
            <div class="col-12"><h4>Agency Information</h4></div>

            <div class="col-md-6">
                <label class="form-label">Procuring Entity</label>
                <input type="text" name="procuring_entity" class="form-control" value="<?= htmlspecialchars($editScoping['procuring_entity'] ?? 'Philippine Economic Zone Authority') ?>" required>
            </div>
        <div class="col-md-6">
            <label class="form-label">End User Unit</label>
            <input type="text" name="end_user_unit" class="form-control" required value="<?= htmlspecialchars($editScoping['end_user_unit'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Representative Name</label>
            <input type="text" name="representative_name" class="form-control" required value="<?= htmlspecialchars($editScoping['representative_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Designation</label>
            <input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($editScoping['designation'] ?? '') ?>">
        </div>

        <div class="col-12"><hr></div>
        <div class="col-12"><h4>Project Overview</h4></div>

        <div class="col-md-6">
            <label class="form-label">Project Name</label>
            <input type="text" name="project_name" class="form-control" required value="<?= htmlspecialchars($editScoping['project_name'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Estimated Budget (PHP)</label>
            <input type="number" name="estimated_budget" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($editScoping['estimated_budget'] ?? '') ?>">
        </div>

        <div class="col-md-6">
            <label class="form-label">Market Scoping Period From (MM/YYYY)</label>
            <div class="row g-2">
                <div class="col-4"><input type="number" class="form-control" name="period_from_month" min="1" max="12" placeholder="MM" value="<?= htmlspecialchars($editScoping['period_from_month'] ?? '') ?>"></div>
                <div class="col-8"><input type="number" class="form-control" name="period_from_year" min="2000" max="2100" placeholder="YYYY" value="<?= htmlspecialchars($editScoping['period_from_year'] ?? '') ?>"></div>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Market Scoping Period To (MM/YYYY)</label>
            <div class="row g-2">
                <div class="col-4"><input type="number" class="form-control" name="period_to_month" min="1" max="12" placeholder="MM" value="<?= htmlspecialchars($editScoping['period_to_month'] ?? '') ?>"></div>
                <div class="col-8"><input type="number" class="form-control" name="period_to_year" min="2000" max="2100" placeholder="YYYY" value="<?= htmlspecialchars($editScoping['period_to_year'] ?? '') ?>"></div>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Expected Delivery Date (MM/YYYY)</label>
            <div class="row g-2">
                <div class="col-4"><input type="number" class="form-control" name="expected_delivery_month" min="1" max="12" placeholder="MM" value="<?= htmlspecialchars($editScoping['expected_delivery_month'] ?? '') ?>"></div>
                <div class="col-8"><input type="number" class="form-control" name="expected_delivery_year" min="2000" max="2100" placeholder="YYYY" value="<?= htmlspecialchars($editScoping['expected_delivery_year'] ?? '') ?>"></div>
            </div>
        </div>

        <div class="col-12"><hr></div>
        <div class="col-md-6">
            <label class="form-label">Supplier (optional)</label>
            <select name="supplier_id" class="form-select">
                <option value="">-- Select supplier --</option>
                <?php foreach ($companies as $company): ?>
                    <option value="<?=htmlspecialchars($company['company_id'])?>"<?= isset($editScoping['supplier_id']) && $editScoping['supplier_id'] == $company['company_id'] ? ' selected' : '' ?>><?=htmlspecialchars($company['company_name'])?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Quotation (PHP)</label>
            <input type="number" name="quotation" step="0.01" min="0" class="form-control" value="<?= htmlspecialchars($editScoping['quotation'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option<?= (isset($editScoping['status']) && $editScoping['status'] == 'Pending') ? ' selected' : '' ?>>Pending</option>
                <option<?= (isset($editScoping['status']) && $editScoping['status'] == 'Approved') ? ' selected' : '' ?>>Approved</option>
                <option<?= (isset($editScoping['status']) && $editScoping['status'] == 'Rejected') ? ' selected' : '' ?>>Rejected</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Report Link (optional)</label>
            <input type="url" name="report_link" class="form-control" placeholder="https://..." value="<?= htmlspecialchars($editScoping['report_link'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label">Analysis / Notes</label>
            <textarea name="analysis" rows="3" class="form-control"><?= htmlspecialchars($editScoping['analysis'] ?? '') ?></textarea>
        </div>
        <div class="col-12">
            <button class="btn btn-primary"><?= $editScoping ? 'Update Market Scoping Record' : 'Add Market Scoping Record' ?></button>
        </div>
    </form>

    <h2>Existing Market Scoping Records</h2>
    <button type="button" class="btn btn-sm btn-outline-secondary mb-2" onclick="document.getElementById('scopingRecords').classList.toggle('d-none');">Toggle records</button>
    <div id="scopingRecords">
      <table class="table table-bordered table-striped">
          <thead><tr><th>Project</th><th>End User</th><th>Rep</th><th>Budget</th><th>Period</th><th>Delivery</th><th>Supplier</th><th>Quotation</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($scopings as $m): ?>
              <tr>
                  <td><?=htmlspecialchars($m['project_name'])?></td>
                  <td><?=htmlspecialchars($m['end_user_unit'] ?? 'N/A')?></td>
                  <td><?=htmlspecialchars($m['representative_name'] ?? 'N/A')?></td>
                  <td><?= $m['estimated_budget'] !== null ? '₱'.number_format($m['estimated_budget'],2) : 'N/A' ?></td>
                  <td><?= ($m['period_from_month'] && $m['period_from_year'] ? sprintf('%02d/%04d',$m['period_from_month'], $m['period_from_year']) : 'N/A') ?> - <?= ($m['period_to_month'] && $m['period_to_year'] ? sprintf('%02d/%04d',$m['period_to_month'], $m['period_to_year']) : 'N/A') ?></td>
                  <td><?= ($m['expected_delivery_month'] && $m['expected_delivery_year'] ? sprintf('%02d/%04d',$m['expected_delivery_month'], $m['expected_delivery_year']) : 'N/A') ?></td>
                  <td><?=htmlspecialchars($m['company_name'] ?? 'N/A')?></td>
                  <td><?= $m['quotation'] !== null ? '₱'.number_format($m['quotation'],2) : 'N/A' ?></td>
                  <td><?= htmlspecialchars($m['status'] ?? 'Pending') ?></td>
                  <td>
                      <a href="market_scoping.php?edit=<?= intval($m['scoping_id']) ?>" class="btn btn-sm btn-warning me-1">Edit</a>
                      <form method="post" onsubmit="return confirm('Delete this record?');" style="display:inline">
                          <input type="hidden" name="scoping_id" value="<?=htmlspecialchars($m['scoping_id'])?>">
                          <input type="hidden" name="delete_scoping" value="1">
                          <button class="btn btn-sm btn-danger">Delete</button>
                      </form>
                  </td>
              </tr>
          <?php endforeach; ?>
          </tbody>
      </table>
    </div>
</div>
</body>
</html>
