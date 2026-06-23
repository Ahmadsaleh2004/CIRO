<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Cairo Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dark-theme.css" id="theme-style">
</head>
<body>

<?php include "../components/navbar.php"; ?>

<section class="container py-5">
    <h1 class="text-center mb-5">Checkout</h1>

    <div class="row">
        <div class="col-lg-7">
            <div class="card p-4 shadow-sm">
                <h4 class="mb-4">Customer Information</h4>
                <form id="checkoutForm">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" id="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" id="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" id="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea id="address" class="form-control" rows="4" required></textarea>
                    </div>
                    <button class="btn btn-success w-100 btn-lg" type="submit">Place Order</button>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card p-4 shadow-sm">
                <h4 class="mb-3">Order Summary</h4>
                
                <div id="order-summary">
                    <!-- Dynamic Order Summary -->
                </div>
            </div>
        </div>
    </div>
</section>

<?php include "../components/footer.php"; ?>
<script src="../js/checkout.js"></script>

</body>
</html>