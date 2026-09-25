<?php
session_start();
require_once './functions/admin.php';
require_once './functions/database_functions.php';
require_once './db_migration.php';
runDatabaseMigrations();
$conn = db_connect();
$title = 'Quản lý Chatbot';
$notice = '';
$error = '';
$editKnowledge = null;
function chatbot_admin_bind_params($stmt, $types, &$params) {
    $refs = [$types];
    foreach ($params as $key => &$value) $refs[] = &$value;
    return call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $refs));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'settings') {
        $allowUser = isset($_POST['allow_user']) ? 1 : 0;
        $allowGuest = isset($_POST['allow_guest']) ? 1 : 0;
        $stmt = mysqli_prepare($conn, 'UPDATE chatbot_settings SET allow_user = ?, allow_guest = ? WHERE id = 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ii', $allowUser, $allowGuest);
            if (mysqli_stmt_execute($stmt)) $notice = 'Đã lưu cấu hình chatbot.'; else $error = 'Không thể lưu cấu hình.';
        }
    } elseif ($action === 'knowledge') {
        $knowledgeId = (int)($_POST['id'] ?? 0);
        $question = trim($_POST['question'] ?? '');
        $answer = trim($_POST['answer'] ?? '');
        $category = trim($_POST['category'] ?? 'general');
        if ($question === '' || $answer === '') $error = 'Câu hỏi và câu trả lời không được để trống.';
        elseif ($knowledgeId > 0) {
            $stmt = mysqli_prepare($conn, 'UPDATE chatbot_knowledge SET question = ?, answer = ?, category = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'sssi', $question, $answer, $category, $knowledgeId);
            if (mysqli_stmt_execute($stmt)) $notice = 'Đã cập nhật kiến thức chatbot.'; else $error = 'Không thể cập nhật kiến thức.';
        } else {
            $knowledgeStatus = 1;
            $knowledgeSql = 'INSERT INTO chatbot_knowledge (question, answer, category, status) VALUES (' . implode(', ', array_fill(0, 4, '?')) . ')';
            $stmt = mysqli_prepare($conn, $knowledgeSql);
            mysqli_stmt_bind_param($stmt, 'sssi', $question, $answer, $category, $knowledgeStatus);
            if (mysqli_stmt_execute($stmt)) $notice = 'Đã thêm kiến thức chatbot.'; else $error = 'Không thể thêm kiến thức.';
        }
    } elseif ($action === 'toggle_knowledge' || $action === 'delete_knowledge') {
        $knowledgeId = (int)($_POST['id'] ?? 0);
        if ($knowledgeId > 0) {
            if ($action === 'delete_knowledge') {
                $stmt = mysqli_prepare($conn, 'DELETE FROM chatbot_knowledge WHERE id = ?');
                mysqli_stmt_bind_param($stmt, 'i', $knowledgeId);
                $notice = mysqli_stmt_execute($stmt) ? 'Đã xóa kiến thức chatbot.' : 'Không thể xóa kiến thức.';
            } else {
                $stmt = mysqli_prepare($conn, 'UPDATE chatbot_knowledge SET status = 1 - status WHERE id = ?');
                mysqli_stmt_bind_param($stmt, 'i', $knowledgeId);
                $notice = mysqli_stmt_execute($stmt) ? 'Đã cập nhật trạng thái kiến thức.' : 'Không thể cập nhật trạng thái.';
            }
        }
    }
}
if (isset($_GET['edit_knowledge'])) {
    $editId = (int)$_GET['edit_knowledge'];
    $editStmt = mysqli_prepare($conn, 'SELECT id, question, answer, category FROM chatbot_knowledge WHERE id = ? LIMIT 1');
    if ($editStmt) {
        mysqli_stmt_bind_param($editStmt, 'i', $editId);
        mysqli_stmt_execute($editStmt);
        $editResult = mysqli_stmt_get_result($editStmt);
        $editKnowledge = $editResult ? mysqli_fetch_assoc($editResult) : null;
    }
}
$settingsResult = mysqli_query($conn, 'SELECT * FROM chatbot_settings WHERE id = 1 LIMIT 1');
$settings = $settingsResult ? mysqli_fetch_assoc($settingsResult) : ['bot_name' => 'Trợ lý Việt Long', 'allow_user' => 1, 'allow_guest' => 1, 'allow_admin' => 1];
$knowledgeSearch = trim($_GET['knowledge_q'] ?? '');
$knowledgeCategory = trim($_GET['knowledge_category'] ?? '');
$knowledgeStatusFilter = $_GET['knowledge_status'] ?? '';
if (!in_array($knowledgeStatusFilter, ['', '1', '0'], true)) $knowledgeStatusFilter = '';
$knowledgePage = max(1, (int)($_GET['knowledge_page'] ?? 1));
$knowledgePerPage = 10;
$viewUserId = max(0, (int)($_GET['view_user'] ?? 0));
$viewSessionKey = trim((string)($_GET['chat_session'] ?? ''));
$viewCustomer = null;
$viewSessions = null;
$viewMessages = null;
$viewError = '';
if ($viewUserId > 0) {
    $viewUserStmt = mysqli_prepare($conn, "SELECT userid, fullname, username, email FROM users WHERE userid = ? LIMIT 1");
    if (!$viewUserStmt) {
        $viewError = 'Không thể chuẩn bị dữ liệu khách hàng.';
    } else {
        mysqli_stmt_bind_param($viewUserStmt, 'i', $viewUserId);
        mysqli_stmt_execute($viewUserStmt);
        $viewUserResult = mysqli_stmt_get_result($viewUserStmt);
        $viewCustomer = $viewUserResult ? mysqli_fetch_assoc($viewUserResult) : null;
        if (!$viewCustomer) $viewError = 'Không tìm thấy khách hàng.';
    }
    if ($viewCustomer) {
        $viewSessionsStmt = mysqli_prepare($conn, "SELECT session_key, COUNT(*) AS message_count, MIN(created_at) AS started_at, MAX(created_at) AS last_message FROM chatbot_conversations WHERE userid = ? GROUP BY session_key ORDER BY last_message DESC");
        if ($viewSessionsStmt) {
            mysqli_stmt_bind_param($viewSessionsStmt, 'i', $viewUserId);
            mysqli_stmt_execute($viewSessionsStmt);
            $viewSessions = mysqli_stmt_get_result($viewSessionsStmt);
        } else $viewError = 'Không thể tải danh sách phiên hội thoại.';
        if ($viewSessionKey !== '') {
            $viewMessageStmt = mysqli_prepare($conn, "SELECT role, message, response, page_context, created_at FROM chatbot_conversations WHERE userid = ? AND session_key = ? ORDER BY created_at ASC, id ASC");
            if ($viewMessageStmt) {
                mysqli_stmt_bind_param($viewMessageStmt, 'is', $viewUserId, $viewSessionKey);
                mysqli_stmt_execute($viewMessageStmt);
                $viewMessages = mysqli_stmt_get_result($viewMessageStmt);
                if (!$viewMessages || mysqli_num_rows($viewMessages) === 0) $viewError = 'Không tìm thấy phiên hội thoại thuộc khách hàng này.';
            } else $viewError = 'Không thể tải chi tiết hội thoại.';
        }
    }
}
$knowledgeLike = '%' . $knowledgeSearch . '%';
$knowledgeWhere = '(question LIKE ? OR answer LIKE ? OR category LIKE ?)';
$knowledgeTypes = 'sss';
$knowledgeParams = [$knowledgeLike, $knowledgeLike, $knowledgeLike];
if ($knowledgeCategory !== '') { $knowledgeWhere .= ' AND category = ?'; $knowledgeTypes .= 's'; $knowledgeParams[] = $knowledgeCategory; }
if ($knowledgeStatusFilter !== '') { $knowledgeWhere .= ' AND status = ?'; $knowledgeTypes .= 'i'; $knowledgeParams[] = (int)$knowledgeStatusFilter; }
$knowledgeCountStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM chatbot_knowledge WHERE $knowledgeWhere");
if ($knowledgeCountStmt) {
    chatbot_admin_bind_params($knowledgeCountStmt, $knowledgeTypes, $knowledgeParams);
    mysqli_stmt_execute($knowledgeCountStmt);
    $knowledgeTotal = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($knowledgeCountStmt))['total'] ?? 0);
} else $knowledgeTotal = 0;
$knowledgeTotalPages = max(1, (int)ceil($knowledgeTotal / $knowledgePerPage));
$knowledgePage = min($knowledgePage, $knowledgeTotalPages);
$knowledgeOffset = ($knowledgePage - 1) * $knowledgePerPage;
$knowledgeStmt = mysqli_prepare($conn, "SELECT * FROM chatbot_knowledge WHERE $knowledgeWhere ORDER BY id DESC LIMIT ? OFFSET ?");
$knowledgeTypes .= 'ii'; $knowledgeParams[] = $knowledgePerPage; $knowledgeParams[] = $knowledgeOffset;
if ($knowledgeStmt) {
    chatbot_admin_bind_params($knowledgeStmt, $knowledgeTypes, $knowledgeParams);
    mysqli_stmt_execute($knowledgeStmt);
    $knowledgeResult = mysqli_stmt_get_result($knowledgeStmt);
} else $knowledgeResult = null;
$categoryResult = mysqli_query($conn, 'SELECT DISTINCT category FROM chatbot_knowledge WHERE category <> \'\' ORDER BY category');
$conversationCountResult = mysqli_query($conn, 'SELECT COUNT(DISTINCT session_key) total FROM chatbot_conversations');
$todayCountResult = mysqli_query($conn, 'SELECT COUNT(*) total FROM chatbot_conversations WHERE DATE(created_at) = CURDATE()');
$customerSearch = trim($_GET['customer_q'] ?? '');
$customerLike = '%' . $customerSearch . '%';
$customerStmt = mysqli_prepare($conn, "SELECT c.userid, COALESCE(u.fullname, CONCAT('User #', c.userid)) AS customer_name, COUNT(*) AS message_count, COUNT(DISTINCT c.session_key) AS conversation_count, MAX(c.created_at) AS last_message FROM chatbot_conversations c LEFT JOIN users u ON u.userid = c.userid WHERE c.userid IS NOT NULL AND (u.fullname LIKE ? OR u.username LIKE ? OR CAST(c.userid AS CHAR) LIKE ?) GROUP BY c.userid, u.fullname ORDER BY last_message DESC LIMIT 100");
if ($customerStmt) {
    mysqli_stmt_bind_param($customerStmt, 'sss', $customerLike, $customerLike, $customerLike);
    mysqli_stmt_execute($customerStmt);
    $customerResult = mysqli_stmt_get_result($customerStmt);
} else $customerResult = null;
$conversationCount = $conversationCountResult ? (int)mysqli_fetch_assoc($conversationCountResult)['total'] : 0;
$todayCount = $todayCountResult ? (int)mysqli_fetch_assoc($todayCountResult)['total'] : 0;
require './template/header.php';
require_once './template/admin_layout.php';
admin_layout_start('admin_chatbot', '', '');
?>
<div class="admin-chatbot-page">
  <?php if ($notice): ?><div class="alert alert-success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <section class="chatbot-hero"><div class="chatbot-hero-icon"><i class="fa fa-robot"></i></div><div><span class="chatbot-kicker" style="color:#f0b90b">TRUNG TÂM ĐIỀU KHIỂN</span><h2>Trợ lý Việt Long</h2><p>Quản lý cấu hình, kiến thức và theo dõi khách hàng đang trò chuyện.</p></div><div class="chatbot-live"><i></i> Đang hoạt động</div></section>
  <section class="admin-chatbot-stats">
    <div class="chatbot-stat"><span class="chatbot-stat-icon"><i class="fa fa-comments"></i></span><div><small>Cuộc hội thoại</small><strong><?= number_format($conversationCount) ?></strong><em>Tổng phiên đã ghi nhận</em></div></div>
    <div class="chatbot-stat"><span class="chatbot-stat-icon"><i class="fa fa-message"></i></span><div><small>Tin nhắn hôm nay</small><strong><?= number_format($todayCount) ?></strong><em>Cập nhật theo thời gian thực</em></div></div>
    <div class="chatbot-stat"><span class="chatbot-stat-icon"><i class="fa fa-shield-halved"></i></span><div><small>Phân quyền</small><strong>2 nhóm</strong><em>Khách · Người dùng</em></div></div>
  </section>
  <section class="admin-panel mb-4"><div class="chatbot-section-heading"><div><h2>Quyền sử dụng chatbot</h2><p>Chọn nhóm tài khoản được phép sử dụng trợ lý.</p></div><i class="fa fa-sliders text-primary"></i></div><form method="post"><input type="hidden" name="action" value="settings"><div class="chatbot-permissions chatbot-permissions-two"><label><input type="checkbox" name="allow_guest" <?= $settings['allow_guest'] ? 'checked' : '' ?>><span><b>Khách truy cập</b><small>Hỏi sản phẩm, giá và khuyến mãi.</small></span></label><label><input type="checkbox" name="allow_user" <?= $settings['allow_user'] ? 'checked' : '' ?>><span><b>Người dùng</b><small>Hỏi sản phẩm và đơn hàng cá nhân.</small></span></label></div><button class="chatbot-primary mt-3"><i class="fa fa-save"></i> Lưu cấu hình</button></form></section>
  <section class="admin-panel mb-4"><div class="chatbot-section-heading"><div><h2>Quản lý kiến thức chatbot</h2><p>Thêm câu trả lời tự động cho các câu hỏi thường gặp.</p></div><i class="fa fa-lightbulb text-primary"></i></div><details class="chatbot-knowledge-editor" <?= $editKnowledge ? 'open' : '' ?>><summary class="chatbot-add-button"><i class="fa fa-plus"></i> <?= $editKnowledge ? 'Chỉnh sửa kiến thức' : 'Thêm kiến thức' ?></summary><form method="post" class="chatbot-knowledge-form"><input type="hidden" name="action" value="knowledge"><input type="hidden" name="id" value="<?= (int)($editKnowledge['id'] ?? 0) ?>"><label><span>Câu hỏi</span><input name="question" value="<?= htmlspecialchars($editKnowledge['question'] ?? '') ?>" placeholder="Ví dụ: Shop có giao hàng toàn quốc không?" required></label><label><span>Câu trả lời</span><textarea name="answer" rows="2" placeholder="Nội dung chatbot sẽ trả lời..." required><?= htmlspecialchars($editKnowledge['answer'] ?? '') ?></textarea></label><label><span>Danh mục</span><input name="category" placeholder="shipping, payment..." value="<?= htmlspecialchars($editKnowledge['category'] ?? 'general') ?>"></label><div><button class="chatbot-primary"><?= $editKnowledge ? 'Cập nhật' : 'Lưu kiến thức' ?></button><?php if ($editKnowledge): ?><a href="admin_chatbot.php" class="chatbot-cancel-link">Hủy</a><?php endif; ?></div></form></details><div class="chatbot-filter"><form method="get"><input name="knowledge_q" placeholder="Tìm kiếm kiến thức..." value="<?= htmlspecialchars($knowledgeSearch) ?>"><select name="knowledge_category"><option value="">Danh mục: Tất cả</option><?php if ($categoryResult): while ($categoryRow = mysqli_fetch_assoc($categoryResult)): ?><option value="<?= htmlspecialchars($categoryRow['category']) ?>" <?= $knowledgeCategory === $categoryRow['category'] ? 'selected' : '' ?>><?= htmlspecialchars($categoryRow['category']) ?></option><?php endwhile; endif; ?></select><select name="knowledge_status"><option value="">Trạng thái: Tất cả</option><option value="1" <?= $knowledgeStatusFilter === '1' ? 'selected' : '' ?>>Đang dùng</option><option value="0" <?= $knowledgeStatusFilter === '0' ? 'selected' : '' ?>>Đang tắt</option></select><button type="submit">Lọc</button></form></div><div class="chatbot-table-wrap"><table class="chatbot-table"><thead><tr><th>Nội dung</th><th>Danh mục</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody><?php if ($knowledgeResult && mysqli_num_rows($knowledgeResult) > 0): while ($item = mysqli_fetch_assoc($knowledgeResult)): ?><tr><td><b><?= htmlspecialchars($item['question']) ?></b><small title="<?= htmlspecialchars($item['answer'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(mb_strimwidth($item['answer'], 0, 150, '…', 'UTF-8')) ?></small></td><td><?= htmlspecialchars($item['category']) ?></td><td><span class="chatbot-status <?= $item['status'] ? 'on' : 'off' ?>"><?= $item['status'] ? 'Đang dùng' : 'Đang tắt' ?></span></td><td class="chatbot-actions"><a href="?edit_knowledge=<?= (int)$item['id'] ?>">Sửa</a><form method="post"><input type="hidden" name="action" value="toggle_knowledge"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><button><?= $item['status'] ? 'Tắt' : 'Bật' ?></button></form><form method="post" onsubmit="return confirm('Xóa kiến thức này?')"><input type="hidden" name="action" value="delete_knowledge"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><button class="danger">Xóa</button></form></td></tr><?php endwhile; else: ?><tr><td colspan="4" class="chatbot-empty">Chưa có kiến thức nào.</td></tr><?php endif; ?></tbody></table></div><?php if ($knowledgeTotalPages > 1): ?><nav class="chatbot-pagination" aria-label="Phân trang kiến thức"><?php for ($pageNo = 1; $pageNo <= $knowledgeTotalPages; $pageNo++): ?><a class="<?= $pageNo === $knowledgePage ? 'active' : '' ?>" href="?knowledge_q=<?= urlencode($knowledgeSearch) ?>&knowledge_category=<?= urlencode($knowledgeCategory) ?>&knowledge_status=<?= urlencode($knowledgeStatusFilter) ?>&knowledge_page=<?= $pageNo ?>"><?= $pageNo ?></a><?php endfor; ?></nav><?php endif; ?></section>
  <section class="admin-panel mb-4"><div class="chatbot-section-heading"><div><h2>Khách hàng đã nhắn chatbot</h2><p>Bấm vào khách hàng để mở toàn bộ các phiên hội thoại.</p></div><i class="fa fa-users text-primary"></i></div><form method="get" class="customer-filter"><input name="customer_q" value="<?= htmlspecialchars($customerSearch) ?>" placeholder="Tìm theo tên, username hoặc User ID..."><button type="submit"><i class="fa fa-search"></i> Tìm</button></form><div class="chatbot-table-wrap"><table class="chatbot-table"><thead><tr><th>Khách hàng</th><th>User ID</th><th>Số tin nhắn</th><th>Số phiên</th><th>Lần nhắn gần nhất</th></tr></thead><tbody><?php if ($customerResult && mysqli_num_rows($customerResult) > 0): while ($customer = mysqli_fetch_assoc($customerResult)): ?><tr><td><a class="chatbot-customer-link" href="?view_user=<?= (int)$customer['userid'] ?>#chatbot-detail"><?= htmlspecialchars($customer['customer_name']) ?></a></td><td><?= (int)$customer['userid'] ?></td><td><?= (int)$customer['message_count'] ?></td><td><?= (int)$customer['conversation_count'] ?></td><td><?= htmlspecialchars($customer['last_message']) ?></td></tr><?php endwhile; else: ?><tr><td colspan="5" class="chatbot-empty">Chưa có User nào nhắn tin.</td></tr><?php endif; ?></tbody></table></div></section>
  <?php if ($viewUserId > 0): ?>
  <section id="chatbot-detail" class="admin-panel chatbot-detail-panel mb-4">
    <?php if ($viewError && !$viewCustomer): ?><div class="chatbot-empty-state"><i class="fa fa-user-slash"></i><strong><?= htmlspecialchars($viewError) ?></strong><a href="admin_chatbot.php">← Quay lại danh sách khách hàng</a></div>
    <?php elseif ($viewCustomer): ?>
      <div class="chatbot-detail-heading"><div><a class="chatbot-back-link" href="admin_chatbot.php#chatbot-detail">← Quay lại danh sách khách hàng</a><span class="chatbot-kicker">CHI TIẾT HỘI THOẠI</span><h2>Hội thoại của <?= htmlspecialchars($viewCustomer['fullname'] ?: $viewCustomer['username']) ?></h2><p>User ID: <?= (int)$viewCustomer['userid'] ?><?= $viewCustomer['email'] ? ' · ' . htmlspecialchars($viewCustomer['email']) : '' ?></p></div><i class="fa fa-comments chatbot-detail-icon"></i></div>
      <?php $detailMessageCount = 0; $detailSessionCount = 0; if ($viewSessions) { $detailSessionCount = mysqli_num_rows($viewSessions); while ($sessionRow = mysqli_fetch_assoc($viewSessions)) $detailMessageCount += (int)$sessionRow['message_count']; mysqli_data_seek($viewSessions, 0); } ?>
      <div class="chatbot-detail-summary"><div><small>Tổng tin nhắn</small><strong><?= $detailMessageCount ?></strong></div><div><small>Tổng phiên</small><strong><?= $detailSessionCount ?></strong></div><div><small>Trạng thái</small><strong class="text-success">Chỉ xem</strong></div></div>
      <?php if ($viewError && $viewSessionKey !== ''): ?><div class="alert alert-warning mt-3"><?= htmlspecialchars($viewError) ?></div><?php endif; ?>
      <div class="chatbot-session-list"><h3>Danh sách phiên hội thoại</h3><?php if ($viewSessions && mysqli_num_rows($viewSessions) > 0): while ($sessionRow = mysqli_fetch_assoc($viewSessions)): ?><article class="chatbot-session-card <?= ($viewSessionKey === $sessionRow['session_key']) ? 'selected' : '' ?>"><div><span class="chatbot-session-number"><i class="fa fa-comments"></i> Phiên hội thoại</span><strong><?= (int)$sessionRow['message_count'] ?> tin nhắn</strong><small><?= htmlspecialchars($sessionRow['started_at']) ?> → <?= htmlspecialchars($sessionRow['last_message']) ?></small></div><a class="chatbot-view-session" href="?view_user=<?= (int)$viewUserId ?>&chat_session=<?= urlencode($sessionRow['session_key']) ?>#chatbot-detail">Xem hội thoại <i class="fa fa-arrow-right"></i></a></article><?php endwhile; else: ?><div class="chatbot-empty-state"><i class="fa fa-comments"></i><strong>Khách hàng chưa có phiên hội thoại.</strong></div><?php endif; ?></div>
      <?php if ($viewSessionKey !== '' && $viewMessages && mysqli_num_rows($viewMessages) > 0): ?><div class="chatbot-conversation-view"><div class="chatbot-conversation-title"><h3>Toàn bộ tin nhắn trong phiên</h3><span><?= mysqli_num_rows($viewMessages) ?> lượt trao đổi · Cũ nhất trước</span></div><?php while ($chatRow = mysqli_fetch_assoc($viewMessages)): ?><div class="chat-message-row user-message"><div class="chat-message-avatar"><i class="fa fa-user"></i></div><div class="chat-message-bubble"><b>Khách hàng</b><p><?= nl2br(htmlspecialchars($chatRow['message'])) ?></p><time><?= htmlspecialchars($chatRow['created_at']) ?></time></div></div><div class="chat-message-row bot-message"><div class="chat-message-avatar"><i class="fa fa-robot"></i></div><div class="chat-message-bubble"><b>Trợ lý Việt Long</b><p><?= nl2br(htmlspecialchars($chatRow['response'])) ?></p><time><?= htmlspecialchars($chatRow['created_at']) ?> · <?= htmlspecialchars($chatRow['page_context'] ?: 'Không rõ trang') ?></time></div></div><?php endwhile; ?></div><?php endif; ?>
    <?php endif; ?>
  </section>
  <?php endif; ?>
