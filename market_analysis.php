<?php
require_once 'config.php';

// analysis table
$pdo->exec("CREATE TABLE IF NOT EXISTS market_analysis (
    analysis_id INT AUTO_INCREMENT PRIMARY KEY,
    scoping_id INT NULL,
    cost_estimate VARCHAR(20) DEFAULT NULL,
    technical_criteria VARCHAR(200) DEFAULT NULL,
    delivery_lead_time VARCHAR(50) DEFAULT NULL,
    storage_requirements VARCHAR(200) DEFAULT NULL,
    identified_risks VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scoping_id) REFERENCES market_scoping(scoping_id) ON DELETE SET NULL
) ENGINE=InnoDB;");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_analysis'])) {
    $stmt = $pdo->prepare('INSERT INTO market_analysis (scoping_id, cost_estimate, technical_criteria, delivery_lead_time, storage_requirements, identified_risks) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $_POST['scoping_id'] ?: null,
        $_POST['cost_estimate'] ?: null,
        $_POST['technical_criteria'] ?: null,
        $_POST['delivery_lead_time'] ?: null,
        $_POST['storage_requirements'] ?: null,
        $_POST['identified_risks'] ?: null,
    ]);
}

$scopings = $pdo->query('SELECT scoping_id, project_name FROM market_scoping ORDER BY created_at DESC')->fetchAll();
$analysisRows = $pdo->query('SELECT ma.*, ms.project_name FROM market_analysis ma LEFT JOIN market_scoping ms ON ms.scoping_id=ma.scoping_id ORDER BY ma.created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Market Analysis Results</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"><style>body{ background:url('images/PEZA-background.jpeg') no-repeat center center fixed;background-size:cover; } .container{background:rgba(255,255,255,0.95);padding:1rem;margin:20px;border-radius:4px;}</style></head><body>
<div class="container">
    <h1>Market Analysis Results</h1>
    <p><a href="market_scoping.php">Back to Market Scoping</a></p>
    <form method="post" class="row g-3 mb-3">
        <input type="hidden" name="save_analysis" value="1">
        <div class="col-md-6">
            <label class="form-label">For Market Scoping Record</label>
            <select name="scoping_id" class="form-select"><option value="">-- Select project --</option><?php foreach ($scopings as $sc): ?><option value="<?=htmlspecialchars($sc['scoping_id'])?>"><?=htmlspecialchars($sc['project_name'])?></option><?php endforeach; ?></select>
        </div>
        <div class="col-md-6"><label class="form-label">Cost Estimate</label><select name="cost_estimate" class="form-select"><option value="">Choose</option><option>Yes</option><option>No</option><option>N/A</option></select></div>
        <div class="col-md-6"><label class="form-label">Technical Criteria</label><select name="technical_criteria" class="form-select"><option value="">Choose</option><option>Yes</option><option>No</option><option>N/A</option></select></div>
        <div class="col-md-6"><label class="form-label">Delivery Lead Time</label><select name="delivery_lead_time" class="form-select"><option value="">Choose</option><option>Yes</option><option>No</option><option>N/A</option></select></div>
        <div class="col-md-6"><label class="form-label">Storage Requirements</label><input type="text" name="storage_requirements" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Identified Risks</label><input type="text" name="identified_risks" class="form-control"></div>
        <div class="col-12"><button class="btn btn-primary">Save Analysis</button></div>
    </form>
    <h2>Saved Analysis Entries</h2>
    <table class="table table-striped"><thead><tr><th>Project</th><th>Cost</th><th>Technical</th><th>Delivery</th><th>Storage</th><th>Risks</th><th>Created</th></tr></thead><tbody><?php foreach ($analysisRows as $r): ?><tr><td><?=htmlspecialchars($r['project_name'] ?? 'N/A')?></td><td><?=htmlspecialchars($r['cost_estimate'])?></td><td><?=htmlspecialchars($r['technical_criteria'])?></td><td><?=htmlspecialchars($r['delivery_lead_time'])?></td><td><?=htmlspecialchars($r['storage_requirements'])?></td><td><?=htmlspecialchars($r['identified_risks'])?></td><td><?=htmlspecialchars($r['created_at'])?></td></tr><?php endforeach; ?></tbody></table>
</div>
</body></html>