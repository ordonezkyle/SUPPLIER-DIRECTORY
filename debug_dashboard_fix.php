<?php
require "config.php";
function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}
$categoryTotals = ['ICT Equipment'=>0,'Office Supplies'=>0,'Infrastructure'=>0,'Maintenance'=>0,'Other'=>0];
if(columnExists($pdo,'ppmp_plans','category')){
    $rows=$pdo->query('SELECT COALESCE(category, "Other") as category, COUNT(*) AS c FROM ppmp_plans GROUP BY category')->fetchAll(PDO::FETCH_ASSOC);
    foreach($rows as $row){$cat=trim($row['category'])?:'Other'; if(!isset($categoryTotals[$cat])){$cat='Other';} $categoryTotals[$cat]+=(int)$row['c']; echo "ppmp: [{$row['category']}] => {$row['c']}\n";}}
if(columnExists($pdo,'market_scoping','category')){
    $rows=$pdo->query('SELECT COALESCE(category, "Other") as category, COUNT(*) AS c FROM market_scoping GROUP BY category')->fetchAll(PDO::FETCH_ASSOC);
    foreach($rows as $row){$cat=trim($row['category'])?:'Other'; if(!isset($categoryTotals[$cat])){$cat='Other';} $categoryTotals[$cat]+=(int)$row['c']; echo "scoping: [{$row['category']}] => {$row['c']}\n";}}
print_r($categoryTotals);
echo "totalCategory=".array_sum($categoryTotals).PHP_EOL;
?>
