<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    return;
}

require_once __DIR__ . '/../database/db.php';
$cur_uid = intval($_SESSION["user_id"]);
$user_q = mysqli_query($conn, "SELECT name, email, role, avatar, notif_email, notif_site FROM users WHERE id = $cur_uid");
$sidebar_user = mysqli_fetch_assoc($user_q);

$user_name = (!empty($sidebar_user['name'])) ? $sidebar_user['name'] : ((!empty($_SESSION["name"])) ? $_SESSION["name"] : ((!empty($sidebar_user['email'])) ? explode('@', $sidebar_user['email'])[0] : 'Member'));
$user_email = $sidebar_user['email'] ?? $_SESSION["email"] ?? '';
$user_role = $sidebar_user['role'] ?? $_SESSION["role"] ?? 'customer';
$user_avatar = $sidebar_user['avatar'] ?? $_SESSION["avatar"] ?? '';
$notif_email = isset($sidebar_user['notif_email']) ? intval($sidebar_user['notif_email']) : 1;
$notif_site = isset($sidebar_user['notif_site']) ? intval($sidebar_user['notif_site']) : 1;
$user_initial = !empty($user_name) ? strtoupper(substr($user_name, 0, 1)) : 'U';
$is_admin = ($user_role === 'admin');

// Fetch support contacts from cafe_settings
$support_email = 'purr&pour@gmail.com';
$support_phone = '0967 6767 6767';
$support_hours = 'Mon - Sun, 8:00 AM - 9:00 PM';

$settings_res = mysqli_query($conn, "SELECT setting_key, setting_value FROM cafe_settings");
if ($settings_res) {
    while ($s_row = mysqli_fetch_assoc($settings_res)) {
        if ($s_row['setting_key'] === 'support_email') $support_email = $s_row['setting_value'];
        if ($s_row['setting_key'] === 'support_phone') $support_phone = $s_row['setting_value'];
        if ($s_row['setting_key'] === 'support_hours') $support_hours = $s_row['setting_value'];
    }
}

