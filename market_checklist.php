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
    other_activity_checkbox TINYINT(1) DEFAULT 0,
    other_activity TEXT DEFAULT NULL,
    documents VARCHAR(255) DEFAULT NULL,
    cost_estimate_considered ENUM('Yes','No','Not Applicable') DEFAULT 'Not Applicable',
    cost_estimate_recommendation TEXT DEFAULT NULL,
    design_spec_considered ENUM('Yes','No','Not Applicable') DEFAULT 'Not Applicable',
    design_spec_recommendation TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scoping_id) REFERENCES market_scoping(scoping_id) ON DELETE SET NULL
) ENGINE=InnoDB;");

$newColumns = [
    "cost_estimate_considered ENUM('Yes','No','Not Applicable') DEFAULT 'Not Applicable'",
    "other_activity_checkbox TINYINT(1) DEFAULT 0",
    "cost_estimate_recommendation TEXT DEFAULT NULL",
    "design_spec_considered ENUM('Yes','No','Not Applicable') DEFAULT 'Not Applicable'",
    "design_spec_recommendation TEXT DEFAULT NULL",
];
foreach ($newColumns as $definition) {
    $column = strtok($definition, ' ');
    if (!$pdo->query("SHOW COLUMNS FROM market_scoping_checklist LIKE '{$column}'")->fetch()) {
        $pdo->exec("ALTER TABLE market_scoping_checklist ADD COLUMN {$definition}");
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_checklist'])) {
        $stmt = $pdo->prepare('INSERT INTO market_scoping_checklist (scoping_id, consultation, conferences, technical_reports, publications, price_sourcing, philgeps, other_activity_checkbox, other_activity, documents, cost_estimate_considered, cost_estimate_recommendation, design_spec_considered, design_spec_recommendation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $_POST['scoping_id'] ?: null,
            isset($_POST['consultation']) ? 1 : 0,
            isset($_POST['conferences']) ? 1 : 0,
            isset($_POST['technical_reports']) ? 1 : 0,
            isset($_POST['publications']) ? 1 : 0,
            isset($_POST['price_sourcing']) ? 1 : 0,
            isset($_POST['philgeps']) ? 1 : 0,
            isset($_POST['other_activity_checkbox']) ? 1 : 0,
            $_POST['other_activity'] ?: null,
            $_POST['documents'] ?: null,
            $_POST['cost_estimate_considered'] ?? 'Not Applicable',
            $_POST['cost_estimate_recommendation'] ?: null,
            $_POST['design_spec_considered'] ?? 'Not Applicable',
            $_POST['design_spec_recommendation'] ?: null,
        ]);
    }
}

$scopings = $pdo->query('SELECT scoping_id, project_name FROM market_scoping ORDER BY created_at DESC')->fetchAll();
$checklists = $pdo->query('SELECT msc.*, ms.project_name FROM market_scoping_checklist msc LEFT JOIN market_scoping ms ON ms.scoping_id=msc.scoping_id ORDER BY msc.created_at DESC')->fetchAll();

?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Market Scoping Checklist</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"><style>body{background:url('images/PEZA-background.jpeg') no-repeat center center fixed;background-size:cover;}.container{background-color:rgba(255,255,255,0.95);padding:1rem;border-radius:4px;margin:20px;} .sidebar-link{margin-right:8px;}.page-break{page-break-before:always;margin-top:2rem;}</style></head><body>
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

        <div class="col-12 page-break"></div>

        <div class="col-12"><strong>3. MARKET SCOPING ACTIVITY/IES CONDUCTED</strong></div>
        <div class="col-12">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light"><tr><th style="width:8%;">Check (✓)</th><th style="width:52%;">Activity/ies Conducted</th><th>Documentation (as may be applicable)</th></tr></thead>
                    <tbody>
                        <tr>
                            <td class="text-center align-middle"><input class="form-check-input" type="checkbox" name="price_sourcing" id="price_sourcing" <?= isset($_POST['price_sourcing']) ? 'checked' : '' ?>></td>
                            <td>Price sourcing for quotations or cost estimates from suppliers, contractors, or consultants</td>
                            <td>Price quotations/ Canvass sheets/ Online Product Reviews</td>
                        </tr>
                        <tr>
                            <td class="text-center align-middle"><input class="form-check-input" type="checkbox" name="philgeps" id="philgeps" <?= isset($_POST['philgeps']) ? 'checked' : '' ?>></td>
                            <td>Use of data from PhilGEPS or agency websites</td>
                            <td>Reports / Summaries / Screenshots / Price quotations / Canvass sheets/ PhilGEPS Postings/ Online Product Reviews</td>
                        </tr>
                        <tr>
                            <td class="text-center align-middle"><input class="form-check-input" type="checkbox" name="other_activity_checkbox" id="other_activity_checkbox" <?= isset($_POST['other_activity_checkbox']) ? 'checked' : '' ?>></td>
                            <td>Other analogous market scoping activity/ies undertaken:<br><input type="text" name="other_activity" class="form-control" value="<?=htmlspecialchars($_POST['other_activity'] ?? '')?>"></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-12">
            <p class="mb-1"><strong>Notes:</strong></p>
            <p class="mb-1">i. The market scoping activities shall be identified and undertaken at the option of the End-User or Implementing Unit based on its needs and objectives.</p>
            <p class="mb-0">ii. The list of supporting documents in the Documentation column is not exclusive and may include other documents that may be gathered by the End-User or Implementing Unit pertinent to the activity/ies conducted.</p>
        </div>

        <div class="col-12 page-break"></div>
        <div class="col-12"><h2>4. MARKET SCOPING RESULTS</h2></div>
        <div class="col-12">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Parameters</th>
                            <th>Considered? (Yes/No/Not Applicable)</th>
                            <th>Recommendations based on the Market Scoping (Attach additional documents if necessary)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>a. Project Cost Estimate</strong><div class="small text-muted">Does the cost estimate align with current market prices?</div></td>
                            <td>
                                <select name="cost_estimate_considered" class="form-select">
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                    <option value="Not Applicable">Not Applicable</option>
                                </select>
                            </td>
                            <td><textarea name="cost_estimate_recommendation" class="form-control" rows="3"></textarea></td>
                        </tr>
                        <tr>
                            <td><strong>b. Project Design and Specification</strong><div class="small text-muted">Does available supplier/s meet technical and financial requirements?</div></td>
                            <td>
                                <select name="design_spec_considered" class="form-select">
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                    <option value="Not Applicable">Not Applicable</option>
                                </select>
                            </td>
                            <td><textarea name="design_spec_recommendation" class="form-control" rows="3"></textarea></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-12"><button class="btn btn-primary">Save Checklist</button></div>
    </form>

    <h2>Saved Checklists</h2>
    <table class="table table-sm table-striped">
        <thead><tr><th>Project</th><th>Consult</th><th>Conf</th><th>Tech</th><th>Pub</th><th>Price</th><th>Philgeps</th><th>Other</th><th>Docs</th><th>Cost</th><th>Design</th><th>When</th></tr></thead>
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
                <td><?=htmlspecialchars($cl['cost_estimate_considered'] ?? 'N/A')?></td>
                <td><?=htmlspecialchars($cl['design_spec_considered'] ?? 'N/A')?></td>
                <td><?=htmlspecialchars($cl['created_at'])?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body></html>