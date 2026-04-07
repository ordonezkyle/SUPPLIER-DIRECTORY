<?php
require_once 'config.php';

// Ensure tables are created/updated with necessary columns
try {
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
    ) ENGINE=InnoDB");
    
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
        supplier_id INT DEFAULT NULL,
        quotation DECIMAL(13,2) DEFAULT NULL,
        status VARCHAR(50) DEFAULT 'Pending',
        analysis TEXT DEFAULT NULL,
        report_link VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (supplier_id) REFERENCES companies(company_id) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    
    // Add missing columns if they don't exist
    $pdo->exec("ALTER TABLE ppmp_plans ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT 'Other'");
    $pdo->exec("ALTER TABLE market_scoping ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT 'Other'");
} catch (PDOException $e) {
    // Table creation/alteration failed, will fall back to safe defaults
}

// Create dashboard_settings table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS dashboard_settings (
        setting_id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
} catch (PDOException $e) {
    // Table creation failed
}

// Helper function to get dashboard setting from database
function getDashboardSetting($pdo, $key, $default) {
    try {
        $stmt = $pdo->prepare('SELECT setting_value FROM dashboard_settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

// Helper function to save dashboard setting to database
function setDashboardSetting($pdo, $key, $value) {
    try {
        $stmt = $pdo->prepare('INSERT INTO dashboard_settings (setting_key, setting_value) 
                              VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = CURRENT_TIMESTAMP');
        $stmt->execute([$key, $value, $value]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function normalizeCurrency($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return 0.0;
    }
    return floatval(str_replace([',', ' '], '', $value));
}

// Load settings from database (persists across sessions and devices)
$allocatedBudgetDB = getDashboardSetting($pdo, 'allocatedBudget', '15000000');
$_SESSION['allocatedBudget'] = (float)$allocatedBudgetDB;
$_SESSION['show_budget'] = getDashboardSetting($pdo, 'show_budget', 'true') === 'true';
$_SESSION['show_pending'] = getDashboardSetting($pdo, 'show_pending', 'true') === 'true';

// process update form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_dashboard'])) {
    $newBudget = max(0, normalizeCurrency($_POST['allocated_budget'] ?? $_SESSION['allocatedBudget']));
    $_SESSION['allocatedBudget'] = $newBudget;
    setDashboardSetting($pdo, 'allocatedBudget', (string)$newBudget);
    header('Location: pmis_dashboard.php');
    exit;
}

// helper to avoid schema mismatch on legacy data
function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

$ppmpTotal = (int)$pdo->query('SELECT COUNT(*) FROM ppmp_plans')->fetchColumn();
$ppmpPending = 0;
if (columnExists($pdo, 'ppmp_plans', 'status')) {
    $ppmpPending = (int)$pdo->query('SELECT COUNT(*) FROM ppmp_plans WHERE status = "Pending"')->fetchColumn();
} else {
    // migrate if possible
    try {
        $pdo->exec("ALTER TABLE ppmp_plans ADD COLUMN status VARCHAR(50) DEFAULT 'Pending'");
        $ppmpPending = (int)$pdo->query('SELECT COUNT(*) FROM ppmp_plans WHERE status = "Pending"')->fetchColumn();
    } catch (PDOException $e) {
        $ppmpPending = 0;
    }
}

$rfqTotal = (int)$pdo->query('SELECT COUNT(*) FROM rfq_requests')->fetchColumn();
$rfqPending = 0;
if (columnExists($pdo, 'rfq_requests', 'status')) {
    $rfqPending = (int)$pdo->query('SELECT COUNT(*) FROM rfq_requests WHERE status IN ("Open", "Pending")')->fetchColumn();
}

$scopingTotal = (int)$pdo->query('SELECT COUNT(*) FROM market_scoping')->fetchColumn();
$scopingPending = 0;
if (columnExists($pdo, 'market_scoping', 'status')) {
    $scopingPending = (int)$pdo->query('SELECT COUNT(*) FROM market_scoping WHERE status = "Pending"')->fetchColumn();
} else {
    // migrate if possible
    try {
        $pdo->exec("ALTER TABLE market_scoping ADD COLUMN status VARCHAR(50) DEFAULT 'Pending'");
        $scopingPending = (int)$pdo->query('SELECT COUNT(*) FROM market_scoping WHERE status = "Pending"')->fetchColumn();
    } catch (PDOException $e) {
        $scopingPending = 0;
    }
}

$pendingApproval = $ppmpPending + $rfqPending + $scopingPending;
$projectCount = $ppmpTotal + $rfqTotal + $scopingTotal;
$completed = max(0, $projectCount - $pendingApproval);

$allocatedBudget = $_SESSION['allocatedBudget'];
$usedBudget = (float)$pdo->query('SELECT COALESCE(SUM(quotation),0) FROM market_scoping')->fetchColumn();
$remainingBudget = max(0, $allocatedBudget - $usedBudget);
$isOverBudget = $usedBudget > $allocatedBudget;
$progress = $allocatedBudget > 0 ? round(($usedBudget / $allocatedBudget) * 100) : 0;
$budgetDetails = $usedBudget > 0
    ? "Used ₱" . number_format($usedBudget, 2) . " of ₱" . number_format($allocatedBudget, 2)
    : "No quotation data in market_scoping yet (used = 0). Add market scoping items first.";

// procurement analytics categories (explicit category field)
$categoryTotals = ['ICT Equipment'=>0, 'Office Supplies'=>0, 'Infrastructure'=>0, 'Maintenance'=>0, 'Other'=>0];

try {
    if (columnExists($pdo, 'ppmp_plans', 'category')) {
        // Handle both NULL and empty string as 'Other'
        $result = $pdo->query('SELECT COALESCE(category, "Other") as category, COUNT(*) AS c FROM ppmp_plans GROUP BY category')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $row) {
            $cat = trim($row['category']) ?: 'Other';
            if (!isset($categoryTotals[$cat])) {
                $cat = 'Other';
            }
            $categoryTotals[$cat] += (int)$row['c'];
        }
    }
} catch (PDOException $e) {
    // Category column doesn't exist yet
}

try {
    if (columnExists($pdo, 'market_scoping', 'category')) {
        // Handle both NULL and empty string as 'Other'
        $result = $pdo->query('SELECT COALESCE(category, "Other") as category, COUNT(*) AS c FROM market_scoping GROUP BY category')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $row) {
            $cat = trim($row['category']) ?: 'Other';
            if (!isset($categoryTotals[$cat])) {
                $cat = 'Other';
            }
            $categoryTotals[$cat] += (int)$row['c'];
        }
    }
} catch (PDOException $e) {
    // Category column doesn't exist yet
}

$totalCategory = array_sum($categoryTotals);
// Show only real data from database - no fallback examples


if (isset($_GET['action']) && $_GET['action'] === 'get_category_items') {
    // Handle AJAX request for category items
    $category = $_GET['category'] ?? '';
    $ppmpItems = [];
    $scopingItems = [];
    $foundItems = false;
    
    // Get PPMP items in category
    if (columnExists($pdo, 'ppmp_plans', 'category')) {
        try {
            $stmt = $pdo->prepare('SELECT ppmp_id, project_name, budget, status FROM ppmp_plans WHERE category = ? ORDER BY created_at DESC');
            $stmt->execute([$category]);
            $ppmpItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($ppmpItems) {
                $foundItems = true;
                echo '<div class="mb-3"><h6 class="text-primary">PPMP Plans (' . count($ppmpItems) . ')</h6>';
                foreach ($ppmpItems as $item) {
                    echo '<div class="border-bottom pb-2 mb-2">';
                    echo '<strong>' . htmlspecialchars($item['project_name']) . '</strong>';
                    echo '<div class="text-muted small">Budget: ₱' . number_format($item['budget'], 2) . ' | Status: ' . htmlspecialchars($item['status']) . '</div>';
                    echo '</div>';
                }
                echo '</div>';
            }
        } catch (PDOException $e) {
            // Silently fail
        }
    }
    
    // Get Market Scoping items in category
    if (columnExists($pdo, 'market_scoping', 'category')) {
        try {
            $stmt = $pdo->prepare('SELECT scoping_id, project_name, estimated_budget, quotation, status FROM market_scoping WHERE category = ? ORDER BY created_at DESC');
            $stmt->execute([$category]);
            $scopingItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($scopingItems) {
                $foundItems = true;
                echo '<div class="mb-3"><h6 class="text-success">Market Scoping (' . count($scopingItems) . ')</h6>';
                foreach ($scopingItems as $item) {
                    echo '<div class="border-bottom pb-2 mb-2">';
                    echo '<strong>' . htmlspecialchars($item['project_name']) . '</strong>';
                    echo '<div class="text-muted small">Est. Budget: ₱' . ($item['estimated_budget'] ? number_format($item['estimated_budget'], 2) : 'N/A') . ' | Quotation: ₱' . ($item['quotation'] ? number_format($item['quotation'], 2) : 'N/A') . ' | Status: ' . htmlspecialchars($item['status']) . '</div>';
                    echo '</div>';
                }
                echo '</div>';
            }
        } catch (PDOException $e) {
            // Silently fail
        }
    }
    
    if (!$foundItems) {
        echo '<p class="text-muted">No items found in this category.</p>';
    }
    
    exit;
}

$currentDate = date('F j, Y');
$userName = 'Arvin A. Egargue';
$role = 'Administrator';
$tab = $_GET['tab'] ?? 'dashboard';
$allowedTabs = ['dashboard','market_scoping','ppmp','rfq','analysis','reports'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PMIS Dashboard | PEZA SCMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: url('images/PEZA-background.jpeg') no-repeat center center fixed; background-size: cover; }
        .app-shell { min-height: 100vh; display: flex; }
        .sidebar { width: 240px; background: rgba(0, 0, 0, 0.7); color: #fff; padding: 1rem; }
        .sidebar h2 { font-size: 1.15rem; }
        .sidebar a { color: #cfe2ff; text-decoration: none; display: block; margin: 0.35rem 0; }
        .sidebar a:hover { color: #fff; }
        .content { flex: 1; padding: 1rem; background: rgba(255,255,255,0.92); }
        .stats-card { min-height: 120px; }
        .progress { height: 18px; }
        .btn-sm { font-size: 0.8rem; }
        .category-row { cursor: pointer; padding: 0.5rem; border-radius: 4px; transition: background-color 0.2s; }
        .category-row:hover { background-color: rgba(0, 0, 0, 0.05); }
    </style>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <h2>MENU</h2>
        <hr style="border-color:#6c757d">
        <a href="pmis_dashboard.php">Dashboard</a>
        <a href="index.php" class="ps-3">Directory</a>
        <div class="mt-2"><strong>Procurement Management</strong></div>
        <a href="market_scoping.php" class="ps-3">Market Scoping</a>
        <a href="pmis_dashboard.php" class="ps-3">PPMP</a>
        <a href="pmis_dashboard.php" class="ps-3">RFQ</a>
        <a href="pmis_dashboard.php" class="ps-3">Bid Evaluation</a>
        <div class="mt-2"><strong>Supplier Management</strong></div>
        <a href="pmis_dashboard.php" class="ps-3">Supplier Ratings</a>
        <div class="mt-2"><strong>Analytics</strong></div>
        <a href="pmis_dashboard.php" class="ps-3">Market Price Analysis</a>
        <a href="pmis_dashboard.php" class="ps-3">Procurement Trends</a>
        <div class="mt-2"><strong>Documents</strong></div>
        <a href="pmis_dashboard.php" class="ps-3">Generated Reports</a>
        <a href="pmis_dashboard.php" class="ps-3">Archived Procurement</a>
        <div class="mt-2"><strong>Administration</strong></div>
        <a href="admin.php" class="ps-3">User Accounts</a>
        <a href="admin.php" class="ps-3">System Settings</a>
    </aside>

    <main class="content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1>PROCUREMENT MARKET INTELLIGENCE SYSTEM</h1>
                <h5>Dashboard</h5>
            </div>
        </div>

        <ul class="nav nav-tabs mb-3">
            <?php foreach (['dashboard'=>'Dashboard', 'market_scoping'=>'Market Scoping', 'ppmp'=>'PPMP', 'rfq'=>'RFQ', 'analysis'=>'Market Scoping Checklist', 'reports'=>'Reports'] as $key => $label): ?>
                <li class="nav-item"><a class="nav-link <?= $tab === $key ? 'active' : '' ?>" href="?tab=<?= $key ?>"><?=htmlspecialchars($label)?></a></li>
            <?php endforeach; ?>
        </ul>

        <div class="tab-content">
            <?php if ($tab === 'dashboard'): ?>
                <div class="card mb-3 p-3">
                    <h5>Edit Dashboard Values</h5>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="update_dashboard" value="1">
                        <div class="col-md-6">
                            <label class="form-label">Allocated Budget (₱)</label>
                            <input type="text" name="allocated_budget" inputmode="decimal" pattern="^[0-9,]+(\.[0-9]{2})?$" placeholder="0,000,000.00" class="form-control formatted-money" value="<?= htmlspecialchars(number_format($allocatedBudget,2,'.','')) ?>">
                        </div>
                        <div class="col-md-6 align-self-end">
                            <button type="submit" class="btn btn-success">Update</button>
                        </div>
                    </form>
                </div>
                <div class="row gy-3 mb-4">
                    <div class="col-md-3"><div class="card stats-card p-3"><h5>Total Projects</h5><div class="display-6"><?= $projectCount ?></div></div></div>
                    <div class="col-md-3"><div class="card stats-card p-3"><h5>Pending Approval</h5><div class="display-6"><?= $pendingApproval ?></div></div></div>
                    <div class="col-md-3"><div class="card stats-card p-3"><h5>Completed Projects</h5><div class="display-6"><?= $completed ?></div></div></div>
                    <div class="col-md-3"><div class="card stats-card p-3"><h5>PPMP Pending</h5><div class="display-6"><?= $ppmpPending ?> / <?= $ppmpTotal ?></div></div></div>
                </div>
                <div class="row gy-3 mb-4">
                    <div class="col-md-4"><div class="card stats-card p-3"><h5>RFQ Pending</h5><div class="display-6"><?= $rfqPending ?> / <?= $rfqTotal ?></div></div></div>
                    <div class="col-md-4"><div class="card stats-card p-3"><h5>Market Scoping Pending</h5><div class="display-6"><?= $scopingPending ?> / <?= $scopingTotal ?></div></div></div>
                </div>
                <?php if ($_SESSION['show_budget']): ?>
                <div class="card mb-4 p-3" style="border: <?= $isOverBudget ? '3px solid #dc3545' : '1px solid #dee2e6' ?>;">
                    <h5>Budget Utilization <?php if ($isOverBudget): ?><span class="badge bg-danger ms-2">OVERBUDGET</span><?php endif; ?></h5>
                    <p>Allocated Budget : ₱<?= number_format($allocatedBudget,2) ?></p>
                    <p>Used Budget      : ₱<?= number_format($usedBudget,2) ?> <?php if ($isOverBudget): ?><span class="badge bg-danger">EXCEEDED by ₱<?= number_format($usedBudget - $allocatedBudget, 2) ?></span><?php endif; ?></p>
                    <p>Remaining Budget : ₱<?= number_format($remainingBudget,2) ?></p>
                    <p class="text-muted"><em><?= htmlspecialchars($budgetDetails) ?></em></p>

                    <div class="mb-2">
                        <div class="d-flex justify-content-between small">
                            <span>Used (<?= $progress ?>%)</span>
                            <span>Remaining (<?= max(0, 100 - $progress) ?>%)</span>
                        </div>
                        <div class="progress" style="height: 24px;">
                            <div class="progress-bar <?= $isOverBudget ? 'bg-danger' : 'bg-success' ?>" role="progressbar" style="width: <?= min($progress, 100) ?>%" aria-valuenow="<?= min($progress, 100) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            <div class="progress-bar bg-secondary" role="progressbar" style="width: <?= max(0, 100 - $progress) ?>%" aria-valuenow="<?= max(0, 100 - $progress) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card mb-4 p-3">
                    <h5>Procurement Analytics Dashboard</h5>
                    <p>Procurement Categories</p>
                    <?php foreach ($categoryTotals as $category => $count):
                        $pct = $totalCategory ? round(($count / $totalCategory) * 100) : 0;
                    ?>
                        <div class="mb-2 category-row" onclick="showCategoryDetails('<?=addslashes(htmlspecialchars($category))?>', <?= $count ?>)" data-category="<?=htmlspecialchars($category)?>">
                            <div class="d-flex justify-content-between"><span><?=htmlspecialchars($category)?></span><span><?= $pct ?>%</span></div>
                            <div class="progress"><div class="progress-bar" role="progressbar" style="width: <?= $pct ?>%;" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($tab === 'market_scoping'): ?>
                <iframe src="market_scoping.php" style="width:100%; height:75vh; border:1px solid #ddd;"></iframe>
            <?php elseif ($tab === 'ppmp'): ?>
                <iframe src="ppmp.php" style="width:100%; height:75vh; border:1px solid #ddd;"></iframe>
            <?php elseif ($tab === 'rfq'): ?>
                <iframe src="rfq.php" style="width:100%; height:75vh; border:1px solid #ddd;"></iframe>
            <?php elseif ($tab === 'analysis'): ?>
                <iframe src="market_scoping_checklist.php" style="width:100%; height:85vh; border:1px solid #ddd;"></iframe>
            <?php elseif ($tab === 'reports'): ?>
                <iframe src="procurement_reports.php" style="width:100%; height:75vh; border:1px solid #ddd;"></iframe>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Category Details Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Category Details: <span id="categoryName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Total Items: <span id="categoryCount">0</span></h6>
                <div id="categoryItems" style="max-height: 400px; overflow-y: auto;">
                    <p class="text-muted">Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showCategoryDetails(category, count) {
    document.getElementById('categoryName').textContent = category;
    document.getElementById('categoryCount').textContent = count;
    
    // Fetch category details via AJAX
    fetch('pmis_dashboard.php?action=get_category_items&category=' + encodeURIComponent(category))
        .then(response => response.text())
        .then(html => {
            document.getElementById('categoryItems').innerHTML = html;
            const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
            modal.show();
        })
        .catch(error => {
            document.getElementById('categoryItems').innerHTML = '<p class="text-danger">Error loading items: ' + error + '</p>';
            const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
            modal.show();
        });
}

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
</body>
</html>
