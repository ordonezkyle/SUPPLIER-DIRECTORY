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
    price_sourcing TINYINT(1) DEFAULT 0,
    philgeps TINYINT(1) DEFAULT 0,
    other_analogous_activity TINYINT(1) DEFAULT 0,
    other_analogous_activity_text VARCHAR(255) DEFAULT NULL,
    cost_estimate_considered ENUM('Yes','No','Not Applicable') DEFAULT 'Not Applicable',
    cost_estimate_recommendation TEXT DEFAULT NULL,
    design_spec_considered ENUM('Yes','No','Not Applicable') DEFAULT 'Not Applicable',
    design_spec_recommendation TEXT DEFAULT NULL,
    technical_criteria_considered ENUM('Yes','No','Not Applicable') DEFAULT 'Not Applicable',
    technical_criteria_recommendation TEXT DEFAULT NULL,
    delivery_lead_time_considered ENUM('Yes','No','Not Applicable') DEFAULT 'Not Applicable',
    delivery_lead_time_recommendation TEXT DEFAULT NULL,
    storage_warehousing_considered ENUM('Yes','No','Not Applicable') DEFAULT 'Not Applicable',
    storage_warehousing_recommendation TEXT DEFAULT NULL,
    identified_risks_considered ENUM('Yes','No','Not Applicable') DEFAULT 'Not Applicable',
    identified_risks_recommendation TEXT DEFAULT NULL,
    prepared_by_name VARCHAR(255) DEFAULT NULL,
    prepared_by_position VARCHAR(255) DEFAULT NULL,
    prepared_by_date VARCHAR(50) DEFAULT NULL,
    prepared_by_signature TEXT DEFAULT NULL,
    approved_by_name VARCHAR(255) DEFAULT NULL,
    approved_by_position VARCHAR(255) DEFAULT NULL,
    approved_by_date VARCHAR(50) DEFAULT NULL,
    approved_by_signature TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;");