// ORDER HISTORY QUERY:
// If Admin -> Show ALL recent orders (latest first) so admin can approve or reject
// If Regular Member -> Show their own recent orders
if ($is_admin) {
    $orders_q = mysqli_query($conn, "SELECT o.id, o.user_id, o.customer_name, o.payment_method, o.total_amount, o.status, o.created_at,
                                     GROUP_CONCAT(CONCAT(oi.product_name, ' (x', oi.quantity, ')') SEPARATOR ', ') as items_summary
                              FROM orders o
                              LEFT JOIN order_items oi ON o.id = oi.order_id
                              GROUP BY o.id
                              ORDER BY o.created_at DESC, o.id DESC
                              LIMIT 6");
} else {
    $orders_q = mysqli_query($conn, "SELECT o.id, o.payment_method, o.total_amount, o.status, o.created_at,
                                     GROUP_CONCAT(CONCAT(oi.product_name, ' (x', oi.quantity, ')') SEPARATOR ', ') as items_summary
                              FROM orders o
                              LEFT JOIN order_items oi ON o.id = oi.order_id
                              WHERE o.user_id = $cur_uid
                              GROUP BY o.id
                              ORDER BY o.id DESC
                              LIMIT 5");
}

$sidebar_orders = [];
if ($orders_q) {
    while ($o_row = mysqli_fetch_assoc($orders_q)) {
        $sidebar_orders[] = $o_row;
    }
}

// If admin, fetch users for the Help & Support account management tool
$admin_users_list = [];
if ($is_admin) {
    $uq = mysqli_query($conn, "SELECT id, name, email, role FROM users ORDER BY id ASC LIMIT 20");
    if ($uq) {
        while ($ur = mysqli_fetch_assoc($uq)) {
            $admin_users_list[] = $ur;
        }
    }
}
?>
<link rel="stylesheet" href="/purr-and-pour/style/sidebar.css">

<!-- Sidebar Overlay -->
<div class="app-sidebar-overlay" id="appSidebarOverlay"></div>

<!-- Slide-out Sidebar -->
<aside class="app-sidebar" id="appSidebar" aria-label="Member Profile Sidebar">
    <!-- Centered Zoomed-In Logo Banner (Transparent PNG, No BG, No Shadow) -->
    <div class="sidebar-top-branding">
        <div class="sidebar-logo-container">
            <img src="/purr-and-pour/elements/sidebar_logo_cropped.png" alt="Purr & Pour Cat Café Logo" class="sidebar-header-custom-logo">
        </div>
        <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close Sidebar">&times;</button>
    </div>

    <div class="sidebar-scrollable-content">
        <!-- 1. Member Profile Banner -->
        <div class="sidebar-profile-banner">
            <div class="sidebar-avatar-wrapper">
                <?php if (!empty($user_avatar)): ?>
                    <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Avatar" class="sidebar-avatar-img" id="sidebarAvatarImg">
                    <span class="sidebar-avatar-placeholder" id="sidebarAvatarPlaceholder" style="display:none;"><?php echo htmlspecialchars($user_initial); ?></span>
                <?php else: ?>
                    <img src="" alt="Avatar" class="sidebar-avatar-img" id="sidebarAvatarImg" style="display:none;">
                    <span class="sidebar-avatar-placeholder" id="sidebarAvatarPlaceholder"><?php echo htmlspecialchars($user_initial); ?></span>
                <?php endif; ?>
            </div>
            <div class="sidebar-profile-details">
                <h3 class="sidebar-display-name" id="sidebarDisplayNameDisplay"><?php echo htmlspecialchars($user_name); ?></h3>
                <p class="sidebar-email"><?php echo htmlspecialchars($user_email); ?></p>
                <div class="sidebar-badge-row">
                    <span class="sidebar-member-badge <?php echo $is_admin ? 'badge-admin' : 'badge-member'; ?>">
                        <?php echo $is_admin ? '☕ Café Administrator' : '🐾 Café Member'; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Feedback Alert Toast -->
        <div class="sidebar-status-toast" id="sidebarStatusToast"></div>

        <!-- Accordion Section 1: Edit Display Name -->
        <div class="sidebar-accordion-item" id="accEditName">
            <button type="button" class="sidebar-accordion-header" aria-expanded="false">
                <div class="accordion-title-group">
                    <span class="card-icon">✏️</span>
                    <span class="accordion-title-text">Edit Display Name</span>
                </div>
                <span class="accordion-chevron">❯</span>
            </button>
            <div class="sidebar-accordion-content">
                <div class="accordion-inner">
                    <form id="editDisplayNameForm">
                        <div class="sidebar-input-group">
                            <input type="text" id="newDisplayNameInput" class="sidebar-input" value="<?php echo htmlspecialchars($user_name); ?>" placeholder="Enter display name" required maxlength="50">
                            <button type="submit" class="sidebar-btn sidebar-btn-primary" id="saveNameBtn">Save</button>
                        </div>
                        <small class="sidebar-input-hint">Changes how your name appears on your profile and receipts.</small>
                    </form>
                </div>
            </div>
        </div>

        <!-- Accordion Section 2: Change Profile Picture -->
        <div class="sidebar-accordion-item" id="accChangeAvatar">
            <button type="button" class="sidebar-accordion-header" aria-expanded="false">
                <div class="accordion-title-group">
                    <span class="card-icon">📷</span>
                    <span class="accordion-title-text">Change Profile Picture</span>
                </div>
                <span class="accordion-chevron">❯</span>
            </button>
            <div class="sidebar-accordion-content">
                <div class="accordion-inner">
                    <p class="sidebar-card-desc">Upload a square photo or avatar image (JPG, PNG, WEBP, max 4MB).</p>
                    <div class="sidebar-avatar-actions">
                        <label for="avatarFileInput" class="sidebar-btn sidebar-btn-outline" style="cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                            <span>Choose Photo</span>
                            <input type="file" id="avatarFileInput" accept="image/*" style="display:none;">
                        </label>
                        <button type="button" class="sidebar-btn sidebar-btn-primary" id="uploadAvatarBtn" disabled>Upload Photo</button>
                    </div>
                    <div id="avatarFileNameDisplay" class="sidebar-selected-file"></div>
                </div>
            </div>
        </div>

        <?php if (!$is_admin): ?>
        <!-- Accordion Section 3: Notification Settings (CUSTOMER ONLY, removed for Admin) -->
        <div class="sidebar-accordion-item" id="accNotifications">
            <button type="button" class="sidebar-accordion-header" aria-expanded="false">
                <div class="accordion-title-group">
                    <span class="card-icon">🔔</span>
                    <span class="accordion-title-text">Notification Settings</span>
                </div>
                <span class="accordion-chevron">❯</span>
            </button>
            <div class="sidebar-accordion-content">
                <div class="accordion-inner sidebar-toggles-list">
                    <div class="sidebar-toggle-row">
                        <div class="toggle-info">
                            <span class="toggle-title">Email Alerts</span>
                            <span class="toggle-desc">Order receipts, status updates, and café promos</span>
                        </div>
                        <label class="switch-toggle" aria-label="Toggle Email Alerts">
                            <input type="checkbox" id="toggleEmailNotif" <?php echo $notif_email ? 'checked' : ''; ?>>
                            <span class="switch-slider"></span>
                        </label>
                    </div>

                    <div class="sidebar-toggle-row">
                        <div class="toggle-info">
                            <span class="toggle-title">Site Alerts</span>
                            <span class="toggle-desc">In-browser announcements & cart reminders</span>
                        </div>
                        <label class="switch-toggle" aria-label="Toggle Site Alerts">
                            <input type="checkbox" id="toggleSiteNotif" <?php echo $notif_site ? 'checked' : ''; ?>>
                            <span class="switch-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Accordion Section 4: Order History / Orders Pending Approval -->
        <div class="sidebar-accordion-item" id="accOrderHistory">
            <button type="button" class="sidebar-accordion-header" aria-expanded="false">
                <div class="accordion-title-group">
                    <span class="card-icon"><?php echo $is_admin ? '⏳' : '📜'; ?></span>
                    <span class="accordion-title-text">
                        <?php echo $is_admin ? 'Latest Orders (Approve / Reject)' : 'View Order History'; ?>
                        <?php if ($is_admin && !empty($sidebar_orders)):
                            $pending_sidebar_count = 0;
                            foreach ($sidebar_orders as $so) { if ($so['status'] === 'Pending') $pending_sidebar_count++; }
                            if ($pending_sidebar_count > 0): ?>
                            <span class="sidebar-count-pill"><?php echo $pending_sidebar_count; ?> pending</span>
                        <?php endif; endif; ?>
                    </span>
                </div>
                <span class="accordion-chevron">❯</span>
            </button>
            <div class="sidebar-accordion-content">
                <div class="accordion-inner">
                    <?php if (empty($sidebar_orders)): ?>
                        <?php if ($is_admin): ?>
                            <div class="sidebar-empty-state">
                                <span>🎉</span>
                                <p>No orders found yet. New orders will appear here for approval or rejection.</p>
                            </div>
                        <?php else: ?>
                            <p class="sidebar-card-desc" style="text-align:center; padding:10px 0;">No past orders recorded yet.</p>
                            <div style="text-align:center;">
                                <a href="/purr-and-pour/pages/menu.php" class="sidebar-btn sidebar-btn-primary" style="display:inline-block; text-decoration:none;">Explore Menu</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="sidebar-orders-list">
                            <?php foreach ($sidebar_orders as $ord): 
                                $badgeCls = 'order-status-pending';
                                if ($ord['status'] === 'Approved' || $ord['status'] === 'Confirmed') $badgeCls = 'order-status-approved';
                                elseif ($ord['status'] === 'Cancelled') $badgeCls = 'order-status-cancelled';
                            ?>
                                <div class="sidebar-order-card" id="adminOrderCard_<?php echo $ord['id']; ?>">
                                    <div class="sidebar-order-header">
                                        <strong>Order #<?php echo $ord['id']; ?> <?php if ($is_admin && !empty($ord['customer_name'])): ?><span class="customer-tag">— <?php echo htmlspecialchars($ord['customer_name']); ?></span><?php endif; ?></strong>
                                        <span class="order-badge <?php echo $badgeCls; ?>"><?php echo htmlspecialchars($ord['status']); ?></span>
                                    </div>
                                    <div class="sidebar-order-items"><?php echo htmlspecialchars($ord['items_summary'] ?: 'Café item(s)'); ?></div>
                                    <div class="sidebar-order-meta">
                                        <span>$<?php echo number_format($ord['total_amount'], 2); ?></span>
                                        <span><?php echo date('M d, Y h:i A', strtotime($ord['created_at'])); ?></span>
                                    </div>

                                    <?php if ($is_admin && $ord['status'] === 'Pending'): ?>
                                    <!-- Admin Instant Actions inside Sidebar (only for Pending orders) -->
                                    <div class="sidebar-admin-order-actions">
                                        <button type="button" class="btn-sidebar-approve" onclick="updateSidebarOrderStatus(<?php echo $ord['id']; ?>, 'Approved')">✓ Approve Order</button>
                                        <button type="button" class="btn-sidebar-reject" onclick="updateSidebarOrderStatus(<?php echo $ord['id']; ?>, 'Cancelled')">✕ Reject</button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div style="text-align:center; margin-top:12px;">
                        <?php if ($is_admin): ?>
                            <a href="/purr-and-pour/pages/admin.php#orders-section" class="sidebar-btn sidebar-btn-outline" style="display:inline-block; text-decoration:none; width:100%; box-sizing:border-box;">View Full Orders & Manage ⟶</a>
                        <?php else: ?>
                            <a href="/purr-and-pour/pages/account.php" class="sidebar-btn sidebar-btn-outline" style="display:inline-block; text-decoration:none; width:100%; box-sizing:border-box;">View Full History & Receipts ⟶</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accordion Section 5: Help & Support -->
        <div class="sidebar-accordion-item" id="accHelpSupport">
            <button type="button" class="sidebar-accordion-header" aria-expanded="false">
                <div class="accordion-title-group">
                    <span class="card-icon">💬</span>
                    <span class="accordion-title-text"><?php echo $is_admin ? 'Help & Support Settings' : 'Help & Support'; ?></span>
                </div>
                <span class="accordion-chevron">❯</span>
            </button>
            <div class="sidebar-accordion-content">
                <div class="accordion-inner">
                    <?php if ($is_admin): ?>
                        <!-- ADMIN MODE: Edit Support Info & Edit User Accounts -->
                        <div class="sidebar-admin-support-box">
                            <h5 class="admin-support-subheading">Update Support Contact Info</h5>
                            <p class="sidebar-card-desc">Edit the public contact channels and operating hours displayed to customers:</p>
                            <form id="adminSupportSettingsForm" class="sidebar-admin-form">
                                <label class="sidebar-form-label">Support Email:
                                    <input type="email" id="adminSupportEmail" class="sidebar-input" value="<?php echo htmlspecialchars($support_email); ?>" required>
                                </label>
                                <label class="sidebar-form-label">Support Phone:
                                    <input type="text" id="adminSupportPhone" class="sidebar-input" value="<?php echo htmlspecialchars($support_phone); ?>" required>
                                </label>
                                <label class="sidebar-form-label">Café Hours:
                                    <input type="text" id="adminSupportHours" class="sidebar-input" value="<?php echo htmlspecialchars($support_hours); ?>" required>
                                </label>
                                <button type="submit" class="sidebar-btn sidebar-btn-primary" id="saveSupportBtn" style="width:100%; margin-top:4px;">Save Contact Channels</button>
                            </form>

                            <hr class="sidebar-divider">

                            <h5 class="admin-support-subheading">Manage Provided Accounts</h5>
                            <p class="sidebar-card-desc">Edit user profile accounts, email addresses, or roles:</p>
                            <div class="sidebar-user-select-group">
                                <select id="adminSelectUser" class="sidebar-input" style="width:100%; margin-bottom:8px;">
                                    <option value="">-- Choose Account to Modify --</option>
                                    <?php foreach ($admin_users_list as $au): ?>
                                        <option value="<?php echo $au['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($au['name']); ?>" 
                                                data-email="<?php echo htmlspecialchars($au['email']); ?>" 
                                                data-role="<?php echo htmlspecialchars($au['role']); ?>">
                                            #<?php echo $au['id']; ?> - <?php echo htmlspecialchars($au['name']); ?> (<?php echo htmlspecialchars($au['email']); ?>) [<?php echo $au['role']; ?>]
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <form id="adminEditUserForm" class="sidebar-admin-form" style="display:none;">
                                <input type="hidden" id="editTargetUserId" value="">
                                <label class="sidebar-form-label">Name:
                                    <input type="text" id="editTargetUserName" class="sidebar-input" required>
                                </label>
                                <label class="sidebar-form-label">Email:
                                    <input type="email" id="editTargetUserEmail" class="sidebar-input" required>
                                </label>
                                <label class="sidebar-form-label">Role:
                                    <select id="editTargetUserRole" class="sidebar-input">
                                        <option value="customer">Customer (Member)</option>
                                        <option value="admin">Administrator</option>
                                    </select>
                                </label>
                                <div style="display:flex; gap:8px; margin-top:6px;">
                                    <button type="submit" class="sidebar-btn sidebar-btn-primary" id="saveUserEditBtn" style="flex:1;">Update Account</button>
                                    <button type="button" class="sidebar-btn sidebar-btn-outline" id="cancelUserEditBtn">Cancel</button>
                                </div>
                            </form>
                            <div style="text-align:center; margin-top:10px;">
                                <a href="/purr-and-pour/pages/admin.php#accounts-section" class="sidebar-small-link">Open Full Accounts Directory ⟶</a>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- CUSTOMER MODE: View Support Information -->
                        <p class="sidebar-card-desc">Need assistance with café reservations, orders, or your account?</p>
                        <div class="sidebar-support-links">
                            <a href="mailto:<?php echo htmlspecialchars($support_email); ?>" class="support-link-item">
                                <span class="support-icon">✉️</span>
                                <div class="support-text">
                                    <strong>Email Customer Care</strong>
                                    <span id="custSupportEmailText"><?php echo htmlspecialchars($support_email); ?></span>
                                </div>
                            </a>
                            <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $support_phone)); ?>" class="support-link-item">
                                <span class="support-icon">📞</span>
                                <div class="support-text">
                                    <strong>Hotline & Reservations</strong>
                                    <span id="custSupportPhoneText"><?php echo htmlspecialchars($support_phone); ?></span>
                                </div>
                            </a>
                            <div class="support-hours">
                                <strong>🐾 Café Hours:</strong> <span id="custSupportHoursText"><?php echo htmlspecialchars($support_hours); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Footer with Log out Button (Customer only) -->
    <div class="sidebar-footer">
        <?php if (!$is_admin): ?>
        <a href="/purr-and-pour/functions/logout.php" class="sidebar-logout-btn">
            <span>Log out</span>
        </a>
        <?php else: ?>
        <a href="/purr-and-pour/pages/admin.php" class="sidebar-logout-btn" style="background: linear-gradient(135deg, #6b4c2a 0%, #8c6d48 100%); color:#fff; border-color:#6b4c2a;">
            <span>⚙ Go to Admin Dashboard</span>
        </a>
        <?php endif; ?>
    </div>
