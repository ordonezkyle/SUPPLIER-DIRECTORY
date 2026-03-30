<?php
require_once 'config.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS rfq_requests (
    rfq_id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(255) NOT NULL,
    procurement_method VARCHAR(100) DEFAULT 'RFQ',
    suppliers_invited TEXT DEFAULT NULL,
    submission_deadline DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;");

$editRFQ = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_rfq'])) {
    if (!empty($_POST['rfq_id'])) {
        $stmt = $pdo->prepare('UPDATE rfq_requests SET project_name = ?, procurement_method = ?, suppliers_invited = ?, submission_deadline = ?, status = ? WHERE rfq_id = ?');
        $stmt->execute([
            $_POST['project_name'] ?: null,
            $_POST['procurement_method'] ?: 'RFQ',
            $_POST['suppliers_invited'] ?: null,
            $_POST['submission_deadline'] ?: null,
            $_POST['status'] ?: 'Open',
            $_POST['rfq_id'],
        ]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO rfq_requests (project_name, procurement_method, suppliers_invited, submission_deadline, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $_POST['project_name'] ?: null,
            $_POST['procurement_method'] ?: 'RFQ',
            $_POST['suppliers_invited'] ?: null,
            $_POST['submission_deadline'] ?: null,
            $_POST['status'] ?: 'Open',
        ]);
    }
    header('Location: rfq.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_rfq']) && !empty($_POST['rfq_id'])) {
    $stmt = $pdo->prepare('DELETE FROM rfq_requests WHERE rfq_id = ?');
    $stmt->execute([$_POST['rfq_id']]);
    header('Location: rfq.php');
    exit;
}

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editRFQ = $pdo->prepare('SELECT * FROM rfq_requests WHERE rfq_id = ?');
    $editRFQ->execute([$_GET['edit']]);
    $editRFQ = $editRFQ->fetch();
}

$rfqs = $pdo->query('SELECT * FROM rfq_requests ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>RFQ Management</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"><style>body{background:url('images/PEZA-background.jpeg') no-repeat center center fixed;background-size:cover;} .container{background:rgba(255,255,255,0.95);padding:1rem;margin:20px;border-radius:4px;}</style></head><body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>RFQ Management</h1>
        <div>
            <a href="pmis_dashboard.php" class="btn btn-sm btn-outline-secondary">Dashboard</a>
            <a href="market_scoping.php" class="btn btn-sm btn-outline-primary">Market Scoping</a>
            <a href="ppmp.php" class="btn btn-sm btn-outline-info">PPMP</a>
        </div>
    </div>

    <form method="post" class="row g-3 mb-4">
        <input type="hidden" name="save_rfq" value="1">
        <input type="hidden" name="rfq_id" value="<?= htmlspecialchars($editRFQ['rfq_id'] ?? '') ?>">
        <div class="col-md-12">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('rfqForm').classList.toggle('d-none');">Toggle form</button>
        </div>
        <div id="rfqForm" class="row g-3<?= $editRFQ ? '' : '' ?>">
            <div class="col-md-6"><label class="form-label">Project</label><input type="text" name="project_name" class="form-control" required value="<?= htmlspecialchars($editRFQ['project_name'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Submission Deadline</label><input type="date" name="submission_deadline" class="form-control" required value="<?= htmlspecialchars($editRFQ['submission_deadline'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Suppliers Invited (comma-separated)</label><textarea name="suppliers_invited" rows="2" class="form-control"><?= htmlspecialchars($editRFQ['suppliers_invited'] ?? '') ?></textarea></div>
            <div class="col-md-3"><label class="form-label">Procurement Method</label><select name="procurement_method" class="form-select"><option<?= (isset($editRFQ['procurement_method']) && $editRFQ['procurement_method'] == 'RFQ') ? ' selected' : '' ?>>RFQ</option><option<?= (isset($editRFQ['procurement_method']) && $editRFQ['procurement_method'] == 'Shopping') ? ' selected' : '' ?>>Shopping</option><option<?= (isset($editRFQ['procurement_method']) && $editRFQ['procurement_method'] == 'Negotiated') ? ' selected' : '' ?>>Negotiated</option><option<?= (isset($editRFQ['procurement_method']) && $editRFQ['procurement_method'] == 'Public Bidding') ? ' selected' : '' ?>>Public Bidding</option></select></div>
            <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option<?= (isset($editRFQ['status']) && $editRFQ['status'] == 'Open') ? ' selected' : '' ?>>Open</option><option<?= (isset($editRFQ['status']) && $editRFQ['status'] == 'Closed') ? ' selected' : '' ?>>Closed</option><option<?= (isset($editRFQ['status']) && $editRFQ['status'] == 'Awarded') ? ' selected' : '' ?>>Awarded</option></select></div>
            <div class="col-12"><button class="btn btn-primary"><?= $editRFQ ? 'Update RFQ' : 'Save RFQ' ?></button><?php if ($editRFQ): ?> <a href="rfq.php" class="btn btn-secondary btn-sm">Cancel</a><?php endif; ?></div>
        </div>
    </form>

    <h2>RFQ Records</h2>
    <button type="button" class="btn btn-sm btn-outline-secondary mb-2" onclick="document.getElementById('rfqRecords').classList.toggle('d-none');">Toggle records</button>
    <div id="rfqRecords">
      <table class="table table-bordered table-sm"><thead><tr><th>Project</th><th>Deadline</th><th>Suppliers</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead><tbody>
      <?php foreach ($rfqs as $entry): ?>
        <tr>
            <td><?=htmlspecialchars($entry['project_name'])?></td>
            <td><?=htmlspecialchars($entry['submission_deadline'])?></td>
            <td><?=htmlspecialchars($entry['suppliers_invited'])?></td>
            <td><?=htmlspecialchars($entry['status'])?></td>
            <td><?=htmlspecialchars($entry['created_at'])?></td>
            <td>
                <a href="rfq.php?edit=<?= intval($entry['rfq_id']) ?>" class="btn btn-sm btn-warning me-1">Edit</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete this RFQ?');"><input type="hidden" name="rfq_id" value="<?= intval($entry['rfq_id']) ?>"><input type="hidden" name="delete_rfq" value="1"><button class="btn btn-sm btn-danger">Delete</button></form>
            </td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
</div>
</body></html>