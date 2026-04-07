<?php
require_once 'config.php';

function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

function normalizeCurrency($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    $value = preg_replace('/[^0-9\.\-]/u', '', $value);
    if ($value === '' || !is_numeric($value)) {
        return null;
    }
    return round((float)$value, 2);
}

// ensure table exists and has correct fields
$pdo->exec("CREATE TABLE IF NOT EXISTS market_scoping (
    scoping_id INT AUTO_INCREMENT PRIMARY KEY,
    procuring_entity VARCHAR(255) NOT NULL DEFAULT 'Philippine Economic Zone Authority',
    end_user_unit VARCHAR(255) DEFAULT NULL,
    representative_name VARCHAR(255) DEFAULT NULL,
    designation VARCHAR(255) DEFAULT NULL,
    project_name VARCHAR(255) NOT NULL,
    category VARCHAR(50) DEFAULT 'Other',
    estimated_budget DECIMAL(14,2) DEFAULT NULL,
    period_from_month TINYINT DEFAULT NULL,
    period_from_year SMALLINT DEFAULT NULL,
    period_to_month TINYINT DEFAULT NULL,
    period_to_year SMALLINT DEFAULT NULL,
    expected_delivery_month TINYINT DEFAULT NULL,
    expected_delivery_year SMALLINT DEFAULT NULL,
    expected_delivery_date DATE DEFAULT NULL,
    supplier_id INT DEFAULT NULL,
    quotation DECIMAL(13,2) DEFAULT NULL,
    supplier_id_1 INT DEFAULT NULL,
    quotation_1 DECIMAL(13,2) DEFAULT NULL,
    supplier_id_2 INT DEFAULT NULL,
    quotation_2 DECIMAL(13,2) DEFAULT NULL,
    supplier_id_3 INT DEFAULT NULL,
    quotation_3 DECIMAL(13,2) DEFAULT NULL,
    supplier_id_4 INT DEFAULT NULL,
    quotation_4 DECIMAL(13,2) DEFAULT NULL,
    lowest_price DECIMAL(13,2) DEFAULT NULL,
    average_price DECIMAL(13,2) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    analysis TEXT DEFAULT NULL,
    report_link VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES companies(company_id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id_1) REFERENCES companies(company_id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id_2) REFERENCES companies(company_id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id_3) REFERENCES companies(company_id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id_4) REFERENCES companies(company_id) ON DELETE SET NULL
) ENGINE=InnoDB;");

if (!columnExists($pdo, 'market_scoping', 'category')) {
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN category VARCHAR(50) DEFAULT 'Other'");
}
if (!columnExists($pdo, 'market_scoping', 'status')) {
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN status VARCHAR(50) DEFAULT 'Pending'");
}
if (!columnExists($pdo, 'market_scoping', 'expected_delivery_date')) {
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN expected_delivery_date DATE DEFAULT NULL");
}
if (!columnExists($pdo, 'market_scoping', 'period_from_date')) {
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN period_from_date DATE DEFAULT NULL");
}
if (!columnExists($pdo, 'market_scoping', 'period_to_date')) {
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN period_to_date DATE DEFAULT NULL");
}
if (!columnExists($pdo, 'market_scoping', 'supplier_id_1')) {
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN supplier_id_1 INT DEFAULT NULL");
}
if (!columnExists($pdo, 'market_scoping', 'quotation_1')) {
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN quotation_1 DECIMAL(13,2) DEFAULT NULL");
}
if (!columnExists($pdo, 'market_scoping', 'supplier_id_2')) {
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN supplier_id_2 INT DEFAULT NULL");
}
if (!columnExists($pdo, 'market_scoping', 'quotation_2')) {
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN quotation_2 DECIMAL(13,2) DEFAULT NULL");
}
if (!columnExists($pdo, 'market_scoping', 'supplier_id_3')) {
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN supplier_id_3 INT DEFAULT NULL");
}
if (!columnExists($pdo, 'market_scoping', 'quotation_3')) {
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN quotation_3 DECIMAL(13,2) DEFAULT NULL");
}
if (!columnExists($pdo, 'market_scoping', 'supplier_id_4')) {
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN supplier_id_4 INT DEFAULT NULL");
}
if (!columnExists($pdo, 'market_scoping', 'quotation_4')) {
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN quotation_4 DECIMAL(13,2) DEFAULT NULL");
}
if (!columnExists($pdo, 'market_scoping', 'lowest_price')) {
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN lowest_price DECIMAL(13,2) DEFAULT NULL");
}
if (!columnExists($pdo, 'market_scoping', 'average_price')) {
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN average_price DECIMAL(13,2) DEFAULT NULL");
}
if (!columnExists($pdo, 'market_scoping', 'supplier_name_1')) {
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN supplier_name_1 VARCHAR(255) DEFAULT NULL");
}
if (!columnExists($pdo, 'market_scoping', 'supplier_name_2')) {
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN supplier_name_2 VARCHAR(255) DEFAULT NULL");
}
if (!columnExists($pdo, 'market_scoping', 'supplier_name_3')) {
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN supplier_name_3 VARCHAR(255) DEFAULT NULL");
}
if (!columnExists($pdo, 'market_scoping', 'supplier_name_4')) {
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN supplier_name_4 VARCHAR(255) DEFAULT NULL");
}

