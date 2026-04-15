<?php
require 'db.php';

$message = "";

if (isset($_POST['add_item'])) {
    $desc = mysqli_real_escape_string($conn, $_POST['item_description']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $result = mysqli_query($conn, "INSERT INTO items (item_description, price) VALUES ('$desc', '$price')");
    $message = $result ? "Item added." : "Error: " . mysqli_error($conn);
}

if (isset($_POST['edit_item'])) {
    $code = mysqli_real_escape_string($conn, $_POST['item_code']);
    $desc = mysqli_real_escape_string($conn, $_POST['item_description']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $result = mysqli_query($conn, "UPDATE items SET item_description='$desc', price='$price' WHERE item_code='$code'");
    $message = $result ? "Item updated." : "Error: " . mysqli_error($conn);
}

if (isset($_POST['delete_item'])) {
    $code = mysqli_real_escape_string($conn, $_POST['item_code']);
    $result = mysqli_query($conn, "DELETE FROM items WHERE item_code='$code'");
    $message = $result ? "Item deleted." : "Error: " . mysqli_error($conn);
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : "";
$where = $search ? "WHERE item_description LIKE '%$search%' OR item_code LIKE '%$search%'" : "";
$items = mysqli_query($conn, "SELECT * FROM items $where ORDER BY item_code");
$edit_code = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Items</title>
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

        form.inline {
            display: inline;
        }

        input[type=text],
        input[type=number] {
            padding: 4px 6px;
            border: 1px solid #ccc;
        }

        button,
        a.btn {
            padding: 4px 10px;
            cursor: pointer;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 12px;
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

        tr.editing {
            background: #fffbe6;
        }
    </style>
</head>

<body>

    <nav>
        <a href="items.php" class="active">Items</a>
        <a href="reservations.php">Reservations</a>
    </nav>

    <h1>Items</h1>

    <?php if ($message): ?>
        <div class="msg <?= str_starts_with($message, 'Error') ? 'err' : '' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- ADD -->
    <form method="POST">
        Description: <input type="text" name="item_description" required>
        &nbsp; Price: <input type="number" name="price" step="0.01" min="0" required>
        &nbsp; <button type="submit" name="add_item">Add Item</button>
    </form>

    <hr style="margin:14px 0;">

    <!-- SEARCH -->
    <form method="GET">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
            placeholder="Search code or description...">
        <button type="submit">Search</button>
        <?php if ($search): ?> <a href="items.php">Clear</a> <?php endif; ?>
    </form>

    <!-- TABLE -->
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Description</th>
                <th>Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($items)): ?>
                <?php if ($edit_code === (int) $row['item_code']): ?>
                    <tr class="editing">
                        <td><?= $row['item_code'] ?></td>
                        <td colspan="2">
                            <form method="POST">
                                <input type="hidden" name="item_code" value="<?= $row['item_code'] ?>">
                                <input type="text" name="item_description"
                                    value="<?= htmlspecialchars($row['item_description']) ?>" required>
                                &nbsp;
                                <input type="number" name="price" step="0.01" value="<?= $row['price'] ?>" required>
                                &nbsp;
                                <button type="submit" name="edit_item">Save</button>
                                <a href="items.php<?= $search ? '?search=' . urlencode($search) : '' ?>">Cancel</a>
                            </form>
                        </td>
                        <td></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td><?= $row['item_code'] ?></td>
                        <td><?= htmlspecialchars($row['item_description']) ?></td>
                        <td>Php <?= number_format($row['price'], 2) ?></td>
                        <td>
                            <a
                                href="items.php?edit=<?= $row['item_code'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>">Edit</a>
                            &nbsp;
                            <form class="inline" method="POST">
                                <input type="hidden" name="item_code" value="<?= $row['item_code'] ?>">
                                <button type="submit" name="delete_item"
                                    onclick="return confirm('Delete this item?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endwhile; ?>
        </tbody>
    </table>

</body>

</html>
<?php mysqli_close($conn); ?>