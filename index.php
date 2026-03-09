<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PEZA Supplier Directory</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            /* place your chosen photo in scms/images/ and update the filename below */
            background: url('images/PEZA-background.jpeg') no-repeat center center fixed;
            background-size: cover;
        }
        /* optionally dim the background behind panels */
        .container {
            background-color: rgba(255,255,255,0.9);
            padding: 1rem;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="mb-4">PEZA Supplier &amp; Contact Directory</h1>

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
            <a href="index.php" class="btn btn-secondary">Show All</a>
            <a href="export.php" class="btn btn-info" target="_blank">Export PDF</a>
            <a href="admin.php" class="btn btn-success">Admin</a>
        </div>
    </form>

    <?php
    require_once 'config.php';

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
            <th>Company</th>
            <th>Officer</th>
            <th>Position</th>
            <th>Email</th>
            <th>Contact #</th>
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