$errors = [];
$editScoping = null;
$validCategories = ['ICT Equipment', 'Office Supplies', 'Infrastructure', 'Maintenance', 'Other'];

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
        // Validate category
        $category = isset($_POST['category']) ? trim($_POST['category']) : '';
        if (empty($category)) {
            $errors[] = 'Category is required.';
        } elseif (!in_array($category, $validCategories, true)) {
            $errors[] = 'Invalid category selected.';
        }
        if (!$errors) {
            $period_from_date = !empty($_POST['period_from_date']) ? $_POST['period_from_date'] : null;
            $period_to_date = !empty($_POST['period_to_date']) ? $_POST['period_to_date'] : null;
            $expected_delivery_date = !empty($_POST['expected_delivery_date']) ? $_POST['expected_delivery_date'] : null;

            $period_from_month = $period_from_date ? (int)date('n', strtotime($period_from_date)) : ($_POST['period_from_month'] ?: null);
            $period_from_year = $period_from_date ? (int)date('Y', strtotime($period_from_date)) : ($_POST['period_from_year'] ?: null);
            $period_to_month = $period_to_date ? (int)date('n', strtotime($period_to_date)) : ($_POST['period_to_month'] ?: null);
            $period_to_year = $period_to_date ? (int)date('Y', strtotime($period_to_date)) : ($_POST['period_to_year'] ?: null);
            $expected_delivery_month = $expected_delivery_date ? (int)date('n', strtotime($expected_delivery_date)) : ($_POST['expected_delivery_month'] ?: null);
            $expected_delivery_year = $expected_delivery_date ? (int)date('Y', strtotime($expected_delivery_date)) : ($_POST['expected_delivery_year'] ?: null);

            $supplierIds = [];
            $supplierQuotes = [];
            $supplierNames = [];
            
            for ($i = 1; $i <= 4; $i++) {
                $supplierNameKey = 'supplier_name_' . $i;
                $quotationKey = 'quotation_' . $i;
                
                $supplierName = isset($_POST[$supplierNameKey]) ? trim($_POST[$supplierNameKey]) : '';
                $quoteValue = normalizeCurrency($_POST[$quotationKey] ?? null);
                
                // Try to find supplier ID from company name
                $supplierValue = null;
                if (!empty($supplierName)) {
                    $stmt = $pdo->prepare('SELECT company_id FROM companies WHERE company_name = ?');
                    $stmt->execute([$supplierName]);
                    $result = $stmt->fetch();
                    $supplierValue = $result ? (int)$result['company_id'] : null;
                }
                
                if (!empty($supplierName) && $quoteValue === null) {
                    $errors[] = "Quotation for Supplier #$i is required.";
                }
                if ($quoteValue !== null && empty($supplierName)) {
                    $errors[] = "Supplier #$i name is required when a quotation is provided.";
                }
                
                if (!empty($supplierName) && $quoteValue !== null) {
                    $supplierIds[$i] = $supplierValue;  // supplier_id or null if not in database
                    $supplierQuotes[$i] = $quoteValue;
                    $supplierNames[$i] = $supplierName;
                }
            }

            if (!$errors && count($supplierQuotes) < 2) {
                $errors[] = 'At least 2 suppliers with quotations are required.';
            }

            if (!$errors) {
                $lowest_price = min($supplierQuotes);
                $average_price = array_sum($supplierQuotes) / count($supplierQuotes);
                $selectedIndex = null;
                foreach ($supplierQuotes as $index => $quote) {
                    if ($quote === $lowest_price) {
                        $selectedIndex = $index;
                        break;
                    }
                }
                $selectedSupplierId = $selectedIndex !== null ? $supplierIds[$selectedIndex] : null;
                $selectedQuotation = $lowest_price;

                $estimated_budget = normalizeCurrency($_POST['estimated_budget'] ?? null);
                $supplierValues = [];
                for ($i = 1; $i <= 4; $i++) {
                    $supplierValues[] = isset($supplierIds[$i]) ? $supplierIds[$i] : null;
                    $supplierValues[] = isset($supplierQuotes[$i]) ? $supplierQuotes[$i] : null;
                    $supplierValues[] = isset($supplierNames[$i]) ? $supplierNames[$i] : null;
                }

                if (!empty($_POST['scoping_id']) && isset($_POST['update_scoping'])) {
                    $stmt = $pdo->prepare('UPDATE market_scoping SET procuring_entity=?, end_user_unit=?, representative_name=?, designation=?, project_name=?, category=?, estimated_budget=?, period_from_month=?, period_from_year=?, period_to_month=?, period_to_year=?, expected_delivery_month=?, expected_delivery_year=?, period_from_date=?, period_to_date=?, expected_delivery_date=?, supplier_id=?, quotation=?, supplier_id_1=?, quotation_1=?, supplier_name_1=?, supplier_id_2=?, quotation_2=?, supplier_name_2=?, supplier_id_3=?, quotation_3=?, supplier_name_3=?, supplier_id_4=?, quotation_4=?, supplier_name_4=?, lowest_price=?, average_price=?, status=?, analysis=?, report_link=? WHERE scoping_id=?');
                    $stmt->execute(array_merge([
                        $_POST['procuring_entity'] ?: 'Philippine Economic Zone Authority',
                        $_POST['end_user_unit'] ?: null,
                        $_POST['representative_name'] ?: null,
                        $_POST['designation'] ?: null,
                        $_POST['project_name'],
                        $category,
                        $estimated_budget,
                        $period_from_month,
                        $period_from_year,
                        $period_to_month,
                        $period_to_year,
                        $expected_delivery_month,
                        $expected_delivery_year,
                        $period_from_date,
                        $period_to_date,
                        $expected_delivery_date,
                        $selectedSupplierId,
                        $selectedQuotation,
                    ], $supplierValues, [
                        $lowest_price,
                        $average_price,
                        $_POST['status'] ?: 'Pending',
                        $_POST['analysis'] ?: null,
                        $_POST['report_link'] ?: null,
                        $_POST['scoping_id'],
                    ]));
                } else {
                    $stmt = $pdo->prepare('INSERT INTO market_scoping (procuring_entity, end_user_unit, representative_name, designation, project_name, category, estimated_budget, period_from_month, period_from_year, period_to_month, period_to_year, expected_delivery_month, expected_delivery_year, period_from_date, period_to_date, expected_delivery_date, supplier_id, quotation, supplier_id_1, quotation_1, supplier_name_1, supplier_id_2, quotation_2, supplier_name_2, supplier_id_3, quotation_3, supplier_name_3, supplier_id_4, quotation_4, supplier_name_4, lowest_price, average_price, status, analysis, report_link) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute(array_merge([
                        $_POST['procuring_entity'] ?: 'Philippine Economic Zone Authority',
                        $_POST['end_user_unit'] ?: null,
                        $_POST['representative_name'] ?: null,
                        $_POST['designation'] ?: null,
                        $_POST['project_name'],
                        $category,
                        $estimated_budget,
                        $period_from_month,
                        $period_from_year,
                        $period_to_month,
                        $period_to_year,
                        $expected_delivery_month,
                        $expected_delivery_year,
                        $period_from_date,
                        $period_to_date,
                        $expected_delivery_date,
                        $selectedSupplierId,
                        $selectedQuotation,
                    ], $supplierValues, [
                        $lowest_price,
                        $average_price,
                        $_POST['status'] ?: 'Pending',
                        $_POST['analysis'] ?: null,
                        $_POST['report_link'] ?: null,
                    ]));
                }
                header('Location: market_scoping.php');
                exit;
            }
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
    if ($editScoping) {
        if (empty($editScoping['supplier_id_1']) && !empty($editScoping['supplier_id'])) {
            $editScoping['supplier_id_1'] = $editScoping['supplier_id'];
            $editScoping['quotation_1'] = $editScoping['quotation'];
        }
        for ($i = 1; $i <= 4; $i++) {
            $supplierNameKey = 'supplier_name_' . $i;
            $supplierIdKey = 'supplier_id_' . $i;
            if (empty($editScoping[$supplierNameKey]) && !empty($editScoping[$supplierIdKey])) {
                $stmt = $pdo->prepare('SELECT company_name FROM companies WHERE company_id = ?');
                $stmt->execute([$editScoping[$supplierIdKey]]);
                $company = $stmt->fetch();
                if ($company) {
                    $editScoping[$supplierNameKey] = $company['company_name'];
                }
            }
        }
    }
}

