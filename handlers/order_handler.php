<?php
/**
 * handlers/order_handler.php — المرحلة 19
 * ينشئ الطلب + يحدّث المخزون + عمليات الأدمن (استلام، تسليم، إلغاء، بلاغات)
 */
require_once __DIR__ . '/../config/error_handler.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../helpers/audit_log_helper.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDB();

// ── إرجاع تلقائي للطلبات التي استلمها مندوب وانتهت مهلتها (3 ساعات) ──
function autoReleaseTakenOrders(PDO $pdo): void {
    $threeHoursAgo = date('Y-m-d H:i:s', time() - 3 * 3600);
    $pdo->prepare("UPDATE orders SET status = 'not_taken', taken_at = NULL WHERE status = 'taken' AND taken_at < ?")
        ->execute([$threeHoursAgo]);
}
autoReleaseTakenOrders($pdo);

function respond(bool $ok, string $msg, array $extra=[]): void {
    // أرجع csrf_token دائماً لمزامنة الـ DOM
    $extra['csrf_token'] = generateCsrfToken();
    echo json_encode(array_merge(['success'=>$ok,'message'=>$msg],$extra));
    exit;
}

$action = $_POST['action'] ?? '';

if ($action !== '') {
    // عمليات الأدمن
    if (!isAdmin()) respond(false, 'Unauthorized');
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    
    $adminId = getCurrentAdminId();
    $orderId = (int)($_POST['order_id'] ?? 0);
    
    if (!$orderId) respond(false, 'طلب غير صحيح.');
    
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) respond(false, 'الطلب غير موجود.');
    
    $targetUserId = (int)$order['user_id'];
    
    if ($action === 'taken') {
        if ($order['status'] !== 'not_taken') {
            respond(false, 'لا يمكن استلام هذا الطلب نظراً لحالته الحالية.');
        }
        
        $pdo->prepare("UPDATE orders SET status = 'taken', taken_at = NOW() WHERE order_id = ?")
            ->execute([$orderId]);
        
        // إشعار للمستخدم
        $pdo->prepare("INSERT INTO notifications (user_id, title, message, sender_admin_id) VALUES (?, ?, ?, ?)")
            ->execute([
                $targetUserId,
                'تحديث حالة الطلب / Order Status Update',
                "تم استلام طلبك رقم #{$orderId} من قبل أحد المندوبين وهو قيد التجهيز الآن.",
                $adminId
            ]);
            
        logAdminAction($adminId, 'take_order', 'orders', $orderId, "Status changed to taken");
        respond(true, 'تم استلام الطلب بنجاح.');
    }
    
    if ($action === 'mark_delivered') {
        $pdo->prepare("UPDATE orders SET status = 'completed' WHERE order_id = ?")
            ->execute([$orderId]);

        $customMsg = trim($_POST['notif_msg'] ?? '');
        $notifMsg  = $customMsg ?: "Your order #{$orderId} has been delivered successfully. Thank you for shopping with us!";

        $pdo->prepare("INSERT INTO notifications (user_id, title, message, sender_admin_id) VALUES (?, ?, ?, ?)")
            ->execute([$targetUserId, 'Order Delivered ✅', $notifMsg, $adminId]);

        logAdminAction($adminId, 'mark_delivered', 'orders', $orderId, "Status changed to completed");
        respond(true, 'Order marked as delivered successfully.');
    }

    if ($action === 'cancel_delivery') {
        $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?")
            ->execute([$orderId]);

        $customMsg = trim($_POST['notif_msg'] ?? '');
        $notifMsg  = $customMsg ?: "We regret to inform you that your order #{$orderId} delivery has been cancelled.";

        $pdo->prepare("INSERT INTO notifications (user_id, title, message, sender_admin_id) VALUES (?, ?, ?, ?)")
            ->execute([$targetUserId, 'Delivery Cancelled ❌', $notifMsg, $adminId]);

        logAdminAction($adminId, 'cancel_delivery', 'orders', $orderId, "Status changed to cancelled");
        respond(true, 'Order delivery cancelled.');
    }

    if ($action === 'report_issue') {
        $reason = trim($_POST['reason'] ?? '');
        if (!$reason) respond(false, 'Please provide a reason for the report.');

        // إضافة note في contact_messages أو user_strikes حسب القرار
        // هنا نضيفها فقط كـ note في user profile بدون strike تلقائي
        $pdo->prepare("INSERT INTO notifications (user_id, title, message, sender_admin_id) VALUES (?, ?, ?, ?)")
            ->execute([
                $targetUserId,
                "Order Issue Reported — Order #{$orderId}",
                "An issue was reported on your order #{$orderId}:\n{$reason}\nPlease contact support if you have questions.",
                $adminId
            ]);

        logAdminAction($adminId, 'report_order_issue', 'orders', $orderId, "Issue reported. Reason: {$reason}");
        respond(true, 'Issue reported and user notified.');
    }

    respond(false, 'Invalid action.');

