<?php
require "config.php";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);
foreach($tables as $t){ echo "TABLE:" . $t[0] . PHP_EOL; }
$cols = $pdo->query("SHOW COLUMNS FROM market_scoping")->fetchAll(PDO::FETCH_ASSOC);
echo "MARKET_SCOPING COLUMNS:\n"; print_r($cols);
$cols2 = $pdo->query("SHOW COLUMNS FROM ppmp_plans")->fetchAll(PDO::FETCH_ASSOC);
echo "PPMP_PLANS COLUMNS:\n"; print_r($cols2);
$count = $pdo->query('SELECT COUNT(*) FROM market_scoping')->fetchColumn();
echo "market_scoping_count=".$count.PHP_EOL;
$count2 = $pdo->query('SELECT COUNT(*) FROM ppmp_plans')->fetchColumn();
echo "ppmp_plans_count=".$count2.PHP_EOL;
$rows = $pdo->query('SELECT category, COUNT(*) AS c FROM market_scoping GROUP BY category')->fetchAll(PDO::FETCH_ASSOC);
echo "market_scoping query rows:".count($rows).PHP_EOL; print_r($rows);
$rows2 = $pdo->query('SELECT category, COUNT(*) AS c FROM ppmp_plans GROUP BY category')->fetchAll(PDO::FETCH_ASSOC);
echo "ppmp_plans query rows:".count($rows2).PHP_EOL; print_r($rows2);
?>
