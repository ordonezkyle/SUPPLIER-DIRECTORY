<?php
require_once 'config.php';

$rows = $pdo->query('SELECT m.project_name, c.company_name, m.quotation, m.created_at FROM market_scoping m LEFT JOIN companies c ON c.company_id=m.supplier_id WHERE m.quotation IS NOT NULL ORDER BY m.project_name, m.quotation')->fetchAll();

$aggregated = [];
foreach ($rows as $r) {
    $project = $r['project_name'];
    if (!isset($aggregated[$project])) {
        $aggregated[$project] = ['min'=>INF,'max'=>0,'sum'=>0,'count'=>0,'suppliers'=>[]];
    }
    $price = (float)$r['quotation'];
    $aggregated[$project]['min'] = min($aggregated[$project]['min'], $price);
    $aggregated[$project]['max'] = max($aggregated[$project]['max'], $price);
    $aggregated[$project]['sum'] += $price;
    $aggregated[$project]['count'] += 1;
    $aggregated[$project]['suppliers'][] = ['company'=>$r['company_name'] ?? 'N/A','price'=>$price];
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Market Price Comparison</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"><style>body{background:url('images/PEZA-background.jpeg') no-repeat center center fixed;background-size:cover;}.container{background:rgba(255,255,255,0.95);padding:1rem;margin:20px;border-radius:4px;}</style></head><body>
<div class="container"><h1>Market Price Comparison</h1><p><a href="index.php" class="btn btn-sm btn-outline-secondary me-2">Directory</a><a href="market_scoping.php" class="btn btn-sm btn-outline-primary">Market Scoping</a></p>
<?php if (!$aggregated): ?><div class="alert alert-warning">No quotation data available.</div><?php endif; ?>
<?php foreach ($aggregated as $project=>$stats): $avg = $stats['count'] ? round($stats['sum']/$stats['count'],2) : 0; ?>
    <div class="card mb-3 p-3"><h4><?=htmlspecialchars($project)?></h4>
        <p>Average Market Price: ₱<?=number_format($avg,2)?><br>Lowest Price: ₱<?=number_format($stats['min'],2)?><br>Highest Price: ₱<?=number_format($stats['max'],2)?></p>
        <table class="table table-sm"><thead><tr><th>Supplier</th><th>Price</th></tr></thead><tbody>
        <?php foreach ($stats['suppliers'] as $supplier): ?>
            <tr><td><?=htmlspecialchars($supplier['company'])?></td><td>₱<?=number_format($supplier['price'],2)?></td></tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
<?php endforeach; ?></div></body></html>