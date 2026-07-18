<?php
/**
 * handlers/order_handler.php
 * ينشئ الطلب + يحدّث المخزون + عمليات الأدمن
 */
require_once __DIR__ . '/../config/error_handler.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../helpers/audit_log_helper.php';
require_once __DIR__ . '/../helpers/http_helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    respond(false, 'Invalid request method.');
}

$pdo = getDB();

// ── auto-release taken orders past 3h ──────────────────────────
$pdo->prepare("UPDATE orders SET status='not_taken', taken_at=NULL WHERE status='taken' AND taken_at < ?")
    ->execute([date('Y-m-d H:i:s', time() - 3 * 3600)]);

$action = $_POST['action'] ?? '';

// ── Admin actions ─────────────────────────────────────────────
if ($action !== '') {
    if (!isAdmin()) respond(false, 'Unauthorized');
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $adminId = getCurrentAdminId();
    $orderId = (int)($_POST['order_id'] ?? 0);
    if (!$orderId) respond(false, 'Invalid order ID.');

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) respond(false, 'Order not found.');

    $targetUserId = (int)$order['user_id'];

    if ($action === 'taken') {
        if ($order['status'] !== 'not_taken') {
            respond(false, 'Cannot take this order — invalid status.');
        }
        $pdo->prepare("UPDATE orders SET status='taken', taken_at=NOW() WHERE order_id=?")
            ->execute([$orderId]);
        $pdo->prepare("INSERT INTO notifications (user_id,title,message,sender_admin_id) VALUES (?,?,?,?)")
            ->execute([$targetUserId, 'Order Status Update', "Your order #{$orderId} has been picked up and is being prepared.", $adminId]);
        logAdminAction($adminId, 'take_order', 'orders', $orderId, 'Status: taken');
        respond(true, 'Order taken successfully.');
    }

    if ($action === 'mark_delivered') {
        $pdo->prepare("UPDATE orders SET status='completed' WHERE order_id=?")->execute([$orderId]);
        $msg = trim($_POST['notif_msg'] ?? '') ?: "Your order #{$orderId} has been delivered. Thank you!";
        $pdo->prepare("INSERT INTO notifications (user_id,title,message,sender_admin_id) VALUES (?,?,?,?)")
            ->execute([$targetUserId, 'Order Delivered ✅', $msg, $adminId]);
        logAdminAction($adminId, 'mark_delivered', 'orders', $orderId, 'Status: completed');
        respond(true, 'Order marked as delivered.');
    }

    if ($action === 'cancel_delivery') {
        $pdo->prepare("UPDATE orders SET status='cancelled' WHERE order_id=?")->execute([$orderId]);
        $msg = trim($_POST['notif_msg'] ?? '') ?: "Your order #{$orderId} delivery has been cancelled.";
        $pdo->prepare("INSERT INTO notifications (user_id,title,message,sender_admin_id) VALUES (?,?,?,?)")
            ->execute([$targetUserId, 'Delivery Cancelled ❌', $msg, $adminId]);
        logAdminAction($adminId, 'cancel_delivery', 'orders', $orderId, 'Status: cancelled');
        respond(true, 'Delivery cancelled.');
    }

    if ($action === 'report_issue') {
        $reason = trim($_POST['reason'] ?? '');
        if (!$reason) respond(false, 'Please provide a reason for the report.');
        $pdo->prepare("INSERT INTO notifications (user_id,title,message,sender_admin_id) VALUES (?,?,?,?)")
            ->execute([
                $targetUserId,
                "Order Issue Reported — Order #{$orderId}",
                "An issue was reported on your order #{$orderId}:\n{$reason}\nPlease contact support if you have questions.",
                $adminId,
            ]);
        logAdminAction($adminId, 'report_order_issue', 'orders', $orderId, "Reason: {$reason}");
        respond(true, 'Issue reported and user notified.');
    }

    // Unknown admin action
    http_response_code(400);
    respond(false, 'Invalid action.');
}

// ── Create new order (user) ───────────────────────────────────
requireUser();
verifyCsrfToken($_POST['csrf_token'] ?? '');

$userId     = getCurrentUserId();
$cart       = json_decode($_POST['cart']       ?? '[]', true) ?: [];
$payment    = $_POST['payment']                ?? 'cash_on_delivery';
$addressId  = (int)($_POST['address_id']       ?? 0);
$manualAddr = json_decode($_POST['manual_addr'] ?? '{}', true) ?: [];

