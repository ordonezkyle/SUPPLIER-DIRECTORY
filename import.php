<?php
// import.php - simple importer for the companies table (CSV or Excel)
require_once 'config.php';

// try to load PhpSpreadsheet if available
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

// optionally alias the IOFactory class (if available)
if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
    class_alias('PhpOffice\PhpSpreadsheet\IOFactory', 'IOFactory');
}

$message = '';
$importedRows = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['datafile'])) {
    $tmpName = $_FILES['datafile']['tmp_name'];
    $name = $_FILES['datafile']['name'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    $rows = [];

    if (in_array($ext, ['xls', 'xlsx']) && class_exists('IOFactory')) {
        // parse with PhpSpreadsheet
        $spreadsheet = \IOFactory::load($tmpName);
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($sheet->getRowIterator() as $i => $row) {
            if ($i === 1) continue; // skip header
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $data = [];
            foreach ($cellIterator as $cell) {
                $data[] = trim($cell->getValue());
            }
            $rows[] = $data;
        }
    } else {
        // fallback to CSV
        if (($handle = fopen($tmpName, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }
    }

    // if the first row looks like a header, build a column map
    $colMap = null;
    if (count($rows) > 0) {
        $first = $rows[0];
        $lower = array_map('strtolower', $first);
        // look for keywords
        $map = [];
        foreach ($lower as $i => $h) {
            if (strpos($h, 'company') !== false || strpos($h, 'supplier') !== false) {
                $map['company'] = $i;
            }
            if (strpos($h, 'officer') !== false) {
                $map['officer'] = $i;
            }
            if (strpos($h, 'position') !== false) {
                $map['position'] = $i;
            }
            if (strpos($h, 'email') !== false) {
                $map['email'] = $i;
            }
            if (strpos($h, 'remark') !== false) {
                $map['remarks'] = $i;
            }
            if (strpos($h, 'status') !== false) {
                $map['status'] = $i;
            }
        }
        // If we have at least company column, treat first row as header
        if (isset($map['company'])) {
            $colMap = $map;
            array_shift($rows); // drop header row
        }
    }

    foreach ($rows as $data) {
        if ($colMap) {
            // use named columns, default to null if missing
            $company = $data[$colMap['company']] ?? '';
            $status  = $data[$colMap['status']] ?? '';
            $remarks = $data[$colMap['remarks']] ?? '';
        } else {
            // fallback positional: company,category,status,remarks
            if (count($data) < 3) continue;
            $company = $data[0];
            $status  = $data[2] ?? '';
            $remarks = $data[3] ?? null;
        }
        // optionally interpret status term 'inactive' in remarks
        if (!$status && stripos($remarks, 'inactive') !== false) {
            $status = 'Inactive';
        }
        $stmt = $pdo->prepare('INSERT INTO companies (company_name, category, status, remarks) VALUES (?, ?, ?, ?)');
        $stmt->execute([$company, null, $status ?: 'Active', $remarks]);
        $importedRows[] = $data;
    }

    $message = 'Import completed (' . count($importedRows) . ' rows)';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CSV Import</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container">
    <h1>Import Suppliers from Excel/CSV</h1>
    <?php if ($message): ?><div class="alert alert-success"><?=htmlspecialchars($message)?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <!-- accept both CSV and Excel formats -->
            <input type="file" name="datafile" accept=".csv,.xls,.xlsx" required>
        </div>
        <button class="btn btn-primary">Upload</button>
    </form>

    <?php if (!empty($importedRows)): ?>
        <h2 class="mt-4">Rows Imported</h2>
        <table class="table table-sm">
            <thead><tr><th>Company</th><th>Category</th><th>Status</th><th>Remarks</th></tr></thead>
            <tbody>
            <?php foreach ($importedRows as $r): ?>
                <tr>
                    <td><?=htmlspecialchars($r[0] ?? '')?></td>
                    <td><?=htmlspecialchars($r[1] ?? '')?></td>
                    <td><?=htmlspecialchars($r[2] ?? '')?></td>
                    <td><?=htmlspecialchars($r[3] ?? '')?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="index.php">Back to directory</a></p>
</div>
</body>
</html>