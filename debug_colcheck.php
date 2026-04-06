<?php
require "config.php";
function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetch();
    } catch (PDOException $e) {
        echo "ERROR: " . $e->getMessage() . PHP_EOL;
        return false;
    }
}
$columns = ['category','status','expected_delivery_date','period_from_date','period_to_date'];
foreach ($columns as $col) {
    echo $col . ': ' . (columnExists($pdo,'market_scoping',$col)?'YES':'NO') . PHP_EOL;
}