// ── إنشاء طلب جديد للمستخدم ──────────────────────────────────
requireUser();
verifyCsrfToken($_POST['csrf_token'] ?? '');

$userId = getCurrentUserId();

$cart       = json_decode($_POST['cart']        ?? '[]', true) ?: [];
$payment    = $_POST['payment']                  ?? 'cash_on_delivery';
$addressId  = (int)($_POST['address_id']         ?? 0);
$manualAddr = json_decode($_POST['manual_addr']  ?? '{}', true) ?: [];

if (empty($cart)) respond(false, 'السلة فارغة.');

try {
    $pdo->beginTransaction();

    // ── عنوان ──────────────────────────────────────────────────
    $finalAddrId = null;
    if ($addressId > 0) {
        $s = $pdo->prepare("SELECT id FROM user_addresses WHERE id=? AND user_id=? LIMIT 1");
        $s->execute([$addressId, $userId]);
        if ($s->fetch()) $finalAddrId = $addressId;
    }
    if (!$finalAddrId && !empty($manualAddr['full'])) {
        if (!empty($manualAddr['save'])) {
            $pdo->prepare("INSERT INTO user_addresses (user_id,country,city,full_address,phone_number,label)
                VALUES (?,?,?,?,?,'Shipping Address')")
                ->execute([$userId,$manualAddr['country'],$manualAddr['city'],
                           $manualAddr['full'],$manualAddr['phone']]);
            $finalAddrId = (int)$pdo->lastInsertId();
        }
    }

    // ── التحقق من المخزون + حساب الإجمالي ──────────────────────
    $total      = 0;
    $validItems = [];
    foreach ($cart as $item) {
        $pid = (int)($item['id'] ?? 0);
        $qty = (int)($item['quantity'] ?? 1);
        if (!$pid || $qty < 1) continue;

        $s = $pdo->prepare("SELECT id,name,price,discount_percentage,price_after_discount,stock_quantity FROM products WHERE id=? LIMIT 1");
        $s->execute([$pid]);
        $prod = $s->fetch();

        if (!$prod) continue;
        if ((int)$prod['stock_quantity'] < $qty) {
            $pdo->rollBack();
            respond(false, "«{$prod['name']}» غير متوفر بالكمية المطلوبة. المتوفر: {$prod['stock_quantity']}");
        }

        $unitPrice = (float)($prod['discount_percentage']>0 ? $prod['price_after_discount'] : $prod['price']);
        $total    += $unitPrice * $qty;
        $validItems[] = ['id'=>$pid,'qty'=>$qty,'price'=>$unitPrice];
    }

    if (empty($validItems)) { $pdo->rollBack(); respond(false,'لا توجد منتجات صالحة.'); }

    // ── إنشاء الطلب ────────────────────────────────────────────
    $pdo->prepare("INSERT INTO orders (user_id,address_id,total_amount,payment_method,status,is_notified)
        VALUES (?,?,?,?,'not_taken',0)")
        ->execute([$userId, $finalAddrId, round($total,2), $payment]);
    $orderId = (int)$pdo->lastInsertId();

    // ── العناصر + تحديث المخزون ────────────────────────────────
    $insItem   = $pdo->prepare("INSERT INTO order_items (order_id,product_id,quantity,price_at_purchase) VALUES (?,?,?,?)");
    $updStock  = $pdo->prepare("UPDATE products SET stock_quantity=stock_quantity-?, sales_count=sales_count+? WHERE id=?");
    foreach ($validItems as $vi) {
        $insItem->execute([$orderId, $vi['id'], $vi['qty'], $vi['price']]);
        $updStock->execute([$vi['qty'], $vi['qty'], $vi['id']]);
    }

    $pdo->commit();
    updateUserActivity();

    // إرسال بريد إلكتروني لتأكيد الطلب
    try {
        $uStmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ? LIMIT 1");
        $uStmt->execute([$userId]);
        $userRow = $uStmt->fetch();
        if ($userRow) {
            $emailItems = [];
            foreach ($validItems as $vi) {
                $nameStmt = $pdo->prepare("SELECT name FROM products WHERE id = ? LIMIT 1");
                $nameStmt->execute([$vi['id']]);
                $pName = $nameStmt->fetchColumn() ?: 'Product';
                $emailItems[] = [
                    'name' => $pName,
                    'quantity' => $vi['qty'],
                    'price' => $vi['price']
                ];
            }
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
    respond(false, 'حدث خطأ أثناء معالجة الطلب. حاول مجدداً.');
}