</div>
<style>
.admin-topbar-admin_chatbot{display:none}.admin-chatbot-page{color:#172033}.admin-chatbot-page .admin-panel{border:1px solid #e2e8f0;box-shadow:0 5px 18px rgba(15,23,42,.05);border-radius:14px}.chatbot-hero{display:flex;align-items:center;gap:16px;padding:22px 24px;margin-bottom:18px;border-radius:16px;background:linear-gradient(135deg,#252b39,#343b4d);color:#fff}.chatbot-hero-icon{display:grid;place-items:center;width:54px;height:54px;border-radius:15px;background:#f0b90b;color:#20242b;font-size:24px}.chatbot-hero h2{margin:4px 0;font-size:23px}.chatbot-hero p{margin:0;color:#cbd5e1;font-size:13px}.chatbot-live{margin-left:auto;padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.1);font-size:12px}.chatbot-live i{display:inline-block;width:8px;height:8px;margin-right:6px;border-radius:50%;background:#4ade80}.admin-chatbot-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:18px}.chatbot-stat{display:flex;align-items:center;gap:13px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:17px;box-shadow:0 4px 14px rgba(15,23,42,.04)}.chatbot-stat small,.chatbot-stat strong,.chatbot-stat em{display:block}.chatbot-stat small{color:#64748b;font-size:11px}.chatbot-stat strong{font-size:23px;margin:3px 0}.chatbot-stat em{font-style:normal;color:#94a3b8;font-size:10px}.chatbot-stat-icon{display:grid;place-items:center;width:44px;height:44px;border-radius:12px;background:#eef2ff;color:#4f46e5;font-size:18px}.chatbot-section-heading{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:17px}.chatbot-section-heading h2{margin:3px 0;font-size:18px}.chatbot-section-heading p{margin:0;color:#64748b;font-size:12px}.chatbot-kicker{color:#4f46e5;font-size:10px;font-weight:800;letter-spacing:.14em}.chatbot-permissions{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:15px}.chatbot-permissions-two{grid-template-columns:repeat(2,minmax(0,1fr));max-width:760px}.chatbot-permissions label{display:flex;gap:8px;padding:11px;border:1px solid #e2e8f0;border-radius:10px;cursor:pointer}.chatbot-permissions small{display:block;color:#64748b;font-size:10px;margin-top:3px}.chatbot-primary{border:0;border-radius:8px;padding:10px 15px;background:#4f46e5;color:#fff;font-size:12px;font-weight:700;cursor:pointer}.chatbot-primary:hover{background:#4338ca}.chatbot-knowledge-form{display:grid;grid-template-columns:1fr 1.3fr .7fr auto;gap:10px;align-items:end}.chatbot-knowledge-form label span{display:block;margin-bottom:6px;color:#475569;font-size:11px;font-weight:700}.chatbot-knowledge-form input,.chatbot-knowledge-form textarea{width:100%;box-sizing:border-box;border:1px solid #dbe2ea;border-radius:8px;padding:10px;outline:0}.chatbot-knowledge-form textarea{resize:vertical}.chatbot-table-wrap{overflow-x:auto;margin-top:16px}.chatbot-table{width:100%;border-collapse:collapse;font-size:12px}.chatbot-table th{padding:10px 8px;text-align:left;color:#94a3b8;font-size:10px;text-transform:uppercase;border-bottom:1px solid #edf1f5}.chatbot-table td{padding:12px 8px;border-bottom:1px solid #f0f2f5;vertical-align:top}.chatbot-table td small{display:block;max-width:360px;margin-top:4px;color:#64748b;line-height:1.4}.chatbot-table tr:last-child td{border-bottom:0}.chatbot-status{display:inline-flex;padding:5px 8px;border-radius:999px;font-size:10px;font-weight:700}.chatbot-status.on{background:#ecfdf3;color:#15803d}.chatbot-status.off{background:#f1f5f9;color:#64748b}.chatbot-actions{display:flex;gap:8px;white-space:nowrap}.chatbot-actions a,.chatbot-actions button{border:0;background:none;color:#4f46e5;font-size:11px;cursor:pointer;text-decoration:none;padding:0}.chatbot-actions .danger{color:#dc2626}.chatbot-filter{margin-top:16px}.chatbot-filter form{display:flex;gap:8px;flex-wrap:wrap}.chatbot-filter input{flex:1;min-width:220px;border:1px solid #dbe2ea;border-radius:8px;padding:9px}.chatbot-filter select,.chatbot-filter button{border:1px solid #dbe2ea;border-radius:8px;background:#f8fafc;color:#475569;padding:0 12px;font-weight:700}.customer-filter{display:flex;gap:8px;margin:0 0 14px}.customer-filter input{flex:1;border:1px solid #dbe2ea;border-radius:8px;padding:9px}.customer-filter button{border:0;border-radius:8px;background:#f1f5f9;color:#475569;padding:0 14px;font-weight:700}.chatbot-add-button{display:inline-flex;align-items:center;gap:7px;cursor:pointer;padding:10px 14px;border-radius:8px;background:#4f46e5;color:#fff;font-size:12px;font-weight:700;list-style:none}.chatbot-add-button::-webkit-details-marker{display:none}.chatbot-knowledge-editor[open] .chatbot-add-button{margin-bottom:14px}.chatbot-pagination{display:flex;gap:5px;margin-top:16px}.chatbot-pagination a{display:grid;place-items:center;width:30px;height:30px;border-radius:7px;background:#f1f5f9;color:#475569;text-decoration:none;font-size:12px;font-weight:700}.chatbot-pagination a.active{background:#4f46e5;color:#fff}.chatbot-permissions-two{grid-template-columns:repeat(2,minmax(0,1fr));max-width:760px}.chatbot-knowledge-editor{margin-bottom:16px}.chatbot-add-button{display:inline-flex;align-items:center;gap:7px;list-style:none;cursor:pointer;padding:10px 14px;border-radius:8px;background:#4f46e5;color:#fff;font-size:12px;font-weight:700}.chatbot-add-button::-webkit-details-marker{display:none}.chatbot-knowledge-editor[open] .chatbot-add-button{margin-bottom:14px}.chatbot-filter form{display:flex;gap:8px;flex-wrap:wrap}.chatbot-filter select{border:1px solid #dbe2ea;border-radius:8px;padding:0 10px;background:#fff;color:#475569}.chatbot-pagination{display:flex;gap:5px;margin-top:16px}.chatbot-pagination a{display:grid;place-items:center;width:30px;height:30px;border-radius:7px;background:#f1f5f9;color:#475569;text-decoration:none;font-size:12px;font-weight:700}.chatbot-pagination a.active{background:#4f46e5;color:#fff}.customer-filter{display:flex;gap:8px;margin-bottom:14px}.customer-filter input{flex:1;border:1px solid #dbe2ea;border-radius:8px;padding:9px}.customer-filter button{border:0;border-radius:8px;background:#f1f5f9;color:#475569;padding:0 14px;font-weight:700}.chatbot-cancel-link{display:inline-block;margin-left:8px;color:#64748b;font-size:12px;text-decoration:none}.chatbot-customer-link{color:#4f46e5;text-decoration:none;font-weight:700}.chatbot-history-response{max-width:340px;color:#475569}.chatbot-role{display:inline-flex;padding:5px 8px;border-radius:999px;background:#eef2ff;color:#4338ca;font-size:10px;font-weight:700}.chatbot-role.guest{background:#f1f5f9;color:#64748b}.chatbot-empty{text-align:center;color:#94a3b8;padding:22px!important}.chatbot-detail-panel{scroll-margin-top:20px}.chatbot-detail-heading{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid #edf1f5;padding-bottom:16px}.chatbot-detail-heading h2{margin:5px 0;font-size:20px}.chatbot-detail-heading p{margin:0;color:#64748b;font-size:12px}.chatbot-back-link{display:block;margin-bottom:10px;color:#4f46e5;text-decoration:none;font-size:12px;font-weight:700}.chatbot-detail-icon{color:#4f46e5;font-size:24px}.chatbot-detail-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:16px 0}.chatbot-detail-summary>div{padding:12px 14px;border:1px solid #edf1f5;border-radius:10px;background:#f8fafc}.chatbot-detail-summary small,.chatbot-detail-summary strong{display:block}.chatbot-detail-summary small{color:#64748b;font-size:11px}.chatbot-detail-summary strong{margin-top:4px;font-size:18px}.chatbot-session-list h3,.chatbot-conversation-view h3{margin:18px 0 10px;font-size:15px}.chatbot-session-card{display:flex;justify-content:space-between;align-items:center;gap:14px;padding:14px;margin-top:8px;border:1px solid #e5e9f0;border-radius:10px;background:#fff}.chatbot-session-card.selected{border-color:#4f46e5;background:#f8faff}.chatbot-session-number,.chatbot-session-card strong,.chatbot-session-card small{display:block}.chatbot-session-number{color:#4f46e5;font-size:11px;font-weight:700}.chatbot-session-card strong{margin-top:5px;font-size:13px}.chatbot-session-card small{margin-top:4px;color:#94a3b8;font-size:11px}.chatbot-view-session{flex:0 0 auto;padding:8px 11px;border-radius:8px;background:#eef2ff;color:#4338ca;text-decoration:none;font-size:11px;font-weight:700}.chatbot-conversation-view{margin-top:18px;padding:16px;border:1px solid #e5e9f0;border-radius:12px;background:#f8fafc}.chatbot-conversation-title{display:flex;justify-content:space-between;align-items:center;gap:10px}.chatbot-conversation-title h3{margin:0}.chatbot-conversation-title span{color:#94a3b8;font-size:11px}.chat-message-row{display:flex;gap:9px;max-width:88%;margin-top:14px}.chat-message-row.bot-message{margin-left:auto;flex-direction:row-reverse}.chat-message-avatar{display:grid;place-items:center;flex:0 0 30px;width:30px;height:30px;border-radius:50%;background:#eef2ff;color:#4f46e5}.bot-message .chat-message-avatar{background:#fff7d6;color:#a16b00}.chat-message-bubble{padding:10px 12px;border:1px solid #e2e8f0;border-radius:12px;background:#fff}.bot-message .chat-message-bubble{background:#fffdf2;border-color:#f5e6a8}.chat-message-bubble b{display:block;color:#334155;font-size:11px}.chat-message-bubble p{margin:5px 0;white-space:normal;color:#172033;font-size:13px;line-height:1.45}.chat-message-bubble time{display:block;color:#94a3b8;font-size:10px}.chatbot-empty-state{display:flex;align-items:center;flex-direction:column;gap:8px;padding:26px;color:#94a3b8;text-align:center}.chatbot-empty-state i{font-size:25px;color:#cbd5e1}.chatbot-empty-state strong{color:#64748b;font-size:13px}.chatbot-empty-state a{color:#4f46e5;font-size:12px;text-decoration:none;font-weight:700}@media(max-width:900px){.chatbot-knowledge-form{grid-template-columns:1fr 1fr}.chatbot-knowledge-form label:nth-child(2){grid-column:span 2}}@media(max-width:700px){.admin-chatbot-stats,.chatbot-permissions,.chatbot-detail-summary{grid-template-columns:1fr}.chatbot-hero{padding:17px}.chatbot-live{display:none}.chatbot-knowledge-form{grid-template-columns:1fr}.chatbot-knowledge-form label:nth-child(2){grid-column:auto}.chatbot-table{min-width:700px}.chatbot-session-card,.chatbot-conversation-title{align-items:flex-start;flex-direction:column}.chatbot-view-session{width:100%;box-sizing:border-box;text-align:center}.chat-message-row{max-width:96%}.chatbot-conversation-view{padding:11px}}
/* Final admin chatbot visual polish */
.admin-chatbot-page{max-width:1180px;margin:0 auto;padding-bottom:28px}.admin-chatbot-page>.alert{border:0;border-radius:12px;padding:12px 16px;font-size:13px;box-shadow:0 4px 12px rgba(15,23,42,.04)}
.chatbot-hero{position:relative;overflow:hidden;padding:25px 28px;border:1px solid #30394c;box-shadow:0 12px 30px rgba(15,23,42,.12)}.chatbot-hero:after{content:'';position:absolute;right:-70px;top:-95px;width:250px;height:250px;border:38px solid rgba(240,185,11,.1);border-radius:50%;pointer-events:none}.chatbot-hero>div{position:relative;z-index:1}.chatbot-hero-icon{box-shadow:0 8px 18px rgba(240,185,11,.2)}.chatbot-hero h2{font-size:25px;letter-spacing:-.02em}.chatbot-hero p{font-size:12px}
.admin-chatbot-stats{grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.chatbot-stat{min-height:82px;padding:16px 18px}.chatbot-stat:hover{border-color:#c7d2fe;box-shadow:0 8px 20px rgba(79,70,229,.08)}.chatbot-stat-icon{width:42px;height:42px;flex:0 0 42px}.chatbot-stat strong{font-size:21px}
.admin-chatbot-page>.admin-panel{padding:24px;border-radius:14px;background:#fff}.chatbot-section-heading{padding-bottom:15px;border-bottom:1px solid #edf1f5}.chatbot-section-heading h2{font-size:19px;letter-spacing:-.01em}.chatbot-section-heading p{font-size:12px;margin-top:5px}.chatbot-section-heading>i{width:38px;height:38px;display:grid;place-items:center;border-radius:10px;background:#eef2ff;color:#4f46e5!important;font-size:16px}
.chatbot-permissions{gap:14px;margin-top:18px}.chatbot-permissions-two{max-width:none}.chatbot-permissions label{min-height:74px;align-items:flex-start;padding:15px;background:#fbfcff;border-color:#e5e7eb;transition:.18s ease}.chatbot-permissions label:hover{border-color:#a5b4fc;background:#f8faff}.chatbot-permissions input{accent-color:#4f46e5;margin-top:3px}.chatbot-permissions b{font-size:13px;color:#172033}.chatbot-permissions small{font-size:11px;line-height:1.45}
.chatbot-primary{box-shadow:0 5px 12px rgba(79,70,229,.18);padding:10px 16px}.chatbot-knowledge-editor{margin-top:4px}.chatbot-add-button{box-shadow:0 5px 12px rgba(79,70,229,.16)}.chatbot-knowledge-form{grid-template-columns:1fr 1.45fr .75fr auto;padding:17px;border:1px solid #e5e7eb;border-radius:12px;background:#f8fafc}.chatbot-knowledge-form input,.chatbot-knowledge-form textarea{background:#fff;border-color:#dfe4ec}.chatbot-filter{margin-top:18px}.chatbot-filter form{align-items:center}.chatbot-filter input{height:40px;box-sizing:border-box;background:#fff}.chatbot-filter select,.chatbot-filter button{height:40px}.chatbot-filter button{background:#20242b;color:#fff;border-color:#20242b;cursor:pointer}.chatbot-table-wrap{border:1px solid #edf1f5;border-radius:11px;margin-top:14px}.chatbot-table{background:#fff}.chatbot-table th{padding:12px 14px;background:#f8fafc;color:#64748b}.chatbot-table td{padding:14px;line-height:1.4}.chatbot-table tbody tr:hover{background:#fbfcff}.chatbot-table td:first-child{min-width:250px}.chatbot-table td small{font-size:11px;max-width:420px;color:#64748b}.chatbot-actions{gap:10px}.chatbot-actions a,.chatbot-actions button{font-weight:700}.chatbot-status{padding:6px 9px}.chatbot-pagination{padding:0 2px}.chatbot-pagination a{border:1px solid #e2e8f0;background:#fff}.chatbot-pagination a.active{border-color:#4f46e5}
.customer-filter{margin-top:2px}.customer-filter input{height:40px;box-sizing:border-box}.customer-filter button{height:40px;background:#20242b;color:#fff;cursor:pointer}.chatbot-customer-link{display:inline-flex;align-items:center;gap:7px;font-size:13px}.chatbot-customer-link:before{content:'\\f007';font-family:'Font Awesome 6 Free';font-weight:900;display:grid;place-items:center;width:26px;height:26px;border-radius:50%;background:#eef2ff;color:#4f46e5;font-size:11px}.chatbot-detail-panel{background:#fbfcff!important}.chatbot-detail-summary>div{background:#fff}.chatbot-session-card{background:#fff;transition:.18s ease}.chatbot-session-card:hover,.chatbot-session-card.selected{border-color:#a5b4fc;box-shadow:0 5px 14px rgba(79,70,229,.07)}
@media(max-width:900px){.chatbot-knowledge-form{grid-template-columns:1fr 1fr}.chatbot-knowledge-form label:nth-child(2){grid-column:span 2}}
@media(max-width:700px){.admin-chatbot-page>.admin-panel{padding:17px}.chatbot-hero{padding:19px}.chatbot-hero h2{font-size:21px}.admin-chatbot-stats{grid-template-columns:1fr}.chatbot-permissions-two{grid-template-columns:1fr}.chatbot-knowledge-form{grid-template-columns:1fr}.chatbot-knowledge-form label:nth-child(2){grid-column:auto}.chatbot-filter form,.customer-filter{align-items:stretch}.chatbot-filter input,.chatbot-filter select,.chatbot-filter button,.customer-filter input,.customer-filter button{width:100%;min-width:0}.chatbot-table{min-width:760px}}

/* Màu viền nhẹ để giao diện bớt trắng */
.admin-chatbot-page{background:#f8f7ff;padding:14px;border:1px solid #d9d2f2;border-radius:18px}
.admin-chatbot-page>.admin-panel,.chatbot-stat{border-color:#c9c1e8;background:#fffdfd}
.admin-chatbot-page>.admin-panel:hover,.chatbot-stat:hover{border-color:#a99bdd}
.chatbot-section-heading{border-bottom-color:#ded8f3}
.chatbot-table-wrap{border-color:#d5cdec}
.chatbot-table th{background:#f2efff;border-bottom-color:#d5cdec}
.chatbot-table td{border-bottom-color:#eeeaf8}
.chatbot-permissions label{border-color:#d5cdec;background:#fbfaff}
.chatbot-permissions label:hover{border-color:#a99bdd;background:#f4f1ff}
.chatbot-knowledge-form{border-color:#d5cdec;background:#f5f2ff}
.chatbot-knowledge-form input,.chatbot-knowledge-form textarea,.chatbot-filter input,.chatbot-filter select,.customer-filter input{border-color:#cfc6ea}
.chatbot-filter select,.chatbot-filter button,.customer-filter button{border-color:#cfc6ea;background:#f4f1ff}
.chatbot-detail-panel{background:#f6f3ff!important;border-color:#c9c1e8!important}
.chatbot-detail-summary>div,.chatbot-conversation-view{border-color:#d5cdec;background:#fbfaff}
.chatbot-session-card{border-color:#d5cdec;background:#fffdfd}
.chatbot-session-card:hover,.chatbot-session-card.selected{border-color:#9d8fe0;background:#f4f1ff}

/* Tô màu sáng hơn để phân biệt rõ từng khu vực */
.admin-chatbot-page{background:#f1efff;border-color:#bdb2ed}
.admin-chatbot-page>.admin-panel{background:#fff!important;border-color:#b9afe4!important;box-shadow:0 6px 18px rgba(91,74,170,.09)}
.admin-chatbot-page>.admin-panel:nth-of-type(2){background:#f0f7ff!important;border-color:#a9ccef!important}
.admin-chatbot-page>.admin-panel:nth-of-type(3){background:#fff8e8!important;border-color:#f0cd82!important}
.admin-chatbot-page>.admin-panel:nth-of-type(4){background:#edfff5!important;border-color:#a8d9ba!important}
.admin-chatbot-page>.admin-panel:nth-of-type(5){background:#fff0f6!important;border-color:#e8b4cb!important}
.admin-chatbot-stats .chatbot-stat:nth-child(1){background:#e9f3ff;border-color:#9fc7ee}
.admin-chatbot-stats .chatbot-stat:nth-child(2){background:#fff6df;border-color:#edca79}
.admin-chatbot-stats .chatbot-stat:nth-child(3){background:#eafbf2;border-color:#a7d9ba}
.admin-chatbot-stats .chatbot-stat-icon{background:#dbeafe}
.admin-chatbot-stats .chatbot-stat:nth-child(2) .chatbot-stat-icon{background:#ffedb5;color:#a16207}
.admin-chatbot-stats .chatbot-stat:nth-child(3) .chatbot-stat-icon{background:#c9f3d9;color:#15803d}
.chatbot-section-heading{border-bottom-color:rgba(79,70,229,.2)}
.chatbot-table-wrap{background:rgba(255,255,255,.72);border-color:#b8c7e8}
.chatbot-table th{background:#e5efff;color:#435477;border-bottom-color:#b8c7e8}
.chatbot-table tbody tr:nth-child(even){background:rgba(255,255,255,.58)}
.chatbot-permissions label{background:#fff;border-color:#aec8e8}
.chatbot-permissions label:first-child{background:#e8f3ff;border-color:#9fc7ee}
.chatbot-permissions label:last-child{background:#fff5dc;border-color:#edca79}
.chatbot-knowledge-form{background:#fff8e8;border-color:#edca79}
.chatbot-detail-summary>div{background:#fff;border-color:#c5b9e8}
.chatbot-conversation-view{background:#eef5ff;border-color:#a9ccef}
.chatbot-session-card{background:#fff;border-color:#b8c7e8}
.chatbot-session-card:hover,.chatbot-session-card.selected{background:#e9f3ff;border-color:#7da9df}
</style>
<?php admin_layout_end(); require './template/footer.php'; ?>