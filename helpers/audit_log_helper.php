<?php
/**
 * helpers/audit_log_helper.php
 * دالة واحدة تُدخل سطراً في admin_audit_log
 * تُستدعى من كل عملية حساسة بلوحة الأدمن
 */

require_once __DIR__ . '/../config/db.php';

function logAdminAction(
    int     $adminId,
    string  $action,
    string  $targetType,
    ?int    $targetId = null,
    ?string $details  = null
): void {
    try {
        getDB()->prepare("
            INSERT INTO admin_audit_log (admin_id,action,target_type,target_id,details,created_at)
            VALUES (?,?,?,?,?,NOW())
        ")->execute([$adminId, $action, $targetType, $targetId, $details]);
    } catch (Exception $e) {
        error_log('audit_log error: ' . $e->getMessage());
    }
}
