<?php
require "init.php";

$products = $stripe->products->all();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Products</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.min.css">
    <style>
        .card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }
        .card-image img {
            object-fit: cover;
            width: 100%;
            height: auto;
        }
        .columns {
            margin-top: 1rem;
        }
        footer {
            background-color: #f5f5f5;
            padding: 2rem 1rem;
            margin-top: 2rem;
        }
        .footer-content {
            text-align: center;
        }
    </style>
</head>
<body>
    <section class="section">
        <div class="container">
            <h1 class="title">Product List</h1>
            <div class="columns is-multiline">
                <?php foreach ($products as $product): ?>
                    <div class="column is-one-third">
                        <div class="card">
                            <div class="card-image">
                                <?php 
                                $image = array_pop($product->images); 
                                if ($image): ?>
                                    <figure class="image is-4by3">
                                        <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($product->name) ?>">
                                    </figure>
                                <?php else: ?>
                                    <figure class="image is-4by3">
                                        <img src="https://via.placeholder.com/300x225?text=No+Image" alt="No image available">
                                    </figure>
                                <?php endif; ?>
                            </div>
                            <div class="card-content">
                                <div class="media">
                                    <div class="media-content">
                                        <p class="title is-4"><?= htmlspecialchars($product->name) ?></p>
                                    </div>
                                </div>
                                <div class="content">
                                    <p><strong>Price:</strong> 
                                        <?php
                                        if (!empty($product->default_price)):
                                            try {
                                                $price = $stripe->prices->retrieve($product->default_price);
                                                echo htmlspecialchars(strtoupper($price->currency)) . ' ' . number_format($price->unit_amount / 100, 2);
                                            } catch (Exception $e) {
                                                echo 'Error retrieving price';
                                            }
                                        else:
                                            echo 'No default price available';
                                        endif;
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <p><strong>Stripe Product Showcase</strong> by <a href="#">Jansen Venal</a>. Powered by <a href="https://stripe.com/" target="_blank">Stripe API</a>.</p>
                <p>&copy; <?= date("Y") ?> All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