$companies = $pdo->query('SELECT company_id, company_name FROM companies ORDER BY company_name')->fetchAll();

$scopingColumns = [
    'm.scoping_id',
    'm.procuring_entity',
    'm.end_user_unit',
    'm.representative_name',
    'm.designation',
    'm.project_name',
    'm.category',
    'm.estimated_budget',
    'm.period_from_month',
    'm.period_from_year',
    'm.period_to_month',
    'm.period_to_year',
    'm.expected_delivery_month',
    'm.expected_delivery_year',
    'm.quotation',
    'm.supplier_id_1',
    'm.quotation_1',
    'm.supplier_id_2',
    'm.quotation_2',
    'm.supplier_id_3',
    'm.quotation_3',
    'm.supplier_id_4',
    'm.quotation_4',
    'm.supplier_name_1',
    'm.supplier_name_2',
    'm.supplier_name_3',
    'm.supplier_name_4',
    'm.lowest_price',
    'm.average_price',
    'm.analysis',
    'm.report_link',
    'm.created_at',
    'c.company_name AS selected_supplier_name',
    'COALESCE(m.supplier_name_1, c1.company_name) AS supplier_1_name',
    'COALESCE(m.supplier_name_2, c2.company_name) AS supplier_2_name',
    'COALESCE(m.supplier_name_3, c3.company_name) AS supplier_3_name',
    'COALESCE(m.supplier_name_4, c4.company_name) AS supplier_4_name',
];

