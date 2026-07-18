<?php
require_once __DIR__ . '/../helpers/auth_helper.php';

$pageTitle = 'My Wishlist';
require_once __DIR__ . '/../components/header.php';
?>
<?php include '../components/navbar.php'; ?>

<main id="main-content" role="main">
<section class="container py-5">
    <nav class="store-breadcrumb mb-4">
        <a href="/Task(1)/index.php">🏠 Home</a>
        <span class="sep">/</span>
        <span class="current">My Wishlist</span>
    </nav>
    <h1 class="section-title">My Wishlist</h1>
    <div id="wishlist-container" class="row"></div>
</section>
</main>

<?php include '../components/footer.php'; ?>
<script src="/Task(1)/js/wishlist.js" defer></script>
</body>
</html>
