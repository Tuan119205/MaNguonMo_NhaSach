<?php
session_start();
require_once './functions/admin.php';
require_once './functions/database_functions.php';
require_once './db_migration.php';
runDatabaseMigrations();
$conn = db_connect();
$title = 'Quản lý khuyến mãi';
$notice = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'save') {
    $id = (int)($_POST['id'] ?? 0);
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'percent';
    $value = max(0, (float)($_POST['value'] ?? 0));
    $minOrder = max(0, (float)($_POST['min_order'] ?? 0));
    $expiresAt = $_POST['expires_at'] ?? '';
    $active = isset($_POST['active']) ? 1 : 0;
    if ($code === '' || $name === '' || !in_array($type, ['percent','fixed','shipping','special'], true) || $expiresAt === '') {
      $error = 'Vui lòng nhập đầy đủ thông tin khuyến mãi.';
    } elseif ($type === 'percent' && $value > 100) {
      $error = 'Phần trăm giảm giá không được vượt quá 100.';
    } else {
      $codeEsc = mysqli_real_escape_string($conn, $code); $nameEsc = mysqli_real_escape_string($conn, $name); $typeEsc = mysqli_real_escape_string($conn, $type); $dateEsc = mysqli_real_escape_string($conn, $expiresAt);
      $query = $id > 0 ? "UPDATE promotions SET code='$codeEsc', name='$nameEsc', type='$typeEsc', value=$value, min_order=$minOrder, expires_at='$dateEsc', active=$active WHERE id=$id" : "INSERT INTO promotions (code,name,type,value,min_order,expires_at,active) VALUES ('$codeEsc','$nameEsc','$typeEsc',$value,$minOrder,'$dateEsc',$active)";
      if (mysqli_query($conn, $query)) { $notice = 'Đã lưu chương trình khuyến mãi.'; } else { $error = 'Không thể lưu: ' . mysqli_error($conn); }
    }
  } elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (mysqli_query($conn, "DELETE FROM promotions WHERE id=$id")) $notice = 'Đã xóa khuyến mãi.'; else $error = 'Không thể xóa khuyến mãi.';
  } elseif ($action === 'toggle') {
    $id = (int)($_POST['id'] ?? 0); mysqli_query($conn, "UPDATE promotions SET active=1-active WHERE id=$id"); $notice = 'Đã cập nhật trạng thái.';
  }
}
$edit = null;
if (isset($_GET['edit'])) { $editResult = mysqli_query($conn, 'SELECT * FROM promotions WHERE id=' . (int)$_GET['edit']); $edit = $editResult ? mysqli_fetch_assoc($editResult) : null; }
$list = mysqli_query($conn, 'SELECT * FROM promotions ORDER BY id DESC');
require './template/header.php';
require_once './template/admin_layout.php';
admin_layout_start('admin_promotions', 'Quản lý khuyến mãi', 'MARKETING', 'Tạo và kiểm soát mã giảm giá đang áp dụng trên hệ thống', '');
?>
<div class="admin-panel" style="padding: 22px 20px; margin-bottom: 22px;">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
    <div>
      <h2 class="mb-1" style="font-size: 1.5rem; margin: 0;">Tạo khuyến mãi mới</h2>
      <p class="mb-0 text-muted">Điền đầy đủ thông tin để cập nhật chương trình khuyến mãi.</p>
    </div>
    <a href="promotions.php" class="admin-button" style="background:#fff; border:1px solid #e5e7eb;">Xem trang khách hàng</a>
  </div>

  <?php if ($notice): ?><div class="alert alert-success mb-3"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger mb-3"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="post" class="row g-3">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="col-md-6">
      <label class="form-label fw-semibold">Mã khuyến mãi</label>
      <input class="form-control" name="code" value="<?= htmlspecialchars($edit['code'] ?? '') ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label fw-semibold">Tên chương trình</label>
      <input class="form-control" name="name" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" required>
    </div>
    <div class="col-md-4">
      <label class="form-label fw-semibold">Loại giảm</label>
      <select class="form-select" name="type">
        <option value="percent" <?= (($edit['type'] ?? '') === 'percent') ? 'selected' : '' ?>>Theo phần trăm</option>
        <option value="fixed" <?= (($edit['type'] ?? '') === 'fixed') ? 'selected' : '' ?>>Số tiền cố định</option>
        <option value="shipping" <?= (($edit['type'] ?? '') === 'shipping') ? 'selected' : '' ?>>Miễn phí vận chuyển</option>
        <option value="special" <?= (($edit['type'] ?? '') === 'special') ? 'selected' : '' ?>>Đặc biệt</option>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label fw-semibold">Giá trị</label>
      <input type="number" step="0.01" min="0" class="form-control" name="value" value="<?= htmlspecialchars((string)($edit['value'] ?? 0)) ?>" required>
    </div>
    <div class="col-md-4">
      <label class="form-label fw-semibold">Đơn tối thiểu</label>
      <input type="number" step="0.01" min="0" class="form-control" name="min_order" value="<?= htmlspecialchars((string)($edit['min_order'] ?? 0)) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label fw-semibold">Hết hạn</label>
      <input type="date" class="form-control" name="expires_at" value="<?= htmlspecialchars($edit['expires_at'] ?? '') ?>" required>
    </div>
    <div class="col-md-6 d-flex align-items-end">
      <label class="form-check-label d-flex align-items-center gap-2 w-100 p-2 border rounded-3 bg-light">
        <input type="checkbox" name="active" value="1" <?= (($edit['active'] ?? 1) == 1) ? 'checked' : '' ?>>
        <span class="fw-semibold">Kích hoạt ngay</span>
      </label>
    </div>
    <div class="col-12 d-flex gap-2">
      <button type="submit" class="admin-button"><?= $edit ? 'Cập nhật' : 'Lưu khuyến mãi' ?></button>
      <a href="admin_promotions.php" class="btn btn-outline-secondary">Hủy</a>
    </div>
  </form>
