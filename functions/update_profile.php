<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to continue.']);
    exit();
}

require_once __DIR__ . '/../database/db.php';
$user_id = intval($_SESSION["user_id"]);
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Check admin role
$user_check = mysqli_query($conn, "SELECT role FROM users WHERE id = $user_id");
$user_role_row = mysqli_fetch_assoc($user_check);
$is_admin = ($user_role_row && $user_role_row['role'] === 'admin');

if ($action === 'update_name') {
    $newName = trim($_POST['name'] ?? '');
    if (empty($newName)) {
        echo json_encode(['success' => false, 'message' => 'Display name cannot be empty.']);
        exit();
    }
    if (mb_strlen($newName) > 50) {
        echo json_encode(['success' => false, 'message' => 'Display name must be 50 characters or less.']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
    $stmt->bind_param("si", $newName, $user_id);
    if ($stmt->execute()) {
        $_SESSION["name"] = $newName;
        echo json_encode([
            'success' => true,
            'message' => 'Display name updated successfully.',
            'name' => htmlspecialchars($newName)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error while updating name.']);
    }
    $stmt->close();
    exit();
}

if ($action === 'upload_avatar') {
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Please select a valid image file to upload.']);
        exit();
    }

    $file = $_FILES['avatar'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $file_info = @getimagesize($file['tmp_name']);

    if (!$file_info || !in_array($file_info['mime'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, WEBP, or GIF images are allowed.']);
        exit();
    }

    if ($file['size'] > 4 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Image size must be less than 4MB.']);
        exit();
    }

    $ext_map = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];
    $ext = $ext_map[$file_info['mime']] ?? 'jpg';
    $filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
    $targetDir = __DIR__ . '/../uploads/avatars/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $targetPath = $targetDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $avatarUrl = '/purr-and-pour/uploads/avatars/' . $filename;
        $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->bind_param("si", $avatarUrl, $user_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION["avatar"] = $avatarUrl;

        echo json_encode([
            'success' => true,
            'message' => 'Profile picture updated successfully.',
            'avatar_url' => $avatarUrl
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
    }
    exit();
}

if ($action === 'update_notifications') {
    $notif_email = isset($_POST['notif_email']) && ($_POST['notif_email'] === '1' || $_POST['notif_email'] === true || $_POST['notif_email'] === 'true') ? 1 : 0;
    $notif_site = isset($_POST['notif_site']) && ($_POST['notif_site'] === '1' || $_POST['notif_site'] === true || $_POST['notif_site'] === 'true') ? 1 : 0;

    $stmt = $conn->prepare("UPDATE users SET notif_email = ?, notif_site = ? WHERE id = ?");
    $stmt->bind_param("iii", $notif_email, $notif_site, $user_id);
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification preferences saved successfully.',
            'notif_email' => $notif_email,
            'notif_site' => $notif_site
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error while saving notifications.']);
    }
    $stmt->close();
    exit();
}

// Admin: Quick Order Status update directly from sidebar
if ($action === 'admin_update_order_status') {
    if (!$is_admin) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Only administrators can update orders.']);
        exit();
    }
    $order_id = intval($_POST['order_id'] ?? 0);
    $new_status = trim($_POST['status'] ?? 'Approved');
    $allowed_statuses = ['Approved', 'Cancelled', 'Pending', 'Completed'];
    if ($order_id <= 0 || !in_array($new_status, $allowed_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid order or status.']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => "Order #$order_id updated to '$new_status'.",
            'order_id' => $order_id,
            'status' => $new_status
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error updating order status.']);
    }
    $stmt->close();
    exit();
}

// Admin: Update Help & Support contact info
if ($action === 'admin_update_support_info') {
    if (!$is_admin) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Only administrators can update contact information.']);
        exit();
    }

    $email = trim($_POST['support_email'] ?? '');
    $phone = trim($_POST['support_phone'] ?? '');
    $hours = trim($_POST['support_hours'] ?? '');

    if (empty($email) || empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Email and Phone cannot be blank.']);
        exit();
    }

    $settings = [
        'support_email' => $email,
        'support_phone' => $phone,
        'support_hours' => $hours
    ];

    $stmt = $conn->prepare("INSERT INTO cafe_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($settings as $k => $v) {
        $stmt->bind_param("ss", $k, $v);
        $stmt->execute();
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Support contact details updated successfully.',
        'support_email' => htmlspecialchars($email),
        'support_phone' => htmlspecialchars($phone),
        'support_hours' => htmlspecialchars($hours)
    ]);
    exit();
}

// Admin: Edit a registered user account from the sidebar Help & Support manager
if ($action === 'admin_edit_user') {
    if (!$is_admin) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Only administrators can edit accounts.']);
        exit();
    }
    $target_uid = intval($_POST['user_id'] ?? 0);
    $target_name = trim($_POST['user_name'] ?? '');
    $target_email = trim($_POST['user_email'] ?? '');
    $target_role = trim($_POST['user_role'] ?? 'customer');

    if ($target_uid <= 0 || empty($target_name) || empty($target_email)) {
        echo json_encode(['success' => false, 'message' => 'Please provide valid account details.']);
        exit();
    }

    // Prevent demoting self from admin
    if ($target_uid === $user_id && $target_role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'You cannot remove your own admin privileges.']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
    $stmt->bind_param("sssi", $target_name, $target_email, $target_role, $target_uid);
    if ($stmt->execute()) {
        if ($target_uid === $user_id) {
            $_SESSION["name"] = $target_name;
            $_SESSION["email"] = $target_email;
        }
        echo json_encode([
            'success' => true,
            'message' => "Account #$target_uid updated successfully."
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error updating user account.']);
    }
    $stmt->close();
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
exit();
