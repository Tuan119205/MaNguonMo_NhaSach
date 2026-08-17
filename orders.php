<?php
  header('Content-Type: text/html; charset=utf-8');
  session_start();

  // Check login
  $isAdmin = isset($_SESSION['admin']) && $_SESSION['admin'] === true;
  $isUser = isset($_SESSION['user']) && $_SESSION['user'] === true && isset($_SESSION['userid']);

  if (!$isAdmin && !$isUser) {
    header('Location: auth.php');
    exit;
  }

  require_once "./functions/database_functions.php";
  require_once "./db_migration.php";
  runDatabaseMigrations();

  $conn = db_connect();
  $title = $isAdmin ? "Quản Lý Đơn Hàng (Admin)" : "Đơn Hàng Của Tôi";

  $userid = $isUser ? intval($_SESSION['userid']) : 0;
  $success = '';
  $error = '';

  // Status mapping definition
  $statusMap = array(
    'chờ_xử_lý' => array('name' => 'Chờ xử lý', 'icon' => 'fa-hourglass-half', 'class' => 'warning', 'text_class' => 'text-dark'),
    'đang_giao' => array('name' => 'Đang giao hàng', 'icon' => 'fa-truck', 'class' => 'info', 'text_class' => 'text-dark'),
    'đã_giao' => array('name' => 'Đã giao hàng', 'icon' => 'fa-check-circle', 'class' => 'success', 'text_class' => 'text-white'),
    'đã_hủy' => array('name' => 'Đã hủy', 'icon' => 'fa-times-circle', 'class' => 'danger', 'text_class' => 'text-white'),
    'pending' => array('name' => 'Chờ xử lý', 'icon' => 'fa-hourglass-half', 'class' => 'warning', 'text_class' => 'text-dark'),
    'shipping' => array('name' => 'Đang giao hàng', 'icon' => 'fa-truck', 'class' => 'info', 'text_class' => 'text-dark'),
    'delivered' => array('name' => 'Đã giao hàng', 'icon' => 'fa-check-circle', 'class' => 'success', 'text_class' => 'text-white'),
    'cancelled' => array('name' => 'Đã hủy', 'icon' => 'fa-times-circle', 'class' => 'danger', 'text_class' => 'text-white'),
  );

  // Handle Cancel order request (User or Admin)
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $orderid = intval($_POST['orderid']);

    // If regular user, ensure order belongs to them
    if (!$isAdmin) {
      $checkOwner = mysqli_query($conn, "SELECT orderid FROM orders WHERE orderid = $orderid AND customerid = $userid AND (order_status = 'chờ_xử_lý' OR order_status = 'pending') LIMIT 1");
      if ($checkOwner && mysqli_num_rows($checkOwner) > 0) {
        $cancelQuery = "UPDATE orders SET order_status = 'đã_hủy' WHERE orderid = $orderid";
        if (mysqli_query($conn, $cancelQuery)) {
          $success = 'Hủy đơn hàng #' . str_pad($orderid, 5, '0', STR_PAD_LEFT) . ' thành công.';
        } else {
          $error = 'Hủy đơn hàng thất bại: ' . mysqli_error($conn);
        }
      } else {
        $error = 'Không thể hủy đơn hàng này do đơn hàng không thuộc về bạn hoặc đã qua trạng thái xử lý.';
      }
    } else {
      // Admin cancel
      $cancelQuery = "UPDATE orders SET order_status = 'đã_hủy' WHERE orderid = $orderid";
      if (mysqli_query($conn, $cancelQuery)) {
        $success = 'Đã cập nhật trạng thái hủy cho đơn hàng #' . str_pad($orderid, 5, '0', STR_PAD_LEFT);
      } else {
        $error = 'Hủy đơn hàng thất bại: ' . mysqli_error($conn);
      }
    }
  }

  // Handle Admin status update
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && $isAdmin) {
    $orderid = intval($_POST['orderid']);
    $new_status = trim($_POST['status']);
    $allowedStatuses = array('chờ_xử_lý', 'đang_giao', 'đã_giao', 'đã_hủy');
    if (!in_array($new_status, $allowedStatuses, true)) {
      $error = 'Trạng thái đơn hàng không hợp lệ.';
      $new_status = 'chờ_xử_lý';
    }
    $new_status_escaped = mysqli_real_escape_string($conn, $new_status);

    $updateQuery = "UPDATE orders SET order_status = '$new_status_escaped' WHERE orderid = $orderid";
    if (mysqli_query($conn, $updateQuery)) {
      $success = 'Cập nhật trạng thái đơn hàng #' . str_pad($orderid, 5, '0', STR_PAD_LEFT) . ' thành công.';
    } else {
      $error = 'Cập nhật trạng thái thất bại: ' . mysqli_error($conn);
    }
  }

  // Filter parameters for Admin
  $filterStatus = trim($_GET['status'] ?? '');
  $searchQuery = trim($_GET['q'] ?? '');

  // Query orders
  if ($isAdmin) {
    $whereClauses = array();
    if (!empty($filterStatus)) {
      $whereClauses[] = "order_status = '" . mysqli_real_escape_string($conn, $filterStatus) . "'";
    }
    if (!empty($searchQuery)) {
      $s = mysqli_real_escape_string($conn, $searchQuery);
      $whereClauses[] = "(orderid LIKE '%$s%' OR ship_name LIKE '%$s%' OR ship_phone LIKE '%$s%')";
    }
    $whereSql = count($whereClauses) > 0 ? "WHERE " . implode(" AND ", $whereClauses) : "";
    $ordersQuery = "SELECT * FROM orders $whereSql ORDER BY date DESC, orderid DESC LIMIT 200";
  } else {
    // Regular customer: strictly filter by their own userid
    $ordersQuery = "SELECT * FROM orders WHERE customerid = '$userid' ORDER BY date DESC, orderid DESC";
  }

  $ordersResult = mysqli_query($conn, $ordersQuery);
  $orders = array();
  if ($ordersResult) {
    while ($order = mysqli_fetch_assoc($ordersResult)) {
      $orders[] = $order;
    }
  }

  require_once "./template/header.php";
