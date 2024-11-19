<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require "init.php";

    // Get the form data
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? ''; // Default to an empty string if not set

    // Check if address is not empty
    if (empty($address)) {
        echo "Empty address!";
        exit;  // Exit if address is missing
    }

    // Send the data to Stripe API
    try {
        $customer = $stripe->customers->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => [
                'line1' => $address,
                'city' => 'Angeles City',
                'country' => 'Philippines',
                'postal_code' => '2019', // Replace with dynamic postal code if needed
            ]
        ]);

        // Capture customer ID from the Stripe response
        $customerId = $customer->id;

    } catch (\Stripe\Exception\ApiErrorException $e) {
        // Handle error here (for example, log it or show an error message)
        echo "Error: " . $e->getMessage();
        exit;
    }
} 
?>


<?php
require 'vendor/autoload.php';
use Dotenv\Dotenv;


// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();


// Stripe API Key
$stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? null;
if (!$stripeSecretKey) {
    die("Stripe secret key not set in the .env file.");
}


\Stripe\Stripe::setApiKey($stripeSecretKey);


// Fetch customers and products
try {
    $customers = \Stripe\Customer::all(['limit' => 10]); // Fetch 10 customers
    $products = \Stripe\Product::all(['active' => true]); // Fetch active products
    $prices = \Stripe\Price::all(['active' => true]);    // Fetch active prices
} catch (\Stripe\Exception\ApiErrorException $e) {
    die("Error fetching data: " . $e->getMessage());
}


// Map product prices by product ID, only including 'one_time' prices
$price_map = [];
foreach ($prices->data as $price) {
    if (isset($price->product) && $price->type === 'one_time') {
        $price_map[$price->product] = $price;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.min.css">
    <style>
        /* Custom Styling */
        .form-container {
            background-color: #000000;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .title {
            font-size: 2rem;
            margin-bottom: 20px;
            text-align: center;
        }
        .button.is-primary {
            background-color: #3273dc;
            border-color: #3273dc;
            width: 100%;
            margin-top: 10px;
        }
        .field label {
            font-weight: bold;
        }
        .input, .button {
            border-radius: 5px;
        }
        .notification {
            margin-top: 20px;
            background-color: #48c78e;
            color: white;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container is-fluid">
        <div class="section">
            <div class="columns is-centered">
                <div class="column is-half form-container">
                    <h1 class="title">Customer Registration</h1>

                    <!-- Registration Form -->
                    <form action="create_customer.php" method="POST">
                        <!-- Full Name -->
                        <div class="field">
                            <label class="label">Full Name</label>
                            <div class="control">
                                <input class="input" type="text" name="name" required placeholder="Enter your full name">
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="field">
                            <label class="label">Email</label>
                            <div class="control">
                                <input class="input" type="email" name="email" required placeholder="Enter your email">
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="field">
                            <label class="label">Phone Number</label>
                            <div class="control">
                                <input class="input" type="text" name="phone" required placeholder="Enter your phone number">
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="field">
                            <label class="label">Address</label>
                            <div class="control">
                                <input class="input" type="text" name="address" required placeholder="Enter your address">
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="field">
                            <div class="control">
                                <button class="button is-primary" type="submit">Register</button>
                            </div>
                        </div>
                    </form>

                    <?php if (isset($customerId)): ?>
                        <div class="notification">
                            <p>Customer ID: <?php echo $customerId; ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        form {
            max-width: 600px;
            margin: auto;
        }
        select, input, button {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            background-color: #27ae60;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #219150;
        }
    </style>
</head>
<body>
    <h1>Create Invoice</h1>
    <form action="generate-invoice.php" method="POST">
        <label for="customer">Select Customer</label>
        <select id="customer" name="customer_id" required>
            <option value="">-- Select Customer --</option>
            <?php foreach ($customers->data as $customer): ?>
                <option value="<?= htmlspecialchars($customer->id) ?>">
                    <?= htmlspecialchars($customer->name ?: $customer->email) ?>
                </option>
            <?php endforeach; ?>
        </select>


        <h2>Products</h2>
        <?php foreach ($products->data as $product): ?>
            <?php if (isset($price_map[$product->id])): ?>
                <label>
                    <input type="checkbox" name="products[]" value="<?= htmlspecialchars($price_map[$product->id]->id) ?>">
                    <?= htmlspecialchars($product->name) ?> -
                    $<?= number_format($price_map[$product->id]->unit_amount / 100, 2) ?>
                </label>
            <?php endif; ?>
        <?php endforeach; ?>


        <button type="submit">Generate Invoice</button>
    </form>
</body>
</html>
