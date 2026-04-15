# PHP + MySQL Exam Reviewer (mysqli)

Date: April 16, 2026
Target: 3-hour programming skills evaluation

This reviewer is based on your current practice style and focuses on:
- system-generated customer number
- database access and data manipulation
- CRUD + search
- transactions
- mysqli functions you listed

---

## 1) System-Generated Customer Number

### Option A: Prefixed Sequence (recommended)
Example format:
- CUST-20260416-0001
- CUST-20260416-0002

This is easy to read, easy to debug, and good for exam demonstrations.

### SQL Table for Daily Counter

```sql
CREATE TABLE IF NOT EXISTS customer_no_counter (
  counter_date DATE PRIMARY KEY,
  last_no INT NOT NULL
);
```

### PHP Generator (procedural)

```php
function generatePrefixedCustomerNumber(mysqli $conn): string
{
    mysqli_begin_transaction($conn);

    try {
        $today = date('Y-m-d');

        $res = mysqli_query(
            $conn,
            "SELECT last_no FROM customer_no_counter WHERE counter_date = '$today' FOR UPDATE"
        );

        if (mysqli_num_rows($res) === 0) {
            mysqli_query(
                $conn,
                "INSERT INTO customer_no_counter (counter_date, last_no) VALUES ('$today', 1)"
            );
            $seq = 1;
        } else {
            $row = mysqli_fetch_assoc($res);
            $seq = (int)$row['last_no'] + 1;
            mysqli_query(
                $conn,
                "UPDATE customer_no_counter SET last_no = $seq WHERE counter_date = '$today'"
            );
        }

        $customerNo = 'CUST-' . date('Ymd') . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

        mysqli_commit($conn);
        return $customerNo;
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        throw $e;
    }
}
```

Usage:

```php
$customer_number = generatePrefixedCustomerNumber($conn);
```

---

### Option B: Random Unique Number
Example format:
- CUST-A1B2C3D4

Good when you do not want predictable sequence numbers.

Add unique constraint:

```sql
ALTER TABLE reservations
ADD UNIQUE KEY uq_customer_number (customer_number);
```

PHP generator:

```php
function generateRandomCustomerNumber(mysqli $conn): string
{
    for ($i = 0; $i < 20; $i++) {
        $candidate = 'CUST-' . strtoupper(bin2hex(random_bytes(4)));
        $safe = mysqli_real_escape_string($conn, $candidate);

        $res = mysqli_query($conn, "SELECT 1 FROM reservations WHERE customer_number = '$safe' LIMIT 1");
        if (mysqli_num_rows($res) === 0) {
            return $candidate;
        }
    }

    throw new Exception('Unable to generate unique customer number');
}
```

---

## 2) Access and Manipulate Database Data

Basic flow to remember:
1. Connect to DB
2. Validate user input
3. Execute SQL
4. Handle success/failure
5. Fetch output rows for SELECT
6. Close connection

### Procedural CRUD + Search

```php
// CREATE
$stmt = mysqli_prepare($conn, "INSERT INTO items (item_description, price) VALUES (?, ?)");
mysqli_stmt_bind_param($stmt, "sd", $desc, $price);
mysqli_stmt_execute($stmt);

// READ
$res = mysqli_query($conn, "SELECT item_code, item_description, price FROM items ORDER BY item_code");
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['item_code'] . ' - ' . $row['item_description'] . '<br>';
}

// UPDATE
$stmt = mysqli_prepare($conn, "UPDATE items SET item_description = ?, price = ? WHERE item_code = ?");
mysqli_stmt_bind_param($stmt, "sdi", $desc, $price, $itemCode);
mysqli_stmt_execute($stmt);

// DELETE
$stmt = mysqli_prepare($conn, "DELETE FROM items WHERE item_code = ?");
mysqli_stmt_bind_param($stmt, "i", $itemCode);
mysqli_stmt_execute($stmt);

// SEARCH
$kw = "%{$search}%";
$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM items WHERE item_description LIKE ? OR item_code LIKE ? ORDER BY item_code"
);
mysqli_stmt_bind_param($stmt, "ss", $kw, $kw);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
```

### OOP version (quick sample)

```php
$conn = new mysqli("localhost", "root", "PASSWORDPASS", "ROLDAN_PSE");

$stmt = $conn->prepare("INSERT INTO items (item_description, price) VALUES (?, ?)");
$stmt->bind_param("sd", $desc, $price);
$stmt->execute();

$result = $conn->query("SELECT * FROM items ORDER BY item_code");
while ($row = $result->fetch_assoc()) {
    echo $row['item_description'];
}

$conn->close();
```

---

## 3) mysqli Functions (Meaning + Syntax + Example)

## 1. mysqli_connect
Purpose:
- Opens connection to MySQL

Syntax:

```php
$conn = mysqli_connect($host, $username, $password, $database, $port, $socket);
```

Example:

```php
$conn = mysqli_connect("localhost", "root", "PASSWORDPASS", "ROLDAN_PSE");
```

