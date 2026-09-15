<?php
session_start();
require_once './functions/admin.php';
require_once './functions/database_functions.php';
$conn = db_connect();
$title = 'Thống kê và báo cáo';

$selectedMonth = isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month']) ? $_GET['month'] : date('Y-m');
$monthStart = $selectedMonth . '-01';
$nextMonth = date('Y-m', strtotime($monthStart . ' +1 month'));
$previousMonth = date('Y-m', strtotime($monthStart . ' -1 month'));
$monthStartEsc = mysqli_real_escape_string($conn, $monthStart);
$nextMonthEsc = mysqli_real_escape_string($conn, $nextMonth . '-01');
$validOrderSql = "order_status NOT IN ('đã_hủy','cancelled','đã hủy')";

function report_value($conn, $sql) {
  $result = mysqli_query($conn, $sql);
  if (!$result) return 0;
  $row = mysqli_fetch_row($result);
  return $row[0] ?? 0;
}
function report_money($value) { return number_format((float)$value, 0, ',', '.') . 'đ'; }

$revenue = report_value($conn, "SELECT COALESCE(SUM(amount),0) FROM orders WHERE date >= '$monthStartEsc' AND date < '$nextMonthEsc' AND $validOrderSql");
$orderCount = report_value($conn, "SELECT COUNT(*) FROM orders WHERE date >= '$monthStartEsc' AND date < '$nextMonthEsc'");
$soldCount = report_value($conn, "SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi INNER JOIN orders o ON o.orderid=oi.orderid WHERE o.date >= '$monthStartEsc' AND o.date < '$nextMonthEsc' AND o.$validOrderSql");
$averageOrder = $orderCount > 0 ? $revenue / $orderCount : 0;
$dailyLabels = $dailyRevenue = $dailyOrders = array();
$daysInMonth = (int)date('t', strtotime($monthStart));
for ($day = 1; $day <= $daysInMonth; $day++) { $dailyLabels[] = str_pad($day, 2, '0', STR_PAD_LEFT); $dailyRevenue[] = 0; $dailyOrders[] = 0; }
$dailyResult = mysqli_query($conn, "SELECT DAY(date) AS day, COALESCE(SUM(amount),0) AS revenue, COUNT(*) AS orders FROM orders WHERE date >= '$monthStartEsc' AND date < '$nextMonthEsc' AND $validOrderSql GROUP BY DAY(date) ORDER BY day");
if ($dailyResult) while ($row = mysqli_fetch_assoc($dailyResult)) { $index = (int)$row['day'] - 1; $dailyRevenue[$index] = (float)$row['revenue']; $dailyOrders[$index] = (int)$row['orders']; }

$topBooks = array();
$topResult = mysqli_query($conn, "SELECT b.book_title, SUM(oi.quantity) sold, SUM(oi.quantity * oi.item_price) revenue FROM order_items oi INNER JOIN books b ON b.book_isbn=oi.book_isbn INNER JOIN orders o ON o.orderid=oi.orderid WHERE o.date >= '$monthStartEsc' AND o.date < '$nextMonthEsc' AND o.$validOrderSql GROUP BY b.book_isbn,b.book_title ORDER BY sold DESC LIMIT 10");
if ($topResult) while ($row = mysqli_fetch_assoc($topResult)) $topBooks[] = $row;

$statusCounts = array('chờ_xử_lý'=>0, 'đang_giao'=>0, 'đã_giao'=>0, 'đã_hủy'=>0);
$statusResult = mysqli_query($conn, "SELECT order_status, COUNT(*) total FROM orders WHERE date >= '$monthStartEsc' AND date < '$nextMonthEsc' GROUP BY order_status");
if ($statusResult) while ($row = mysqli_fetch_assoc($statusResult)) {
  $status = $row['order_status'];
  if (in_array($status, array('pending','chờ_xử_lý'), true)) $statusCounts['chờ_xử_lý'] += (int)$row['total'];
  elseif (in_array($status, array('shipping','đang_giao'), true)) $statusCounts['đang_giao'] += (int)$row['total'];
  elseif (in_array($status, array('delivered','đã_giao'), true)) $statusCounts['đã_giao'] += (int)$row['total'];
  else $statusCounts['đã_hủy'] += (int)$row['total'];
}
require './template/header.php';
require_once './template/admin_layout.php';
admin_layout_start('admin_reports', 'Thống kê và báo cáo', 'BÁO CÁO KINH DOANH', 'Theo dõi kết quả kinh doanh theo từng tháng.', '<form method="get" class="d-flex align-items-center gap-2"><a href="?month=' . htmlspecialchars($previousMonth) . '" class="btn btn-sm btn-outline-secondary" title="Tháng trước"><i class="fa fa-chevron-left"></i></a><input type="month" name="month" value="' . htmlspecialchars($selectedMonth) . '" class="form-control form-control-sm" onchange="this.form.submit()"><a href="?month=' . htmlspecialchars($nextMonth) . '" class="btn btn-sm btn-outline-secondary" title="Tháng sau"><i class="fa fa-chevron-right"></i></a></form>');
?>
<section class="admin-panel" style="padding: 20px; margin-bottom: 22px;">
  <div class="row g-3">
    <div class="col-md-6 col-xl-3"><div class="border rounded-4 p-3 h-100 bg-light"><small class="text-muted d-block">Doanh thu tháng</small><strong class="fs-4"><?= report_money($revenue) ?></strong></div></div>
    <div class="col-md-6 col-xl-3"><div class="border rounded-4 p-3 h-100 bg-light"><small class="text-muted d-block">Tổng đơn hàng</small><strong class="fs-4"><?= number_format($orderCount) ?></strong></div></div>
    <div class="col-md-6 col-xl-3"><div class="border rounded-4 p-3 h-100 bg-light"><small class="text-muted d-block">Sách đã bán</small><strong class="fs-4"><?= number_format($soldCount) ?></strong></div></div>
    <div class="col-md-6 col-xl-3"><div class="border rounded-4 p-3 h-100 bg-light"><small class="text-muted d-block">Giá trị đơn trung bình</small><strong class="fs-4"><?= report_money($averageOrder) ?></strong></div></div>
  </div>
