<?php
require 'config.php';
function columnExists($pdo,$table,$column){try{$stmt=$pdo->prepare('SHOW COLUMNS FROM `' . str_replace('`','``',$table) . '` LIKE ?');$stmt->execute([$column]);return (bool)$stmt->fetch();}catch(PDOException $e){return false;}}
$categoryTotals = ['ICT Equipment'=>0,'Office Supplies'=>0,'Infrastructure'=>0,'Maintenance'=>0,'Other'=>0];
if(columnExists($pdo,'ppmp_plans','category')){
    $rows=$pdo->query('SELECT COALESCE(category, "Other") as category, COUNT(*) AS c FROM ppmp_plans GROUP BY category')->fetchAll(PDO::FETCH_ASSOC);
    echo "PPMP rows: " . count($rows) . PHP_EOL;
    foreach($rows as $row){$cat=trim($row['category'])?:'Other'; if(!isset($categoryTotals[$cat])){$cat='Other';} $categoryTotals[$cat]+=(int)$row['c']; echo "ppmp: [{$row['category']}] => {$row['c']} -> {$cat}\n";} }
if(columnExists($pdo,'market_scoping','category')){
    $rows=$pdo->query('SELECT COALESCE(category, "Other") as category, COUNT(*) AS c FROM market_scoping GROUP BY category')->fetchAll(PDO::FETCH_ASSOC);
    echo "SCOPING rows: " . count($rows) . PHP_EOL;
    foreach($rows as $row){$cat=trim($row['category'])?:'Other'; if(!isset($categoryTotals[$cat])){$cat='Other';} $categoryTotals[$cat]+=(int)$row['c']; echo "scoping: [{$row['category']}] => {$row['c']} -> {$cat}\n";} }
print_r($categoryTotals);
echo "totalCategory=".array_sum($categoryTotals).PHP_EOL;
?>