?>

<?php if ($isAdmin): ?>
<div class="admin-orders-shell">
  <aside class="modern-admin-sidebar admin-orders-sidebar">
    <div class="modern-admin-brand"><span class="modern-brand-mark">VL</span><div><strong>Nhà Sách Việt Long</strong><small>Admin Panel</small></div></div>
    <nav>
      <a href="admin_dashboard.php"><i class="fas fa-chart-pie"></i>Dashboard</a>
      <a href="admin_customer.php"><i class="fas fa-users"></i>Quản lý người dùng</a>
      <a href="admin_book.php"><i class="fas fa-book"></i>Quản lý sách</a>
      <a class="active" href="orders.php"><i class="fas fa-shopping-bag"></i>Quản lý đơn hàng</a>
    </nav>
    <a class="modern-logout" href="admin_signout.php"><i class="fas fa-sign-out-alt"></i>Đăng xuất</a>
  </aside>
  <main class="admin-orders-main">
<?php endif; ?>
<div class="container py-4 orders-page <?php echo $isAdmin ? 'admin-orders-page' : 'user-orders-page'; ?>">
  <div class="row">
    <div class="col-12">
      <!-- Breadcrumb -->
      <div class="orders-breadcrumb mb-3 text-muted small">
        <a href="index.php" class="text-decoration-none text-muted">Trang chủ</a>
        <span class="mx-2">&rsaquo;</span>
        <span class="fw-bold text-dark"><?php echo $isAdmin ? 'Quản lý đơn hàng' : 'Đơn hàng của tôi'; ?></span>
      </div>

      <!-- Main Card -->
      <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
        <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h3 class="mb-0 fw-bold text-dark fs-5">
            <i class="fa fa-box me-2"></i><?php echo $isAdmin ? 'Quản Lý Tất Cả Đơn Hàng (Admin)' : 'Danh Sách Đơn Hàng Của Bạn'; ?>
          </h3>
          <span class="badge bg-dark text-white fs-6 fw-normal px-3 py-2 rounded-pill">
            Tổng cộng: <strong><?php echo count($orders); ?></strong> đơn hàng
          </span>
        </div>

        <div class="card-body p-4">
          <!-- Flash Messages -->
          <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
              <i class="fa fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
              <i class="fa fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <!-- Admin Filter Toolbar -->
          <?php if ($isAdmin): ?>
          <form method="get" action="orders.php" class="row g-2 mb-4 p-3 bg-light rounded-3 border">
            <div class="col-md-5">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
                <input type="text" name="q" class="form-control" placeholder="Tìm theo mã đơn, tên hoặc SĐT..." value="<?php echo htmlspecialchars($searchQuery); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <select name="status" class="form-select form-select-sm">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="chờ_xử_lý" <?php echo ($filterStatus === 'chờ_xử_lý') ? 'selected' : ''; ?>>Chờ xử lý</option>
                <option value="đang_giao" <?php echo ($filterStatus === 'đang_giao') ? 'selected' : ''; ?>>Đang giao hàng</option>
                <option value="đã_giao" <?php echo ($filterStatus === 'đã_giao') ? 'selected' : ''; ?>>Đã giao hàng</option>
                <option value="đã_hủy" <?php echo ($filterStatus === 'đã_hủy') ? 'selected' : ''; ?>>Đã hủy</option>
              </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
              <button type="submit" class="btn btn-sm btn-warning fw-bold flex-grow-1">Lọc đơn</button>
              <?php if (!empty($filterStatus) || !empty($searchQuery)): ?>
                <a href="orders.php" class="btn btn-sm btn-outline-secondary">Xóa lọc</a>
              <?php endif; ?>
            </div>
          </form>
          <?php endif; ?>

          <!-- Orders Card List -->
          <div class="orders-list">
            <div class="orders-table-head" aria-hidden="true">
              <span>Mã đơn / Ngày đặt</span><span>Người nhận & SĐT</span><span>Tổng tiền / Thanh toán</span><span>Trạng thái</span><span>Thao tác</span>
            </div>
            <div class="orders-table-body">
              <div class="orders-empty-state">
                <?php if (count($orders) > 0): ?>
                  <?php foreach ($orders as $order): ?>
                    <?php
                      $statusKey = $order['order_status'] ?? 'chờ_xử_lý';
                      $statusInfo = $statusMap[$statusKey] ?? array(
                        'name' => 'Chờ xử lý',
                        'icon' => 'fa-hourglass-half',
                        'class' => 'warning',
                        'text_class' => 'text-dark'
                      );
                      $isCancellable = ($statusKey === 'chờ_xử_lý' || $statusKey === 'pending');
                    ?>
                    <article class="order-card">
                      <div class="order-cell order-code-cell" data-label="Mã đơn / Ngày đặt">
                        <strong class="order-code">#DH<?php echo str_pad($order['orderid'], 5, '0', STR_PAD_LEFT); ?></strong>
                        <span class="order-date"><i class="fa fa-calendar-alt me-1"></i><?php echo date('d/m/Y H:i', strtotime($order['date'])); ?></span>
                      </div>
                      <div class="order-cell recipient-cell" data-label="Người nhận & SĐT">
                        <div class="fw-semibold text-dark small"><?php echo htmlspecialchars($order['ship_name']); ?></div>
                        <?php if (!empty($order['ship_phone'])): ?>
                          <div class="text-muted small"><i class="fa fa-phone me-1"></i><?php echo htmlspecialchars($order['ship_phone']); ?></div>
                        <?php endif; ?>
                      </div>
                      <div class="order-cell payment-cell" data-label="Tổng tiền / Thanh toán">
                        <strong class="text-danger fs-6">
                          <?php echo number_format($order['amount'], 0, ',', '.'); ?>đ
                        </strong>
                        <div class="payment-method"><i class="fa fa-credit-card me-1"></i><?php echo ($order['payment_method'] === 'bank_transfer') ? 'Chuyển khoản' : 'COD'; ?></div>
                      </div>
                      <div class="order-cell status-cell" data-label="Trạng thái">
                        <span class="status-badge status-<?php echo $statusInfo['class']; ?> <?php echo $statusInfo['text_class']; ?> px-2 py-1 rounded-pill">
                          <i class="fa <?php echo $statusInfo['icon']; ?> me-1"></i>
                          <?php echo $statusInfo['name']; ?>
                        </span>
                      </div>
                      <div class="order-cell actions-cell" data-label="Thao tác">
                        <div class="order-actions">
                          <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $order['orderid']; ?>" title="Xem chi tiết">
                            <i class="fa fa-eye me-1"></i>Xem
                          </button>
                          <?php if ($isCancellable): ?>
                            <form method="post" action="orders.php" style="display: inline;" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng #DH<?php echo str_pad($order['orderid'], 5, '0', STR_PAD_LEFT); ?>?');">
                              <input type="hidden" name="orderid" value="<?php echo $order['orderid']; ?>">
                              <button type="submit" name="cancel_order" class="btn btn-sm btn-outline-danger" title="Hủy đơn hàng">
                                <i class="fa fa-times me-1"></i>Hủy
                              </button>
                            </form>
                          <?php endif; ?>
                        </div>
                      </div>
                    </article>

                    <!-- Modal Detail for Order -->
                    <div class="modal fade" id="orderModal<?php echo $order['orderid']; ?>" tabindex="-1" aria-labelledby="modalLabel<?php echo $order['orderid']; ?>" aria-hidden="true">
                      <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content rounded-4 border-0 overflow-hidden shadow">
                          <div class="modal-header bg-warning py-3 px-4">
                            <h5 class="modal-title fw-bold text-dark" id="modalLabel<?php echo $order['orderid']; ?>">
                              <i class="fa fa-box me-2"></i>Đơn Hàng #DH<?php echo str_pad($order['orderid'], 5, '0', STR_PAD_LEFT); ?>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body p-4">
                            <!-- Progress Status Indicator -->
                            <?php if ($statusKey !== 'đã_hủy' && $statusKey !== 'cancelled'): ?>
                            <div class="order-progress-steps mb-4 p-3 bg-light rounded-3 border">
                              <div class="row text-center g-2">
                                <div class="col-4">
                                  <div class="step-box <?php echo ($statusKey === 'chờ_xử_lý' || $statusKey === 'pending' || $statusKey === 'đang_giao' || $statusKey === 'shipping' || $statusKey === 'đã_giao' || $statusKey === 'delivered') ? 'active' : ''; ?>">
                                    <div class="step-icon mb-1"><i class="fa fa-receipt"></i></div>
                                    <div class="step-label fw-bold small">1. Đã đặt hàng</div>
                                  </div>
                                </div>
                                <div class="col-4">
                                  <div class="step-box <?php echo ($statusKey === 'đang_giao' || $statusKey === 'shipping' || $statusKey === 'đã_giao' || $statusKey === 'delivered') ? 'active' : ''; ?>">
                                    <div class="step-icon mb-1"><i class="fa fa-truck"></i></div>
                                    <div class="step-label fw-bold small">2. Đang giao hàng</div>
                                  </div>
                                </div>
                                <div class="col-4">
                                  <div class="step-box <?php echo ($statusKey === 'đã_giao' || $statusKey === 'delivered') ? 'active' : ''; ?>">
                                    <div class="step-icon mb-1"><i class="fa fa-check-circle"></i></div>
                                    <div class="step-label fw-bold small">3. Đã giao thành công</div>
                                  </div>
                                </div>
                              </div>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-danger rounded-3 mb-4">
                              <i class="fa fa-times-circle me-2"></i>Đơn hàng này đã bị hủy.
                            </div>
                            <?php endif; ?>

                            <!-- Order & Recipient Info -->
                            <div class="row g-3 mb-4">
                              <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                  <h6 class="fw-bold text-dark mb-2"><i class="fa fa-map-marker-alt text-danger me-2"></i>Thông Tin Giao Hàng</h6>
                                  <p class="mb-1"><strong>Người nhận:</strong> <?php echo htmlspecialchars($order['ship_name']); ?></p>
                                  <?php if (!empty($order['ship_phone'])): ?>
                                    <p class="mb-1"><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['ship_phone']); ?></p>
                                  <?php endif; ?>
                                  <p class="mb-1"><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order['ship_address'] . ', ' . $order['ship_city']); ?></p>
                                  <?php if (!empty($order['ship_zip_code'])): ?>
                                    <p class="mb-0"><strong>Mã bưu điện:</strong> <?php echo htmlspecialchars($order['ship_zip_code']); ?></p>
                                  <?php endif; ?>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                  <h6 class="fw-bold text-dark mb-2"><i class="fa fa-info-circle text-primary me-2"></i>Chi Tiết Thanh Toán</h6>
                                  <p class="mb-1"><strong>Ngày đặt:</strong> <?php echo date('d/m/Y H:i', strtotime($order['date'])); ?></p>
                                  <p class="mb-1"><strong>Phương thức:</strong> <?php echo ($order['payment_method'] === 'bank_transfer') ? 'Chuyển khoản ngân hàng' : 'Thanh toán khi nhận hàng (COD)'; ?></p>
                                  <p class="mb-1"><strong>Trạng thái:</strong> <span class="badge bg-<?php echo $statusInfo['class']; ?> <?php echo $statusInfo['text_class']; ?>"><?php echo $statusInfo['name']; ?></span></p>
                                  <?php if (!empty($order['notes'])): ?>
                                    <p class="mb-0"><strong>Ghi chú:</strong> <?php echo htmlspecialchars($order['notes']); ?></p>
                                  <?php endif; ?>
                                </div>
                              </div>
                            </div>

                            <!-- Items Table in Modal -->
                            <h6 class="fw-bold text-dark mb-2"><i class="fa fa-book text-warning me-2"></i>Danh Sách Sách Trong Đơn</h6>
                            <div class="table-responsive border rounded-3 mb-3">
                              <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                  <tr>
                                    <th>Sách</th>
                                    <th class="text-center">Số lượng</th>
                                    <th class="text-end">Đơn giá</th>
                                    <th class="text-end">Tổng</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <?php
                                    $itemsQuery = "SELECT oi.*, b.book_title, b.book_author, b.book_image
                                                   FROM order_items oi
                                                   LEFT JOIN books b ON oi.book_isbn = b.book_isbn
                                                   WHERE oi.orderid = " . intval($order['orderid']);
                                    $itemsResult = mysqli_query($conn, $itemsQuery);
                                    $totalItemCount = 0;
                                    $itemsSubtotal = 0;

                                    if ($itemsResult && mysqli_num_rows($itemsResult) > 0) {
                                      while ($item = mysqli_fetch_assoc($itemsResult)) {
                                        $itemLineTotal = $item['item_price'] * $item['quantity'];
                                        $itemsSubtotal += $itemLineTotal;
                                        $totalItemCount++;
                                        $itemImg = !empty($item['book_image']) ? './bootstrap/img/' . $item['book_image'] : './bootstrap/img/default-book.jpg';
                                  ?>
                                    <tr>
                                      <td>
                                        <div class="d-flex align-items-center gap-2">
                                          <img src="<?php echo $itemImg; ?>" alt="" style="width: 32px; height: 42px; object-fit: cover; border-radius: 4px;">
                                          <div>
                                            <div class="fw-semibold text-dark small"><?php echo htmlspecialchars($item['book_title'] ?? 'Sách'); ?></div>
                                            <div class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($item['book_author'] ?? ''); ?></div>
                                          </div>
                                        </div>
                                      </td>
                                      <td class="text-center fw-bold"><?php echo $item['quantity']; ?></td>
                                      <td class="text-end small"><?php echo number_format($item['item_price'], 0, ',', '.'); ?>đ</td>
                                      <td class="text-end fw-bold"><?php echo number_format($itemLineTotal, 0, ',', '.'); ?>đ</td>
                                    </tr>
                                  <?php
                                      }
                                    } else {
                                  ?>
                                    <tr>
                                      <td colspan="4" class="text-center text-muted py-3">Không tìm thấy thông tin sản phẩm.</td>
                                    </tr>
                                  <?php } ?>
                                </tbody>
                                <tfoot class="order-total-summary">
                                  <tr>
                                    <td colspan="3" class="text-end text-muted">Tạm tính:</td>
                                    <td class="text-end fw-semibold"><?php echo number_format($itemsSubtotal, 0, ',', '.'); ?>đ</td>
                                  </tr>
                                  <?php $difference = $itemsSubtotal - (float)$order['amount']; ?>
                                  <?php if (abs($difference) > 0.01): ?>
                                  <tr>
                                    <?php if ($difference > 0): ?>
                                      <td colspan="3" class="text-end text-success">Giảm giá / khuyến mãi:</td>
                                      <td class="text-end fw-semibold text-success">-<?php echo number_format($difference, 0, ',', '.'); ?>đ</td>
                                    <?php else: ?>
                                      <td colspan="3" class="text-end text-muted">Phụ phí / điều chỉnh đơn:</td>
                                      <td class="text-end fw-semibold text-muted">+<?php echo number_format(abs($difference), 0, ',', '.'); ?>đ</td>
                                    <?php endif; ?>
                                  </tr>
                                  <?php endif; ?>
                                  <tr>
                                    <td colspan="3" class="text-end text-muted">Phí vận chuyển:</td>
                                    <td class="text-end text-success">Miễn phí</td>
                                  </tr>
                                  <tr class="total-row">
                                    <td colspan="3" class="text-end fw-bold">Tổng thanh toán:</td>
                                    <td class="text-end fw-bold text-danger fs-6"><?php echo number_format($order['amount'], 0, ',', '.'); ?>đ</td>
                                  </tr>
                                </tfoot>
                              </table>
                            </div>

                            <!-- Admin Status Update Form in Modal -->
                            <?php if ($isAdmin): ?>
                              <hr>
                              <div class="p-3 bg-light rounded-3 border">
                                <h6 class="fw-bold text-dark mb-2"><i class="fa fa-user-shield me-2"></i>Cập Nhật Trạng Thái (Dành cho Admin)</h6>
                                <form method="post" action="orders.php" class="row g-2 align-items-center">
                                  <input type="hidden" name="orderid" value="<?php echo $order['orderid']; ?>">
                                  <div class="col-sm-8">
                                    <select name="status" class="form-select">
                                      <option value="chờ_xử_lý" <?php echo ($statusKey === 'chờ_xử_lý' || $statusKey === 'pending') ? 'selected' : ''; ?>>Chờ xử lý</option>
                                      <option value="đang_giao" <?php echo ($statusKey === 'đang_giao' || $statusKey === 'shipping') ? 'selected' : ''; ?>>Đang giao hàng</option>
                                      <option value="đã_giao" <?php echo ($statusKey === 'đã_giao' || $statusKey === 'delivered') ? 'selected' : ''; ?>>Đã giao hàng</option>
                                      <option value="đã_hủy" <?php echo ($statusKey === 'đã_hủy' || $statusKey === 'cancelled') ? 'selected' : ''; ?>>Đã hủy</option>
                                    </select>
                                  </div>
                                  <div class="col-sm-4">
                                    <button type="submit" name="update_status" class="btn btn-warning w-100 fw-bold">
                                      <i class="fa fa-save me-1"></i>Lưu trạng thái
                                    </button>
                                  </div>
                                </form>
                              </div>
                            <?php endif; ?>
                          </div>
                          <div class="modal-footer bg-light py-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                          </div>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="empty-orders text-center py-5">
                    <i class="fa fa-inbox fa-3x mb-3 text-secondary d-block"></i>
                    <h5 class="fw-bold text-dark">Chưa có đơn hàng nào</h5>
                    <p class="small text-muted mb-3">Bạn chưa có đơn hàng nào trong lịch sử.</p>
                    <a href="books.php" class="btn btn-warning fw-bold px-4"><i class="fa fa-book me-2"></i>Khám Phá Sách Ngay</a>
                  </div>
                <?php endif; ?>
              </div>
            </div>

          <!-- Bottom Actions -->
          <div class="mt-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <a href="books.php" class="btn btn-outline-warning text-dark fw-semibold">
              <i class="fa fa-shopping-bag me-2"></i>Tiếp tục mua sách
            </a>
            <?php if (!$isAdmin): ?>
              <a href="profile.php" class="btn btn-outline-secondary">
                <i class="fa fa-user me-2"></i>Thông tin cá nhân
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if ($isAdmin): ?>
  </main>
