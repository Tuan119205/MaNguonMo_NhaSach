<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/functions/database_functions.php';
$conn = db_connect();

function chatbot_json($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
function chatbot_role() {
    if (!empty($_SESSION['admin'])) return 'ADMIN';
    if (!empty($_SESSION['user']) && isset($_SESSION['userid'])) return 'USER';
    return 'GUEST';
}
function chatbot_setting($conn, $field, $default = 1) {
    $allowed = ['allow_user', 'allow_guest', 'allow_admin'];
    if (!in_array($field, $allowed, true)) return $default;
    $result = mysqli_query($conn, "SELECT {$field} FROM chatbot_settings WHERE id = 1 LIMIT 1");
    $row = $result ? mysqli_fetch_assoc($result) : null;
    return $row ? (int)$row[$field] : $default;
}
function chatbot_money($value) { return number_format((float)$value, 0, ',', '.') . 'đ'; }
function chatbot_like($value) { return '%' . $value . '%'; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') chatbot_json(['success' => false, 'message' => 'Phương thức không hợp lệ.'], 405);
$message = trim((string)($_POST['message'] ?? ''));
$context = trim((string)($_POST['context'] ?? ''));
if ($message === '' || mb_strlen($message) > 1000) chatbot_json(['success' => false, 'message' => 'Vui lòng nhập câu hỏi ngắn hơn.'], 422);

$role = chatbot_role();
$permissionField = $role === 'USER' ? 'allow_user' : 'allow_guest';
if ($role !== 'ADMIN' && !chatbot_setting($conn, $permissionField, 1)) chatbot_json(['success' => false, 'message' => 'Chatbot hiện đang tạm tắt cho tài khoản của bạn.'], 403);

$text = mb_strtolower($message, 'UTF-8');
$response = '';

$knowledgeStmt = mysqli_prepare($conn, "SELECT answer FROM chatbot_knowledge WHERE status = 1 AND (question LIKE ? OR answer LIKE ?) ORDER BY id DESC LIMIT 1");
if ($knowledgeStmt) {
    $needle = chatbot_like($message);
    mysqli_stmt_bind_param($knowledgeStmt, 'ss', $needle, $needle);
    mysqli_stmt_execute($knowledgeStmt);
    $knowledgeResult = mysqli_stmt_get_result($knowledgeStmt);
    $knowledge = $knowledgeResult ? mysqli_fetch_assoc($knowledgeResult) : null;
    if ($knowledge) $response = $knowledge['answer'];
}

if ($response === '' && preg_match('/(đơn hàng|don hang|order|giao|trạng thái đơn|trang thai don)/u', $text)) {
    if ($role === 'GUEST') {
        $response = 'Bạn cần đăng nhập để tôi kiểm tra đơn hàng của bạn.';
    } elseif ($role === 'ADMIN') {
        $result = mysqli_query($conn, "SELECT COUNT(*) total, COALESCE(SUM(amount),0) revenue FROM orders WHERE DATE(date) = CURDATE() AND order_status NOT IN ('đã_hủy','cancelled','đã hủy')");
        $row = $result ? mysqli_fetch_assoc($result) : null;
        $response = $row ? 'Hôm nay có ' . (int)$row['total'] . ' đơn hợp lệ, doanh thu ' . chatbot_money($row['revenue']) . '.' : 'Chưa có dữ liệu đơn hàng hôm nay.';
    } else {
        $userid = (int)$_SESSION['userid'];
        $stmt = mysqli_prepare($conn, "SELECT orderid, amount, order_status, date FROM orders WHERE customerid = ? ORDER BY date DESC, orderid DESC LIMIT 3");
        mysqli_stmt_bind_param($stmt, 'i', $userid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $items = [];
        while ($result && ($row = mysqli_fetch_assoc($result))) $items[] = '#' . $row['orderid'] . ' - ' . ($row['order_status'] ?: 'Chờ xử lý') . ' - ' . chatbot_money($row['amount']);
        $response = $items ? "Các đơn gần đây của bạn:\n" . implode("\n", $items) : 'Tôi chưa tìm thấy đơn hàng nào của bạn.';
    }
}

if ($response === '' && $role === 'ADMIN' && preg_match('/(doanh thu|thống kê|thong ke|bao nhiêu đơn|bao nhieu don|khách hàng|khach hang|bán chạy|ban chay)/u', $text)) {
    $orders = mysqli_query($conn, "SELECT COUNT(*) total, COALESCE(SUM(amount),0) revenue FROM orders WHERE MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE()) AND order_status NOT IN ('đã_hủy','cancelled','đã hủy')");
    $users = mysqli_query($conn, "SELECT COUNT(*) total FROM users");
    $o = $orders ? mysqli_fetch_assoc($orders) : null;
    $u = $users ? mysqli_fetch_assoc($users) : null;
    $response = 'Tháng này: ' . (int)($o['total'] ?? 0) . ' đơn hợp lệ, doanh thu ' . chatbot_money($o['revenue'] ?? 0) . ', ' . (int)($u['total'] ?? 0) . ' tài khoản người dùng.';
}

if ($response === '' && preg_match('/(khuyến mãi|khuyen mai|mã giảm|ma giam|giảm giá|giam gia)/u', $text)) {
    $result = mysqli_query($conn, "SELECT code, name, type, value, expires_at FROM promotions WHERE active = 1 AND expires_at >= CURDATE() ORDER BY id DESC LIMIT 5");
    $items = [];
    while ($result && ($row = mysqli_fetch_assoc($result))) $items[] = $row['code'] . ' - ' . $row['name'];
    $response = $items ? "Khuyến mãi đang áp dụng:\n" . implode("\n", $items) : 'Hiện chưa có chương trình khuyến mãi đang áp dụng.';
}

if ($response === '' && preg_match('/(giao hàng|giao hang)/u', $text)) {
    $response = 'Nhà sách Việt Long hỗ trợ giao hàng toàn quốc. Thời gian và phí giao hàng có thể thay đổi theo khu vực nhận hàng.';
}
if ($response === '' && preg_match('/(đổi trả|doi tra|bảo hành|bao hanh)/u', $text)) {
    $response = 'Bạn vui lòng giữ lại hóa đơn và liên hệ Nhà sách Việt Long để được hỗ trợ đổi trả hoặc bảo hành theo tình trạng sản phẩm.';
}
if ($response === '' && preg_match('/(thanh toán|thanh toan)/u', $text)) {
    $response = 'Website hiện hỗ trợ thanh toán khi nhận hàng và các phương thức thanh toán được hiển thị tại bước thanh toán.';
}
if ($response === '' && preg_match('/(liên hệ|lien he|chính sách|chinh sach|thông tin cửa hàng|thong tin cua hang)/u', $text)) {
    $response = 'Nhà sách Việt Long hỗ trợ tư vấn sản phẩm, đơn hàng, khuyến mãi, giao hàng và đổi trả. Bạn hãy chọn một chủ đề hoặc nhập câu hỏi cụ thể.';
}

if ($response === '' && preg_match('/(giá|gia|còn hàng|con hang|tồn kho|ton kho|tìm|tim|sách|sach|sản phẩm|san pham)/u', $text)) {
    $search = trim(preg_replace('/^(cho tôi|cho toi|tìm|tim|giá|gia|sách|sach)\s+/u', '', $message));
    if ($context !== '' && preg_match('/^\d[\d-]+$/', $context)) {
        $contextStmt = mysqli_prepare($conn, 'SELECT book_title, book_author, book_price, inventory FROM books WHERE book_isbn = ? LIMIT 1');
        mysqli_stmt_bind_param($contextStmt, 's', $context);
        mysqli_stmt_execute($contextStmt);
        $contextResult = mysqli_stmt_get_result($contextStmt);
        $contextBook = $contextResult ? mysqli_fetch_assoc($contextResult) : null;
        if ($contextBook) {
            $response = $contextBook['book_title'] . ' - ' . chatbot_money($contextBook['book_price']) . ' - ' . ((int)($contextBook['inventory'] ?? 0) > 0 ? 'Còn hàng' : 'Hết hàng');
        }
    }
    if ($response !== '') {
        $search = '';
    } elseif ($search === '' && $context !== '' && !preg_match('/^\d[\d-]+$/', $context)) {
        $search = $context;
    }
    $search = mb_substr($search, 0, 100);
    if ($response !== '') {
        // Đã trả lời theo sản phẩm hiện tại, không cần truy vấn tìm kiếm thêm.
    } else {
    $stmt = mysqli_prepare($conn, "SELECT book_title, book_author, book_price, inventory FROM books WHERE book_title LIKE ? OR book_author LIKE ? LIMIT 5");
    $like = chatbot_like($search);
    mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $items = [];
    while ($result && ($row = mysqli_fetch_assoc($result))) $items[] = $row['book_title'] . ' - ' . chatbot_money($row['book_price']) . ' - ' . ((int)($row['inventory'] ?? 0) > 0 ? 'Còn hàng' : 'Hết hàng');
    $response = $items ? implode("\n", $items) : 'Tôi chưa tìm thấy sản phẩm phù hợp trong dữ liệu cửa hàng.';
    }
}

if ($response === '') $response = 'Xin lỗi, tôi chưa hiểu câu hỏi này. Bạn có thể hỏi về sản phẩm, đơn hàng, khuyến mãi hoặc chính sách của cửa hàng.';

$sessionKey = session_id();
$userid = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : null;
$logSql = 'INSERT INTO chatbot_conversations (session_key, userid, role, message, response, page_context) VALUES (' . implode(', ', array_fill(0, 6, '?')) . ')';
$logStmt = mysqli_prepare($conn, $logSql);
if ($logStmt) {
    mysqli_stmt_bind_param($logStmt, 'sissss', $sessionKey, $userid, $role, $message, $response, $context);
    mysqli_stmt_execute($logStmt);
}
chatbot_json(['success' => true, 'message' => $response, 'role' => $role]);
