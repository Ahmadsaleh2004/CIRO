<?php
/**
 * handlers/order_handler.php — المرحلة 11
 * ينشئ الطلب + يحدّث المخزون + sales_count
 */
require_once __DIR__ . '/../config/error_handler.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';

header('Content-Type: application/json; charset=utf-8');

requireUser();
verifyCsrfToken($_POST['csrf_token'] ?? '');

$pdo    = getDB();
$userId = getCurrentUserId();

$cart       = json_decode($_POST['cart']        ?? '[]', true) ?: [];
$payment    = $_POST['payment']                  ?? 'cash_on_delivery';
$addressId  = (int)($_POST['address_id']         ?? 0);
$manualAddr = json_decode($_POST['manual_addr']  ?? '{}', true) ?: [];

function respond(bool $ok, string $msg, array $extra=[]): void {
    echo json_encode(array_merge(['success'=>$ok,'message'=>$msg],$extra));
    exit;
}

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
        VALUES (?,?,?,?,'pending',0)")
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

    respond(true, 'تم إنشاء الطلب بنجاح.', ['order_id' => $orderId]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Order Error: ' . $e->getMessage());
    respond(false, 'حدث خطأ أثناء معالجة الطلب. حاول مجدداً.');
}
