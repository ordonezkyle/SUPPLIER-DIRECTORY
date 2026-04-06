<?php
require 'config.php';
foreach ($pdo->query('SELECT scoping_id, category, project_name, quotation FROM market_scoping ORDER BY created_at DESC LIMIT 20') as $row) {
    echo $row['scoping_id'] . '|' . ($row['category'] === null ? 'NULL' : $row['category']) . '|' . ($row['project_name'] === null ? 'NULL' : $row['project_name']) . '|' . ($row['quotation'] === null ? 'NULL' : $row['quotation']) . PHP_EOL;
}
?>