</section>

<section class="admin-panel" style="padding: 20px; margin-bottom: 22px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="mb-1">Doanh thu theo ngày</h2>
      <p class="text-muted mb-0">Tháng <?= date('m/Y', strtotime($monthStart)) ?></p>
    </div>
    <i class="fa fa-chart-line text-warning fs-4"></i>
  </div>
  <canvas id="reportChart"></canvas>
</section>

<section class="row g-3">
  <div class="col-lg-7">
    <div class="admin-panel" style="padding: 20px; height: 100%;">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 class="mb-1">Top sách bán chạy</h2>
          <p class="text-muted mb-0">Top 10 theo số lượng bán ra trong tháng</p>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light"><tr><th>#</th><th>Sách</th><th>Đã bán</th><th>Doanh thu</th></tr></thead>
          <tbody>
            <?php if (!$topBooks): ?><tr><td colspan="4" class="text-center text-muted py-3">Chưa có dữ liệu</td></tr><?php else: foreach ($topBooks as $i => $book): ?><tr><td><?= $i + 1 ?></td><td><?= htmlspecialchars($book['book_title']) ?></td><td><?= number_format($book['sold']) ?></td><td><?= report_money($book['revenue']) ?></td></tr><?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="admin-panel" style="padding: 20px; height: 100%;">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 class="mb-1">Tình trạng đơn hàng</h2>
          <p class="text-muted mb-0">Thống kê theo trạng thái</p>
        </div>
      </div>
      <div class="d-grid gap-2">
        <div class="d-flex justify-content-between align-items-center border rounded-3 p-2"><span>Chờ xử lý</span><strong><?= $statusCounts['chờ_xử_lý'] ?></strong></div>
        <div class="d-flex justify-content-between align-items-center border rounded-3 p-2"><span>Đang giao</span><strong><?= $statusCounts['đang_giao'] ?></strong></div>
        <div class="d-flex justify-content-between align-items-center border rounded-3 p-2"><span>Đã giao</span><strong><?= $statusCounts['đã_giao'] ?></strong></div>
        <div class="d-flex justify-content-between align-items-center border rounded-3 p-2"><span>Đã hủy</span><strong><?= $statusCounts['đã_hủy'] ?></strong></div>
      </div>
    </div>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('reportChart'), { type: 'line', data: { labels: <?= json_encode($dailyLabels) ?>, datasets: [{ label: 'Doanh thu', data: <?= json_encode($dailyRevenue) ?>, borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,.12)', fill: true, tension: .35, yAxisID: 'revenue' }, { label: 'Đơn hàng', data: <?= json_encode($dailyOrders) ?>, borderColor: '#f59e0b', backgroundColor: 'transparent', tension: .35, yAxisID: 'orders' }] }, options: { responsive: true, interaction: { mode: 'index', intersect: false }, plugins: { tooltip: { callbacks: { label: function(context) { return context.dataset.label + ': ' + (context.dataset.yAxisID === 'revenue' ? new Intl.NumberFormat('vi-VN').format(context.raw) + 'đ' : context.raw + ' đơn'); } } } }, scales: { revenue: { beginAtZero: true, ticks: { callback: function(value) { return new Intl.NumberFormat('vi-VN', { notation: 'compact' }).format(value) + 'đ'; } } }, orders: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { precision: 0 } } } } });
</script>
<?php admin_layout_end(); ?>
<?php require_once "./template/footer.php"; ?>