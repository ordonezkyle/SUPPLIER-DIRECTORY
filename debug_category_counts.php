<?php
require 'config.php';
foreach ($pdo->query('SELECT category, COUNT(*) AS c FROM ppmp_plans GROUP BY category') as $row) {
    echo "PPMP:" . ($row['category']===null?'NULL':$row['category']) . ' => ' . $row['c'] . PHP_EOL;
}
foreach ($pdo->query('SELECT category, COUNT(*) AS c FROM market_scoping GROUP BY category') as $row) {
    echo "SCOPING:" . ($row['category']===null?'NULL':$row['category']) . ' => ' . $row['c'] . PHP_EOL;
}
?>
