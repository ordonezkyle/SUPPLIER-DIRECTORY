<?php
require_once 'config.php';

// checklist table
$pdo->exec("CREATE TABLE IF NOT EXISTS market_scoping_checklist (
    checklist_id INT AUTO_INCREMENT PRIMARY KEY,
    procurement_entity VARCHAR(255) DEFAULT 'Philippine Economic Zone Authority',
    end_user_unit VARCHAR(255) DEFAULT NULL,
    representative_name VARCHAR(255) DEFAULT NULL,
    project_name VARCHAR(255) DEFAULT NULL,
    estimated_budget VARCHAR(100) DEFAULT NULL,
    period_from VARCHAR(20) DEFAULT NULL,
    period_to VARCHAR(20) DEFAULT NULL,
    expected_delivery_date VARCHAR(20) DEFAULT NULL,
    consultation TINYINT(1) DEFAULT 0,
    conferences TINYINT(1) DEFAULT 0,
    technical_reports TINYINT(1) DEFAULT 0,
    publications TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_checklist'])) {
    $isAutoSave = isset($_POST['auto_save']);
    
    $stmt = $pdo->prepare('INSERT INTO market_scoping_checklist (procurement_entity, end_user_unit, representative_name, project_name, estimated_budget, period_from, period_to, expected_delivery_date, consultation, conferences, technical_reports, publications) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $_POST['procurement_entity'] ?: 'Philippine Economic Zone Authority',
        $_POST['end_user_unit'] ?: null,
        $_POST['representative_name'] ?: null,
        $_POST['project_name'] ?: null,
        $_POST['estimated_budget'] ?: null,
        $_POST['period_from'] ?: null,
        $_POST['period_to'] ?: null,
        $_POST['expected_delivery_date'] ?: null,
        isset($_POST['consultation']) ? 1 : 0,
        isset($_POST['conferences']) ? 1 : 0,
        isset($_POST['technical_reports']) ? 1 : 0,
        isset($_POST['publications']) ? 1 : 0,
    ]);
    if (!$isAutoSave) {
        // No redirect for this form
    }
}

