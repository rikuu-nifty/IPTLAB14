<?php
// Load environment variables
require 'vendor/autoload.php';
use Dotenv\Dotenv;

// Initialize environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Stripe API Key
$stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? null;
if (!$stripeSecretKey) {
    die("Stripe secret key not set in the .env file.");
}

// Set the Stripe API key
\Stripe\Stripe::setApiKey($stripeSecretKey);

// Fetch customers and products from Stripe
try {
    $customers = \Stripe\Customer::all(['limit' => 10]); // Fetch 10 customers
    $products = \Stripe\Product::all(['active' => true]); // Fetch active products
    $prices = \Stripe\Price::all(['active' => true]); // Fetch active prices
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'];
    $selected_products = $_POST['products'] ?? [];

    // Create an invoice for the selected customer
    $invoice = \Stripe\Invoice::create([
        'customer' => $customer_id,
    ]);

    // Attach selected products as invoice items
    foreach ($selected_products as $price_id) {
        \Stripe\InvoiceItem::create([
            'customer' => $customer_id,
            'price' => $price_id,
            'invoice' => $invoice->id,
        ]);
    }

    // Finalize the invoice (instance method call)
    $invoice->finalizeInvoice();

    // Retrieve the finalized invoice
    $invoice = \Stripe\Invoice::retrieve($invoice->id);

    // Fetch the PDF URL
    $pdf_url = $invoice->invoice_pdf;

    // Download and save the PDF file with a custom name
    $custom_pdf_name = 'IPT-LAB14.pdf';
    $pdf_content = file_get_contents($pdf_url);
    file_put_contents($custom_pdf_name, $pdf_content);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Invoice</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.min.css">
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
    <div class="container is-fluid">
        <div class="section">
            <div class="columns is-centered">
                <div class="column is-half">
                    <h1 class="title">Generate Invoice</h1>

                    <!-- Invoice Form -->
                    <form method="POST" action="generate-invoice.php">
                        <!-- Customer Selection -->
                        <div class="field">
                            <label class="label">Select Customer</label>
                            <div class="control">
                                <div class="select">
                                    <select name="customer_id" required>
                                        <option value="">Select a customer</option>
                                        <?php foreach ($customers->data as $customer): ?>
                                            <option value="<?= htmlspecialchars($customer->id) ?>">
                                                <?= htmlspecialchars($customer->name ?: $customer->email) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Product Selection -->
                        <div class="field">
                            <label class="label">Select Products</label>
                            <div class="control">
                                <?php foreach ($products->data as $product): ?>
                                    <?php if (isset($price_map[$product->id])): ?>
                                        <label class="checkbox">
                                            <input type="checkbox" name="products[]" value="<?= htmlspecialchars($price_map[$product->id]->id) ?>">
                                            <?= htmlspecialchars($product->name) ?> -
                                            $<?= number_format($price_map[$product->id]->unit_amount / 100, 2) ?>
                                        </label><br>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="field">
                            <div class="control">
                                <button class="button is-primary" type="submit">Generate Invoice</button>
                            </div>
                        </div>
                    </form>

                    <?php if (isset($invoice)): ?>
                        <div class="notification is-primary">
                            <p>Invoice successfully generated!</p>
                            <p><strong>Invoice PDF:</strong> <a href="<?= $invoice->invoice_pdf ?>" target="_blank">Download PDF</a></p>
                            <p><strong>Payment Link:</strong> <a href="<?= $invoice->hosted_invoice_url ?>" target="_blank">Pay Invoice</a></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