</div>

<div class="admin-panel" style="padding: 22px 20px;">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
    <div>
      <h2 class="mb-1" style="font-size: 1.5rem; margin: 0;">Danh sách khuyến mãi</h2>
      <p class="mb-0 text-muted">Tất cả chương trình đang có trong hệ thống.</p>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Mã</th>
          <th>Tên</th>
          <th>Loại</th>
          <th>Giá trị</th>
          <th>Hết hạn</th>
          <th>Trạng thái</th>
          <th>Hành động</th>
        </tr>
      </thead>
      <tbody>
        <?php if (mysqli_num_rows($list) > 0): while ($row = mysqli_fetch_assoc($list)): ?>
          <tr>
            <td><?= htmlspecialchars($row['code']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= match ($row['type']) { 'percent' => 'Phần trăm', 'fixed' => 'Số tiền', 'shipping' => 'Miễn phí ship', 'special' => 'Đặc biệt', default => 'Không xác định' }; ?></td>
            <td><?= $row['type'] === 'percent' ? $row['value'] . '%' : number_format((float)$row['value'], 0, ',', '.') . 'đ'; ?></td>
            <td><?= htmlspecialchars($row['expires_at']) ?></td>
            <td><?= $row['active'] ? '<span class="badge bg-success">Đang áp dụng</span>' : '<span class="badge bg-secondary">Tắt</span>' ?></td>
            <td>
              <div class="d-flex gap-2 flex-wrap">
                <a href="admin_promotions.php?edit=<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
                <form method="post" action="admin_promotions.php" class="d-inline">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-secondary"><?= $row['active'] ? 'Tắt' : 'Bật' ?></button>
                </form>
                <form method="post" action="admin_promotions.php" class="d-inline" onsubmit="return confirm('Xóa khuyến mãi này?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Chưa có khuyến mãi nào.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php admin_layout_end(); ?>
<?php require_once "./template/footer.php"; ?>