$newColumns = [
    "procurement_entity VARCHAR(255) DEFAULT 'Philippine Economic Zone Authority'",
    "end_user_unit VARCHAR(255) DEFAULT NULL",
    "representative_name VARCHAR(255) DEFAULT NULL",
    "project_name VARCHAR(255) DEFAULT NULL",
    "estimated_budget VARCHAR(100) DEFAULT NULL",
    "period_from VARCHAR(20) DEFAULT NULL",
    "period_to VARCHAR(20) DEFAULT NULL",
    "expected_delivery_date VARCHAR(20) DEFAULT NULL",
    "price_sourcing TINYINT(1) DEFAULT 0",
    "philgeps TINYINT(1) DEFAULT 0",
    "other_analogous_activity TINYINT(1) DEFAULT 0",
    "other_analogous_activity_text VARCHAR(255) DEFAULT NULL",
    "cost_estimate_considered ENUM('Yes','No','Not Applicable') DEFAULT 'Not Applicable'",
    "cost_estimate_recommendation TEXT DEFAULT NULL",
    "design_spec_considered ENUM('Yes','No','Not Applicable') DEFAULT 'Not Applicable'",
    "design_spec_recommendation TEXT DEFAULT NULL",
    "technical_criteria_considered ENUM('Yes','No','Not Applicable') DEFAULT 'Not Applicable'",
    "technical_criteria_recommendation TEXT DEFAULT NULL",
    "delivery_lead_time_considered ENUM('Yes','No','Not Applicable') DEFAULT 'Not Applicable'",
    "delivery_lead_time_recommendation TEXT DEFAULT NULL",
    "storage_warehousing_considered ENUM('Yes','No','Not Applicable') DEFAULT 'Not Applicable'",
    "storage_warehousing_recommendation TEXT DEFAULT NULL",
    "identified_risks_considered ENUM('Yes','No','Not Applicable') DEFAULT 'Not Applicable'",
    "identified_risks_recommendation TEXT DEFAULT NULL",
    "prepared_by_name VARCHAR(255) DEFAULT NULL",
    "prepared_by_position VARCHAR(255) DEFAULT NULL",
    "prepared_by_date VARCHAR(50) DEFAULT NULL",
    "prepared_by_signature TEXT DEFAULT NULL",
    "approved_by_name VARCHAR(255) DEFAULT NULL",
    "approved_by_position VARCHAR(255) DEFAULT NULL",
    "approved_by_date VARCHAR(50) DEFAULT NULL",
    "approved_by_signature TEXT DEFAULT NULL",
];
foreach ($newColumns as $definition) {
    $column = strtok($definition, ' ');
    if (!$pdo->query("SHOW COLUMNS FROM market_scoping_checklist LIKE '{$column}'")->fetch()) {
        $pdo->exec("ALTER TABLE market_scoping_checklist ADD COLUMN {$definition}");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_checklist'])) {
    $stmt = $pdo->prepare('INSERT INTO market_scoping_checklist (procurement_entity, end_user_unit, representative_name, project_name, estimated_budget, period_from, period_to, expected_delivery_date, consultation, conferences, technical_reports, publications, price_sourcing, philgeps, other_analogous_activity, other_analogous_activity_text, cost_estimate_considered, cost_estimate_recommendation, design_spec_considered, design_spec_recommendation, technical_criteria_considered, technical_criteria_recommendation, delivery_lead_time_considered, delivery_lead_time_recommendation, storage_warehousing_considered, storage_warehousing_recommendation, identified_risks_considered, identified_risks_recommendation, prepared_by_name, prepared_by_position, prepared_by_date, prepared_by_signature, approved_by_name, approved_by_position, approved_by_date, approved_by_signature) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
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
        isset($_POST['price_sourcing']) ? 1 : 0,
        isset($_POST['philgeps']) ? 1 : 0,
        isset($_POST['other_analogous_activity']) ? 1 : 0,
        $_POST['other_analogous_activity_text'] ?: null,
        $_POST['cost_estimate_considered'] ?? 'Not Applicable',
        $_POST['cost_estimate_recommendation'] ?: null,
        $_POST['design_spec_considered'] ?? 'Not Applicable',
        $_POST['design_spec_recommendation'] ?: null,
        $_POST['technical_criteria_considered'] ?? 'Not Applicable',
        $_POST['technical_criteria_recommendation'] ?: null,
        $_POST['delivery_lead_time_considered'] ?? 'Not Applicable',
        $_POST['delivery_lead_time_recommendation'] ?: null,
        $_POST['storage_warehousing_considered'] ?? 'Not Applicable',
        $_POST['storage_warehousing_recommendation'] ?: null,
        $_POST['identified_risks_considered'] ?? 'Not Applicable',
        $_POST['identified_risks_recommendation'] ?: null,
        $_POST['prepared_by_name'] ?: null,
        $_POST['prepared_by_position'] ?: null,
        $_POST['prepared_by_date'] ?: null,
        $_POST['prepared_by_signature'] ?: null,
        $_POST['approved_by_name'] ?: null,
        $_POST['approved_by_position'] ?: null,
        $_POST['approved_by_date'] ?: null,
        $_POST['approved_by_signature'] ?: null,
    ]);
    $lastInsertedId = $pdo->lastInsertId();
    $submittedChecklist = $pdo->query("SELECT * FROM market_scoping_checklist WHERE checklist_id = $lastInsertedId")->fetch();
}

