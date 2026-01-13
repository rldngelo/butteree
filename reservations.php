<?php
require_once 'config.php';

$conn = getDBConnection();
$message = '';
$view_reservation = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $customer_number = $_POST['customer_number'];
    $reservation_date = $_POST['reservation_date'];
    $expected_payment_date = $_POST['expected_payment_date'];
    $payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : null;
    $payment_mode = $_POST['payment_mode'];
    $item_ids = $_POST['item_ids'] ?? [];
    $quantities = $_POST['quantities'] ?? [];

    // Calculate subtotal
    $subtotal = 0;
    $items_data = [];

    foreach ($item_ids as $index => $item_id) {
        if (!empty($item_id) && !empty($quantities[$index]) && $quantities[$index] > 0) {
            $sql = "SELECT price FROM items WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $item = $result->fetch_assoc();

            if ($item) {
                $item_total = $item['price'] * $quantities[$index];
                $subtotal += $item_total;
                $items_data[] = [
                    'id' => $item_id,
                    'quantity' => $quantities[$index],
                    'price' => $item['price']
                ];
            }
            $stmt->close();
        }
    }

    // Calculate discount/surcharge based on payment timing and mode
    $discount_rate = 0;
    $surcharge_rate = 0;

    $res_date = new DateTime($reservation_date);
    $exp_date = new DateTime($expected_payment_date);
    $pay_date = $payment_date ? new DateTime($payment_date) : null;

    if ($payment_mode === 'CASH') {
        if ($pay_date && $pay_date < $exp_date) {
            // Before expected date: 10% discount
            $discount_rate = 10;
        } elseif ($pay_date && $pay_date == $exp_date) {
            // On expected date: 5% discount
            $discount_rate = 5;
        } elseif ($pay_date && $pay_date > $exp_date) {
            $days_late = $pay_date->diff($exp_date)->days;
            if ($days_late <= 5) {
                // Within 5 days after: 2% surcharge
                $surcharge_rate = 2;
            } else {
                // Beyond 5 days: no discount
                $discount_rate = 0;
            }
        }
    } elseif ($payment_mode === 'CREDIT') {
        if ($pay_date && $pay_date < $exp_date) {
            // Before expected date: 5% discount
            $discount_rate = 5;
        } elseif ($pay_date && $pay_date == $exp_date) {
            // On expected date: 2% discount
            $discount_rate = 2;
        } elseif ($pay_date && $pay_date > $exp_date) {
            $days_late = $pay_date->diff($exp_date)->days;
            if ($days_late <= 5) {
                // Within 5 days after: 2% surcharge
                $surcharge_rate = 2;
            } else {
                // Beyond 5 days: 5% surcharge
                $surcharge_rate = 5;
            }
        }
    }

    // Calculate total amount
    $discount_amount = $subtotal * ($discount_rate / 100);
    $surcharge_amount = $subtotal * ($surcharge_rate / 100);
    $total_amount = $subtotal - $discount_amount + $surcharge_amount;

    // Insert reservation
    $sql = "INSERT INTO reservations (customer_number, reservation_date, expected_payment_date, payment_date, payment_mode, subtotal, discount_rate, surcharge_rate, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssdddd", $customer_number, $reservation_date, $expected_payment_date, $payment_date, $payment_mode, $subtotal, $discount_rate, $surcharge_rate, $total_amount);

    if ($stmt->execute()) {
        $reservation_id = $stmt->insert_id;

        // Insert reservation items
        foreach ($items_data as $item_data) {
            $sql = "INSERT INTO reservation_items (reservation_id, item_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt2 = $conn->prepare($sql);
            $stmt2->bind_param("iiid", $reservation_id, $item_data['id'], $item_data['quantity'], $item_data['price']);
            $stmt2->execute();
            $stmt2->close();
        }

        $message = "<div class='alert success'>Reservation created successfully! Total Amount: ₱" . number_format($total_amount, 2) . "</div>";
    } else {
        $message = "<div class='alert error'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// Handle view request
if (isset($_GET['view'])) {
    $id = $_GET['view'];
    $sql = "SELECT * FROM reservations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $view_reservation = $result->fetch_assoc();

    // Get reservation items
    $sql = "SELECT ri.*, i.item_code, i.item_description 
            FROM reservation_items ri 
            JOIN items i ON ri.item_id = i.id 
            WHERE ri.reservation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $view_reservation['items'] = $stmt->get_result();
    $stmt->close();
}

// Get all items for the dropdown
$items_result = $conn->query("SELECT * FROM items ORDER BY item_code");

// Get all reservations
$reservations = $conn->query("SELECT * FROM reservations ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations and Billing</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f4f4f4;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .nav {
            margin-bottom: 20px;
        }

        .nav a {
            text-decoration: none;
            color: #007bff;
            margin-right: 15px;
        }

        .nav a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        input[type="text"],
        input[type="date"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        input[type="number"] {
            width: 100px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 30px;
        }

        .item-row {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 10px;
            margin-bottom: 10px;
            align-items: end;
        }

        .items-container {
            margin-top: 10px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal.active {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .close-btn {
            float: right;
            cursor: pointer;
            font-size: 24px;
        }

        .info-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: bold;
            color: #555;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="nav">
            <a href="index.php">← Back to Home</a>
            <a href="items.php">Items Management</a>
        </div>

        <h1>Reservations and Billing System</h1>

        <?php echo $message; ?>

        <div class="form-section">
            <h2>Create New Reservation</h2>
            <form method="POST" id="reservationForm">
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label>Customer Number:</label>
                    <input type="text" name="customer_number" required>
                </div>

                <div class="form-group">
                    <label>Reservation Date:</label>
                    <input type="date" name="reservation_date" required>
                </div>

                <div class="form-group">
                    <label>Expected Payment Date:</label>
                    <input type="date" name="expected_payment_date" required>
                </div>

                <div class="form-group">
                    <label>Payment Date (leave empty if not paid yet):</label>
                    <input type="date" name="payment_date">
                </div>

                <div class="form-group">
                    <label>Payment Mode:</label>
                    <select name="payment_mode" required>
                        <option value="">Select payment mode</option>
                        <option value="CASH">CASH</option>
                        <option value="CREDIT">CREDIT</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Items:</label>
                    <div class="items-container" id="itemsContainer">
                        <div class="item-row">
                            <select name="item_ids[]" required>
                                <option value="">Select item</option>
                                <?php
                                $items_result->data_seek(0);
                                while ($item = $items_result->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $item['id']; ?>">
                                        <?php echo htmlspecialchars($item['item_code'] . ' - ' . $item['item_description'] . ' (₱' . number_format($item['price'], 2) . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <input type="number" name="quantities[]" placeholder="Quantity" min="1" required>
                            <button type="button" class="btn-danger" onclick="removeItem(this)">Remove</button>
                        </div>
                    </div>
                    <button type="button" class="btn-secondary" onclick="addItem()">Add Another Item</button>
                </div>

                <button type="submit" class="btn-primary">Create Reservation</button>
            </form>
        </div>

        <h2>All Reservations</h2>
        <table>
            <thead>
                <tr>
                    <th>Customer Number</th>
                    <th>Reservation Date</th>
                    <th>Expected Payment</th>
                    <th>Payment Date</th>
                    <th>Mode</th>
                    <th>Total Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $reservations->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['customer_number']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['reservation_date'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['expected_payment_date'])); ?></td>
                        <td><?php echo $row['payment_date'] ? date('M d, Y', strtotime($row['payment_date'])) : 'Not paid'; ?>
                        </td>
                        <td><?php echo $row['payment_mode']; ?></td>
                        <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                        <td>
                            <a href="reservations.php?view=<?php echo $row['id']; ?>">
                                <button class="btn-success">View Details</button>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php if ($view_reservation): ?>
        <div class="modal active">
            <div class="modal-content">
                <span class="close-btn" onclick="window.location.href='reservations.php'">&times;</span>
                <h2>Reservation Details</h2>

                <div class="info-row">
                    <div class="info-label">Customer Number:</div>
                    <div><?php echo htmlspecialchars($view_reservation['customer_number']); ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">Reservation Date:</div>
                    <div><?php echo date('M d, Y', strtotime($view_reservation['reservation_date'])); ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">Expected Payment Date:</div>
                    <div><?php echo date('M d, Y', strtotime($view_reservation['expected_payment_date'])); ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">Payment Date:</div>
                    <div>
                        <?php echo $view_reservation['payment_date'] ? date('M d, Y', strtotime($view_reservation['payment_date'])) : 'Not paid yet'; ?>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Payment Mode:</div>
                    <div><?php echo $view_reservation['payment_mode']; ?></div>
                </div>

                <h3 style="margin-top: 20px;">Items Ordered:</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Item Code</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $view_reservation['items']->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                                <td><?php echo htmlspecialchars($item['item_description']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <h3 style="margin-top: 20px;">Billing Summary:</h3>
                <div class="info-row">
                    <div class="info-label">Subtotal:</div>
                    <div>₱<?php echo number_format($view_reservation['subtotal'], 2); ?></div>
                </div>

                <?php if ($view_reservation['discount_rate'] > 0): ?>
                    <div class="info-row">
                        <div class="info-label">Discount (<?php echo $view_reservation['discount_rate']; ?>%):</div>
                        <div>
                            -₱<?php echo number_format($view_reservation['subtotal'] * ($view_reservation['discount_rate'] / 100), 2); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($view_reservation['surcharge_rate'] > 0): ?>
                    <div class="info-row">
                        <div class="info-label">Surcharge (<?php echo $view_reservation['surcharge_rate']; ?>%):</div>
                        <div>
                            +₱<?php echo number_format($view_reservation['subtotal'] * ($view_reservation['surcharge_rate'] / 100), 2); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="info-row"
                    style="font-size: 18px; font-weight: bold; margin-top: 10px; padding-top: 10px; border-top: 2px solid #333;">
                    <div class="info-label">Total Amount Due:</div>
                    <div>₱<?php echo number_format($view_reservation['total_amount'], 2); ?></div>
                </div>

                <button class="btn-secondary" style="margin-top: 20px;"
                    onclick="window.location.href='reservations.php'">Close</button>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function addItem() {
            const container = document.getElementById('itemsContainer');
            const newRow = container.querySelector('.item-row').cloneNode(true);
            newRow.querySelectorAll('input').forEach(input => input.value = '');
            newRow.querySelector('select').selectedIndex = 0;
            container.appendChild(newRow);
        }

        function removeItem(button) {
            const container = document.getElementById('itemsContainer');
            if (container.querySelectorAll('.item-row').length > 1) {
                button.closest('.item-row').remove();
            } else {
                alert('At least one item is required!');
            }
        }
    </script>
</body>

</html>

<?php
$conn->close();
?>