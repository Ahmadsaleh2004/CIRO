<?php /** admin/layout_end.php — نهاية القالب */ ?>
</main>

<div class="admin-footer">
    Cairo Store Admin Panel © <?= date('Y') ?> — Logged in as
    <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? '') ?></strong>
    (Role <?= htmlspecialchars($_SESSION['admin_role'] ?? '') ?>)
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/Task(1)/js/main.js"></script>
<script src="/Task(1)/js/helpers.js"></script>
<script src="/Task(1)/js/auth.js"></script>
<?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