if (columnExists($pdo, 'market_scoping', 'period_from_date')) {
    $scopingColumns[] = 'm.period_from_date';
}
if (columnExists($pdo, 'market_scoping', 'period_to_date')) {
    $scopingColumns[] = 'm.period_to_date';
}
if (columnExists($pdo, 'market_scoping', 'expected_delivery_date')) {
    $scopingColumns[] = 'm.expected_delivery_date';
}

$scopingsSql = 'SELECT ' . implode(',', $scopingColumns) . "\n    FROM market_scoping m\n    LEFT JOIN companies c ON c.company_id=m.supplier_id\n    LEFT JOIN companies c1 ON c1.company_id=m.supplier_id_1\n    LEFT JOIN companies c2 ON c2.company_id=m.supplier_id_2\n    LEFT JOIN companies c3 ON c3.company_id=m.supplier_id_3\n    LEFT JOIN companies c4 ON c4.company_id=m.supplier_id_4\n    ORDER BY m.created_at DESC";
$scopings = $pdo->query($scopingsSql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Market Scoping | PEZA SCMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body{background:url('images/PEZA-background.jpeg') no-repeat center center fixed;background-size:cover;}
        .container{background-color:rgba(255,255,255,0.95);padding:1rem;border-radius:4px;margin-top:20px;margin-bottom:20px;}
        .supplier-search-wrapper{position:relative;}
        .supplier-suggestions{position:absolute;top:100%;left:0;right:0;max-height:200px;overflow-y:auto;background:#fff;border:1px solid #ddd;border-top:none;border-radius:0 0 4px 4px;z-index:1000;display:none;}
        .supplier-suggestions.show{display:block;}
        .supplier-suggestion-item{padding:8px 12px;cursor:pointer;border-bottom:1px solid #f0f0f0;}
        .supplier-suggestion-item:hover{background-color:#f3f3f3;}
        .supplier-suggestion-item.selected{background-color:#007bff;color:white;}
    </style>
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

        <div class="col-md-4">
            <label class="form-label">Project Name</label>
            <input type="text" name="project_name" class="form-control" required value="<?= htmlspecialchars($editScoping['project_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Category</label>
            <select name="category" class="form-select" required>
                <option value="" disabled<?= !isset($editScoping['category']) ? ' selected' : '' ?>>Select category</option>
                <option value="ICT Equipment"<?= (isset($editScoping['category']) && $editScoping['category'] == 'ICT Equipment') ? ' selected' : '' ?>>ICT Equipment</option>
                <option value="Office Supplies"<?= (isset($editScoping['category']) && $editScoping['category'] == 'Office Supplies') ? ' selected' : '' ?>>Office Supplies</option>
                <option value="Infrastructure"<?= (isset($editScoping['category']) && $editScoping['category'] == 'Infrastructure') ? ' selected' : '' ?>>Infrastructure</option>
                <option value="Maintenance"<?= (isset($editScoping['category']) && $editScoping['category'] == 'Maintenance') ? ' selected' : '' ?>>Maintenance</option>
                <option value="Other"<?= (isset($editScoping['category']) && $editScoping['category'] == 'Other') ? ' selected' : '' ?>>Other</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Estimated Budget (PHP)</label>
            <input type="text" name="estimated_budget" inputmode="decimal" pattern="^\s*(?:₱|PHP)?\s*[0-9,]+(?:\.[0-9]{2})?\s*$" placeholder="0,000,000.00" class="form-control formatted-money" value="<?= htmlspecialchars($editScoping['estimated_budget'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Market Scoping Period From</label>
            <input type="date" class="form-control" name="period_from_date" value="<?= htmlspecialchars($editScoping['period_from_date'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Market Scoping Period To</label>
            <input type="date" class="form-control" name="period_to_date" value="<?= htmlspecialchars($editScoping['period_to_date'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Expected Delivery Date</label>
            <input type="date" class="form-control" name="expected_delivery_date" value="<?= htmlspecialchars($editScoping['expected_delivery_date'] ?? '') ?>">
        </div>

        <div class="col-12"><hr></div>
        <div class="col-12"><small class="text-muted">Enter at least 2 suppliers with quotations for comparative analysis; up to 4 suppliers are allowed.</small></div>
        <div class="col-md-3">
            <label class="form-label">Supplier 1</label>
            <div class="supplier-search-wrapper">
                <input type="text" name="supplier_name_1" class="form-control supplier-search" placeholder="Type supplier name..." autocomplete="off" required value="<?= htmlspecialchars($editScoping['supplier_name_1'] ?? '') ?>">
                <input type="hidden" name="supplier_id_1" value="<?= htmlspecialchars($editScoping['supplier_id_1'] ?? '') ?>">
                <div class="supplier-suggestions"></div>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Quotation 1 (PHP)</label>
            <input type="text" name="quotation_1" inputmode="decimal" pattern="^\s*(?:₱|PHP)?\s*[0-9,]+(?:\.[0-9]{2})?\s*$" placeholder="0,000,000.00" class="form-control formatted-money" value="<?= htmlspecialchars($editScoping['quotation_1'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Supplier 2</label>
            <div class="supplier-search-wrapper">
                <input type="text" name="supplier_name_2" class="form-control supplier-search" placeholder="Type supplier name..." autocomplete="off" required value="<?= htmlspecialchars($editScoping['supplier_name_2'] ?? '') ?>">
                <input type="hidden" name="supplier_id_2" value="<?= htmlspecialchars($editScoping['supplier_id_2'] ?? '') ?>">
                <div class="supplier-suggestions"></div>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Quotation 2 (PHP)</label>
            <input type="text" name="quotation_2" inputmode="decimal" pattern="^\s*(?:₱|PHP)?\s*[0-9,]+(?:\.[0-9]{2})?\s*$" placeholder="0,000,000.00" class="form-control formatted-money" value="<?= htmlspecialchars($editScoping['quotation_2'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Supplier 3 (optional)</label>
            <div class="supplier-search-wrapper">
                <input type="text" name="supplier_name_3" class="form-control supplier-search" placeholder="Type supplier name..." autocomplete="off" value="<?= htmlspecialchars($editScoping['supplier_name_3'] ?? '') ?>">
                <input type="hidden" name="supplier_id_3" value="<?= htmlspecialchars($editScoping['supplier_id_3'] ?? '') ?>">
                <div class="supplier-suggestions"></div>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Quotation 3 (PHP)</label>
            <input type="text" name="quotation_3" inputmode="decimal" pattern="^\s*(?:₱|PHP)?\s*[0-9,]+(?:\.[0-9]{2})?\s*$" placeholder="0,000,000.00" class="form-control formatted-money" value="<?= htmlspecialchars($editScoping['quotation_3'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Supplier 4 (optional)</label>
            <div class="supplier-search-wrapper">
                <input type="text" name="supplier_name_4" class="form-control supplier-search" placeholder="Type supplier name..." autocomplete="off" value="<?= htmlspecialchars($editScoping['supplier_name_4'] ?? '') ?>">
                <input type="hidden" name="supplier_id_4" value="<?= htmlspecialchars($editScoping['supplier_id_4'] ?? '') ?>">
                <div class="supplier-suggestions"></div>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Quotation 4 (PHP)</label>
            <input type="text" name="quotation_4" inputmode="decimal" pattern="^\s*(?:₱|PHP)?\s*[0-9,]+(?:\.[0-9]{2})?\s*$" placeholder="0,000,000.00" class="form-control formatted-money" value="<?= htmlspecialchars($editScoping['quotation_4'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="Pending"<?= (isset($editScoping['status']) && $editScoping['status'] == 'Pending') ? ' selected' : '' ?>>Pending</option>
                <option value="Approved"<?= (isset($editScoping['status']) && $editScoping['status'] == 'Approved') ? ' selected' : '' ?>>Approved</option>
                <option value="Rejected"<?= (isset($editScoping['status']) && $editScoping['status'] == 'Rejected') ? ' selected' : '' ?>>Rejected</option>
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
            <button type="submit" class="btn btn-primary"><?= $editScoping ? 'Update Market Scoping Record' : 'Add Market Scoping Record' ?></button>
        </div>
        </div>
    </form>

    <h2>Existing Market Scoping Records</h2>
    <button type="button" class="btn btn-sm btn-outline-secondary mb-2" onclick="document.getElementById('scopingRecords').classList.toggle('d-none');">Toggle records</button>
    <div id="scopingRecords">
      <div class="table-responsive">
      <table class="table table-bordered table-striped">
          <thead><tr><th>Project</th><th>Category</th><th>Budget</th><th>Supplier</th><th>Quote</th><th>Lowest</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($scopings as $m): ?>
              <tr>
                  <td><?=htmlspecialchars($m['project_name'])?></td>
                  <td><?=htmlspecialchars($m['category'] ?? 'Other')?></td>
                  <td><?= $m['estimated_budget'] !== null ? '₱'.number_format($m['estimated_budget'],2) : 'N/A' ?></td>
                  <td><?=htmlspecialchars($m['supplier_1_name'] ?? 'N/A')?></td>
                  <td><?= $m['quotation_1'] !== null ? '₱'.number_format($m['quotation_1'],2) : 'N/A' ?></td>
                  <td><?= $m['lowest_price'] !== null ? '<strong>₱'.number_format($m['lowest_price'],2).'</strong>' : 'N/A' ?></td>
                  <td><?= htmlspecialchars($m['status'] ?? 'Pending') ?></td>
                  <td>
                      <a href="market_scoping.php?edit=<?= intval($m['scoping_id']) ?>" class="btn btn-sm btn-warning me-1">Edit</a>
                      <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailsModal" onclick="loadScopingDetails(<?= htmlspecialchars(json_encode($m), ENT_QUOTES, 'UTF-8') ?>)">Details</button>
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
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Market Scoping Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detailsContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function formatCurrency(value) {
    if (!value || isNaN(value)) return 'N/A';
    return '₱' + Number(value).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    try {
        const date = new Date(dateStr + ' 00:00:00');
        return date.toLocaleDateString('en-US', {year: 'numeric', month: '2-digit', day: '2-digit'});
    } catch (e) {
        return dateStr;
    }
}

function loadScopingDetails(record) {
    let html = '<div class="row mb-3">';
    html += '<div class="col-md-6"><strong>Project:</strong> ' + (record.project_name || 'N/A') + '</div>';
    html += '<div class="col-md-6"><strong>Category:</strong> ' + (record.category || 'Other') + '</div>';
    html += '<div class="col-md-6"><strong>End User:</strong> ' + (record.end_user_unit || 'N/A') + '</div>';
    html += '<div class="col-md-6"><strong>Representative:</strong> ' + (record.representative_name || 'N/A') + '</div>';
    html += '<div class="col-md-6"><strong>Designation:</strong> ' + (record.designation || 'N/A') + '</div>';
    html += '<div class="col-md-6"><strong>Budget:</strong> ' + formatCurrency(record.estimated_budget) + '</div>';
    html += '<div class="col-md-6"><strong>Procuring Entity:</strong> ' + (record.procuring_entity || 'N/A') + '</div>';
    html += '<div class="col-md-6"><strong>Status:</strong> ' + (record.status || 'Pending') + '</div>';
    html += '</div>';
    
    html += '<h6 class="mt-3 mb-2">Period &amp; Delivery</h6>';
    html += '<div class="row mb-3">';
    const periodFrom = record.period_from_date ? formatDate(record.period_from_date) : (record.period_from_month && record.period_from_year ? String(record.period_from_month).padStart(2, '0') + '/' + record.period_from_year : 'N/A');
    const periodTo = record.period_to_date ? formatDate(record.period_to_date) : (record.period_to_month && record.period_to_year ? String(record.period_to_month).padStart(2, '0') + '/' + record.period_to_year : 'N/A');
    html += '<div class="col-md-6"><strong>Period:</strong> ' + periodFrom + ' - ' + periodTo + '</div>';
    const deliveryDate = record.expected_delivery_date ? formatDate(record.expected_delivery_date) : (record.expected_delivery_month && record.expected_delivery_year ? String(record.expected_delivery_month).padStart(2, '0') + '/' + record.expected_delivery_year : 'N/A');
    html += '<div class="col-md-6"><strong>Expected Delivery:</strong> ' + deliveryDate + '</div>';
    html += '</div>';
    
    // Collect suppliers and quotes
    let suppliers = [];
    for (let i = 1; i <= 4; i++) {
        const supplierId = 'supplier_' + i + '_name';
        const quotationId = 'quotation_' + i;
        if (record[supplierId] && record[quotationId]) {
            suppliers.push({
                name: record[supplierId],
                quote: parseFloat(record[quotationId]) || 0,
                index: i
            });
        }
    }
    
    // Sort by quote ascending
    suppliers.sort((a, b) => a.quote - b.quote);
    
    if (suppliers.length > 0) {
        const lowestQuote = suppliers[0].quote;
        const highestQuote = suppliers[suppliers.length - 1].quote;
        const quoteRange = highestQuote - lowestQuote;
        
        html += '<h6 class="mt-3 mb-2"><i class="bi bi-graph-up"></i> Supplier Comparison & Analysis</h6>';
        html += '<div class="table-responsive">';
        html += '<table class="table table-sm table-hover">';
        html += '<thead class="table-light"><tr><th>Rank</th><th>Supplier</th><th>Quotation</th><th>Variance</th><th>Status</th></tr></thead><tbody>';
        
        let rank = 1;
        suppliers.forEach((supplier, idx) => {
            const variance = supplier.quote - lowestQuote;
            const percentVariance = quoteRange > 0 ? ((variance / lowestQuote) * 100).toFixed(2) : 0;
            const isLowest = supplier.quote === lowestQuote;
            const rowClass = isLowest ? 'table-success' : '';
            
            let statusBadge = '';
            if (isLowest) {
                statusBadge = '<span class="badge bg-success">Lowest</span>';
            } else if (idx === suppliers.length - 1) {
                statusBadge = '<span class="badge bg-danger">Highest</span>';
            } else {
                statusBadge = '<span class="badge bg-secondary">Mid</span>';
            }
            
            html += '<tr class="' + rowClass + '">';
            html += '<td><strong>' + rank + '</strong></td>';
            html += '<td>' + supplier.name + '</td>';
            html += '<td><strong>' + formatCurrency(supplier.quote) + '</strong></td>';
            html += '<td>';
            if (isLowest) {
                html += '<span class="badge bg-success">Base</span>';
            } else {
                html += '<span class="text-danger">+' + formatCurrency(variance) + ' (' + percentVariance + '%)</span>';
            }
            html += '</td>';
            html += '<td>' + statusBadge + '</td>';
            html += '</tr>';
            rank++;
        });
        
        html += '</tbody></table>';
        html += '</div>';
        
        // Summary stats
        html += '<div class="row mt-3 mb-3">';
        html += '<div class="col-md-4 border-start ps-3"><strong>Lowest Quote:</strong><br><span class="text-success h5">₱' + lowestQuote.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</span> (' + suppliers[0].name + ')</div>';
        
        const avgQuote = suppliers.reduce((sum, s) => sum + s.quote, 0) / suppliers.length;
        html += '<div class="col-md-4 border-start ps-3"><strong>Average Quote:</strong><br><span class="text-info h5">₱' + avgQuote.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</span></div>';
        
        const savingsVsAvg = avgQuote - lowestQuote;
        const savingsPercentage = ((savingsVsAvg / avgQuote) * 100).toFixed(2);
        html += '<div class="col-md-4 border-start ps-3"><strong>Potential Savings:</strong><br><span class="text-success h5">₱' + savingsVsAvg.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</span> (' + savingsPercentage + '%)</div>';
        html += '</div>';
    }
    
    if (record.report_link) {
        html += '<div class="mt-3 mb-2"><strong>Report Link:</strong> <a href="' + record.report_link + '" target="_blank" class="btn btn-sm btn-outline-primary">View Report</a></div>';
    }
    
    if (record.analysis) {
        html += '<div class="mt-3 p-3 bg-light border rounded"><strong>Analysis / Notes:</strong><br>' + record.analysis.replace(/\n/g, '<br>') + '</div>';
    }
    
    document.getElementById('detailsContent').innerHTML = html;
}

// Money input formatting
(function() {
    function unformat(value) {
        return String(value).replace(/[^0-9.\-]/g, '').trim();
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

// Supplier search with typeahead suggestions
(function() {
    const companiesData = <?= json_encode($companies) ?>;
    
    document.querySelectorAll('.supplier-search').forEach((input, idx) => {
        const wrapper = input.parentElement;
        const suggestionsDiv = wrapper.querySelector('.supplier-suggestions');
        const hiddenIdField = wrapper.querySelector('input[type="hidden"]');
        
        // Pre-fill if editing
        if (input.value && suggestionsDiv) {
            const matching = companiesData.find(c => c.company_name === input.value);
            if (matching && hiddenIdField) {
                hiddenIdField.value = matching.company_id;
            }
        }
        
        // Function to show filtered suggestions
        function showSuggestions(query = '') {
            query = query.toLowerCase().trim();
            
            // Filter companies by name
            let matches = companiesData;
            if (query.length > 0) {
                matches = companiesData.filter(c => 
                    c.company_name.toLowerCase().includes(query)
                );
            }
            
            // Show all matching results (no limit)
            if (matches.length === 0) {
                suggestionsDiv.classList.remove('show');
                return;
            }
            
            // Build suggestions HTML
            let html = '';
            matches.forEach(company => {
                html += '<div class="supplier-suggestion-item" data-id="' + company.company_id + '" data-name="' + company.company_name.replace(/"/g, '&quot;') + '">' + company.company_name + '</div>';
            });
            
            suggestionsDiv.innerHTML = html;
            suggestionsDiv.classList.add('show');
            
            // Add click handlers to suggestions
            suggestionsDiv.querySelectorAll('.supplier-suggestion-item').forEach(item => {
                item.addEventListener('click', function() {
                    input.value = this.dataset.name;
                    hiddenIdField.value = this.dataset.id;
                    suggestionsDiv.classList.remove('show');
                });
            });
        }
        
        // Show all suppliers on focus
        input.addEventListener('focus', function() {
            showSuggestions('');
        });
        
        // Filter suggestions as user types
        input.addEventListener('input', function() {
            showSuggestions(this.value);
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) {
                suggestionsDiv.classList.remove('show');
            }
        });
    });
})();
</script>
</body>
</html>