</div>
<?php endif; ?>

<style>
html,body{margin:0!important;padding:0!important}.admin-orders-shell{font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.admin-orders-shell .modern-admin-sidebar{display:flex;flex-direction:column;color:#fff}.admin-orders-shell .modern-admin-brand{display:flex;align-items:center;gap:10px;padding:0 10px 24px;border-bottom:1px solid #3b4149;color:#fff}.admin-orders-shell .modern-brand-mark{width:38px;height:38px;border-radius:10px;background:#f0b90b;color:#20242b;display:grid;place-items:center;font-weight:800}.admin-orders-shell .modern-admin-brand strong,.admin-orders-shell .modern-admin-brand small{display:block}.admin-orders-shell .modern-admin-brand strong{font-size:13px;line-height:1.3}.admin-orders-shell .modern-admin-brand small{font-size:11px;color:#aeb5bf;margin-top:3px}.admin-orders-shell .modern-admin-sidebar nav{padding-top:20px}.admin-orders-shell .modern-admin-sidebar nav a,.admin-orders-shell .modern-logout{display:flex;align-items:center;gap:11px;margin:0 0 4px;padding:12px 13px;border-radius:8px;color:#bcc3cc;text-decoration:none;font-size:13px;line-height:1.35}.admin-orders-shell .modern-admin-sidebar nav a i,.admin-orders-shell .modern-logout i{width:17px;text-align:center}.admin-orders-shell .modern-admin-sidebar nav a:hover,.admin-orders-shell .modern-admin-sidebar nav a.active{background:#343a43;color:#ffd45b;box-shadow:inset 3px 0 #f0b90b}.admin-orders-shell .modern-logout{margin-top:auto;border-top:1px solid #3b4149;border-radius:0;padding-top:20px}
.orders-page {
  max-width: 1180px;
  margin: 0 auto;
  padding-left: 16px;
  padding-right: 16px;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  color: #1f2937;
}

.orders-page > .row > .col-12 { width: 100%; }
.orders-page .card { background: #fff; border: 1px solid #e5e7eb; }
.orders-page .card-header { background: #fff; border-bottom: 1px solid #e5e7eb; }
.orders-table-head,
.order-card {
  display: grid;
  grid-template-columns: 1.15fr 1.3fr 1.2fr 1.1fr 1.15fr;
  gap: 16px;
  align-items: center;
}
.orders-table-head {
  padding: 10px 18px;
  color: #6b7280;
  font-size: .72rem;
  font-weight: 700;
  letter-spacing: .06em;
  text-transform: uppercase;
}
.order-card {
  padding: 18px;
  margin-bottom: 12px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(31,41,55,.05);
  transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
}
.order-card:hover { transform: translateY(-2px); border-color: #f2c94c; box-shadow: 0 7px 18px rgba(31,41,55,.09); }
.order-cell { min-width: 0; }
.order-code { display: block; color: #b7791f; font-size: 1rem; }
.order-date, .payment-method { display: block; color: #6b7280; font-size: .78rem; margin-top: 5px; }
.order-actions { display: flex; justify-content: flex-end; gap: 7px; flex-wrap: wrap; }
.order-actions form { margin: 0; }
.order-actions .btn { border-radius: 7px; white-space: nowrap; transition: all .2s ease; }
.status-badge { display: inline-flex; align-items: center; gap: 4px; padding: 7px 10px; border-radius: 999px; font-size: .78rem; font-weight: 700; white-space: nowrap; }
.status-warning { color: #8a5a00; background: #fff4cc; }
.status-info { color: #075985; background: #e0f2fe; }
.status-success { color: #166534; background: #dcfce7; }
.status-danger { color: #991b1b; background: #fee2e2; }
.orders-table-body { width: 100%; }
.orders-empty-state { width: 100%; }
.order-total-summary { border-top: 1px solid #e5e7eb; }
.order-total-summary td { padding: 7px 12px; }
.order-total-summary .total-row td { padding-top: 12px; border-top: 1px solid #f2c94c; }

.step-box {
  padding: 12px 6px;
  border-radius: 8px;
  background: #f3f4f6;
  color: #9ca3af;
  transition: all 0.25s ease;
}

.step-box.active {
  background: #fff8e1;
  color: #d97706;
  border: 1px solid #fcd34d;
}

.step-box .step-icon { font-size: 1.4rem; }

@media (max-width: 992px) {
  .orders-table-head { display: none; }
  .order-card { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 20px; }
  .actions-cell { grid-column: 1 / -1; padding-top: 12px; border-top: 1px solid #f1f5f9; }
  .order-actions { justify-content: flex-start; }
}

@media (max-width: 576px) {
  .orders-page { padding: 12px 10px 28px; }
  .orders-page .card-body { padding: 14px !important; }
  .orders-page .card-header { padding: 16px !important; }
  .orders-page .card-header h3 { font-size: 1rem !important; }
  .order-card { display: block; padding: 15px; margin-bottom: 10px; }
  .order-cell { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 9px 0; border-bottom: 1px solid #f1f5f9; }
  .order-cell::before { content: attr(data-label); color: #6b7280; font-size: .73rem; font-weight: 700; text-transform: uppercase; flex: 0 0 37%; }
  .order-cell:last-child { border-bottom: 0; }
  .order-code-cell { align-items: flex-start; }
  .order-code-cell::before { padding-top: 2px; }
  .order-actions { width: 100%; justify-content: stretch; }
  .order-actions .btn, .order-actions form { flex: 1; }
  .order-actions form .btn { width: 100%; }
  .modal-dialog { margin: 10px; }
  .modal-body { padding: 16px !important; }
  .modal .table { min-width: 580px; }
  .modal .table-responsive { overflow-x: auto; }
}
</style>

<?php
  if(isset($conn)) { mysqli_close($conn); }
  require_once "./template/footer.php";
?>

<style>
/* Admin orders layout aligned with the books management page */
html,body{margin:0!important;padding:0!important;background:#f5f6f8}.admin-orders-shell{display:flex;min-height:100vh;width:100vw;background:#f5f6f8}.admin-orders-sidebar{position:fixed!important;inset:0 auto 0 0!important;width:250px!important;height:100vh!important;box-sizing:border-box;background:#20242b!important;padding:24px 14px!important;z-index:20}.admin-orders-main{width:auto;min-width:0;flex:1;margin-left:250px;padding:30px 34px 50px}.admin-orders-page{max-width:none!important;width:auto!important;margin:0!important;padding:0!important}.admin-orders-page .orders-breadcrumb{display:none}.admin-orders-page .card{border:1px solid #e2e8f0!important;border-radius:10px!important;box-shadow:0 2px 5px rgba(15,23,42,.04)!important}.admin-orders-page .card-header{padding:20px 22px!important;background:#fff!important;border-bottom:1px solid #e2e8f0!important}.admin-orders-page .card-header h3{color:#20242b!important;font-size:22px!important}.admin-orders-page .card-header .badge{background:#fff1bd!important;color:#a17c0f!important;font-size:12px!important}.admin-orders-page .card-body{padding:22px!important}.admin-orders-page .card-body>form{padding:14px!important;background:#f8fafc!important;border:1px solid #e2e8f0!important}.admin-orders-page .btn-warning{background:#f0b90b!important;border-color:#f0b90b!important;color:#20242b!important}.admin-orders-page .orders-table-head{color:#7c8590;border-bottom:1px solid #e2e8f0}.admin-orders-page .order-card{border:1px solid #e2e8f0;box-shadow:0 2px 5px rgba(15,23,42,.04);border-radius:10px}.admin-orders-page .order-card:hover{border-color:#f0b90b;box-shadow:0 7px 18px rgba(15,23,42,.09)}.admin-orders-page .order-code{color:#b17d00}.admin-orders-page .order-actions .btn-outline-primary{border-color:#b9c2d0;color:#475569}.admin-orders-page .order-actions .btn-outline-primary:hover{background:#20242b;color:#fff}.admin-orders-page .modal-header{background:#f0b90b!important}.admin-orders-page .status-warning{background:#fff1bd;color:#8a5a00}.admin-orders-page .order-total-summary .total-row td{border-top-color:#f0b90b}.admin-orders-page .empty-orders{padding:35px!important}
@media(max-width:700px){.admin-orders-shell{display:block}.admin-orders-sidebar{position:relative!important;width:100%!important;height:auto!important;min-height:auto!important;padding:12px!important}.admin-orders-sidebar nav{display:grid;grid-template-columns:1fr 1fr;gap:2px}.admin-orders-sidebar nav a{margin:0;padding:9px;font-size:12px}.admin-orders-main{margin-left:0;padding:20px 14px 32px}.admin-orders-page .card-header h3{font-size:17px!important}.admin-orders-page .card-body{padding:14px!important}}
</style>

<style>
/* Prevent admin orders from exceeding the viewport */
html,body{width:100%;max-width:100%;overflow-x:hidden}.admin-orders-shell{display:block!important;width:100%;min-height:100vh;overflow:hidden}.admin-orders-sidebar{position:fixed!important;left:0;top:0;bottom:0;width:250px!important;box-sizing:border-box}.admin-orders-main{display:block!important;width:calc(100% - 250px)!important;max-width:none!important;min-width:0!important;margin-left:250px!important;box-sizing:border-box;overflow:hidden}.admin-orders-page{width:100%!important;max-width:none!important;box-sizing:border-box}.admin-orders-page .orders-list,.admin-orders-page .orders-table-body{max-width:100%;overflow:hidden}.admin-orders-page .order-card{max-width:100%;box-sizing:border-box}@media(max-width:700px){.admin-orders-shell{display:block!important;overflow:visible}.admin-orders-sidebar{position:relative!important;width:100%!important;height:auto!important;min-height:auto}.admin-orders-main{width:100%!important;margin-left:0!important;overflow:visible}}
</style>

<style>
/* Remove the empty top strip from admin order management */
body:has(.admin-orders-shell) .clear-fix,body:has(.admin-orders-shell) .site-footer-spacer,body:has(.admin-orders-shell) .pt-5{display:none!important}body:has(.admin-orders-shell) .admin-orders-shell{margin-top:0!important;padding-top:0!important}.admin-orders-main{padding-top:0!important}.admin-orders-page{padding-top:30px!important}
</style>

<style>
/* Admin orders do not use the public footer/container spacing */
body:has(.admin-orders-shell) .site-footer,body:has(.admin-orders-shell) .site-footer-spacer{display:none!important}body:has(.admin-orders-shell) #pageContent{margin:0!important;padding:0!important;max-width:none!important}.admin-orders-main>.orders-page{margin:0!important}
</style>
