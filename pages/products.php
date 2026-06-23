<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Products | Cairo Store</title>

    <meta name="description"
          content="Browse all products available at Cairo Store. Search, filter and discover the latest electronics.">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dark-theme.css" id="theme-style" disabled>

</head>

<body>

<?php include "../components/navbar.php"; ?>

<section class="container py-5">

    <h1 class="section-title">Our Products</h1>

    <div class="row mb-4">

        <div class="col-lg-4 mb-3">

            <input
                type="text"
                id="search"
                class="form-control"
                placeholder="Search Product..."
            >

        </div>

        <div class="col-lg-4 mb-3">

            <select
                id="sort"
                class="form-select"
            >

                <option value="">
                    Sort Products
                </option>

                <option value="az">
                    Name A-Z
                </option>

                <option value="za">
                    Name Z-A
                </option>

                <option value="low">
                    Price Low To High
                </option>

                <option value="high">
                    Price High To Low
                </option>

            </select>

        </div>

        <div class="col-lg-4 mb-3">

            <button
                id="reset"
                class="btn btn-secondary w-100"
            >
                Reset
            </button>

        </div>

    </div>

    <div
        class="row"
        id="products-container"
    >

    </div>

</section>

<?php include "../components/footer.php"; ?>


</body>

</html>