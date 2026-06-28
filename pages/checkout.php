<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Cairo Store</title>
    <meta name="description" content="Complete your purchase at Cairo Store. Fast and secure checkout.">
    <meta name="robots" content="noindex, nofollow">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dark-theme.css" id="theme-style">
</head>
<body class="page-transitioning">

<a href="#main-content" class="skip-nav">Skip to main content</a>

<?php include "../components/navbar.php"; ?>

<main id="main-content" role="main">
<section class="container py-5">
    <h1 class="text-center mb-5">Checkout</h1>
    <div class="row">
        <div class="col-lg-7">
            <div class="card p-4 shadow-sm">
                <h4 class="mb-4">Customer Information</h4>
                <form id="checkoutForm" novalidate>
                    <div class="float-group">
                        <input type="text" id="name" placeholder=" " required autocomplete="name">
                        <label for="name">Full Name</label>
                    </div>
                    <div class="float-group">
                        <input type="email" id="email" placeholder=" " required autocomplete="email">
                        <label for="email">Email Address</label>
                    </div>
                    <div class="float-group">
                        <input type="tel" id="phone" placeholder=" " required autocomplete="tel">
                        <label for="phone">Phone Number</label>
                    </div>
                    <div class="float-group">
                        <textarea id="address" rows="4" placeholder=" " required autocomplete="street-address"></textarea>
                        <label for="address">Delivery Address</label>
                    </div>
                    <button class="btn btn-success w-100 btn-lg" type="submit" aria-label="Place your order">Place Order</button>
                </form>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card p-4 shadow-sm">
                <h4 class="mb-3">Order Summary</h4>
                <div id="order-summary" aria-live="polite"></div>
            </div>
        </div>
    </div>
</section>
</main>

<?php include "../components/footer.php"; ?>
<script src="../js/checkout.js"></script>

</body>
</html>