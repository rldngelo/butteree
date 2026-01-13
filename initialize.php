<?php
// Initialize database and tables
$conn = new mysqli('localhost', 'root', 'PASSWORDPASS');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS reservation_system";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

$conn->select_db('reservation_system');

// Create items table
$sql = "CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(50) UNIQUE NOT NULL,
    item_description VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,   
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Items table created successfully<br>";
} else {
    echo "Error creating items table: " . $conn->error . "<br>";
}

// Create reservations table
$sql = "CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_number VARCHAR(50) NOT NULL,
    reservation_date DATE NOT NULL,
    expected_payment_date DATE NOT NULL,
    payment_date DATE,
    payment_mode ENUM('CASH', 'CREDIT') NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    discount_rate DECIMAL(5,2) DEFAULT 0,
    surcharge_rate DECIMAL(5,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Reservations table created successfully<br>";
} else {
    echo "Error creating reservations table: " . $conn->error . "<br>";
}

// Create reservation_items table
$sql = "CREATE TABLE IF NOT EXISTS reservation_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Reservation items table created successfully<br>";
} else {
    echo "Error creating reservation_items table: " . $conn->error . "<br>";
}

// Insert sample items from the exam
$items = [
    ['1', 'Dining Tables (10pax)', 35000.00],
    ['2', 'Conference Room (50pax)', 50000.00],
    ['3', 'Premium Dinner Package (100pax)', 30000.00],
    ['4', 'Premium Dinner Package (50pax)', 20000.00],
    ['5', 'Premium Dinner Package (10pax)', 15000.00],
    ['6', 'Floral Arrangement', 100000.00],
    ['7', 'Wine and Liquor Package (50pax)', 15000.00]
];

foreach ($items as $item) {
    $sql = "INSERT IGNORE INTO items (item_code, item_description, price) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssd", $item[0], $item[1], $item[2]);

    if ($stmt->execute()) {
        echo "Inserted item: {$item[1]}<br>";
    }
    $stmt->close();
}

echo "<br><a href='index.php'>Go to Main Page</a>";

$conn->close();
?>