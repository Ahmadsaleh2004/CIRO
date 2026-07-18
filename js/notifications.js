/**
 * js/notifications.js — المرحلة 15
 * نظام إشعارات كامل:
 *  - Polling كل 30 ثانية لجلب الإشعارات الجديدة
 *  - Badge بعدد غير المقروءة على أيقونة الجرس
 *  - Sidebar يفتح عند الضغط على الجرس
 *  - Modal لعرض تفاصيل الإشعار
 *  - تحديد كل كمقروءة عند الفتح
 */

(function () {
    'use strict';

    // ── عناصر DOM الرئيسية ──────────────────────────────────────
    const bell        = document.getElementById('notifBell');
    const badge       = document.getElementById('notifBadge');
    const sidebar     = document.getElementById('notifSidebar');
    const sidebarList = document.getElementById('notifList');
    const closeBtn    = document.getElementById('notifClose');
    const markAllBtn  = document.getElementById('notifMarkAll');

    if (!bell) return; // ليس مستخدماً مسجلاً — لا تشغيل

    let allNotifs = [];

    // ── جلب الإشعارات من الـ Server ─────────────────────────────
    async function fetchNotifications() {
        try {
            const res  = await fetch('/Task(1)/handlers/notifications_handler.php?action=list');
            const data = await res.json();
            if (!data.success) return;

            allNotifs = data.notifications || [];
            const unread = data.unread || 0;

            // تحديث الـ Badge
            if (badge) {
                badge.textContent = unread > 99 ? '99+' : unread;
                badge.style.display = unread > 0 ? '' : 'none';
            }

            renderSidebar();
        } catch (e) {
            console.warn('Notifications fetch error:', e);
        }
    }

    // ── رسم قائمة الإشعارات في الـ Sidebar ─────────────────────
    function renderSidebar() {
        if (!sidebarList) return;
        if (allNotifs.length === 0) {
            sidebarList.innerHTML = '<li class="notif-empty">No notifications</li>';
            return;
        }
        sidebarList.innerHTML = allNotifs.map(n => `
            <li class="notif-item ${n.is_read == 1 ? 'read' : 'unread'}"
                data-id="${n.id}" onclick="openNotifDetail(${n.id})">
                <button class="notif-dismiss-btn"
                        onclick="dismissNotif(event, ${n.id})"
                        title="Dismiss">✕</button>
                <div class="notif-title">${escapeHtml(n.title)}</div>
                <div class="notif-msg">${escapeHtml(n.message.length > 80 ? n.message.slice(0,80) + '…' : n.message)}</div>
                <div class="notif-time">${formatRelativeTime(n.created_at)}</div>
                ${n.is_read == 0 ? '<span class="notif-dot"></span>' : ''}
            </li>
        `).join('');
    }

    // ── فتح Modal تفاصيل الإشعار ────────────────────────────────
    window.openNotifDetail = function (id) {
        const notif = allNotifs.find(n => n.id == id);
        if (!notif) return;

        if (notif.is_read == 0) markAsRead(id);

        // ابن الـ Modal ديناميكياً بـ SweetAlert
        const sentDate = new Date(notif.created_at).toLocaleString('en-US', {
            year:'numeric', month:'short', day:'numeric',
            hour:'2-digit', minute:'2-digit'
        });
        const senderName  = notif.sender_name  || 'Cairo Store';
        const senderEmail = notif.sender_email || '';

        Swal.fire({
            title: escapeHtml(notif.title),
            html: `
                <div style="text-align:left;">
                    <p style="white-space:pre-line;margin-bottom:1rem;">${escapeHtml(notif.message)}</p>
                    <hr style="border-color:#e5e7eb;">
                    <small style="color:#6b7280;">
                        <strong>From:</strong> ${escapeHtml(senderName)}<br>
                        ${senderEmail ? `<strong>Email:</strong> ${escapeHtml(senderEmail)}<br>` : ''}
                        <strong>Date:</strong> ${sentDate}
                    </small>
                </div>`,
            confirmButtonText: 'Close',
            confirmButtonColor: '#6366f1',
            width: '500px',
        });
    };

    // ── تعليم إشعار كمقروء ──────────────────────────────────────
    async function markAsRead(id) {
        try {
            const fd = new FormData();
            fd.append('action', 'mark_read');
            fd.append('id', id);
            fd.append('csrf_token', window._csrfToken || '');
            const data = await fetchWithCsrfRetry(
                '/Task(1)/handlers/notifications_handler.php',
                { method: 'POST', body: fd }
            );
            if (data.csrf_token) updateCsrfToken(data.csrf_token);
            const n = allNotifs.find(n => n.id == id);
            if (n) n.is_read = 1;
            const unread = allNotifs.filter(n => n.is_read == 0).length;
            if (badge) {
                badge.textContent = unread > 99 ? '99+' : unread;
                badge.style.display = unread > 0 ? '' : 'none';
            }
            renderSidebar();
        } catch (e) {}
    }

    // ── حذف إشعار واحد ──────────────────────────────────────────
    window.dismissNotif = async function(event, id) {
        event.stopPropagation();
        const fd = new FormData();
        fd.append('action', 'dismiss');
        fd.append('id', id);
        fd.append('csrf_token', window._csrfToken || document.querySelector('input[name="csrf_token"]')?.value || '');
        const data = await fetchWithCsrfRetry('/Task(1)/handlers/notifications_handler.php', { method: 'POST', body: fd });
        if (data.success) {
            allNotifs = allNotifs.filter(n => n.id != id);
            const unread = allNotifs.filter(n => n.is_read == 0).length;
            if (badge) {
                badge.textContent = unread > 99 ? '99+' : unread;
                badge.style.display = unread > 0 ? '' : 'none';
            }
            renderSidebar();
        }
    };

    // ── حذف كل الإشعارات ────────────────────────────────────────
    const deleteAllBtn = document.getElementById('notifDeleteAll');
    if (deleteAllBtn) {
        deleteAllBtn.addEventListener('click', async () => {
            const fd = new FormData();
            fd.append('action', 'delete_all');
            fd.append('csrf_token', window._csrfToken || document.querySelector('input[name="csrf_token"]')?.value || '');
            const data = await fetchWithCsrfRetry('/Task(1)/handlers/notifications_handler.php', { method: 'POST', body: fd });
            if (data.success) {
                allNotifs = [];
                if (badge) badge.style.display = 'none';
                renderSidebar();
            }
        });
    }

    // ── تعليم الكل كمقروء ───────────────────────────────────────
    if (markAllBtn) {
        markAllBtn.addEventListener('click', async () => {
            const fd = new FormData();
            fd.append('action', 'mark_all_read');
            fd.append('csrf_token', window._csrfToken || '');
            const data = await fetchWithCsrfRetry(
                '/Task(1)/handlers/notifications_handler.php',
                { method: 'POST', body: fd }
            );
            if (data.csrf_token) updateCsrfToken(data.csrf_token);
            allNotifs.forEach(n => n.is_read = 1);
            if (badge) badge.style.display = 'none';
            renderSidebar();
        });
    }

    // ── فتح/إغلاق الـ Sidebar ───────────────────────────────────
    if (bell && sidebar) {
        bell.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }
    if (closeBtn && sidebar) {
        closeBtn.addEventListener('click', () => sidebar.classList.remove('open'));
    }

    // إغلاق عند النقر خارج الـ Sidebar
    document.addEventListener('click', (e) => {
        if (sidebar && sidebar.classList.contains('open')
            && !sidebar.contains(e.target)
            && e.target !== bell) {
            sidebar.classList.remove('open');
        }
    });

    // ── Polling كل 30 ثانية ──────────────────────────────────────
    fetchNotifications();
    setInterval(fetchNotifications, 30_000);

    // ── دوال مساعدة ─────────────────────────────────────────────
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    function formatRelativeTime(dateStr) {
        if (!dateStr) return '';
        const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
        if (diff < 60)    return 'Just now';
        if (diff < 3600)  return `${Math.floor(diff/60)}m ago`;
        if (diff < 86400) return `${Math.floor(diff/3600)}h ago`;
        return `${Math.floor(diff/86400)}d ago`;
    }

})();