$checklists = $pdo->query('SELECT * FROM market_scoping_checklist ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MARKET SCOPING CHECKLIST</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background:url('images/PEZA-background.jpeg') no-repeat center center fixed; background-size:cover; }
        .container { background:rgba(255,255,255,0.99); padding:1.5rem; margin:20px auto; border-radius:8px; max-width:950px; }
        .doc-header { border-bottom: 0; padding-bottom: 0; margin-bottom: 0.8rem; page-break-after: avoid; }
        .doc-header img { width: 100%; height: auto; display: block; border-bottom: 2px solid #000; }
        .doc-title-main { font-size: 1.3rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; margin: 0.6rem 0; text-align: center; }
        .doc-title-line { height: 2px; background: #000; margin: 0.5rem auto 0.8rem; width: 350px; }
        .section-title { font-weight: 700; margin-top: 1.2rem; margin-bottom: 0.6rem; font-size: 0.95rem; page-break-after: avoid; }
        .table { border-collapse: collapse; width: 100%; }
        .table-bordered > :not(caption) > * > * { border: 1px solid #333 !important; }
        .table thead th, .table td { padding: 0.65rem; font-size: 0.9rem; }
        .table thead th { background: #f5f5f5; border-bottom: 2px solid #333 !important; font-weight: 600; }
        .form-control, .form-select, .form-check-input { border-radius: 0; border: 1px solid #999; font-size: 0.9rem; }
        .table-bordered td, .table-bordered th { border: 1px solid #333 !important; }
        .table td { vertical-align: middle; }
        .signature-pad { width: 100%; height: 120px; border: 1px solid #ccc; background: #fff; touch-action: none; }
        .signature-actions { margin-top: 0.4rem; font-size: 0.85rem; }
        .signature-preview { max-width: 100%; border: 1px solid #ddd; margin-top: 0.4rem; display: none; }
        .print-only { display: none; }
        .print-page-break { display: none; }
        .not-print { display: block; }
        .subtext { font-size: 0.85rem; color: #666; }
        .notes-section { font-size: 0.9rem; line-height: 1.3; }
        .action-buttons { display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .table-responsive { overflow-x: auto; }
        .mb-3 { margin-bottom: 0.75rem !important; }
        .mb-4 { margin-bottom: 1rem !important; }
        .mt-3 { margin-top: 0.75rem !important; }
        .mt-4 { margin-top: 1rem !important; }
        .pt-3 { padding-top: 0.75rem !important; }
        .text-center { text-align: center; }
        @page { size: A4 portrait; margin: 15mm 15mm 15mm 15mm; }
        @media print {
            .btn, .signature-actions, .alert, .not-print { display: none !important; }
            body { background: white !important; font-family: Arial, sans-serif; margin: 0; padding: 0; }
            .container { background: white !important; padding: 0; margin: 0; max-width: none; border-radius: 0; }
            .signature-pad { display: none; }
            .signature-preview { display: block !important; }
            .action-buttons { display: none; }
            .print-only { display: block !important; }
            .print-page-break { display: block !important; page-break-before: always; margin: 0; padding: 0; }
            .doc-header { margin-bottom: 0.5rem; }
            .doc-title-main { margin: 0.4rem 0; font-size: 1.1rem; }
            .doc-title-line { margin: 0.4rem auto 0.5rem; width: 300px; }
            .section-title { margin-top: 0.8rem; margin-bottom: 0.4rem; }
            .table-responsive, .table, .section-title, .doc-header { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="container" id="printableArea">
    <div class="doc-header">
        <img src="images/PEZA_Header.jpeg" alt="PEZA Header">
    </div>
    <div class="text-center mb-3">
        <p class="doc-title-main">MARKET SCOPING CHECKLIST</p>
        <div class="doc-title-line"></div>
    </div>
    <div class="action-buttons not-print">
        <a href="index.php" class="btn btn-sm btn-outline-secondary">Directory</a>
        <a href="market_scoping.php" class="btn btn-sm btn-outline-primary">Market Scoping</a>
    </div>

    <form method="post">
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

        <div class="print-page-break"></div>
        <div class="doc-header print-only">
            <img src="images/PEZA_Header.jpeg" alt="PEZA Header">
        </div>
        <div class="text-center print-only" style="margin-bottom: 0.8rem;">
            <p class="doc-title-main" style="margin-bottom: 0.3rem;">MARKET SCOPING CHECKLIST</p>
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-bordered mb-0">
                <thead class="table-light"><tr><th style="width:8%;">Check (✓)</th><th style="width:35%;">Activity/ies Conducted</th><th>Documentation (as may be applicable)</th></tr></thead>
                <tbody>
                    <tr>
                        <td class="text-center align-middle"><input class="form-check-input" type="checkbox" name="price_sourcing" <?= isset($_POST['price_sourcing']) ? 'checked' : '' ?>></td>
                        <td>Price sourcing for quotations or cost estimates from suppliers, contractors, or consultants</td>
                        <td>Price quotations/ Canvass sheets/ Online Product Reviews</td>
                    </tr>
                    <tr>
                        <td class="text-center align-middle"><input class="form-check-input" type="checkbox" name="philgeps" <?= isset($_POST['philgeps']) ? 'checked' : '' ?>></td>
                        <td>Use of data from PhilGEPS or agency websites</td>
                        <td>Reports / Summaries / Screenshots / Price quotations/ Canvass sheets/ PhilGEPS Postings/ Online Product Reviews</td>
                    </tr>
                    <tr>
                        <td class="text-center align-middle"><input class="form-check-input" type="checkbox" name="other_analogous_activity" <?= isset($_POST['other_analogous_activity']) ? 'checked' : '' ?>></td>
                        <td>Other analogous market scoping activity/ies undertaken:<br><input type="text" name="other_analogous_activity_text" class="form-control mt-2" value="<?=htmlspecialchars($_POST['other_analogous_activity_text'] ?? '')?>"></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mb-4">
            <p class="mb-1"><strong>Notes:</strong></p>
            <p class="mb-1">i. The market scoping activities shall be identified and undertaken at the option of the End-User or Implementing Unit based on its needs and objectives.</p>
            <p class="mb-0">ii. The list of supporting documents in the Documentation column is not exclusive and may include other documents that may be gathered by the End-User or Implementing Unit pertinent to the activity/ies conducted.</p>
        </div>

        <div class="print-page-break"></div>
        <div class="doc-header print-only">
            <img src="images/PEZA_Header.jpeg" alt="PEZA Header">
        </div>
        <div class="text-center print-only" style="margin-bottom: 0.8rem;">
            <p class="doc-title-main" style="margin-bottom: 0.3rem;">MARKET SCOPING CHECKLIST</p>
        </div>
        <div class="section-title">4. MARKET SCOPING RESULTS</div>
        <div class="table-responsive mb-4">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:35%;">Parameters</th>
                        <th style="width:25%;">Considered? (Yes/No/Not Applicable)</th>
                        <th>Recommendations based on the Market Scoping (Attach additional documents if necessary)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>a. Project Cost Estimate</strong><div class="subtext">Does the cost estimate align with current market prices?</div></td>
                        <td>
                            <select name="cost_estimate_considered" class="form-select">
                                <option value="Yes" <?= (($_POST['cost_estimate_considered'] ?? '') === 'Yes') ? 'selected' : '' ?>>Yes</option>
                                <option value="No" <?= (($_POST['cost_estimate_considered'] ?? '') === 'No') ? 'selected' : '' ?>>No</option>
                                <option value="Not Applicable" <?= (($_POST['cost_estimate_considered'] ?? '') === 'Not Applicable') ? 'selected' : '' ?>>Not Applicable</option>
                            </select>
                        </td>
                        <td><textarea name="cost_estimate_recommendation" class="form-control" rows="3"><?=htmlspecialchars($_POST['cost_estimate_recommendation'] ?? '')?></textarea></td>
                    </tr>
                    <tr>
                        <td><strong>b. Project Design and Specification</strong><div class="subtext">Does available supplier/s meet technical and financial requirements?</div></td>
                        <td>
                            <select name="design_spec_considered" class="form-select">
                                <option value="Yes" <?= (($_POST['design_spec_considered'] ?? '') === 'Yes') ? 'selected' : '' ?>>Yes</option>
                                <option value="No" <?= (($_POST['design_spec_considered'] ?? '') === 'No') ? 'selected' : '' ?>>No</option>
                                <option value="Not Applicable" <?= (($_POST['design_spec_considered'] ?? '') === 'Not Applicable') ? 'selected' : '' ?>>Not Applicable</option>
                            </select>
                        </td>
                        <td><textarea name="design_spec_recommendation" class="form-control" rows="3"><?=htmlspecialchars($_POST['design_spec_recommendation'] ?? '')?></textarea></td>
                    </tr>
                    <tr>
                        <td><strong>c. Technical Criteria</strong><div class="subtext">[Does the market support the proposed technical requirements?]</div></td>
                        <td>
                            <select name="technical_criteria_considered" class="form-select">
                                <option value="Yes" <?= (($_POST['technical_criteria_considered'] ?? '') === 'Yes') ? 'selected' : '' ?>>Yes</option>
                                <option value="No" <?= (($_POST['technical_criteria_considered'] ?? '') === 'No') ? 'selected' : '' ?>>No</option>
                                <option value="Not Applicable" <?= (($_POST['technical_criteria_considered'] ?? '') === 'Not Applicable') ? 'selected' : '' ?>>Not Applicable</option>
                            </select>
                        </td>
                        <td><textarea name="technical_criteria_recommendation" class="form-control" rows="3"><?=htmlspecialchars($_POST['technical_criteria_recommendation'] ?? '')?></textarea></td>
                    </tr>
                    <tr>
                        <td><strong>d. Delivery Lead Time</strong><div class="subtext">[Are the timelines for delivery feasible?]</div></td>
                        <td>
                            <select name="delivery_lead_time_considered" class="form-select">
                                <option value="Yes" <?= (($_POST['delivery_lead_time_considered'] ?? '') === 'Yes') ? 'selected' : '' ?>>Yes</option>
                                <option value="No" <?= (($_POST['delivery_lead_time_considered'] ?? '') === 'No') ? 'selected' : '' ?>>No</option>
                                <option value="Not Applicable" <?= (($_POST['delivery_lead_time_considered'] ?? '') === 'Not Applicable') ? 'selected' : '' ?>>Not Applicable</option>
                            </select>
                        </td>
                        <td><textarea name="delivery_lead_time_recommendation" class="form-control" rows="3"><?=htmlspecialchars($_POST['delivery_lead_time_recommendation'] ?? '')?></textarea></td>
                    </tr>
                    <tr>
                        <td><strong>e. Storage and Warehousing Requirements</strong><div class="subtext">[Can the storage/warehousing needs be met considering specific conditions like temperature, humidity, and handling?]</div></td>
                        <td>
                            <select name="storage_warehousing_considered" class="form-select">
                                <option value="Yes" <?= (($_POST['storage_warehousing_considered'] ?? '') === 'Yes') ? 'selected' : '' ?>>Yes</option>
                                <option value="No" <?= (($_POST['storage_warehousing_considered'] ?? '') === 'No') ? 'selected' : '' ?>>No</option>
                                <option value="Not Applicable" <?= (($_POST['storage_warehousing_considered'] ?? '') === 'Not Applicable') ? 'selected' : '' ?>>Not Applicable</option>
                            </select>
                        </td>
                        <td><textarea name="storage_warehousing_recommendation" class="form-control" rows="3"><?=htmlspecialchars($_POST['storage_warehousing_recommendation'] ?? '')?></textarea></td>
                    </tr>
                    <tr>
                        <td><strong>f. Identified Risk/s</strong><div class="subtext">[Were there any market risks identified? (e.g., limited suppliers, price volatility)]</div></td>
                        <td>
                            <select name="identified_risks_considered" class="form-select">
                                <option value="Yes" <?= (($_POST['identified_risks_considered'] ?? '') === 'Yes') ? 'selected' : '' ?>>Yes</option>
                                <option value="No" <?= (($_POST['identified_risks_considered'] ?? '') === 'No') ? 'selected' : '' ?>>No</option>
                                <option value="Not Applicable" <?= (($_POST['identified_risks_considered'] ?? '') === 'Not Applicable') ? 'selected' : '' ?>>Not Applicable</option>
                            </select>
                        </td>
                        <td><textarea name="identified_risks_recommendation" class="form-control" rows="3"><?=htmlspecialchars($_POST['identified_risks_recommendation'] ?? '')?></textarea></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <p class="mb-1"><strong>Prepared by:</strong><br>Personnel-in-Charge, End-User or Implementing Unit</p>
                <input type="text" name="prepared_by_name" class="form-control mb-2" placeholder="[Signature over Printed Name]" value="<?=htmlspecialchars($_POST['prepared_by_name'] ?? '')?>">
                <input type="text" name="prepared_by_position" class="form-control mb-2" placeholder="[Position/Designation]" value="<?=htmlspecialchars($_POST['prepared_by_position'] ?? '')?>">
                <input type="text" name="prepared_by_date" class="form-control mb-2" placeholder="[Date]" value="<?=htmlspecialchars($_POST['prepared_by_date'] ?? '')?>">
                <label class="form-label">Digital Signature</label>
                <canvas id="preparedSignaturePad" class="signature-pad"></canvas>
                <div class="signature-actions">
                    <button type="button" class="btn btn-sm btn-secondary me-2" onclick="clearSignature('prepared')">Clear</button>
                    <span class="text-muted">Draw your signature here.</span>
                </div>
                <img id="preparedSignatureImg" class="signature-preview" src="<?=htmlspecialchars($_POST['prepared_by_signature'] ?? '')?>" alt="Prepared Signature">
                <input type="hidden" name="prepared_by_signature" id="prepared_by_signature" value="<?=htmlspecialchars($_POST['prepared_by_signature'] ?? '')?>">
            </div>
            <div class="col-md-6">
                <p class="mb-1"><strong>Approved by:</strong><br>Head, End-User or Implementing Unit</p>
                <input type="text" name="approved_by_name" class="form-control mb-2" placeholder="[Signature over Printed Name]" value="<?=htmlspecialchars($_POST['approved_by_name'] ?? '')?>">
                <input type="text" name="approved_by_position" class="form-control mb-2" placeholder="[Position/Designation]" value="<?=htmlspecialchars($_POST['approved_by_position'] ?? '')?>">
                <input type="text" name="approved_by_date" class="form-control mb-2" placeholder="[Date]" value="<?=htmlspecialchars($_POST['approved_by_date'] ?? '')?>">
                <label class="form-label">Digital Signature</label>
                <canvas id="approvedSignaturePad" class="signature-pad"></canvas>
                <div class="signature-actions">
                    <button type="button" class="btn btn-sm btn-secondary me-2" onclick="clearSignature('approved')">Clear</button>
                    <span class="text-muted">Draw your signature here.</span>
                </div>
                <img id="approvedSignatureImg" class="signature-preview" src="<?=htmlspecialchars($_POST['approved_by_signature'] ?? '')?>" alt="Approved Signature">
                <input type="hidden" name="approved_by_signature" id="approved_by_signature" value="<?=htmlspecialchars($_POST['approved_by_signature'] ?? '')?>">
            </div>
        </div>

        <div class="mt-4 pt-3 not-print">
            <button type="submit" class="btn btn-primary">Submit Checklist</button>
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_checklist'])): ?>
            <div class="alert alert-success mt-3">Checklist submitted successfully!</div>
            <button type="button" class="btn btn-secondary" onclick="window.print()">Print Checklist</button>
            <button type="button" class="btn btn-success" onclick="downloadPDF()">Download PDF</button>
            <?php endif; ?>
        </div>
    </form>
</div>
<script>
    function downloadPDF() {
        const element = document.getElementById('printableArea');
        if (!element) {
            alert('Error: Unable to find document area');
            return;
        }
        
        const btn = event.target;
        btn.disabled = true;
        btn.textContent = 'Generating PDF...';
        
        // Hide signature pads temporarily
        const signaturePads = document.querySelectorAll('.signature-pad');
        signaturePads.forEach(pad => pad.style.display = 'none');
        
        html2canvas(element, {
            scale: 2,
            useCORS: true,
            allowTaint: true,
            logging: false,
            backgroundColor: '#ffffff'
        }).then(function(canvas) {
            const { jsPDF } = window.jspdf;
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF('p', 'mm', 'a4');
            const imgWidth = 210; // A4 width in mm
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            let heightLeft = imgHeight;
            let position = 0;
            
            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= 297; // A4 height in mm
            
            while (heightLeft >= 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= 297;
            }
            
            pdf.save('Market_Scoping_Checklist_' + new Date().getTime() + '.pdf');
            
            // Restore signature pads
            signaturePads.forEach(pad => pad.style.display = 'block');
            btn.disabled = false;
            btn.textContent = 'Download PDF';
        }).catch(function(error) {
            console.error('PDF generation error:', error);
            signaturePads.forEach(pad => pad.style.display = 'block');
            btn.disabled = false;
            btn.textContent = 'Download PDF';
            alert('Error generating PDF: ' + error.message);
        });
    }

    function SignaturePad(canvas, hiddenInput) {
        const ctx = canvas.getContext('2d');
        let drawing = false;
        let currentStroke = [];
        const strokes = [];

        function resizeCanvas() {
            const ratio = window.devicePixelRatio || 1;
            const width = canvas.offsetWidth;
            const height = canvas.offsetHeight;
            canvas.width = width * ratio;
            canvas.height = height * ratio;
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            redraw();
        }

        function redraw() {
            ctx.clearRect(0, 0, canvas.offsetWidth, canvas.offsetHeight);
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineJoin = 'round';
            ctx.lineCap = 'round';
            strokes.forEach(stroke => {
                if (!stroke.length) return;
                ctx.beginPath();
                ctx.moveTo(stroke[0].x, stroke[0].y);
                stroke.slice(1).forEach(point => ctx.lineTo(point.x, point.y));
                ctx.stroke();
            });
            if (currentStroke.length) {
                ctx.beginPath();
                ctx.moveTo(currentStroke[0].x, currentStroke[0].y);
                currentStroke.slice(1).forEach(point => ctx.lineTo(point.x, point.y));
                ctx.stroke();
            }
        }

        function getPoint(event) {
            const rect = canvas.getBoundingClientRect();
            return {
                x: event.clientX - rect.left,
                y: event.clientY - rect.top,
            };
        }

        function saveSignature() {
            hiddenInput.value = canvas.toDataURL('image/png');
            const imgId = hiddenInput.id.replace('_signature', 'SignatureImg');
            document.getElementById(imgId).src = hiddenInput.value;
        }

        canvas.addEventListener('pointerdown', (event) => {
            drawing = true;
            canvas.setPointerCapture(event.pointerId);
            currentStroke = [getPoint(event)];
        });

        canvas.addEventListener('pointermove', (event) => {
            if (!drawing) return;
            currentStroke.push(getPoint(event));
            redraw();
        });

        canvas.addEventListener('pointerup', () => {
            if (!drawing) return;
            drawing = false;
            strokes.push(currentStroke);
            currentStroke = [];
            saveSignature();
        });

        canvas.addEventListener('pointercancel', () => {
            drawing = false;
            currentStroke = [];
        });

        this.clear = function () {
            strokes.length = 0;
            currentStroke = [];
            ctx.clearRect(0, 0, canvas.offsetWidth, canvas.offsetHeight);
            hiddenInput.value = '';
        };

        this.load = function () {
            const dataUrl = hiddenInput.value;
            if (!dataUrl) return;
            const img = new Image();
            img.onload = function () {
                ctx.clearRect(0, 0, canvas.offsetWidth, canvas.offsetHeight);
                ctx.drawImage(img, 0, 0, canvas.offsetWidth, canvas.offsetHeight);
            };
            img.src = dataUrl;
            const imgId = hiddenInput.id.replace('_signature', 'SignatureImg');
            document.getElementById(imgId).src = dataUrl;
        };

        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();
        this.load();
    }

    let preparedPad;
    let approvedPad;

    function initSignaturePads() {
        preparedPad = new SignaturePad(document.getElementById('preparedSignaturePad'), document.getElementById('prepared_by_signature'));
        approvedPad = new SignaturePad(document.getElementById('approvedSignaturePad'), document.getElementById('approved_by_signature'));
    }

    function clearSignature(type) {
        if (type === 'prepared' && preparedPad) {
            preparedPad.clear();
        }
        if (type === 'approved' && approvedPad) {
            approvedPad.clear();
        }
    }

    document.addEventListener('DOMContentLoaded', initSignaturePads);
</script>
</body>
</html>