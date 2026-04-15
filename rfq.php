<?php
require_once 'config.php';

// Create RFQ master table
$pdo->exec("CREATE TABLE IF NOT EXISTS rfq (
    rfq_id INT AUTO_INCREMENT PRIMARY KEY,
    rfq_number VARCHAR(50) UNIQUE NOT NULL,
    recipient_name VARCHAR(255) NOT NULL,
    recipient_address TEXT DEFAULT NULL,
    rfq_date DATE NOT NULL,
    submission_deadline DATE NOT NULL,
    rfq_description TEXT DEFAULT NULL,
    delivery_period VARCHAR(50) DEFAULT NULL,
    bidder_documents TEXT DEFAULT NULL,
    supplier_full_name VARCHAR(255) DEFAULT NULL,
    supplier_designation VARCHAR(255) DEFAULT NULL,
    supplier_company VARCHAR(255) DEFAULT NULL,
    supplier_contact_no VARCHAR(100) DEFAULT NULL,
    supplier_address TEXT DEFAULT NULL,
    bac_chair VARCHAR(255) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY (rfq_number),
    KEY (status)
) ENGINE=InnoDB;");

// Add missing columns to existing rfq table
$existingColumns = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rfq'")->fetchAll(PDO::FETCH_COLUMN);
$alterFields = [
    'delivery_period' => 'VARCHAR(50) DEFAULT NULL',
    'sealed_bid_address' => 'TEXT DEFAULT NULL',
    'bidder_documents' => 'TEXT DEFAULT NULL',
    'delivery_address' => 'TEXT DEFAULT NULL',
    'supplier_full_name' => 'VARCHAR(255) DEFAULT NULL',
    'supplier_designation' => 'VARCHAR(255) DEFAULT NULL',
    'supplier_company' => 'VARCHAR(255) DEFAULT NULL',
    'supplier_contact_no' => 'VARCHAR(100) DEFAULT NULL',
    'supplier_address' => 'TEXT DEFAULT NULL',
    'bac_chair' => 'VARCHAR(255) DEFAULT NULL',
];
foreach ($alterFields as $column => $definition) {
    if (!in_array($column, $existingColumns)) {
        $pdo->exec("ALTER TABLE rfq ADD COLUMN $column $definition");
    }
}

