<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation and Billing System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            width: 100%;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
            font-size: 32px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 40px;
            font-size: 14px;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: white;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .card h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .card p {
            font-size: 14px;
            opacity: 0.9;
        }

        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-box h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .info-box ul {
            list-style: none;
            color: #555;
            line-height: 1.8;
        }

        .info-box ul li:before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
            margin-right: 8px;
        }

        .init-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background 0.3s;
        }

        .init-btn:hover {
            background: #218838;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🎉 Reservation and Billing System</h1>
        <p class="subtitle">Complete Event Management Solution</p>

        <div class="info-box">
            <h3>System Features:</h3>
            <ul>
                <li>Item Management (Add, Edit, Delete, Search)</li>
                <li>Reservation Creation with Multiple Items</li>
                <li>Automatic Discount/Surcharge Calculation</li>
                <li>Payment Tracking (Cash/Credit)</li>
                <li>Detailed Billing Reports</li>
            </ul>
        </div>

        <div class="card-grid">
            <a href="items.php" class="card">
                <h2>📦 Items</h2>
                <p>Manage event items, packages, and pricing</p>
            </a>

            <a href="reservations.php" class="card">
                <h2>📅 Reservations</h2>
                <p>Create reservations and calculate billing</p>
            </a>
        </div>

        <a href="initialize.php" class="init-btn">
            🔧 Initialize Database (Run this first!)
        </a>
    </div>
</body>

</html>