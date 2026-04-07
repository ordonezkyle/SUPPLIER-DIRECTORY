<?php
require_once 'config.php';

// ensure checklist table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS market_scoping_checklist (
    checklist_id INT AUTO_INCREMENT PRIMARY KEY,
    scoping_id INT NULL,
    consultation TINYINT(1) DEFAULT 0,
    conferences TINYINT(1) DEFAULT 0,
    technical_reports TINYINT(1) DEFAULT 0,
    publications TINYINT(1) DEFAULT 0,
    price_sourcing TINYINT(1) DEFAULT 0,
    philgeps TINYINT(1) DEFAULT 0,
    other_activity TEXT DEFAULT NULL,
    documents VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scoping_id) REFERENCES market_scoping(scoping_id) ON DELETE SET NULL
) ENGINE=InnoDB;");

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_checklist'])) {
        $stmt = $pdo->prepare('INSERT INTO market_scoping_checklist (scoping_id, consultation, conferences, technical_reports, publications, price_sourcing, philgeps, other_activity, documents) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $_POST['scoping_id'] ?: null,
            isset($_POST['consultation']) ? 1 : 0,
            isset($_POST['conferences']) ? 1 : 0,
            isset($_POST['technical_reports']) ? 1 : 0,
            isset($_POST['publications']) ? 1 : 0,
            isset($_POST['price_sourcing']) ? 1 : 0,
            isset($_POST['philgeps']) ? 1 : 0,
            $_POST['other_activity'] ?: null,
            $_POST['documents'] ?: null,
        ]);
    }
}

$scopings = $pdo->query('SELECT scoping_id, project_name FROM market_scoping ORDER BY created_at DESC')->fetchAll();
$checklists = $pdo->query('SELECT msc.*, ms.project_name FROM market_scoping_checklist msc LEFT JOIN market_scoping ms ON ms.scoping_id=msc.scoping_id ORDER BY msc.created_at DESC')->fetchAll();

?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Market Scoping Checklist</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"><style>body{background:url('images/PEZA-background.jpeg') no-repeat center center fixed;background-size:cover;}.container{background-color:rgba(255,255,255,0.95);padding:1rem;border-radius:4px;margin:20px;} .sidebar-link{margin-right:8px;}</style></head><body>
<div class="container">
    <p><a class="sidebar-link btn btn-sm btn-outline-secondary" href="index.php">Directory</a> <a class="sidebar-link" href="market_scoping.php">Back to Market Scoping</a> <a class="sidebar-link" href="pmis_dashboard.php">Dashboard</a></p>

    <form method="post" class="row g-3 mb-3">
        <input type="hidden" name="save_checklist" value="1">
        <div class="col-md-6">
            <label class="form-label">Related Market Scoping Record</label>
            <select name="scoping_id" class="form-select">
                <option value="">-- none --</option>
                <?php foreach ($scopings as $sc): ?>
                <option value="<?=htmlspecialchars($sc['scoping_id'])?>"><?=htmlspecialchars($sc['project_name'])?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12"><strong>Market Scoping Activities</strong></div>
        <?php $boxes = ['consultation'=>'Consultation with suppliers','conferences'=>'Participation in conferences','technical_reports'=>'Review of technical reports','publications'=>'Review of brochures / publications','price_sourcing'=>'Price sourcing from suppliers','philgeps'=>'PhilGEPS data review']; foreach ($boxes as $name => $label): ?>
        <div class="col-md-4 form-check">
            <input class="form-check-input" type="checkbox" name="<?=$name?>" id="<?=$name?>">
            <label class="form-check-label" for="<?=$name?>"><?=htmlspecialchars($label)?></label>
        </div>
        <?php endforeach; ?>

        <div class="col-6"><label class="form-label">Other activity</label><input type="text" name="other_activity" class="form-control"></div>
        <div class="col-6"><label class="form-label">Supporting Documents (URL or name)</label><input type="text" name="documents" class="form-control" placeholder="PDF / DOCX / Excel / Image"></div>

        <div class="col-12"><button class="btn btn-primary">Save Checklist</button></div>
    </form>

    <h2>Saved Checklists</h2>
    <table class="table table-sm table-striped">
        <thead><tr><th>Project</th><th>Consult</th><th>Conf</th><th>Tech</th><th>Pub</th><th>Price</th><th>Philgeps</th><th>Other</th><th>Docs</th><th>When</th></tr></thead>
        <tbody>
            <?php foreach ($checklists as $cl): ?>
            <tr>
                <td><?=htmlspecialchars($cl['project_name'] ?? 'N/A')?></td>
                <td><?= $cl['consultation'] ? '✔' : '—'?></td>
                <td><?= $cl['conferences'] ? '✔' : '—'?></td>
                <td><?= $cl['technical_reports'] ? '✔' : '—'?></td>
                <td><?= $cl['publications'] ? '✔' : '—'?></td>
                <td><?= $cl['price_sourcing'] ? '✔' : '—'?></td>
                <td><?= $cl['philgeps'] ? '✔' : '—'?></td>
                <td><?=htmlspecialchars($cl['other_activity'] ?? '')?></td>
                <td><?=htmlspecialchars($cl['documents'] ?? '')?></td>
                <td><?=htmlspecialchars($cl['created_at'])?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body></html>