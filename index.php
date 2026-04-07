<?php
// ensure session and config are loaded before any output
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PEZA Supplier Directory</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: url('images/PEZA-background.jpeg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background-color: rgba(255,255,255,0.96);
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 20px 45px rgba(0,0,0,0.12);
        }
        .sidebar {
            min-width: 260px;
            margin-right: 1.25rem;
            padding: 1rem 1rem 0.85rem;
            background: rgba(255,255,255,0.98);
            border-radius: 16px;
            border: 1px solid rgba(13, 110, 253, 0.08);
            box-shadow: 0 10px 30px rgba(13, 110, 253, 0.06);
            animation: slideIn 0.5s ease forwards;
        }
        .sidebar h5 {
            margin-bottom: 1rem;
            font-size: 1.05rem;
            letter-spacing: 0.02em;
            color: #0d6efd;
        }
        .sidebar .nav-link {
            display: block;
            margin-bottom: 0.5rem;
            padding: 0.65rem 0.9rem;
            border-radius: 12px;
            color: #0d6efd;
            transition: transform 0.25s ease, background-color 0.25s ease, color 0.25s ease;
            background: rgba(13, 110, 253, 0.05);
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background: #0d6efd;
            transform: translateX(6px);
            text-decoration: none;
        }
        .sidebar .nav-link:nth-child(2) { animation: popIn 0.45s ease both; }
        .sidebar .nav-link:nth-child(3) { animation: popIn 0.47s ease both; }
        .sidebar .nav-link:nth-child(4) { animation: popIn 0.49s ease both; }
        .sidebar .nav-link:nth-child(5) { animation: popIn 0.51s ease both; }
        .sidebar .nav-link:nth-child(6) { animation: popIn 0.53s ease both; }
        .sidebar .nav-link:nth-child(7) { animation: popIn 0.55s ease both; }
        .sidebar .nav-link:nth-child(8) { animation: popIn 0.57s ease both; }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes popIn {
            from { opacity: 0; transform: translateX(-14px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .table thead {
            background: rgba(13, 110, 253, 0.08);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex align-items-start mb-4">
        <nav class="sidebar">
            <h5>Quick Modules</h5>
            <a href="pmis_dashboard.php" class="nav-link">PMIS Dashboard</a>
            <a href="market_scoping.php" class="nav-link">Market Scoping</a>
            <a href="ppmp.php" class="nav-link">PPMP</a>
            <a href="rfq.php" class="nav-link">RFQ</a>
            <a href="market_checklist.php" class="nav-link">Market Checklist</a>
            <a href="market_scoping_checklist.php" class="nav-link">Market Scoping Checklist</a>
            <a href="price_comparison.php" class="nav-link">Price Comparison</a>
            <a href="procurement_reports.php" class="nav-link">Procurement Reports</a>
        </nav>
        <div class="flex-fill">
            <h1 class="mb-3">PEZA Supplier &amp; Contact Directory</h1>

    <form method="get" class="row g-2 mb-3 align-items-center">
        <div class="col-md-4">
            <input autocomplete="off" type="text" name="q" class="form-control" placeholder="Search company or officer" value="<?=htmlspecialchars(
                $_GET['q'] ?? ''
            )?>">
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <option value="Active" <?= (isset($_GET['status']) && $_GET['status']==='Active') ? 'selected' : ''?>>Active</option>
                <option value="Inactive" <?= (isset($_GET['status']) && $_GET['status']==='Inactive') ? 'selected' : ''?>>Inactive</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="category" class="form-select">
                <option value="">All Categories</option>
                <option value="Equipment" <?= (isset($_GET['category']) && $_GET['category']==='Equipment') ? 'selected' : ''?>>Equipment</option>
                <option value="Distributor" <?= (isset($_GET['category']) && $_GET['category']==='Distributor') ? 'selected' : ''?>>Distributor</option>
                <option value="Development" <?= (isset($_GET['category']) && $_GET['category']==='Development') ? 'selected' : ''?>>Development</option>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2 justify-content-end">
            <button class="btn btn-primary">Search</button>
            <?php if (!empty($_SESSION['logged_in'])): ?>
                <a href="admin.php" class="btn btn-success">Admin</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-success">Admin</a>
            <?php endif; ?>
        </div>
    </form>

    <?php
    // use single-quoted string so backticks are treated literally
    $sql = 'SELECT c.company_id, c.company_name, `c`.`category`, c.status,
                   o.officer_name, o.position, o.email, o.phone
            FROM companies c
            LEFT JOIN officers o ON o.company_id = c.company_id';
    $conditions = [];
    $params = [];

    if (isset($_GET['q'])) {
        $q = trim($_GET['q']);
        if ($q !== '') {
            // spaces in query should act like wildcards
            $q = preg_replace('/\s+/', '%', $q);
            // search across multiple fields; use numbered parameters to avoid reuse issues
            $conditions[] = '(' .
                'c.company_name LIKE :q1 OR ' .
                'o.officer_name LIKE :q2 OR ' .
                'o.position LIKE :q3 OR ' .
                'o.email LIKE :q4 OR ' .
                'o.phone LIKE :q5 OR ' .
                'c.category LIKE :q6 OR ' .
                'c.remarks LIKE :q7' .
                ')';
            $like = '%'.$q.'%';
            for ($i = 1; $i <= 7; $i++) {
                $params[":q$i"] = $like;
            }
        }
    }
    if (!empty($_GET['status'])) {
        $conditions[] = 'c.status = :status';
        $params[':status'] = $_GET['status'];
    }
    if (!empty($_GET['category'])) {
        $conditions[] = 'c.category = :category';
        $params[':category'] = $_GET['category'];
    }

    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY c.company_name';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    ?>

    <table class="table table-bordered mt-3">
        <thead>
        <tr>
            <th>Company/Supplier</th>
            <th>Officer</th>
            <th>Position</th>
            <th>Email</th>
            <th>Contact Number</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): 
            $rowClass = '';
            if ($r['status'] === 'Active') {
                $rowClass = 'table-success';
            } elseif ($r['status'] === 'Inactive') {
                $rowClass = 'table-danger';
            }
        ?>
            <tr class="<?= $rowClass ?>">
                <td><?=htmlspecialchars($r['company_name'])?></td>
                <td><?=htmlspecialchars($r['officer_name'])?></td>
                <td><?=htmlspecialchars($r['position'])?></td>
                <td><a href="mailto:<?=htmlspecialchars($r['email'])?>"><?=htmlspecialchars($r['email'])?></a></td>
                <td><?=htmlspecialchars($r['phone'])?></td>
                <td><span class="badge <?= $r['status'] === 'Active' ? 'bg-success' : 'bg-danger' ?>"><?=htmlspecialchars($r['status'])?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>