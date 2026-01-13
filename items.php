<?php
require_once 'config.php';

$conn = getDBConnection();
$message = '';
$edit_item = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $code = $_POST['item_code'];
                $description = $_POST['item_description'];
                $price = $_POST['price'];

                $sql = "INSERT INTO items (item_code, item_description, price) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssd", $code, $description, $price);

                if ($stmt->execute()) {
                    $message = "<div class='alert success'>Item added successfully!</div>";
                } else {
                    $message = "<div class='alert error'>Error: " . $stmt->error . "</div>";
                }
                $stmt->close();
                break;

            case 'edit':
                $id = $_POST['id'];
                $code = $_POST['item_code'];
                $description = $_POST['item_description'];
                $price = $_POST['price'];

                $sql = "UPDATE items SET item_code = ?, item_description = ?, price = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssdi", $code, $description, $price, $id);

                if ($stmt->execute()) {
                    $message = "<div class='alert success'>Item updated successfully!</div>";
                } else {
                    $message = "<div class='alert error'>Error: " . $stmt->error . "</div>";
                }
                $stmt->close();
                break;

            case 'delete':
                $id = $_POST['id'];
                $sql = "DELETE FROM items WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    $message = "<div class='alert success'>Item deleted successfully!</div>";
                } else {
                    $message = "<div class='alert error'>Error: " . $stmt->error . "</div>";
                }
                $stmt->close();
                break;
        }
    }
}

// Handle edit request
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $sql = "SELECT * FROM items WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_item = $result->fetch_assoc();
    $stmt->close();
}

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT * FROM items WHERE item_code LIKE ? OR item_description LIKE ? ORDER BY item_code";
$stmt = $conn->prepare($sql);
$search_param = "%$search%";
$stmt->bind_param("ss", $search_param, $search_param);
$stmt->execute();
$items = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Items Management</title>
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

        h1 {
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
        input[type="number"] {
            width: 100%;
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

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-warning {
            background: #ffc107;
            color: #000;
        }

        .btn-warning:hover {
            background: #e0a800;
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

        .actions {
            display: flex;
            gap: 5px;
        }

        .search-box {
            margin-bottom: 20px;
        }

        .search-box input {
            display: inline-block;
            width: 300px;
            margin-right: 10px;
        }

        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 30px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="nav">
            <a href="index.php">← Back to Home</a>
            <a href="reservations.php">Reservations</a>
        </div>

        <h1>Items Management</h1>

        <?php echo $message; ?>

        <div class="form-section">
            <h2><?php echo $edit_item ? 'Edit Item' : 'Add New Item'; ?></h2>
            <form method="POST">
                <?php if ($edit_item): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_item['id']; ?>">
                    <input type="hidden" name="action" value="edit">
                <?php else: ?>
                    <input type="hidden" name="action" value="add">
                <?php endif; ?>

                <div class="form-group">
                    <label>Item Code:</label>
                    <input type="text" name="item_code" value="<?php echo $edit_item['item_code'] ?? ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>Item Description:</label>
                    <input type="text" name="item_description"
                        value="<?php echo $edit_item['item_description'] ?? ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>Price:</label>
                    <input type="number" step="0.01" name="price" value="<?php echo $edit_item['price'] ?? ''; ?>"
                        required>
                </div>

                <button type="submit" class="btn-primary">
                    <?php echo $edit_item ? 'Update Item' : 'Add Item'; ?>
                </button>

                <?php if ($edit_item): ?>
                    <a href="items.php"><button type="button" class="btn-secondary">Cancel</button></a>
                <?php endif; ?>
            </form>
        </div>

        <div class="search-box">
            <form method="GET">
                <input type="text" name="search" placeholder="Search by code or description..."
                    value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-primary">Search</button>
                <?php if ($search): ?>
                    <a href="items.php"><button type="button" class="btn-secondary">Clear</button></a>
                <?php endif; ?>
            </form>
        </div>

        <h2>Items List</h2>
        <table>
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $items->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['item_code']); ?></td>
                        <td><?php echo htmlspecialchars($row['item_description']); ?></td>
                        <td>₱<?php echo number_format($row['price'], 2); ?></td>
                        <td class="actions">
                            <a href="items.php?edit=<?php echo $row['id']; ?>">
                                <button class="btn-warning">Edit</button>
                            </a>
                            <form method="POST" style="display:inline;"
                                onsubmit="return confirm('Are you sure you want to delete this item?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>

</html>

<?php
$conn->close();
?>