// Create RFQ items detail table
$pdo->exec("CREATE TABLE IF NOT EXISTS rfq_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    rfq_id INT NOT NULL,
    item_number INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(50) NOT NULL,
    articles VARCHAR(255) NOT NULL,
    unit_price DECIMAL(12,2) DEFAULT 0,
    total_cost DECIMAL(12,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY (rfq_id),
    UNIQUE KEY unique_item_number (rfq_id, item_number),
    FOREIGN KEY (rfq_id) REFERENCES rfq(rfq_id) ON DELETE CASCADE
) ENGINE=InnoDB;");

$editRFQ = null;
$rfqItems = [];
$errors = [];

// Generate next RFQ number
function generateRFQNumber($pdo) {
    $date = date('Ymd');
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM rfq WHERE rfq_number LIKE 'RFQ-$date-%'")->fetch()['cnt'];
    return 'RFQ-' . $date . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

// Handle Save RFQ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_rfq'])) {
    $rfqId = !empty($_POST['rfq_id']) ? intval($_POST['rfq_id']) : null;
    $recipientName = trim($_POST['recipient_name'] ?? '');
    $recipientAddress = trim($_POST['recipient_address'] ?? '');
    $rfqDate = trim($_POST['rfq_date'] ?? '');
    $submissionDeadline = trim($_POST['submission_deadline'] ?? '');
    $rfqDescription = trim($_POST['rfq_description'] ?? '');
    $deliveryPeriod = trim($_POST['delivery_period'] ?? '');
    $sealedBidAddress = trim($_POST['sealed_bid_address'] ?? '');
    $bidderDocuments = trim($_POST['bidder_documents'] ?? '');
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');
    $supplierFullName = trim($_POST['supplier_full_name'] ?? '');
    $supplierDesignation = trim($_POST['supplier_designation'] ?? '');
    $supplierCompany = trim($_POST['supplier_company'] ?? '');
    $supplierContactNo = trim($_POST['supplier_contact_no'] ?? '');
    $supplierAddress = trim($_POST['supplier_address'] ?? '');
    $bacChair = trim($_POST['bac_chair'] ?? '');
    $status = trim($_POST['status'] ?? 'Draft');
    $itemNumbers = $_POST['item_number'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $units = $_POST['unit'] ?? [];
    $articlesList = $_POST['articles'] ?? [];
    $unitPrices = $_POST['unit_price'] ?? [];
    
    if (empty($recipientName) || empty($rfqDate) || empty($submissionDeadline)) {
        $errors[] = "Recipient name, RFQ date, and submission deadline are required.";
    } else {
        try {
            if ($rfqId) {
                // Update existing RFQ
                $stmt = $pdo->prepare('UPDATE rfq SET recipient_name = ?, recipient_address = ?, rfq_date = ?, submission_deadline = ?, rfq_description = ?, delivery_period = ?, sealed_bid_address = ?, bidder_documents = ?, delivery_address = ?, supplier_full_name = ?, supplier_designation = ?, supplier_company = ?, supplier_contact_no = ?, supplier_address = ?, bac_chair = ?, status = ? WHERE rfq_id = ?');
                $stmt->execute([$recipientName, $recipientAddress, $rfqDate, $submissionDeadline, $rfqDescription, $deliveryPeriod, $sealedBidAddress, $bidderDocuments, $deliveryAddress, $supplierFullName, $supplierDesignation, $supplierCompany, $supplierContactNo, $supplierAddress, $bacChair, $status, $rfqId]);
            } else {
                // Create new RFQ
                $rfqNumber = generateRFQNumber($pdo);
                $stmt = $pdo->prepare('INSERT INTO rfq (rfq_number, recipient_name, recipient_address, rfq_date, submission_deadline, rfq_description, delivery_period, sealed_bid_address, bidder_documents, delivery_address, supplier_full_name, supplier_designation, supplier_company, supplier_contact_no, supplier_address, bac_chair, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$rfqNumber, $recipientName, $recipientAddress, $rfqDate, $submissionDeadline, $rfqDescription, $deliveryPeriod, $sealedBidAddress, $bidderDocuments, $deliveryAddress, $supplierFullName, $supplierDesignation, $supplierCompany, $supplierContactNo, $supplierAddress, $bacChair, $status]);
                $rfqId = $pdo->lastInsertId();
                
                // Save inline items from creation form
                foreach ($itemNumbers as $index => $itemNumber) {
                    $quantity = floatval($quantities[$index] ?? 0);
                    $unit = trim($units[$index] ?? '');
                    $articles = trim($articlesList[$index] ?? '');
                    $unitPrice = floatval($unitPrices[$index] ?? 0);

                    if (!empty($itemNumber) && $quantity > 0 && !empty($unit) && !empty($articles)) {
                        $itemStmt = $pdo->prepare('INSERT INTO rfq_items (rfq_id, item_number, quantity, unit, articles, unit_price) VALUES (?, ?, ?, ?, ?, ?)');
                        $itemStmt->execute([$rfqId, intval($itemNumber), $quantity, $unit, $articles, $unitPrice]);
                    }
                }
            }
            header('Location: rfq.php?edit=' . $rfqId);
            exit;
        } catch (PDOException $e) {
            $errors[] = "Error saving RFQ: " . $e->getMessage();
        }
    }
}

// Handle Add Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $rfqId = intval($_POST['rfq_id']);
    $itemNumber = intval($_POST['item_number'] ?? 0);
    $quantity = floatval($_POST['quantity'] ?? 0);
    $unit = trim($_POST['unit'] ?? '');
    $articles = trim($_POST['articles'] ?? '');
    $unitPrice = floatval($_POST['unit_price'] ?? 0);
    
    if ($itemNumber > 0 && $quantity > 0 && !empty($unit) && !empty($articles)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO rfq_items (rfq_id, item_number, quantity, unit, articles, unit_price) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$rfqId, $itemNumber, $quantity, $unit, $articles, $unitPrice]);
        } catch (PDOException $e) {
            $errors[] = "Error adding item: " . $e->getMessage();
        }
    } else {
        $errors[] = "All item fields are required and quantity must be greater than 0.";
    }
    header('Location: rfq.php?edit=' . $rfqId);
    exit;
}

// Handle Delete Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_item'])) {
    $itemId = intval($_POST['item_id']);
    $rfqId = intval($_POST['rfq_id']);
    try {
        $stmt = $pdo->prepare('DELETE FROM rfq_items WHERE item_id = ?');
        $stmt->execute([$itemId]);
    } catch (PDOException $e) {
        $errors[] = "Error deleting item: " . $e->getMessage();
    }
    header('Location: rfq.php?edit=' . $rfqId);
    exit;
}

// Handle Delete RFQ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_rfq'])) {
    $rfqId = intval($_POST['rfq_id']);
    try {
        $stmt = $pdo->prepare('DELETE FROM rfq WHERE rfq_id = ?');
        $stmt->execute([$rfqId]);
    } catch (PDOException $e) {
        $errors[] = "Error deleting RFQ: " . $e->getMessage();
    }
    header('Location: rfq.php');
    exit;
}

// Load RFQ for editing
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $rfqId = intval($_GET['edit']);
    $stmt = $pdo->prepare('SELECT * FROM rfq WHERE rfq_id = ?');
    $stmt->execute([$rfqId]);
    $editRFQ = $stmt->fetch();
    
    if ($editRFQ) {
        $stmt = $pdo->prepare('SELECT * FROM rfq_items WHERE rfq_id = ? ORDER BY item_number');
        $stmt->execute([$rfqId]);
        $rfqItems = $stmt->fetchAll();
    }
}

// Load all RFQs
$rfqs = $pdo->query('SELECT * FROM rfq ORDER BY created_at DESC')->fetchAll();