if (empty($cart)) respond(false, 'Cart is empty.');

try {
    $pdo->beginTransaction();

    // ── address ───────────────────────────────────────────────
    $finalAddrId = null;
    if ($addressId > 0) {
        $s = $pdo->prepare("SELECT id FROM user_addresses WHERE id=? AND user_id=? LIMIT 1");
        $s->execute([$addressId, $userId]);
        if ($s->fetch()) $finalAddrId = $addressId;
    }
    if (!$finalAddrId && !empty($manualAddr['full'])) {
        if (!empty($manualAddr['save'])) {
            $pdo->prepare("INSERT INTO user_addresses (user_id,country,city,full_address,phone_number,label) VALUES (?,?,?,?,?,'Shipping Address')")
                ->execute([$userId, $manualAddr['country'], $manualAddr['city'], $manualAddr['full'], $manualAddr['phone']]);
            $finalAddrId = (int)$pdo->lastInsertId();
        }
    }

    // ── validate stock + calc total (single IN query) ─────────
    $total      = 0;
    $validItems = [];
    $cartIds    = array_values(array_filter(array_map(fn($i) => (int)($i['id'] ?? 0), $cart)));

    if (empty($cartIds)) { $pdo->rollBack(); respond(false, 'Empty cart.'); }

    $ph       = implode(',', array_fill(0, count($cartIds), '?'));
    $prodStmt = $pdo->prepare("SELECT id,name,price,discount_percentage,price_after_discount,stock_quantity FROM products WHERE id IN ({$ph})");
    $prodStmt->execute($cartIds);
    $productsMap = [];
    foreach ($prodStmt->fetchAll() as $row) {
        $productsMap[$row['id']] = $row;
    }

    foreach ($cart as $item) {
        $pid  = (int)($item['id'] ?? 0);
        $qty  = (int)($item['quantity'] ?? 1);
        if (!$pid || $qty < 1) continue;
        $prod = $productsMap[$pid] ?? null;
        if (!$prod) continue;
        if ((int)$prod['stock_quantity'] < $qty) {
            $pdo->rollBack();
            respond(false, "«{$prod['name']}» is not available in the requested quantity. Available: {$prod['stock_quantity']}");
        }
        $unitPrice    = (float)($prod['discount_percentage'] > 0 ? $prod['price_after_discount'] : $prod['price']);
        $total       += $unitPrice * $qty;
        $validItems[] = ['id' => $pid, 'qty' => $qty, 'price' => $unitPrice, 'name' => $prod['name']];
    }

    if (empty($validItems)) { $pdo->rollBack(); respond(false, 'No valid products in cart.'); }

    // ── create order ──────────────────────────────────────────
    $pdo->prepare("INSERT INTO orders (user_id,address_id,total_amount,payment_method,status,is_notified) VALUES (?,?,?,?,'not_taken',0)")
        ->execute([$userId, $finalAddrId, round($total, 2), $payment]);
    $orderId = (int)$pdo->lastInsertId();

    // ── order items + stock update ────────────────────────────
    $insItem  = $pdo->prepare("INSERT INTO order_items (order_id,product_id,quantity,price_at_purchase) VALUES (?,?,?,?)");
    $updStock = $pdo->prepare("UPDATE products SET stock_quantity=stock_quantity-?, sales_count=sales_count+? WHERE id=?");
    foreach ($validItems as $vi) {
        $insItem->execute([$orderId, $vi['id'], $vi['qty'], $vi['price']]);
        $updStock->execute([$vi['qty'], $vi['qty'], $vi['id']]);
    }

    $pdo->commit();
    updateUserActivity();

    // ── confirmation email ────────────────────────────────────
    try {
        $uStmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id=? LIMIT 1");
        $uStmt->execute([$userId]);
        $userRow = $uStmt->fetch();
        if ($userRow) {
            $emailItems = array_map(fn($vi) => ['name' => $vi['name'], 'quantity' => $vi['qty'], 'price' => $vi['price']], $validItems);
            require_once __DIR__ . '/../helpers/mail_helper.php';
            sendOrderConfirmationEmail($orderId, $userRow['email'], $userRow['full_name'], $emailItems, round($total, 2));
        }
    } catch (Exception $e) {
        error_log('Order Mail Error: ' . $e->getMessage());
    }

    respond(true, 'Order placed successfully.', ['order_id' => $orderId]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Order Error: ' . $e->getMessage());
    respond(false, 'An error occurred while processing the order. Please try again.');
}
