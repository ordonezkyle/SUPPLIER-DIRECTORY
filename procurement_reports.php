<?php
require_once 'config.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS procurement_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(255) NOT NULL,
    document_type VARCHAR(100) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_report'])) {
    $stmt = $pdo->prepare('INSERT INTO procurement_reports (project_name, document_type, status) VALUES (?, ?, ?)');
    $stmt->execute([
        $_POST['project_name'],
        $_POST['document_type'],
        $_POST['status'],
    ]);
}

$reports = $pdo->query('SELECT * FROM procurement_reports ORDER BY created_at DESC')->fetchAll();
$projects = $pdo->query('SELECT DISTINCT project_name FROM market_scoping ORDER BY project_name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Procurement Reports</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"><style>body{background:url('images/PEZA-background.jpeg') no-repeat center center fixed;background-size:cover;}.container{background:rgba(255,255,255,0.95);padding:1rem;margin:20px;border-radius:4px;}</style></head><body>
<div class="container"><h1>Generated Procurement Reports</h1><p><a href="index.php" class="btn btn-sm btn-outline-secondary me-2">Directory</a><a href="pmis_dashboard.php" class="btn btn-sm btn-outline-secondary">Dashboard</a></p>
<form method="post" class="row g-3 mb-3">
    <input type="hidden" name="add_report" value="1">
    <div class="col-md-4"><label class="form-label">Project Name</label><select name="project_name" class="form-select" required><option value="">-- select --</option><?php foreach ($projects as $p): ?><option><?=htmlspecialchars($p['project_name'])?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label">Document Type</label><select name="document_type" class="form-select"><option>Market Scoping</option><option>PPMP</option><option>RFQ</option><option>Bid Evaluation</option></select></div>
    <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option>Generated</option><option>Approved</option><option>Pending</option><option>Archived</option></select></div>
    <div class="col-12"><button class="btn btn-primary">Add Report</button></div>
</form>
<table class="table table-striped"><thead><tr><th>Project</th><th>Document Type</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead><tbody><?php foreach ($reports as $r): ?><tr><td><?=htmlspecialchars($r['project_name'])?></td><td><?=htmlspecialchars($r['document_type'])?></td><td><?=htmlspecialchars($r['status'])?></td><td><?=htmlspecialchars($r['created_at'])?></td><td><a href="#" class="btn btn-sm btn-outline-primary">Download PDF</a> <a href="#" class="btn btn-sm btn-outline-secondary">Archive</a></td></tr><?php endforeach; ?></tbody></table>
</div></body></html>