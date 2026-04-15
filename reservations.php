<?php
require 'db.php';

$message = "";

if (isset($_POST['add_reservation'])) {
    $customer_number = mysqli_real_escape_string($conn, $_POST['customer_number']);
    $expected_payment_date = mysqli_real_escape_string($conn, $_POST['expected_payment_date']);
    $actual_payment_date = mysqli_real_escape_string($conn, $_POST['actual_payment_date']);
    $payment_type = mysqli_real_escape_string($conn, $_POST['payment_type']);
    $item_codes = $_POST['item_codes'];
    $quantities = $_POST['quantities'];

    // ── SUBTOTAL ──────────────────────────────────────────────────────────────
    $subtotal = 0;
    $item_rows = [];
    foreach ($item_codes as $i => $code) {
        $code_esc = mysqli_real_escape_string($conn, $code);
        $qty = max(1, (int) $quantities[$i]);
        $res = mysqli_query($conn, "SELECT price FROM items WHERE item_code='$code_esc'");
        $r = mysqli_fetch_assoc($res);
        if ($r) {
            $subtotal += $r['price'] * $qty;
            $item_rows[] = ['code' => $code_esc, 'qty' => $qty];
        }
    }

    // ── DISCOUNT / SURCHARGE ──────────────────────────────────────────────────
    $expected = strtotime($expected_payment_date);
    $actual = strtotime($actual_payment_date);
    $diff_days = ($actual - $expected) / 86400;  // negative = early, positive = late

    $rate = 0;
    if ($payment_type === 'CASH') {
        if ($actual < $expected)
            $rate = -0.10;  // before due    → −10%
        elseif ($actual === $expected)
            $rate = -0.05;  // on due date   → −5%
        elseif ($diff_days <= 5)
            $rate = 0.02;  // 1–5 days late → +2%
        else
            $rate = 0.05;  // >5 days late  → +5%
    } elseif ($payment_type === 'CREDIT') {
        if ($actual < $expected)
            $rate = -0.05;  // before due    → −5%
        elseif ($actual === $expected)
            $rate = -0.02;  // on due date   → −2%
        elseif ($diff_days <= 5)
            $rate = 0.02;  // 1–5 days late → +2%
        else
            $rate = 0.05;  // >5 days late  → +5%
    }

    $total_amount = $subtotal + ($subtotal * $rate);

    // ── SAVE reservations ─────────────────────────────────────────────────────
    $sql = "INSERT INTO reservations
                            (customer_number, expected_payment_date,
               actual_payment_date, payment_type, total_amount)
            VALUES
                            ('$customer_number', '$expected_payment_date',
               '$actual_payment_date', '$payment_type', '$total_amount')";

    if (mysqli_query($conn, $sql)) {

        // FIX: reservation_items.reservation_id FK → reservations.customer_number
        // Pass $customer_number as the reservation_id value
        foreach ($item_rows as $ir) {
            mysqli_query(
                $conn,
                "INSERT INTO reservation_items (reservation_id, item_code, quantity)
                 VALUES ('$customer_number', '$ir[code]', '$ir[qty]')"
            );
        }

        $adj = $subtotal * $rate;
        $adj_str = $adj == 0
            ? "no adjustment"
            : ($adj < 0 ? "discount of Php " : "surcharge of Php ")
            . number_format(abs($adj), 2)
            . " (" . abs($rate * 100) . "%)";
        $message = "Reservation saved! Subtotal: Php " . number_format($subtotal, 2)
            . " | " . $adj_str
            . " | Amount Due: Php " . number_format($total_amount, 2);
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}

$items = mysqli_query($conn, "SELECT * FROM items ORDER BY item_code");
$reservations = mysqli_query($conn, "SELECT * FROM reservations ORDER BY expected_payment_date DESC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reservations</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 14px;
        }

        nav {
            margin-bottom: 16px;
        }

        nav a {
            margin-right: 12px;
            text-decoration: none;
            color: #1a6bbd;
        }

        nav a.active {
            font-weight: bold;
            text-decoration: underline;
        }

        h1 {
            font-size: 18px;
            margin-bottom: 12px;
        }

        h2 {
            font-size: 15px;
            margin: 20px 0 10px;
        }

        .msg {
            background: #dff0d8;
            border: 1px solid #b2dba1;
            padding: 8px 12px;
            margin-bottom: 12px;
        }

        .err {
            background: #fde;
            border: 1px solid #f99;
        }

        input[type=text],
        input[type=number],
        input[type=date],
        select {
            padding: 4px 6px;
            border: 1px solid #ccc;
        }

        button {
            padding: 4px 10px;
            cursor: pointer;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 8px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px 10px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
        }

        .form-row {
            margin-bottom: 8px;
        }

        .form-row label {
            display: inline-block;
            width: 180px;
        }

        .hint {
            font-size: 12px;
            color: #555;
            margin: 8px 0 14px;
        }

        .hint table {
            width: auto;
            font-size: 12px;
        }

        .hint td,
        .hint th {
            padding: 3px 8px;
        }

        .items-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .items-list li {
            white-space: nowrap;
        }
    </style>
</head>

<body>

    <nav>
        <a href="items.php">Items</a>
        <a href="reservations.php" class="active">Reservations</a>
    </nav>

    <h1>Reservations</h1>

    <?php if ($message): ?>
        <div class="msg <?= str_starts_with($message, 'Error') ? 'err' : '' ?>"><?= $message ?></div>
    <?php endif; ?>

    <!-- ══ ADD RESERVATION ══════════════════════════════════════════════════════ -->
    <h2>Add Reservation</h2>
    <form method="POST">
        <div class="form-row">
            <label>Customer Number</label>
            <input type="number" name="customer_number" required>
        </div>
        <strong>Items</strong>
        <table id="items-table" style="width:auto;margin-bottom:8px;">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="items-body">
                <tr>
                    <td>
                        <select name="item_codes[]">
                            <?php while ($row = mysqli_fetch_assoc($items)): ?>
                                <option value="<?= $row['item_code'] ?>">
                                    [
                                    <?= $row['item_code'] ?>]
                                    <?= htmlspecialchars($row['item_description']) ?> — Php
                                    <?= number_format($row['price'], 2) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </td>
                    <td><input type="number" name="quantities[]" value="1" min="1" style="width:60px"></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        <div class="form-row">
            <label>Expected Payment Date</label>
            <input type="date" name="expected_payment_date" required>
        </div>
        <div class="form-row">
            <label>Actual Payment Date</label>
            <input type="date" name="actual_payment_date" required>
        </div>
        <div class="form-row">
            <label>Payment Type</label>
            <select name="payment_type">
                <option value="CASH">CASH</option>
                <option value="CREDIT">CREDIT</option>
            </select>
        </div>

        <div class="hint">
            <strong>Rates:</strong><br>
            <table>
                <tr>
                    <th></th>
                    <th>Before due</th>
                    <th>On due</th>
                    <th>1–5 days late</th>
                    <th>&gt;5 days late</th>
                </tr>
                <tr>
                    <td>CASH</td>
                    <td>−10%</td>
                    <td>−5%</td>
                    <td>+2%</td>
                    <td>+5%</td>
                </tr>
                <tr>
                    <td>CREDIT</td>
                    <td>−5%</td>
                    <td>−2%</td>
                    <td>+2%</td>
                    <td>+5%</td>
                </tr>
            </table>
        </div>
        <button type="button" onclick="addRow()">+ Add Row</button>
        &nbsp;&nbsp;
        <button type="submit" name="add_reservation">Save &amp; Compute</button>
    </form>

    <hr style="margin:20px 0;">

    <!-- ══ ALL RESERVATIONS ═════════════════════════════════════════════════════ -->
    <h2>All Reservations</h2>
    <table>
        <thead>
            <tr>
                <th>Customer #</th>
                <th>Expected Payment</th>
                <th>Actual Payment</th>
                <th>Type</th>
                <th>Items Reserved</th>
                <th>Amount Due</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($reservations)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['customer_number']) ?></td>
                    <td><?= date('F j, Y', strtotime($row['expected_payment_date'])) ?></td>
                    <td><?= $row['actual_payment_date'] ? date('F j, Y', strtotime($row['actual_payment_date'])) : '' ?>
                    </td>
                    <td><?= $row['payment_type'] ?></td>

                    <!-- FIX: JOIN via customer_number since that is the actual FK -->
                    <td>
                        <?php
                        $cnum = (int) $row['customer_number'];
                        $item_res = mysqli_query(
                            $conn,
                            "SELECT ri.quantity, i.item_description, i.price
                           FROM reservation_items ri
                           JOIN items i ON i.item_code = ri.item_code
                          WHERE ri.reservation_id = $cnum"
                        );
                        if ($item_res && mysqli_num_rows($item_res) > 0) {
                            echo '<ul class="items-list">';
                            while ($ir = mysqli_fetch_assoc($item_res)) {
                                $line_total = $ir['price'] * $ir['quantity'];
                                echo '<li>'
                                    . htmlspecialchars($ir['item_description']) . ' &times; ' . $ir['quantity']
                                    . '</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<em>—</em>';
                        }
                        ?>
                    </td>

                    <td>Php <?= number_format($row['total_amount'], 2) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <script>
        var optionsHTML = <?php
        mysqli_data_seek($items, 0);
        $opts = [];
        while ($r = mysqli_fetch_assoc($items)) {
            $opts[] = '<option value="' . $r['item_code'] . '">'
                . '[' . $r['item_code'] . '] '
                . htmlspecialchars($r['item_description'])
                . ' — Php ' . number_format($r['price'], 2)
                . '</option>';
        }
        echo json_encode(implode('', $opts));
        ?>;

        function addRow() {
            var tbody = document.getElementById('items-body');
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td><select name="item_codes[]">' + optionsHTML + '</select></td>' +
                '<td><input type="number" name="quantities[]" value="1" min="1" style="width:60px"></td>' +
                '<td><button type="button" onclick="this.closest(\'tr\').remove()">Remove</button></td>';
            tbody.appendChild(tr);
        }
    </script>

</body>

</html>
<?php mysqli_close($conn); ?>