<?php
require_once 'config.php';

// if dompdf isn't available via normal vendor directory, try local dompdf folder
$loaderPath = null;
if (file_exists(__DIR__.'/vendor/autoload.php')) {
    $loaderPath = __DIR__.'/vendor/autoload.php';
} elseif (file_exists(__DIR__.'/dompdf/vendor/autoload.php')) {
    $loaderPath = __DIR__.'/dompdf/vendor/autoload.php';
}

if (!$loaderPath) {
    // do CSV export instead of PDF
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="peza_directory.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Company','Officer','Position','Email','Phone','Status']);
    // we will still execute the query below to gather rows
    $csvFallback = true;
} else {
    require_once $loaderPath;
    $csvFallback = false;
}

// if dompdf loaded we can alias the class for later use
if (!$csvFallback && class_exists('Dompdf\Dompdf')) {
    class_alias('Dompdf\Dompdf', 'Dompdf');
}

// replicate same query logic as index.php
$sql = 'SELECT c.company_id, c.company_name, `c`.`category`, c.status,
               o.officer_name, o.position, o.email, o.phone
        FROM companies c
        LEFT JOIN officers o ON o.company_id = c.company_id';
$conditions = [];
$params = [];
if (isset($_GET['q'])) {
    $q = trim($_GET['q']);
    if ($q !== '') {
        $q = preg_replace('/\s+/', '%', $q);
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

// CSV fallback (when Dompdf not available)
if (!empty($csvFallback)) {
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['company_name'],
            $r['officer_name'],
            $r['position'],
            $r['email'],
            $r['phone'],
            $r['status'],
        ]);
    }
    fclose($out);
    exit;
}

$html = '<h1>PEZA Supplier Directory</h1>';
$html .= '<table border="1" cellpadding="4" cellspacing="0" width="100%">';
$html .= '<tr><th>Company</th><th>Officer</th><th>Position</th><th>Email</th><th>Phone</th><th>Status</th></tr>';
foreach ($rows as $r) {
    $html .= '<tr>';
    $html .= '<td>'.htmlspecialchars($r['company_name']).'</td>';
    $html .= '<td>'.htmlspecialchars($r['officer_name']).'</td>';
    $html .= '<td>'.htmlspecialchars($r['position']).'</td>';
    $html .= '<td>'.htmlspecialchars($r['email']).'</td>';
    $html .= '<td>'.htmlspecialchars($r['phone']).'</td>';
    $html .= '<td>'.htmlspecialchars($r['status']).'</td>';
    $html .= '</tr>';
}
$html .= '</table>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
// (optional) setup paper size
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('peza_directory.pdf');