</aside>

<script>
(function() {
    function initSidebar() {
        const openBtn = document.getElementById('sidebarOpenBtn');
        const closeBtn = document.getElementById('sidebarCloseBtn');
        const sidebar = document.getElementById('appSidebar');
        const overlay = document.getElementById('appSidebarOverlay');
        const toast = document.getElementById('sidebarStatusToast');

        if (!sidebar || !overlay) return;

        window.showSidebarToast = function(msg, isSuccess) {
            if (!toast) return;
            toast.textContent = msg;
            toast.className = 'sidebar-status-toast is-visible ' + (isSuccess ? 'toast-success' : 'toast-error');
            setTimeout(function() {
                toast.className = 'sidebar-status-toast';
            }, 3500);
        };

        function openSidebar() {
            sidebar.classList.add('is-open');
            overlay.classList.add('is-open');
            document.body.classList.add('sidebar-locked');
        }

        function closeSidebar() {
            sidebar.classList.remove('is-open');
            overlay.classList.remove('is-open');
            document.body.classList.remove('sidebar-locked');
        }

        if (openBtn) {
            openBtn.addEventListener('click', function(e) {
                e.preventDefault();
                openSidebar();
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closeSidebar();
            });
        }

        overlay.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('is-open')) {
                closeSidebar();
            }
        });

        // Accordion Expand/Collapse Logic
        const accordionHeaders = sidebar.querySelectorAll('.sidebar-accordion-header');
        accordionHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const item = this.closest('.sidebar-accordion-item');
                const isExpanded = item.classList.contains('is-open');

                if (isExpanded) {
                    item.classList.remove('is-open');
                    this.setAttribute('aria-expanded', 'false');
                } else {
                    item.classList.add('is-open');
                    this.setAttribute('aria-expanded', 'true');
                }
            });
        });

        // 1. Edit Display Name Form submission
        const nameForm = document.getElementById('editDisplayNameForm');
        const nameInput = document.getElementById('newDisplayNameInput');
        const nameDisplay = document.getElementById('sidebarDisplayNameDisplay');
        const saveNameBtn = document.getElementById('saveNameBtn');

        if (nameForm) {
            nameForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const newName = nameInput.value.trim();
                if (!newName) {
                    window.showSidebarToast('Display name cannot be empty', false);
                    return;
                }

                saveNameBtn.disabled = true;
                saveNameBtn.textContent = 'Saving...';

                const formData = new FormData();
                formData.append('action', 'update_name');
                formData.append('name', newName);

                fetch('/purr-and-pour/functions/update_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    saveNameBtn.disabled = false;
                    saveNameBtn.textContent = 'Save';
                    if (data.success) {
                        if (nameDisplay) nameDisplay.textContent = newName;
                        const placeholder = document.getElementById('sidebarAvatarPlaceholder');
                        if (placeholder && newName.length > 0) {
                            placeholder.textContent = newName.charAt(0).toUpperCase();
                        }
                        window.showSidebarToast(data.message, true);
                    } else {
                        window.showSidebarToast(data.message || 'Failed to update name', false);
                    }
                })
                .catch(() => {
                    saveNameBtn.disabled = false;
                    saveNameBtn.textContent = 'Save';
                    window.showSidebarToast('Network error while saving name', false);
                });
            });
        }

        // 2. Profile Picture Upload
        const avatarInput = document.getElementById('avatarFileInput');
        const uploadBtn = document.getElementById('uploadAvatarBtn');
        const fileNameDisplay = document.getElementById('avatarFileNameDisplay');
        const avatarImg = document.getElementById('sidebarAvatarImg');
        const avatarPlaceholder = document.getElementById('sidebarAvatarPlaceholder');

        if (avatarInput) {
            avatarInput.addEventListener('change', function() {
                if (avatarInput.files && avatarInput.files[0]) {
                    const file = avatarInput.files[0];
                    if (fileNameDisplay) fileNameDisplay.textContent = file.name;
                    if (uploadBtn) uploadBtn.disabled = false;
                } else {
                    if (fileNameDisplay) fileNameDisplay.textContent = '';
                    if (uploadBtn) uploadBtn.disabled = true;
                }
            });
        }

        if (uploadBtn) {
            uploadBtn.addEventListener('click', function() {
                if (!avatarInput || !avatarInput.files || !avatarInput.files[0]) return;

                uploadBtn.disabled = true;
                uploadBtn.textContent = 'Uploading...';

                const formData = new FormData();
                formData.append('action', 'upload_avatar');
                formData.append('avatar', avatarInput.files[0]);

                fetch('/purr-and-pour/functions/update_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    uploadBtn.disabled = false;
                    uploadBtn.textContent = 'Upload Photo';
                    if (data.success) {
                        if (avatarImg) {
                            avatarImg.src = data.avatar_url;
                            avatarImg.style.display = 'block';
                        }
                        if (avatarPlaceholder) {
                            avatarPlaceholder.style.display = 'none';
                        }
                        if (fileNameDisplay) fileNameDisplay.textContent = '';
                        avatarInput.value = '';
                        window.showSidebarToast(data.message, true);
                    } else {
                        window.showSidebarToast(data.message || 'Upload failed', false);
                    }
                })
                .catch(() => {
                    uploadBtn.disabled = false;
                    uploadBtn.textContent = 'Upload Photo';
                    window.showSidebarToast('Network error while uploading photo', false);
                });
            });
        }

        // 3. Notification Toggles (Customer only)
        const emailToggle = document.getElementById('toggleEmailNotif');
        const siteToggle = document.getElementById('toggleSiteNotif');

        function saveNotifications() {
            const formData = new FormData();
            formData.append('action', 'update_notifications');
            formData.append('notif_email', emailToggle && emailToggle.checked ? '1' : '0');
            formData.append('notif_site', siteToggle && siteToggle.checked ? '1' : '0');

            fetch('/purr-and-pour/functions/update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.showSidebarToast(data.message, true);
                } else {
                    window.showSidebarToast(data.message || 'Failed to update preferences', false);
                }
            })
            .catch(() => {
                window.showSidebarToast('Network error while saving notifications', false);
            });
        }

        if (emailToggle) emailToggle.addEventListener('change', saveNotifications);
        if (siteToggle) siteToggle.addEventListener('change', saveNotifications);

        // 4. Admin Support Settings Save
        const adminSupportForm = document.getElementById('adminSupportSettingsForm');
        const saveSupportBtn = document.getElementById('saveSupportBtn');
        if (adminSupportForm) {
            adminSupportForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const semail = document.getElementById('adminSupportEmail').value.trim();
                const sphone = document.getElementById('adminSupportPhone').value.trim();
                const shours = document.getElementById('adminSupportHours').value.trim();

                saveSupportBtn.disabled = true;
                saveSupportBtn.textContent = 'Saving...';

                const fd = new FormData();
                fd.append('action', 'admin_update_support_info');
                fd.append('support_email', semail);
                fd.append('support_phone', sphone);
                fd.append('support_hours', shours);

                fetch('/purr-and-pour/functions/update_profile.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(data => {
                    saveSupportBtn.disabled = false;
                    saveSupportBtn.textContent = 'Save Contact Channels';
                    if (data.success) {
                        window.showSidebarToast(data.message, true);
                    } else {
                        window.showSidebarToast(data.message || 'Failed to save', false);
                    }
                })
                .catch(() => {
                    saveSupportBtn.disabled = false;
                    saveSupportBtn.textContent = 'Save Contact Channels';
                    window.showSidebarToast('Network error saving contact details', false);
                });
            });
        }

        // 5. Admin Manage Account Selection & Edit
        const adminSelectUser = document.getElementById('adminSelectUser');
        const adminEditUserForm = document.getElementById('adminEditUserForm');
        const editTargetUserId = document.getElementById('editTargetUserId');
        const editTargetUserName = document.getElementById('editTargetUserName');
        const editTargetUserEmail = document.getElementById('editTargetUserEmail');
        const editTargetUserRole = document.getElementById('editTargetUserRole');
        const cancelUserEditBtn = document.getElementById('cancelUserEditBtn');
        const saveUserEditBtn = document.getElementById('saveUserEditBtn');

        if (adminSelectUser && adminEditUserForm) {
            adminSelectUser.addEventListener('change', function() {
                const sel = this.options[this.selectedIndex];
                const uid = this.value;
                if (!uid) {
                    adminEditUserForm.style.display = 'none';
                    return;
                }
                editTargetUserId.value = uid;
                editTargetUserName.value = sel.getAttribute('data-name') || '';
                editTargetUserEmail.value = sel.getAttribute('data-email') || '';
                editTargetUserRole.value = sel.getAttribute('data-role') || 'customer';
                adminEditUserForm.style.display = 'block';
            });

            if (cancelUserEditBtn) {
                cancelUserEditBtn.addEventListener('click', function() {
                    adminSelectUser.value = '';
                    adminEditUserForm.style.display = 'none';
                });
            }

            adminEditUserForm.addEventListener('submit', function(e) {
                e.preventDefault();
                saveUserEditBtn.disabled = true;
                saveUserEditBtn.textContent = 'Saving...';

                const fd = new FormData();
                fd.append('action', 'admin_edit_user');
                fd.append('user_id', editTargetUserId.value);
                fd.append('user_name', editTargetUserName.value.trim());
                fd.append('user_email', editTargetUserEmail.value.trim());
                fd.append('user_role', editTargetUserRole.value);

                fetch('/purr-and-pour/functions/update_profile.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(data => {
                    saveUserEditBtn.disabled = false;
                    saveUserEditBtn.textContent = 'Update Account';
                    if (data.success) {
                        window.showSidebarToast(data.message, true);
                        const selOpt = adminSelectUser.options[adminSelectUser.selectedIndex];
                        if (selOpt) {
                            selOpt.setAttribute('data-name', editTargetUserName.value);
                            selOpt.setAttribute('data-email', editTargetUserEmail.value);
                            selOpt.setAttribute('data-role', editTargetUserRole.value);
                            selOpt.textContent = '#' + editTargetUserId.value + ' - ' + editTargetUserName.value + ' (' + editTargetUserEmail.value + ') [' + editTargetUserRole.value + ']';
                        }
                    } else {
                        window.showSidebarToast(data.message || 'Failed to update account', false);
                    }
                })
                .catch(() => {
                    saveUserEditBtn.disabled = false;
                    saveUserEditBtn.textContent = 'Update Account';
                    window.showSidebarToast('Network error updating account', false);
                });
            });
        }
    }

    // Global helper for Admin approval/rejection from sidebar
    window.updateSidebarOrderStatus = function(orderId, newStatus) {
        if (!confirm(`Are you sure you want to mark Order #${orderId} as '${newStatus}'?`)) {
            return;
        }

        const fd = new FormData();
        fd.append('action', 'admin_update_order_status');
        fd.append('order_id', orderId);
        fd.append('status', newStatus);

        fetch('/purr-and-pour/functions/update_profile.php', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (window.showSidebarToast) {
                    window.showSidebarToast(data.message, true);
                }
                const card = document.getElementById('adminOrderCard_' + orderId);
                if (card) {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        card.remove();
                        const list = document.querySelector('.sidebar-orders-list');
                        if (list && list.children.length === 0) {
                            list.innerHTML = `<div class="sidebar-empty-state"><span>🎉</span><p>All orders are processed! No pending orders awaiting approval right now.</p></div>`;
                        }
                    }, 300);
                }
            } else {
                if (window.showSidebarToast) window.showSidebarToast(data.message || 'Error updating order', false);
            }
        })
        .catch(() => {
            if (window.showSidebarToast) window.showSidebarToast('Network error while updating order', false);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebar);
    } else {
        initSidebar();
    }
})();
</script>