$checklists = $pdo->query('SELECT * FROM market_scoping_checklist ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MARKET SCOPING CHECKLIST</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background:url('images/PEZA-background.jpeg') no-repeat center center fixed; background-size:cover; }
        .container { background:rgba(255,255,255,0.98); padding:1.75rem; margin:20px auto; border-radius:8px; max-width:1100px; }
        .checklist-heading { text-transform: uppercase; letter-spacing:.12em; font-weight:700; font-size:1.75rem; }
        .section-title { font-weight:700; margin-top:1.75rem; margin-bottom:.75rem; }
        .table-bordered td, .table-bordered th { border:1px solid #333 !important; }
        .table thead th { border-bottom:2px solid #333; }
        .table thead th, .table td { padding:.85rem; }
        .table td { vertical-align: middle; }
        .form-control, .form-check-input { border-radius:0; }
        .subtext { font-size:.92rem; color:#555; }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="checklist-heading">MARKET SCOPING CHECKLIST</div>
        <div>
            <a href="index.php" class="btn btn-sm btn-outline-secondary me-2">Directory</a>
            <a href="market_scoping.php" class="btn btn-sm btn-outline-primary">Market Scoping</a>
        </div>
    </div>

    <form method="post" id="analysisForm">
        <input type="hidden" name="save_checklist" value="1">

        <div class="section-title">1. Agency Information</div>
        <div class="table-responsive mb-4">
            <table class="table table-bordered mb-0">
                <tbody>
                    <tr>
                        <td style="width:35%;"><strong>Name of Procuring Entity</strong></td>
                        <td><input type="text" name="procurement_entity" class="form-control" value="<?=htmlspecialchars($_POST['procurement_entity'] ?? 'Philippine Economic Zone Authority')?>" required></td>
                    </tr>
                    <tr>
                        <td><strong>End-User/Implementing Unit</strong></td>
                        <td><input type="text" name="end_user_unit" class="form-control" value="<?=htmlspecialchars($_POST['end_user_unit'] ?? '')?>" required></td>
                    </tr>
                    <tr>
                        <td><strong>Name & Designation of Representative</strong></td>
                        <td><input type="text" name="representative_name" class="form-control" value="<?=htmlspecialchars($_POST['representative_name'] ?? '')?>" required></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section-title">2. Project Overview</div>
        <div class="table-responsive mb-4">
            <table class="table table-bordered mb-0">
                <tbody>
                    <tr>
                        <td style="width:35%;"><strong>Project Name</strong></td>
                        <td><input type="text" name="project_name" class="form-control" value="<?=htmlspecialchars($_POST['project_name'] ?? '')?>"></td>
                    </tr>
                    <tr>
                        <td><strong>Estimated Budget</strong></td>
                        <td><input type="text" name="estimated_budget" class="form-control" value="<?=htmlspecialchars($_POST['estimated_budget'] ?? '')?>"></td>
                    </tr>
                    <tr>
                        <td><strong>Period of Market Scoping</strong><br><span class="subtext">[From (mm/yyyy) To (mm/yyyy)]</span></td>
                        <td>
                            <div class="row g-2">
                                <div class="col-md-6"><input type="month" name="period_from" class="form-control" value="<?=htmlspecialchars($_POST['period_from'] ?? '')?>"></div>
                                <div class="col-md-6"><input type="month" name="period_to" class="form-control" value="<?=htmlspecialchars($_POST['period_to'] ?? '')?>"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Expected Date of Delivery</strong><br><span class="subtext">(mm/yyyy)</span></td>
                        <td><input type="month" name="expected_delivery_date" class="form-control" value="<?=htmlspecialchars($_POST['expected_delivery_date'] ?? '')?>"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section-title">3. MARKET SCOPING ACTIVITY/IES CONDUCTED</div>
        <p class="subtext mb-3">(Check all that apply) This confirms that market scoping activities were conducted in accordance with Section 10 of Republic Act No. 12009 and its Implementing Rules and Regulations (IRR), and considered in the Project Procurement Management Plan, consistent with the Principle of Proportionality.</p>
        <div class="table-responsive mb-4">
            <table class="table table-bordered mb-0">
                <thead class="table-light"><tr><th style="width:8%;">Check (✓)</th><th style="width:35%;">Activity/ies Conducted</th><th>Documentation (as may be applicable)</th></tr></thead>
                <tbody>
                    <tr>
                        <td class="text-center align-middle"><input class="form-check-input" type="checkbox" name="consultation" <?= isset($_POST['consultation']) ? 'checked' : '' ?>></td>
                        <td>Consultations with suppliers / contractors / consultants / professional associations or industry groups</td>
                        <td>Highlights of consultations or meetings / Proof of attendance / Reports / Summaries / Screenshots / Brochures / Publications / Price quotations / Canvass sheets / Market Analysis Report or similar document/s</td>
                    </tr>
                    <tr>
                        <td class="text-center align-middle"><input class="form-check-input" type="checkbox" name="conferences" <?= isset($_POST['conferences']) ? 'checked' : '' ?>></td>
                        <td>Participation in summits, fora, or conferences</td>
                        <td>Highlights of consultations or meetings / Proof of Attendance / Reports</td>
                    </tr>
                    <tr>
                        <td class="text-center align-middle"><input class="form-check-input" type="checkbox" name="technical_reports" <?= isset($_POST['technical_reports']) ? 'checked' : '' ?>></td>
                        <td>Review of technical, financial, or market/scientific reports</td>
                        <td>Reports / Summaries / Screenshots / Brochures / Publications / Market Analysis Report or similar document / Online Product Reviews</td>
                    </tr>
                    <tr>
                        <td class="text-center align-middle"><input class="form-check-input" type="checkbox" name="publications" <?= isset($_POST['publications']) ? 'checked' : '' ?>></td>
                        <td>Review of product or service brochures, marketing materials, industry journals and publications or related materials</td>
                        <td>Reports / Summaries / Screenshots / Brochures / Publications / Online Product Reviews</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <button type="submit" class="btn btn-primary">Submit Checklist</button>
    </form>
</div>

<script>
let autoSaveTimer;
let isAutoSaving = false;

function autoSave() {
    if (isAutoSaving) return;
    isAutoSaving = true;
    
    const form = document.getElementById('analysisForm');
    if (!form) return;
    const formData = new FormData(form);
    formData.append('auto_save', '1');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(() => {
        // Show saved indicator
        showSaveIndicator();
        isAutoSaving = false;
    })
    .catch(() => {
        isAutoSaving = false;
    });
}

function showSaveIndicator() {
    let indicator = document.getElementById('autoSaveIndicator');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'autoSaveIndicator';
        indicator.style.cssText = 'position:fixed;top:20px;right:20px;background:#28a745;color:white;padding:10px;border-radius:4px;z-index:1000;';
        document.body.appendChild(indicator);
    }
    indicator.textContent = 'Auto-saved';
    indicator.style.display = 'block';
    setTimeout(() => {
        indicator.style.display = 'none';
    }, 2000);
}

function startAutoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(autoSave, 2000); // Save after 2 seconds of inactivity
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('analysisForm');
    if (form) {
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', startAutoSave);
            input.addEventListener('change', startAutoSave);
        });
    }
});
</script>

</body>
</html>