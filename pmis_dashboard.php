<?php
require_once 'config.php';

// init settings values in session
if (!isset($_SESSION['allocatedBudget'])) {
    $_SESSION['allocatedBudget'] = 15000000;
}
if (!isset($_SESSION['show_budget'])) {
    $_SESSION['show_budget'] = true;
}
if (!isset($_SESSION['show_pending'])) {
    $_SESSION['show_pending'] = true;
}

// process update form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_dashboard'])) {
    $_SESSION['allocatedBudget'] = max(0, floatval($_POST['allocated_budget'] ?? $_SESSION['allocatedBudget']));
    header('Location: pmis_dashboard.php');
    exit;
}

// helper to avoid schema mismatch on legacy data
function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare('SHOW COLUMNS FROM `' . str_replace('`','``',$table) . '` LIKE ?');
        $stmt->execute([$column]);
        return (bool)$stmt->fetch();
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
$progress = $allocatedBudget > 0 ? round(($usedBudget / $allocatedBudget) * 100) : 0;

if (isset($_GET['toggle'])) {
    if ($_GET['toggle'] === 'budget') {
        $_SESSION['show_budget'] = !$_SESSION['show_budget'];
        header('Location: pmis_dashboard.php');
        exit;
    }
    if ($_GET['toggle'] === 'pending') {
        $_SESSION['show_pending'] = !$_SESSION['show_pending'];
        header('Location: pmis_dashboard.php');
        exit;
    }
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
    </style>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <h2>MENU</h2>
        <hr style="border-color:#6c757d">
        <a href="pmis_dashboard.php">Dashboard</a>
        <div class="mt-2"><strong>Procurement Management</strong></div>
        <a href="market_scoping.php" class="ps-3">Market Scoping</a>
        <a href="pmis_dashboard.php" class="ps-3">PPMP</a>
        <a href="pmis_dashboard.php" class="ps-3">RFQ</a>
        <a href="pmis_dashboard.php" class="ps-3">Bid Evaluation</a>
        <div class="mt-2"><strong>Supplier Management</strong></div>
        <a href="index.php" class="ps-3">Supplier Database</a>
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
            <div>
                <div>User: <strong><?=htmlspecialchars($userName)?></strong></div>
                <div>Role: <strong><?=htmlspecialchars($role)?></strong></div>
                <div>Date: <strong><?=htmlspecialchars($currentDate)?></strong></div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5>Quick hide/show</h5>
            </div>
            <div>
                <a href="?toggle=budget" class="btn btn-sm <?= $_SESSION['show_budget'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"><?= $_SESSION['show_budget'] ? 'Hide' : 'Show' ?> Budget Utilization</a>
                <a href="?toggle=pending" class="btn btn-sm <?= $_SESSION['show_pending'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"><?= $_SESSION['show_pending'] ? 'Hide' : 'Show' ?> Pending Approval</a>
            </div>
        </div>

        <ul class="nav nav-tabs mb-3">
            <?php foreach (['dashboard'=>'Dashboard', 'market_scoping'=>'Market Scoping', 'ppmp'=>'PPMP', 'rfq'=>'RFQ', 'analysis'=>'Analysis', 'reports'=>'Reports'] as $key => $label): ?>
                <li class="nav-item"><a class="nav-link <?= $tab === $key ? 'active' : '' ?>" href="?tab=<?= $key ?>"><?=htmlspecialchars($label)?></a></li>
            <?php endforeach; ?>
        </ul>

        <div class="tab-content">
            <?php if ($tab === 'dashboard'): ?>
                <div class="card mb-3 p-3">
                    <h5>Edit Dashboard Values</h5>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="update_dashboard" value="1">
                        <div class="col-md-4">
                            <label class="form-label">Allocated Budget (₱)</label>
                            <input type="number" name="allocated_budget" class="form-control" min="0" step="0.01" value="<?= htmlspecialchars(number_format($allocatedBudget,2,'.','')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pending Approval</label>
                            <input type="number" class="form-control" value="<?= htmlspecialchars($pendingApproval) ?>" disabled>
                            <div class="form-text">Derived from PPMP/RFQ/Market Scoping statuses</div>
                        </div>
                        <div class="col-md-4 align-self-end">
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
                <div class="card mb-4 p-3">
                    <h5>Budget Utilization</h5>
                    <p>Allocated Budget : ₱<?= number_format($allocatedBudget,0) ?></p>
                    <p>Used Budget      : ₱<?= number_format($usedBudget,0) ?></p>
                    <p>Remaining Budget : ₱<?= number_format($remainingBudget,0) ?></p>
                    <div class="progress mb-1"><div class="progress-bar" role="progressbar" style="width: <?= $progress ?>%" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"><?= $progress ?>%</div></div>
                </div>
                <?php endif; ?>
            <?php elseif ($tab === 'market_scoping'): ?>
                <iframe src="market_scoping.php" style="width:100%; height:75vh; border:1px solid #ddd;"></iframe>
            <?php elseif ($tab === 'ppmp'): ?>
                <iframe src="ppmp.php" style="width:100%; height:75vh; border:1px solid #ddd;"></iframe>
            <?php elseif ($tab === 'rfq'): ?>
                <iframe src="rfq.php" style="width:100%; height:75vh; border:1px solid #ddd;"></iframe>
            <?php elseif ($tab === 'analysis'): ?>
                <iframe src="market_analysis.php" style="width:100%; height:37vh; border:1px solid #ddd;"></iframe>
                <iframe src="market_checklist.php" style="width:100%; height:37vh; border:1px solid #ddd; margin-top:1rem;"></iframe>
            <?php elseif ($tab === 'reports'): ?>
                <iframe src="procurement_reports.php" style="width:100%; height:75vh; border:1px solid #ddd;"></iframe>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
