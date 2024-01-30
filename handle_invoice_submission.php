<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "medicine";
$port = 3309;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents('php://input'), true);

    $pharmacyName = $data['pharmacyName'] ?? '';
    $pharmacyAddress = $data['pharmacyAddress'] ?? '';
    $items = $data['items'] ?? [];
    $netTotal = 0;
    $totalItems = count($items);
    foreach ($items as $item) {
        $netTotal += $item['totalAmount'] ?? 0;
    }
    $netTotalInWords = numberToWords($netTotal);

    $conn->begin_transaction();
    try {

        $lastInvoiceId = null;


        foreach ($items as $item) {
           
            if (null === $lastInvoiceId) {
                $lastInvoiceId = $conn->insert_id;
            }
            $description = $item['description'] ?? '';
            $qty = $item['qty'] ?? 0;
            $bon = $item['bon'] ?? 0;
            $totalQty = $qty + $bon;  
            
            
           // Check stock availability
           $stockCheckStmt = $conn->prepare("SELECT Qty FROM stock WHERE Description = ?");
           $stockCheckStmt->bind_param("s", $description);
           $stockCheckStmt->execute();
           $stockResult = $stockCheckStmt->get_result();

           if ($stockRow = $stockResult->fetch_assoc()) {
               if ($stockRow['Qty'] < $totalQty) {
                   // Insufficient stock, rollback and exit
                   $conn->rollback();
                   echo json_encode(['error' => "Insufficient stock for item: $description"]);
                   exit;
               }
           } else {
               // No stock record found, rollback and exit
               $conn->rollback();
               echo json_encode(['error' => "No stock record found for item: $description"]);
               exit;
           }
           $stockCheckStmt->close();
            
            // Total quantity including bonus
            $packing = $item['packing'] ?? '';
            $batchNo = $item['batchNo'] ?? '';
            $retailPrice = $item['retailPrice'] ?? 0.0;
            $priceAfterDisc = $item['priceAfterDisc'] ?? 0.0;
            $addDiscPercent = $item['addDiscPercent'] ?? 0.0;
            $addDiscValue = $item['addDiscValue'] ?? 0.0;
            $totalAmount = $item['totalAmount'] ?? 0.0;

            // Inserting data into Invoices table
            $stmt = $conn->prepare("INSERT INTO Invoices (pharmacy_name, pharmacy_address, item_description, qty, bon, packing, batch_no, retail_price, price_after_disc, add_disc_percent, add_disc_value, total_amount, net_total, total_items, net_total_words, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
            $stmt->bind_param("sssiissdddddids", $pharmacyName, $pharmacyAddress, $description, $qty, $bon, $packing, $batchNo, $retailPrice, $priceAfterDisc, $addDiscPercent, $addDiscValue, $totalAmount, $netTotal, $totalItems, $netTotalInWords);
            $stmt->execute();
            $stmt->close();

            // Updating stock
            $stmt = $conn->prepare("SELECT Qty FROM stock WHERE description = ?");
            $stmt->bind_param("s", $description);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $currentStock = $row['Qty'];
                $newStock = $currentStock - $totalQty;  // Subtracting the total quantity (qty + bonus)

                $updateStmt = $conn->prepare("UPDATE stock SET Qty = ? WHERE description = ?");
                $updateStmt->bind_param("is", $newStock, $description);
                $updateStmt->execute();
                $updateStmt->close();
            } else {
                error_log("No stock found for item: $description");
            }
            $stmt->close();
        }

        $conn->commit();
        echo json_encode(['message' => 'Invoice processed successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error processing invoice: " . $e->getMessage());
        echo json_encode(['error' => 'Error processing invoice: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request method.']);
}

$conn->close();

function numberToWords($num) {
    $ones = array(
        0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine'
    );
    $teens = array(
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 
        14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 
        17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'
    );
    $tens = array(
        20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 
        60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
    );

    if ($num < 10) {
        return $ones[$num];
    } elseif ($num < 20) {
        return $teens[$num];
    } elseif ($num < 100) {
        return $tens[intval($num / 10) * 10] . (($num % 10 !== 0) ? ' ' . $ones[$num % 10] : '');
    } elseif ($num < 1000) {
        return $ones[intval($num / 100)] . ' Hundred' . (($num % 100 !== 0) ? ' ' . numberToWords($num % 100) : '');
    } elseif ($num < 1000000) {
        return numberToWords(intval($num / 1000)) . ' Thousand' . (($num % 1000 !== 0) ? ' ' . numberToWords($num % 1000) : '');
    } else {
        return 'Number out of range';
    }
}


?>