OOP equivalent:

```php
$conn = new mysqli("localhost", "root", "PASSWORDPASS", "ROLDAN_PSE");
```

## 2. mysqli_connect_error
Purpose:
- Shows the last connection error

Syntax:

```php
$error = mysqli_connect_error();
```

Example:

```php
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
```

OOP equivalent:

```php
if ($conn->connect_error) {
    die($conn->connect_error);
}
```

## 3. mysqli_real_escape_string
Purpose:
- Escapes special characters in a string before placing it in SQL text

Syntax:

```php
$safe = mysqli_real_escape_string($conn, $rawString);
```

Example:

```php
$desc = mysqli_real_escape_string($conn, $_POST['item_description']);
```

OOP equivalent:

```php
$desc = $conn->real_escape_string($_POST['item_description']);
```

Note:
- Works, but prepared statements are still safer and preferred.

## 4. mysqli_query
Purpose:
- Executes SQL query

Syntax:

```php
$resultOrBool = mysqli_query($conn, $sql);
```

Example:

```php
$result = mysqli_query($conn, "SELECT * FROM items");
```

OOP equivalent:

```php
$result = $conn->query("SELECT * FROM items");
```

## 5. mysqli_fetch_assoc
Purpose:
- Gets one row as associative array

Syntax:

```php
$row = mysqli_fetch_assoc($result);
```

Example:

```php
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['item_description'];
}
```

OOP equivalent:

```php
while ($row = $result->fetch_assoc()) {
    echo $row['item_description'];
}
```

## 6. mysqli_num_rows
Purpose:
- Returns number of rows in SELECT result

Syntax:

```php
$count = mysqli_num_rows($result);
```

Example:

```php
if (mysqli_num_rows($result) > 0) {
    echo "Found records";
}
```

OOP equivalent:

```php
if ($result->num_rows > 0) {
    echo "Found records";
}
```

## 7. mysqli_data_seek
Purpose:
- Moves internal result pointer to target row index (0-based)

Syntax:

```php
mysqli_data_seek($result, $rowIndex);
```

Example:

```php
mysqli_data_seek($items, 0); // rewind to first row
```

OOP equivalent:

```php
$result->data_seek(0);
```

## 8. mysqli_error
Purpose:
- Gets the last SQL error message for current connection

Syntax:

```php
$error = mysqli_error($conn);
```

Example:

```php
if (!$result) {
    echo "Error: " . mysqli_error($conn);
}
```

OOP equivalent:

```php
echo $conn->error;
```

## 9. mysqli_close
Purpose:
- Closes DB connection

Syntax:

```php
mysqli_close($conn);
```

Example:

```php
mysqli_close($conn);
```

OOP equivalent:

```php
$conn->close();
```

---

## 4) Transactions (Very Important)

Use transaction when multiple queries must all succeed together.

Example: Insert reservation + insert reserved items.

### Procedural template

```php
mysqli_begin_transaction($conn);

try {
    $ok1 = mysqli_query($conn, "INSERT INTO reservations (...) VALUES (...)");
    if (!$ok1) {
        throw new Exception(mysqli_error($conn));
    }

    $ok2 = mysqli_query($conn, "INSERT INTO reservation_items (...) VALUES (...)");
    if (!$ok2) {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_commit($conn);
} catch (Throwable $e) {
    mysqli_rollback($conn);
    echo "Transaction failed: " . $e->getMessage();
}
```

### OOP template

```php
$conn->begin_transaction();

try {
    $conn->query("INSERT INTO reservations (...) VALUES (...)");
    $conn->query("INSERT INTO reservation_items (...) VALUES (...)");
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    echo "Transaction failed: " . $e->getMessage();
}
```

---

## 5) What Else To Know For Basic CRUD + Search + Data Manipulation

1. Prepared statements
- Know mysqli_prepare, mysqli_stmt_bind_param, mysqli_stmt_execute.

2. Input validation
- Required fields, date formats, numeric limits, allowed enum values.

3. Output escaping
- Use htmlspecialchars for data printed in HTML.

4. SQL constraints
- PRIMARY KEY, FOREIGN KEY, UNIQUE, NOT NULL.

5. Search patterns
- LIKE '%keyword%' with safe parameter binding.

6. Joins
- Know INNER JOIN for parent-child tables.

7. Error handling
- Friendly user message + technical log.

8. Transaction control
- begin, commit, rollback, and when to use each.

9. Common test cases
- No results, duplicate keys, invalid input, query failure, empty form data.

---

## 6) Quick Exam Checklist

Before coding:
- Confirm table columns and keys.
- Confirm relationships (foreign keys).

While coding:
- Validate inputs.
- Use prepared statements.
- Handle errors after each query.
- Escape output with htmlspecialchars.

Before submit:
- Test add, list, edit, delete, search.
- Test invalid data and empty data.
- Test transaction rollback scenario.
- Confirm connection is closed.

Good luck on your exam.