// Check for print mode
$printMode = isset($_GET['print']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFQ Management - PEZA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: url('images/PEZA-background.jpeg') no-repeat center center fixed; background-size: cover; }
        .container { background: rgba(255, 255, 255, 0.98); padding: 2rem; margin: 20px auto; border-radius: 4px; max-width: 1200px; }
        .form-section { background: #f8f9fa; padding: 1.5rem; border-radius: 4px; margin-bottom: 2rem; }
        .items-table { margin-top: 1rem; }
        .btn-group-actions { gap: 0.25rem; }
        .alert { margin-bottom: 1rem; }
        
        /* Print/RFQ Document Styles */
        .rfq-document { max-width: 850px; margin: 0 auto; font-family: Arial, sans-serif; line-height: 1.2; background: white; padding: 0.75rem; }
        .rfq-print-wrapper { position: relative; padding-top: 0.5rem; }
        .download-pdf-btn { position: absolute; top: 0; right: 0; z-index: 1000; background: #0d6efd; color: #fff; border: 1px solid #0a58ca; padding: 0.55rem 0.9rem; box-shadow: 0 4px 10px rgba(0,0,0,0.18); border-radius: 0.35rem; }
        .download-pdf-btn:hover { background: #0b5ed7; border-color: #0a58ca; }
        .rfq-header { text-align: center; margin-bottom: 0rem; padding-bottom: 0rem; }
        .rfq-header-logo { max-height: 72px; margin: 0 auto 0.4rem; display: block; }
        .rfq-header h2 { margin: 0.2rem 0; font-size: 1.15rem; font-weight: bold; letter-spacing: 1px; }
        .rfq-header p { margin: 0.05rem 0; font-size: 0.85rem; }
        .rfq-meta { display: flex; justify-content: space-between; margin-bottom: 1.2rem; font-weight: bold; background: white; padding: 0.35rem; }
        .rfq-section { margin-bottom: 0.8rem; background: white; }
        .rfq-section-title { font-weight: bold; text-decoration: underline; margin-bottom: 0.3rem; }
        .rfq-intro { margin-bottom: 0.55rem; font-size: 0.86rem; line-height: 1.35; background: white; padding: 0.3rem; }
        .rfq-table { width: 100%; border-collapse: collapse; margin: 0.5rem 0; background: white; }
        .rfq-table th, .rfq-table td { border: 1px solid #333; padding: 0.28rem; text-align: left; font-size: 0.8rem; background: white; }
        .rfq-table th { background-color: #2f8be7; font-weight: bold; }
        .rfq-table td { height: 22px; }
        .rfq-total-row { font-weight: bold; background-color: #2f8be7; }
        .rfq-notes { font-size: 0.8rem; margin: 0.55rem 0; background: white; padding: 0.3rem; }
        .rfq-notes li { margin-bottom: 0.2rem; }
        .rfq-terms { font-size: 0.82rem; margin: 0.55rem 0; line-height: 1.3; background: white; padding: 0.3rem; }
        .rfq-signature { margin-top: 0.3rem; display: flex; justify-content: space-between; background: white; page-break-inside: avoid; }
        .rfq-signature-block { width: 45%; background: white; }
        .rfq-signature-line { border-top: 1px solid #333; margin-top: 0.6rem; text-align: center; font-size: 0.8rem; }
        
        @page {
            size: A4 portrait;
            margin: 12mm;
        }
        @media print {
            html, body { width: 210mm; height: auto; margin: 0; padding: 0; background: white; }
            body { background: white; }
            .container { background: white; padding: 0; margin: 0; box-shadow: none; }
            .management-section { display: none; }
            .rfq-document { max-width: 100%; width: 100%; background: white; padding: 0; margin: 0; font-size: 0.78rem; line-height: 1.05; }
            .rfq-document * { box-shadow: none !important; }
            .download-pdf-btn { display: none; }
        }
    </style>
</head>
<body>
<?php if ($printMode && $editRFQ): ?>
    <!-- Print/RFQ Document View -->
    <div class="rfq-print-wrapper">
        <button type="button" class="btn btn-primary download-pdf-btn" onclick="window.print();">Download PDF</button>
        <div class="rfq-document">
        <header class="rfq-header" style="text-align: left;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.5rem; font-size: 0.82rem; color: #333;">
                <div style="display: flex; align-items: center; gap: 0.0rem; flex: 1;">
                    <img src="images/LOGO2.jpg" class="rfq-header-logo" alt="PEZA Logo" style="float: none; margin: 0; max-height: 80px;">
                    <div style="text-align: left; line-height: 1.2; margin-top: 0.0rem;">
                        <strong>Republic of the Philippines</strong><br>
                        <strong>Philippine Economic Zone Authority</strong><br>
                        Cavite Economic Zone, Rosario, Cavite
                    </div>
                </div>
                <div style="text-align: right; line-height: 1.4; min-width: 140px;">
                    GRD-2.7-005<br>
                    Rev 01, Apr 2, 2015
                </div>
            </div>
            <h2 style="margin: 0.5rem 0 0; font-weight: bold; text-align: center; width: 100%;">REQUEST FOR QUOTATION</h2>
        </header>

        <div style="margin: 1rem 0; background: white;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 0.4rem;">
                <div style="width: 70%;">
                    <strong>TO:</strong>
                    <span style="display: inline-block; width: calc(100% - 40px); border-bottom: 1px solid #000; padding-bottom: 0.12rem; margin-left: 0.4rem;"><?= htmlspecialchars($editRFQ['recipient_name'] ?: '________________________') ?></span>
                </div>
                <div style="width: 28%; text-align: right;">
                    <strong>Date:</strong>
                    <span style="display: inline-block; width: 120px; border-bottom: 1px solid #000; padding-bottom: 0.12rem; margin-left: 0.4rem;"><?= htmlspecialchars(!empty($editRFQ['rfq_date']) ? date('M d, Y', strtotime($editRFQ['rfq_date'])) : '________________________') ?></span>
                </div>
            </div>
            <div style="margin-bottom: 0.5rem; margin-left: 0.50rem;"><strong>Sir/Madam:</strong></div>
        </div>

        <div class="rfq-intro" style="margin-bottom: 0.6rem; font-size: 0.88rem; line-height: 1.3;">
            <div style="text-indent: 1.2rem;">Please quote your lowest and most reasonable price/s of the item/s indicated below which is/are urgently needed by.</div>
            <div>PEZA.The quotation must be in conformity with the detailed description/specification cited herein.</div>
            <div style="text-indent: 1.2rem; margin-top: 0.6rem;">The Bids and Awards Committee (BAC) reserves the right to reject any or all quotation/s received without stating the reasons thereof and accept the offer most advantageous to the government.</div>
            <div style="text-indent: 1.2rem;margin-top: 0.6rem;">The decision of Committee shall be final and binding.</div>
            <div style="text-indent: 1.2rem;margin-top: 0.6rem;">Your attention hereto is highly appreciated.</div>
            <div style="display: flex; justify-content: flex-end; margin-top: 0.5rem;">
                <div style="text-align: center; width: 240px;">
                    <div style="margin-bottom: 0.3rem;">Very truly yours,</div>
                    <div style="font-weight: bold; text-decoration: underline;"><?= htmlspecialchars($editRFQ['bac_chair'] ?: '___________________________') ?></div>
                    <div style="margin-top: 0.12rem; font-size: 0.82rem;">BAC Chair</div>
                </div>
            </div>
            <div style="text-align: center; margin-top: 0.8rem;"><strong>PEZA SUPPLY & PROC. MANAGEMENT DIVISION</strong></div>
        </div>

        <div class="rfq-section" style="margin: 0.5rem 0;">
            <table class="rfq-table" style="width: 100%; border-collapse: collapse; font-size: 0.8rem;">
                <thead>
                    <tr style="background-color: #003366; color: white;">
                        <th style="border: 1px solid #333; padding: 0.3rem; text-align: center; width: 8%;">ITEM NO.</th>
                        <th style="border: 1px solid #333; padding: 0.3rem; text-align: center; width: 10%;">QTY.</th>
                        <th style="border: 1px solid #333; padding: 0.3rem; text-align: center; width: 10%;">UNIT</th>
                        <th style="border: 1px solid #333; padding: 0.3rem; text-align: center; width: 40%;">ARTICLES</th>
                        <th style="border: 1px solid #333; padding: 0.3rem; text-align: center; width: 16%;">UNIT PRICE</th>
                        <th style="border: 1px solid #333; padding: 0.3rem; text-align: center; width: 16%;">TOTAL COST</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grandTotal = 0;
                    if (!empty($rfqItems)) {
                        foreach ($rfqItems as $item): 
                            $grandTotal += floatval($item['total_cost']);
                    ?>
                        <tr>
                            <td style="border: 1px solid #333; padding: 0.3rem; text-align: center; height: 25px;"><?= intval($item['item_number']) ?></td>
                            <td style="border: 1px solid #333; padding: 0.3rem; text-align: right;"><?= number_format($item['quantity'], 2) ?></td>
                            <td style="border: 1px solid #333; padding: 0.3rem; text-align: left;"><?= htmlspecialchars($item['unit']) ?></td>
                            <td style="border: 1px solid #333; padding: 0.3rem; text-align: left;"><?= htmlspecialchars($item['articles']) ?></td>
                            <td style="border: 1px solid #333; padding: 0.3rem; text-align: right;">₱ <?= number_format($item['unit_price'], 2) ?></td>
                            <td style="border: 1px solid #333; padding: 0.3rem; text-align: right;">₱ <?= number_format($item['total_cost'], 2) ?></td>
                        </tr>
                    <?php 
                        endforeach;
                    }
                    // Add minimal empty rows so the table stays formatted but avoids pushing the bottom section to page two
                    $totalRows = max(3, count($rfqItems));
                    for ($i = count($rfqItems); $i < $totalRows; $i++):
                    ?>
                        <tr>
                            <td style="border: 1px solid #333; padding: 0.4rem; height: 25px;"></td>
                            <td style="border: 1px solid #333; padding: 0.4rem;"></td>
                            <td style="border: 1px solid #333; padding: 0.4rem;"></td>
                            <td style="border: 1px solid #333; padding: 0.4rem;"></td>
                            <td style="border: 1px solid #333; padding: 0.4rem;"></td>
                            <td style="border: 1px solid #333; padding: 0.4rem;"></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <div style="font-size: 0.78rem; margin: 0.55rem 0; line-height: 1.12; background: white; padding: 0; font-weight: bold;">
            <div style="margin-bottom: 0.15rem;"><strong>• Bidder shall type or write in "Ink" the following on the sealed envelope:</strong></div>
            <div style="margin-left: 0.9rem; margin-bottom: 0.15rem;">
                <div style="margin-bottom: 0.12rem;"><strong>- his/her name or business name;</strong></div>
                <div style="margin-bottom: 0.12rem;"><strong>- address; and</strong></div>
                <div style="margin-bottom: 0.12rem;"><strong>- identification of the item/s being quoted</strong></div>
            </div>

            <div style="margin-bottom: 0.15rem;"><strong>• Bidder must submit the SEALED BID to the Supply & Property Management Division (SPMD) / Administrative Service Division (ASD) at the following address:</strong></div>
            <div style="margin-left: 0.9rem; margin-bottom: 0.15rem; border-bottom: 1px solid #000; padding-bottom: 0.02rem; min-height: 0.45rem;"></div>

            <div style="margin-bottom: 0.15rem;"><strong>• Deadline of submission of sealed bid is on <span style="border-bottom: 1px solid #000; padding: 0 2px; min-width: 90px; display: inline-block; text-align: center; margin: 0 2px;"><?= !empty($editRFQ['submission_deadline']) ? date('F j, Y', strtotime($editRFQ['submission_deadline'])) : '' ?></span>.</strong></div>

            <div style="margin-bottom: 0.15rem;"><strong>• PRICE QUOTED SHALL BE VALID FOR 30 CALENDAR DAYS FROM DATE OF SUBMISSION.</strong></div>
            <div style="margin-left: 0.9rem; margin-bottom: 0.15rem;"><strong>NON-DELIVERY OF GOODS BASED ON THE PRICE QUOTED SHALL BE A GROUND FOR BLACKLISTING.</strong></div>

            <div style="margin-bottom: 0.15rem;"><strong>• Delivery period: <span style="border-bottom: 1px solid #000; padding: 0 2px; min-width: 36px; display: inline-block; text-align: center; margin: 0 2px;"><?= htmlspecialchars($editRFQ['delivery_period'] ?? '') ?></span> calendar days upon receipt of Purchase Order.</strong></div>

            <div style="margin-bottom: 0.15rem;"><strong>• Terms of payment: Through Cheque/15 working days upon receipt of original invoice.</strong></div>

            <div style="margin-bottom: 0.15rem;"><strong>• Winning bidder must deliver the item/s to the following address:</strong></div>
            <div style="margin-left: 0.9rem; margin-bottom: 0.15rem; border-bottom: 1px solid #000; padding-bottom: 0.02rem; min-height: 0.45rem;"></div>

            <div style="margin-bottom: 0.15rem;"><strong>• BIDDER MUST SUBMIT THE FOLLOWING DOCUMENTS:</strong></div>
            <div style="margin-left: 0.9rem; margin-bottom: 0.1rem;">
                <?php if (!empty($editRFQ['bidder_documents'])): ?>
                    <?php foreach (explode("\n", $editRFQ['bidder_documents']) as $docLine): ?>
                        <?php if (trim($docLine)): ?>
                            <div style="margin-bottom: 0;">
                                <span style="display: inline-block; border-bottom: 1px solid #000; width: 100%; padding-bottom: 0.02rem; line-height: 1.05; display: block;"><?= htmlspecialchars(trim($docLine)) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="margin-bottom: 0;">
                        <span style="display: inline-block; border-bottom: 1px solid #000; width: 100%; min-height: 0.75rem; padding-bottom: 0.02rem; display: block;"></span>
                    </div>
                    <div style="margin-bottom: 0;">
                        <span style="display: inline-block; border-bottom: 1px solid #000; width: 100%; min-height: 0.75rem; padding-bottom: 0.02rem; display: block;"></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="border-top: 1px solid #000; margin: 0.2rem 0 0.2rem; padding: 0;"></div>

        <div style="font-size: 0.78rem; margin: 0.3rem 0 0.25rem; line-height: 1.28; background: white; padding: 0;">
            <div style="margin-bottom: 0.1rem;">If you have any questions or concerns, please feel free to call CCC-ASD at the following telephone numbers:</div>
            <div>(046) 437-6146 / (046) 437-6702</div>
        </div>

        <div style="border-top: 1px solid #000; margin: 0.25rem 0 0.25rem; padding: 0;"></div>

        <div style="margin-top: 0.25rem; background: white; padding: 0;">
            <div style="text-align: left; margin-bottom: 0.15rem; font-size: 0.78rem; font-weight: bold;">
                Sir / Madam:
            </div>
            <div style="text-align: left; font-size: 0.78rem; line-height: 1.35; margin-bottom: 0.5rem;">
                <div style="margin-left: 1rem;">We hereby quote our most reasonable price for the above item/s. We understand and hereby fully accept the terms and</div>
                <div>conditions specified above.</div>
            </div>
        </div>

        <div class="rfq-signature" style="display: block; margin-top: 0.35rem; background: white; padding: 0; page-break-inside: avoid;">
            <div style="width: 100%; text-align: right;">
                <div style="display: inline-block; width: 40%; text-align: center;">
                    <div style="border-top: 1px solid #000; margin-bottom: 0.12rem; padding-top: 0.12rem; font-size: 0.78rem;">Signature of Supplier</div>
                    <div style="margin-top: 0.18rem; font-size: 0.78rem; line-height: 1.25; text-align: left;">
                        <div style="margin-bottom: 0.12rem; display: flex; gap: 0.25rem; align-items: center;">
                            <span style="flex: 0 0 30%;">Full Name:</span>
                            <span style="flex: 1; border-bottom: 1px solid #000; display: inline-block; min-width: 90px;"><?= htmlspecialchars($editRFQ['supplier_full_name'] ?? '') ?></span>
                        </div>
                        <div style="margin-bottom: 0.12rem; display: flex; gap: 0.25rem; align-items: center;">
                            <span style="flex: 0 0 30%;">Designation:</span>
                            <span style="flex: 1; border-bottom: 1px solid #000; display: inline-block; min-width: 90px;"><?= htmlspecialchars($editRFQ['supplier_designation'] ?? '') ?></span>
                        </div>
                        <div style="margin-bottom: 0.12rem; display: flex; gap: 0.25rem; align-items: center;">
                            <span style="flex: 0 0 30%;">Business Name:</span>
                            <span style="flex: 1; border-bottom: 1px solid #000; display: inline-block; min-width: 90px;"><?= htmlspecialchars($editRFQ['supplier_company'] ?? '') ?></span>
                        </div>
                        <div style="margin-bottom: 0.12rem; display: flex; gap: 0.25rem; align-items: center;">
                            <span style="flex: 0 0 30%;">Contact No.:</span>
                            <span style="flex: 1; border-bottom: 1px solid #000; display: inline-block; min-width: 90px;"><?= htmlspecialchars($editRFQ['supplier_contact_no'] ?? '') ?></span>
                        </div>
                        <div style="margin-bottom: 0.12rem; display: flex; gap: 0.25rem; align-items: center;">
                            <span style="flex: 0 0 30%;">Address:</span>
                            <span style="flex: 1; border-bottom: 1px solid #000; display: inline-block; min-width: 90px;"><?= htmlspecialchars($editRFQ['supplier_address'] ?? '') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
<div class="container management-section">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">RFQ Management</h1>
            <small class="text-muted">Request for Quotation - PEZA Procurement</small>
        </div>
        <div class="btn-group-actions d-flex gap-2">
            <a href="pmis_dashboard.php" class="btn btn-sm btn-outline-secondary">Dashboard</a>
            <a href="market_scoping.php" class="btn btn-sm btn-outline-primary">Market Scoping</a>
            <a href="ppmp.php" class="btn btn-sm btn-outline-info">PPMP</a>
            <a href="index.php" class="btn btn-sm btn-outline-dark">Directory</a>
        </div>
    </div>

    <!-- Error Messages -->
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <?php if ($editRFQ): ?>
        <!-- Edit RFQ Form -->
        <div class="form-section">
            <h2 class="mb-3">REQUEST FOR QUOTATION</h2>
            <form method="post">
                <input type="hidden" name="save_rfq" value="1">
                <input type="hidden" name="rfq_id" value="<?= intval($editRFQ['rfq_id']) ?>">

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">TO</label>
                        <input type="text" name="recipient_name" class="form-control" required value="<?= htmlspecialchars($_POST['recipient_name'] ?? $editRFQ['recipient_name']) ?>" placeholder="________________________">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date</label>
                        <input type="date" name="rfq_date" class="form-control" required value="<?= htmlspecialchars($_POST['rfq_date'] ?? $editRFQ['rfq_date']) ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">BAC Chair</label>
                        <input type="text" name="bac_chair" class="form-control" value="<?= htmlspecialchars($_POST['bac_chair'] ?? ($editRFQ['bac_chair'] ?? '')) ?>" placeholder="DZA ENGR. ALEJO F. MACARAEG">
                    </div>
                    <div class="col-md-6"></div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Submission Deadline</label>
                        <input type="date" name="submission_deadline" class="form-control" required value="<?= htmlspecialchars($_POST['submission_deadline'] ?? $editRFQ['submission_deadline']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Delivery Period</label>
                        <input type="text" name="delivery_period" class="form-control" value="<?= htmlspecialchars($_POST['delivery_period'] ?? ($editRFQ['delivery_period'] ?? '')) ?>" placeholder="_________ calendar days">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Bidder Must Submit The Following Document/s</label>
                    <textarea name="bidder_documents" rows="2" class="form-control"><?= htmlspecialchars($_POST['bidder_documents'] ?? ($editRFQ['bidder_documents'] ?? '')) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Sealed Bid Address</label>
                    <textarea name="sealed_bid_address" rows="2" class="form-control"><?= htmlspecialchars($_POST['sealed_bid_address'] ?? ($editRFQ['sealed_bid_address'] ?? '')) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Winning bidder delivery address</label>
                    <textarea name="delivery_address" rows="2" class="form-control"><?= htmlspecialchars($_POST['delivery_address'] ?? ($editRFQ['delivery_address'] ?? '')) ?></textarea>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="supplier_full_name" class="form-control" value="<?= htmlspecialchars($_POST['supplier_full_name'] ?? ($editRFQ['supplier_full_name'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Designation</label>
                        <input type="text" name="supplier_designation" class="form-control" value="<?= htmlspecialchars($_POST['supplier_designation'] ?? ($editRFQ['supplier_designation'] ?? '')) ?>">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Company</label>
                        <input type="text" name="supplier_company" class="form-control" value="<?= htmlspecialchars($_POST['supplier_company'] ?? ($editRFQ['supplier_company'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact No.</label>
                        <input type="text" name="supplier_contact_no" class="form-control" value="<?= htmlspecialchars($_POST['supplier_contact_no'] ?? ($editRFQ['supplier_contact_no'] ?? '')) ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="supplier_address" rows="2" class="form-control"><?= htmlspecialchars($_POST['supplier_address'] ?? ($editRFQ['supplier_address'] ?? '')) ?></textarea>
                </div>
                <div class="mb-3">
                    <button type="button" class="btn btn-primary" onclick="window.location.href='rfq.php?edit=<?= intval($editRFQ['rfq_id']) ?>&print=1'; return false;">🖨️ Print RFQ</button>
                    <button type="submit" class="btn btn-success">Save RFQ</button>
                    <a href="rfq.php" class="btn btn-secondary">Back to List</a>
                </div>
            </form>

            <!-- Items Section -->
            <div class="mt-4">
                <h3>Items</h3>
                <button type="button" class="btn btn-sm btn-outline-success mb-2" data-bs-toggle="modal" data-bs-target="#addItemModal">+ Add Item</button>
                <div class="mb-2"><strong>PEZA SUPPLY & PROC. MANAGEMENT DIVISION</strong></div>
                <?php if (count($rfqItems) > 0): ?>
                    <div class="table-responsive items-table">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:5%">#</th>
                                    <th style="width:15%">Articles</th>
                                    <th style="width:10%">Qty</th>
                                    <th style="width:10%">Unit</th>
                                    <th style="width:15%">Unit Price</th>
                                    <th style="width:15%">Total Cost</th>
                                    <th style="width:15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rfqItems as $item): ?>
                                    <tr>
                                        <td class="text-center"><?= intval($item['item_number']) ?></td>
                                        <td><?= htmlspecialchars($item['articles']) ?></td>
                                        <td class="text-end"><?= number_format($item['quantity'], 2) ?></td>
                                        <td><?= htmlspecialchars($item['unit']) ?></td>
                                        <td class="text-end">₱ <?= number_format($item['unit_price'], 2) ?></td>
                                        <td class="text-end fw-bold">₱ <?= number_format($item['total_cost'], 2) ?></td>
                                        <td>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this item?');">
                                                <input type="hidden" name="rfq_id" value="<?= intval($editRFQ['rfq_id']) ?>">
                                                <input type="hidden" name="item_id" value="<?= intval($item['item_id']) ?>">
                                                <input type="hidden" name="delete_item" value="1">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-info">
                                    <td colspan="4"></td>
                                    <td class="text-end fw-bold">Grand Total:</td>
                                    <td class="text-end fw-bold">₱ <?= number_format(array_sum(array_map(function($i) { return floatval($i['total_cost']); }, $rfqItems)), 2) ?></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No items added yet.</div>
                <?php endif; ?>
            </div>

            <!-- Delete RFQ -->
            <div class="mt-4">
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this entire RFQ and all items?');">
                    <input type="hidden" name="rfq_id" value="<?= intval($editRFQ['rfq_id']) ?>">
                    <input type="hidden" name="delete_rfq" value="1">
                    <button type="submit" class="btn btn-danger">Delete RFQ</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Create New RFQ -->
        <div class="form-section">
            <h2 class="mb-3">REQUEST FOR QUOTATION</h2>
            <form method="post">
                <input type="hidden" name="save_rfq" value="1">

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">TO</label>
                        <input type="text" name="recipient_name" class="form-control" required placeholder="________________________" value="<?= htmlspecialchars($_POST['recipient_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date</label>
                        <input type="date" name="rfq_date" class="form-control" required value="<?= htmlspecialchars($_POST['rfq_date'] ?? date('Y-m-d')) ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">BAC Chair</label>
                    <input type="text" name="bac_chair" class="form-control" value="<?= htmlspecialchars($_POST['bac_chair'] ?? '') ?>">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Submission Deadline</label>
                        <input type="date" name="submission_deadline" class="form-control" required value="<?= htmlspecialchars($_POST['submission_deadline'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Delivery Period</label>
                        <input type="text" name="delivery_period" class="form-control" placeholder="_________ calendar days" value="<?= htmlspecialchars($_POST['delivery_period'] ?? '') ?>">
                    </div>
                </div>
                <div class="mb-4">
                    <h4 class="mb-3">Item List</h4>
                    <div class="mb-2"><strong>PEZA SUPPLY & PROC. MANAGEMENT DIVISION</strong></div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 8%">ITEM NO.</th>
                                    <th style="width: 12%">QTY.</th>
                                    <th style="width: 12%">UNIT</th>
                                    <th>ARTICLES</th>
                                    <th style="width: 16%">UNIT PRICE</th>
                                    <th style="width: 16%">TOTAL COST</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($row = 0; $row < 8; $row++): ?>
                                    <tr>
                                        <td><input type="number" name="item_number[]" class="form-control form-control-sm" min="1" value="<?= htmlspecialchars($_POST['item_number'][$row] ?? '') ?>"></td>
                                        <td><input type="number" name="quantity[]" class="form-control form-control-sm" step="0.01" min="0" value="<?= htmlspecialchars($_POST['quantity'][$row] ?? '') ?>"></td>
                                        <td><input type="text" name="unit[]" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['unit'][$row] ?? '') ?>"></td>
                                        <td><input type="text" name="articles[]" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['articles'][$row] ?? '') ?>"></td>
                                        <td><input type="number" name="unit_price[]" class="form-control form-control-sm" step="0.01" min="0" value="<?= htmlspecialchars($_POST['unit_price'][$row] ?? '') ?>"></td>
                                        <td><input type="text" class="form-control form-control-sm" disabled></td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Bidder Must Submit The Following Document/s</label>
                    <textarea name="bidder_documents" rows="2" class="form-control"><?= htmlspecialchars($_POST['bidder_documents'] ?? '') ?></textarea>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="supplier_full_name" class="form-control" value="<?= htmlspecialchars($_POST['supplier_full_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Designation</label>
                        <input type="text" name="supplier_designation" class="form-control" value="<?= htmlspecialchars($_POST['supplier_designation'] ?? '') ?>">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Company</label>
                        <input type="text" name="supplier_company" class="form-control" value="<?= htmlspecialchars($_POST['supplier_company'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact No.</label>
                        <input type="text" name="supplier_contact_no" class="form-control" value="<?= htmlspecialchars($_POST['supplier_contact_no'] ?? '') ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="supplier_address" rows="2" class="form-control"><?= htmlspecialchars($_POST['supplier_address'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Create RFQ</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- RFQ List -->
    <div class="mt-4">
        <h2 class="mb-3">All RFQs</h2>
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>RFQ #</th>
                        <th>Recipient</th>
                        <th>RFQ Date</th>
                        <th>Deadline</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rfqs as $rfq): 
                        $itemCount = $pdo->query("SELECT COUNT(*) as cnt FROM rfq_items WHERE rfq_id = " . intval($rfq['rfq_id']))->fetch()['cnt'];
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($rfq['rfq_number']) ?></strong></td>
                            <td><?= htmlspecialchars($rfq['recipient_name']) ?></td>
                            <td><?= htmlspecialchars($rfq['rfq_date']) ?></td>
                            <td><?= htmlspecialchars($rfq['submission_deadline']) ?></td>
                            <td class="text-center"><span class="badge bg-secondary"><?= intval($itemCount) ?></span></td>
                            <td>
                                <span class="badge bg-<?= $rfq['status'] === 'Draft' ? 'warning' : ($rfq['status'] === 'Sent' ? 'info' : ($rfq['status'] === 'Awarded' ? 'success' : 'secondary')) ?>">
                                    <?= htmlspecialchars($rfq['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="rfq.php?edit=<?= intval($rfq['rfq_id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                <button type="button" class="btn btn-sm btn-info" onclick="window.location.href='rfq.php?edit=<?= intval($rfq['rfq_id']) ?>&print=1'; return false;">Print</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (count($rfqs) === 0): ?>
            <div class="alert alert-info">No RFQs found. <a href="rfq.php">Create one</a></div>
        <?php endif; ?>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add RFQ Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="add_item" value="1">
                    <input type="hidden" name="rfq_id" value="<?= intval($editRFQ['rfq_id'] ?? 0) ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Item Number *</label>
                            <input type="number" name="item_number" class="form-control" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Articles *</label>
                            <input type="text" name="articles" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity *</label>
                            <input type="number" name="quantity" class="form-control" step="0.01" min="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Unit *</label>
                            <input type="text" name="unit" class="form-control" placeholder="e.g., PC, Box, Set" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Unit Price</label>
                            <input type="number" name="unit_price" class="form-control" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>