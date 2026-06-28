<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us | Cairo Store</title>
<meta name="description" content="Get in touch with Cairo Store for support, questions, or business inquiries.">
<meta name="robots" content="index, follow">
<meta property="og:title" content="Contact Us | Cairo Store">
<meta property="og:description" content="Get in touch with Cairo Store for support and inquiries.">
<meta property="og:type" content="website">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/dark-theme.css" id="theme-style" disabled>
</head>
<body class="page-transitioning">

<a href="#main-content" class="skip-nav">Skip to main content</a>

<?php include "../components/navbar.php"; ?>

<main id="main-content" role="main">
<section class="container py-5">

<div class="text-center mb-5">
    <h1 class="fw-bold">Contact Us</h1>
    <p class="lead">We Would Love To Hear From You</p>
</div>

<div class="row g-4">
<div class="col-lg-6">
<div class="card p-4 h-100">
<h2 class="h3 mb-4">Contact Information</h2>
<p>📍 Cairo, Egypt</p>
<p>📞 +20 123 456 789</p>
<p>✉ info@cairostore.com</p>
<p>🕒 Sun - Thu : 9 AM - 6 PM</p>
<hr>
<h3 class="h5">Follow Us</h3>
<p>Facebook | Instagram | LinkedIn</p>
</div>
</div>

<div class="col-lg-6">
<div class="card p-4">
<h2 class="h3 mb-4">Send Message</h2>
<form id="contactForm" novalidate>
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
    <textarea id="message" rows="5" placeholder=" " required></textarea>
    <label for="message">Your Message</label>
</div>
<button type="submit" class="btn btn-success w-100" aria-label="Send your message">Send Message</button>
</form>
</div>
</div>
</div>

</section>
</main>

<?php include "../components/footer.php"; ?>

<script>
document.getElementById("contactForm").addEventListener("submit", function(e) {
    e.preventDefault();
    window.showToast("Message Sent Successfully!", "success");
    this.reset();
});
</script>

</body>
</html>