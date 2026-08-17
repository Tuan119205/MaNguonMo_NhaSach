<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
  header('Location: admin.php');
  exit;
}
require_once './functions/database_functions.php';
$conn = db_connect();
$title = 'Dashboard Admin';

function admin_count($conn, $sql)
{
  $r = mysqli_query($conn, $sql);
  if (!$r) return 0;
  $row = mysqli_fetch_row($r);
  return $row[0] ?? 0;
}
function admin_money($value)
{
  return number_format((float)$value, 0, ',', '.') . 'đ';
}

$totalRevenue = admin_count($conn, "SELECT COALESCE(SUM(amount),0) FROM orders WHERE order_status NOT IN ('đã_hủy','cancelled','đã hủy')");
$totalOrders = admin_count($conn, "SELECT COUNT(*) FROM orders");
$totalSold = admin_count($conn, "SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi INNER JOIN orders o ON o.orderid=oi.orderid WHERE o.order_status NOT IN ('đã_hủy','cancelled','đã hủy')");
$totalUsers = admin_count($conn, "SELECT COUNT(*) FROM users");
$pendingOrders = admin_count($conn, "SELECT COUNT(*) FROM orders WHERE order_status IN ('chờ_xử_lý','pending')");
$shippingOrders = admin_count($conn, "SELECT COUNT(*) FROM orders WHERE order_status IN ('đang_giao','shipping')");
$deliveredOrders = admin_count($conn, "SELECT COUNT(*) FROM orders WHERE order_status IN ('đã_giao','delivered')");
$cancelledOrders = admin_count($conn, "SELECT COUNT(*) FROM orders WHERE order_status IN ('đã_hủy','cancelled','đã hủy')");

$topBooks = array();
$topResult = mysqli_query($conn, "SELECT b.book_title, COALESCE(SUM(oi.quantity),0) sold, COALESCE(SUM(oi.quantity*oi.item_price),0) revenue FROM order_items oi INNER JOIN books b ON b.book_isbn=oi.book_isbn INNER JOIN orders o ON o.orderid=oi.orderid WHERE o.order_status NOT IN ('đã_hủy','cancelled','đã hủy') GROUP BY b.book_isbn,b.book_title ORDER BY sold DESC LIMIT 5");
if ($topResult) while ($row = mysqli_fetch_assoc($topResult)) $topBooks[] = $row;

$recentOrders = array();
$recentResult = mysqli_query($conn, "SELECT orderid, ship_name, amount, order_status, date FROM orders ORDER BY date DESC, orderid DESC LIMIT 8");
if ($recentResult) while ($row = mysqli_fetch_assoc($recentResult)) $recentOrders[] = $row;

$chartLabels = array();
$chartValues = array();
$chartRevenue = array();
$chartStart = new DateTime('first day of this month');
$chartEnd = new DateTime('first day of next month');
$chartCursor = clone $chartStart;
while ($chartCursor < $chartEnd) {
  $chartLabels[] = $chartCursor->format('d/m');
  $chartValues[] = 0;
  $chartRevenue[] = 0;
  $chartCursor->modify('+1 day');
}
$chartResult = mysqli_query($conn, "SELECT DAY(o.date) day, COALESCE(SUM(o.amount),0) revenue, COALESCE(SUM(items.qty),0) qty FROM orders o LEFT JOIN (SELECT orderid, SUM(quantity) qty FROM order_items GROUP BY orderid) items ON items.orderid=o.orderid WHERE o.date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND o.date < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH) AND o.order_status NOT IN ('đã_hủy','cancelled','đã hủy') GROUP BY DAY(o.date) ORDER BY day");
if ($chartResult) while ($row = mysqli_fetch_assoc($chartResult)) {
  $index = max(0, min(count($chartLabels) - 1, ((int)$row['day']) - 1));
  $chartValues[$index] = (int)$row['qty'];
  $chartRevenue[$index] = (float)$row['revenue'];
}
require './template/header.php';
?>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <div class="admin-brand"><span class="brand-mark">VL</span>
      <div><strong>Việt Long</strong><small>Admin Panel</small></div>
    </div>
    <nav class="admin-nav">
      <a class="active" href="admin_dashboard.php"><i class="fa fa-chart-pie"></i>Dashboard</a>
      <a href="admin_customer.php"><i class="fa fa-users"></i>Quản lý người dùng</a>
      <a href="admin_book.php"><i class="fa fa-book"></i>Quản lý sách</a>
      <a href="orders.php"><i class="fa fa-shopping-bag"></i>Quản lý đơn hàng</a>
    </nav>
    <a class="admin-logout" href="admin_signout.php"><i class="fa fa-sign-out-alt"></i>Đăng xuất</a>
  </aside>
  <main class="admin-main">
    <div class="admin-topbar">
      <div><span class="eyebrow">TỔNG QUAN HỆ THỐNG</span>
        <h1>Dashboard</h1>
      </div>
      <div class="admin-header-tools"></div>
    </div>
    <section class="stat-grid">
      <div class="stat-card revenue"><span class="stat-icon"><i class="fa fa-wallet"></i></span>
        <div><small>Tổng doanh thu</small><strong><?= admin_money($totalRevenue) ?></strong></div>
      </div>
      <div class="stat-card orders"><span class="stat-icon"><i class="fa fa-shopping-bag"></i></span>
        <div><small>Tổng đơn hàng</small><strong><?= number_format($totalOrders) ?></strong></div>
      </div>
      <div class="stat-card books"><span class="stat-icon"><i class="fa fa-book"></i></span>
        <div><small>Sách đã bán</small><strong><?= number_format($totalSold) ?></strong></div>
      </div>
      <div class="stat-card users"><span class="stat-icon"><i class="fa fa-users"></i></span>
        <div><small>Tổng người dùng</small><strong><?= number_format($totalUsers) ?></strong></div>
      </div>
    </section>
    <section class="admin-grid main-grid">
      <div class="admin-panel chart-panel">
        <div class="panel-heading">
          <div>
            <h2>Doanh thu trong tháng</h2>
            <p>Doanh thu theo từng ngày trong tháng hiện tại, không tính đơn đã hủy</p>
          </div><span class="panel-icon"><i class="fa fa-chart-line"></i></span>
        </div><canvas id="salesChart" height="115"></canvas>
      </div>
      <div class="admin-panel order-status-panel">
        <div class="panel-heading">
          <div>
            <h2>Trạng thái đơn hàng</h2>
            <p>Cập nhật theo dữ liệu thực tế</p>
          </div>
        </div>
        <div class="status-list">
          <div><span class="dot pending"></span>Chờ xử lý<strong><?= $pendingOrders ?></strong></div>
          <div><span class="dot shipping"></span>Đang giao<strong><?= $shippingOrders ?></strong></div>
          <div><span class="dot delivered"></span>Đã giao<strong><?= $deliveredOrders ?></strong></div>
          <div><span class="dot cancelled"></span>Đã hủy<strong><?= $cancelledOrders ?></strong></div>
        </div><a class="panel-link" href="orders.php">Xem quản lý đơn hàng <i class="fa fa-arrow-right"></i></a>
      </div>
    </section>
    <section class="admin-grid lower-grid">
      <div class="admin-panel">
        <div class="panel-heading">
          <div>
            <h2>Top sách bán chạy</h2>
            <p>Top 5 theo số lượng thực bán</p>
          </div><a href="admin_book.php" class="panel-link">Quản lý sách</a>
        </div>
        <div class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Sách</th>
                <th>Đã bán</th>
                <th>Doanh thu</th>
              </tr>
            </thead>
            <tbody><?php if (!$topBooks): ?><tr>
                  <td colspan="4" class="empty">Chưa có dữ liệu</td>
                </tr><?php else: foreach ($topBooks as $i => $book): ?><tr>
                    <td><span class="rank"><?= $i + 1 ?></span></td>
                    <td class="book-name"><?= htmlspecialchars($book['book_title']) ?></td>
                    <td><?= number_format($book['sold']) ?> cuốn</td>
                    <td class="money"><?= admin_money($book['revenue']) ?></td>
                  </tr><?php endforeach;
                    endif; ?></tbody>
          </table>
        </div>
      </div>
      <div class="admin-panel">
        <div class="panel-heading">
          <div>
            <h2>Đơn hàng gần đây</h2>
            <p>8 đơn mới nhất</p>
          </div><a href="orders.php" class="panel-link">Xem tất cả</a>
        </div>
        <div class="recent-list"><?php if (!$recentOrders): ?><div class="empty">Chưa có đơn hàng</div><?php else: foreach ($recentOrders as $order): $status = $order['order_status'] ?? 'chờ_xử_lý';
                                                                                                          $statusName = ($status === 'đã_giao' || $status === 'delivered') ? 'Đã giao' : (($status === 'đang_giao' || $status === 'shipping') ? 'Đang giao' : (($status === 'đã_hủy' || $status === 'cancelled') ? 'Đã hủy' : 'Chờ xử lý')); ?><a href="orders.php" class="recent-item"><span class="recent-id">#DH<?= str_pad($order['orderid'], 5, '0', STR_PAD_LEFT) ?></span><span class="recent-customer"><?= htmlspecialchars($order['ship_name']) ?><small><?= date('d/m/Y H:i', strtotime($order['date'])) ?></small></span><span class="recent-total"><?= admin_money($order['amount']) ?><small><?= $statusName ?></small></span></a><?php endforeach;
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                endif; ?></div>
      </div>
    </section>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: {
          labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
          datasets: [{
            data: <?= json_encode($chartRevenue) ?>,
            borderColor: '#b17d00',
            backgroundColor: 'rgba(242,190,45,.14)',
            fill: true,
            tension: .35,
            pointRadius: 4,
            pointBackgroundColor: '#b17d00'
          }]
        },
        options: {
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              callbacks: {
                label: function(context) { return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(context.raw) + 'đ'; }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0,
                callback: function(value) { return new Intl.NumberFormat('vi-VN', { notation: 'compact' }).format(value) + 'đ'; }
              }
            },
            x: {
              grid: {
                display: false
              }
            }
          }
        }
      });
</script>
<style>
  :root {
    --admin-yellow: #f0b90b;
    --admin-ink: #20242b;
    --admin-muted: #77808d;
    --admin-bg: #f5f6f8
  }

  .admin-shell {
    display: flex;
    min-height: calc(100vh - 70px);
    background: var(--admin-bg);
    font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    color: var(--admin-ink)
  }

  .admin-sidebar {
    width: 245px;
    background: #20242b;
    color: #fff;
    padding: 24px 14px;
    display: flex;
    flex-direction: column;
    flex-shrink: 0
  }

  .admin-brand {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 0 12px 28px;
    border-bottom: 1px solid #3a3f47
  }

  .brand-mark {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: var(--admin-yellow);
    color: #20242b;
    font-weight: 800;
    display: grid;
    place-items: center
  }

  .admin-brand strong,
  .admin-brand small {
    display: block
  }

  .admin-brand small {
    color: #aeb5bf;
    font-size: 11px;
    margin-top: 3px
  }

  .admin-nav {
    padding-top: 20px
  }

  .admin-nav a,
  .admin-logout {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #bdc3cc;
    text-decoration: none;
    padding: 12px 14px;
    border-radius: 9px;
    margin-bottom: 5px;
    font-size: 14px;
    transition: .2s
  }

  .admin-nav a i,
  .admin-logout i {
    width: 18px;
    text-align: center
  }

  .admin-nav a:hover,
  .admin-nav a.active {
    background: #343a43;
    color: #fff
  }

  .admin-nav a.active {
    box-shadow: inset 3px 0 var(--admin-yellow);
    color: #ffd45b
  }

  .admin-logout {
    margin-top: auto;
    border-top: 1px solid #3a3f47;
    border-radius: 0;
    padding-top: 20px
  }

  .admin-main {
    max-width: 1240px;
    width: 100%;
    margin: 0 auto;
    padding: 32px 34px 48px
  }

  .admin-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px
  }

  .eyebrow {
    color: #a07b10;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .12em
  }

  .admin-topbar h1 {
    font-size: 30px;
    margin: 5px 0 0;
    font-weight: 800
  }

  .admin-user {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #535b66;
    font-weight: 600
  }

  .avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: #fff0bb;
    color: #b27d00
  }

  .stat-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 20px
  }

  .stat-card {
    background: #fff;
    border: 1px solid #e8eaee;
    border-radius: 12px;
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 2px 8px rgba(20, 30, 40, .035)
  }

  .stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 11px;
    display: grid;
    place-items: center;
    font-size: 18px
  }

  .revenue .stat-icon {
    background: #fff4d3;
    color: #b58100
  }

  .orders .stat-icon {
    background: #e6f1ff;
    color: #3576bf
  }

  .books .stat-icon {
    background: #e7f7ef;
    color: #26945c
  }

  .users .stat-icon {
    background: #f0eaff;
    color: #7653b8
  }

  .stat-card small,
  .stat-card strong {
    display: block
  }

  .stat-card small {
    color: var(--admin-muted);
    font-size: 12px;
    margin-bottom: 5px
  }

  .stat-card strong {
    font-size: 21px
  }

  .admin-grid {
    display: grid;
    gap: 20px
  }

  .main-grid {
    grid-template-columns: 1.65fr 1fr
  }

  .lower-grid {
    grid-template-columns: 1.2fr 1fr;
    margin-top: 20px
  }

  .admin-panel {
    background: #fff;
    border: 1px solid #e8eaee;
    border-radius: 12px;
    padding: 22px;
    box-shadow: 0 2px 8px rgba(20, 30, 40, .035);
    min-width: 0
  }

  .panel-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 18px
  }

  .panel-heading h2 {
    font-size: 17px;
    margin: 0 0 5px;
    font-weight: 750
  }

  .panel-heading p {
    font-size: 12px;
    color: var(--admin-muted);
    margin: 0
  }

  .panel-icon {
    color: #d49c00;
    font-size: 20px
  }

  .panel-link {
    color: #b17d00;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap
  }

  .panel-link:hover {
    text-decoration: underline
  }

  .status-list>div {
    display: flex;
    align-items: center;
    padding: 14px 0;
    border-bottom: 1px solid #f0f1f3;
    font-size: 13px
  }

  .status-list strong {
    margin-left: auto;
    font-size: 15px
  }

  .dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    margin-right: 10px
  }

  .dot.pending {
    background: #edb62d
  }

  .dot.shipping {
    background: #4796da
  }

  .dot.delivered {
    background: #39aa70
  }

  .dot.cancelled {
    background: #df6464
  }

  .status-list+.panel-link {
    display: block;
    margin-top: 18px
  }

  .admin-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px
  }

  .admin-table th {
    text-align: left;
    color: #9299a3;
    font-size: 11px;
    font-weight: 700;
    padding: 10px 8px;
    border-bottom: 1px solid #edf0f2
  }

  .admin-table td {
    padding: 13px 8px;
    border-bottom: 1px solid #f0f1f3
  }

  .admin-table tr:last-child td {
    border: 0
  }

  .rank {
    display: grid;
    place-items: center;
    width: 24px;
    height: 24px;
    border-radius: 7px;
    background: #fff4ce;
    color: #a97800;
    font-weight: 800
  }

  .book-name {
    font-weight: 650
  }

  .money {
    font-weight: 700;
    color: #c18800
  }

  .table-wrap {
    overflow-x: auto
  }

  .empty {
    text-align: center;
    color: #9aa1aa;
    padding: 25px !important
  }

  .recent-list {
    display: flex;
    flex-direction: column
  }

  .recent-item {
    display: grid;
    grid-template-columns: 72px 1fr auto;
    gap: 10px;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f1f3;
    text-decoration: none;
    color: var(--admin-ink);
    font-size: 12px
  }

  .recent-item:last-child {
    border: 0
  }

  .recent-id {
    font-weight: 750;
    color: #b27d00
  }

  .recent-customer {
    font-weight: 650;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap
  }

  .recent-item small {
    display: block;
    color: #9aa1aa;
    font-weight: 400;
    margin-top: 3px
  }

  .recent-total {
    text-align: right;
    font-weight: 700
  }

  .recent-total small {
    color: #b27d00
  }

  .order-status-panel {
    min-height: 280px
  }

  @media(max-width:1000px) {
    .admin-sidebar {
      width: 210px
    }

    .admin-main {
      padding: 25px 20px
    }

    .stat-grid {
      grid-template-columns: repeat(2, 1fr)
    }

    .main-grid,
    .lower-grid {
      grid-template-columns: 1fr
    }
  }

  @media(max-width:650px) {
    .admin-shell {
      display: block
    }

    .admin-sidebar {
      width: 100%;
      padding: 12px;
      min-height: auto
    }

    .admin-brand {
      padding: 4px 8px 12px
    }

    .admin-nav {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 2px;
      padding-top: 10px
    }

    .admin-nav a {
      margin: 0;
      padding: 9px;
      font-size: 12px
    }

    .admin-logout {
      margin-top: 8px;
      padding: 10px 8px;
      border-top: 1px solid #3a3f47
    }

    .admin-main {
      padding: 20px 12px 32px
    }

    .admin-topbar {
      margin-bottom: 20px
    }

    .admin-topbar h1 {
      font-size: 24px
    }

    .admin-user span:last-child {
      display: none
    }

    .stat-grid {
      gap: 10px
    }

    .stat-card {
      padding: 13px 10px;
      gap: 9px
    }

    .stat-icon {
      width: 36px;
      height: 36px;
      font-size: 15px
    }

    .stat-card strong {
      font-size: 16px
    }

    .stat-card small {
      font-size: 10px
    }

    .admin-panel {
      padding: 16px
    }

    .recent-item {
      grid-template-columns: 65px 1fr auto
    }

    .admin-table {
      min-width: 500px
    }
  }
/* Modern light admin override */
:root{--admin-indigo:#4f46e5;--admin-indigo-soft:#eef2ff;--admin-slate:#0f172a;--admin-bg:#f8fafc;--admin-border:#e2e8f0;--admin-muted:#64748b}
html,body{margin:0!important;padding:0!important;width:100%;min-height:100%;overflow-x:hidden}.admin-shell{min-height:100vh;width:100vw;margin:0;background:var(--admin-bg);font-family:Inter,Roboto,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#0f172a}
.admin-sidebar{width:248px;background:var(--admin-slate);padding:24px 14px;border-right:1px solid #1e293b}
.admin-brand{padding:0 12px 24px;border-bottom:1px solid #263449}.brand-mark{background:var(--admin-indigo);color:#fff;border-radius:10px}.admin-brand strong{color:#f8fafc}.admin-brand small{color:#94a3b8}
.admin-nav{padding-top:18px}.admin-nav a,.admin-logout{color:#94a3b8;border-radius:8px;margin-bottom:4px;padding:11px 13px;transition:background .2s,color .2s}.admin-nav a:hover{background:#1e293b;color:#fff}.admin-nav a.active{background:var(--admin-indigo);box-shadow:none;color:#fff}.admin-logout{border-top:1px solid #263449;color:#94a3b8}
.admin-main{max-width:none;width:auto;flex:1;min-width:0;margin:0;padding:28px 32px 44px}.admin-topbar{margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--admin-border)}.eyebrow{color:var(--admin-indigo);font-size:11px}.admin-topbar h1{font-size:26px;color:#0f172a}.admin-header-tools{display:flex;align-items:center;gap:16px}.admin-search{display:flex;align-items:center;gap:9px;background:#fff;border:1px solid var(--admin-border);border-radius:8px;padding:0 12px;height:38px}.admin-search i{color:#94a3b8}.admin-search input{width:180px;border:0;outline:0;color:#334155;background:transparent}.admin-notification{position:relative;background:#fff;border:1px solid var(--admin-border);border-radius:8px;width:38px;height:38px;color:#64748b}.admin-notification span{position:absolute;width:7px;height:7px;border-radius:50%;background:#ef4444;right:8px;top:7px}.admin-user{color:#334155}.admin-user-chevron{font-size:10px;color:#94a3b8}.avatar{background:var(--admin-indigo-soft);color:var(--admin-indigo)}
.stat-grid{gap:16px;margin-bottom:18px}.stat-card{border:1px solid var(--admin-border);border-radius:12px;box-shadow:0 1px 3px rgba(15,23,42,.04);padding:18px}.stat-icon{border-radius:9px}.revenue .stat-icon,.orders .stat-icon,.books .stat-icon,.users .stat-icon{background:var(--admin-indigo-soft);color:var(--admin-indigo)}.stat-card small{color:var(--admin-muted)}.stat-card strong{color:#0f172a}
.admin-panel{border:1px solid var(--admin-border);border-radius:12px;box-shadow:0 1px 3px rgba(15,23,42,.04);padding:20px}.panel-heading h2{color:#0f172a}.panel-heading p{color:var(--admin-muted)}.panel-icon,.panel-link,.money{color:var(--admin-indigo)}.status-list>div{border-color:#f1f5f9}.dot.pending{background:#f59e0b}.dot.shipping{background:#3b82f6}.dot.delivered{background:#10b981}.dot.cancelled{background:#ef4444}.admin-table th{color:#64748b;background:#f8fafc}.admin-table td{border-color:#f1f5f9}.rank{background:var(--admin-indigo-soft);color:var(--admin-indigo)}
.admin-search:focus-within{border-color:var(--admin-indigo);box-shadow:0 0 0 3px rgba(79,70,229,.1)}
@media(max-width:650px){.admin-main{padding:20px 14px 32px}.admin-header-tools{gap:8px}.admin-search{flex:1}.admin-search input{width:100px}.admin-user-chevron{display:none}}
.site-footer,.site-footer-spacer{display:none!important}
/* Dashboard đồng bộ với giao diện Quản lý sách */
.admin-shell{display:flex;min-height:100vh;width:100vw;background:#f5f6f8;color:#20242b;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.admin-sidebar{position:fixed;inset:0 auto 0 0;z-index:20;width:250px;height:100vh;box-sizing:border-box;background:#20242b;padding:24px 14px;border:0}.admin-brand{padding:0 10px 24px;border-bottom:1px solid #3b4149}.brand-mark{width:38px;height:38px;background:#f0b90b;color:#20242b}.admin-brand strong{font-size:13px}.admin-brand small{font-size:11px;color:#aeb5bf}.admin-nav{padding-top:20px}.admin-nav a,.admin-logout{display:flex;align-items:center;gap:11px;color:#bcc3cc;padding:12px 13px;border-radius:8px;margin-bottom:4px;font-size:13px}.admin-nav a:hover,.admin-nav a.active{background:#343a43;color:#ffd45b;box-shadow:inset 3px 0 #f0b90b}.admin-logout{margin-top:auto;border-top:1px solid #3b4149;border-radius:0;padding-top:20px}.admin-main{width:auto;max-width:none;flex:1;min-width:0;margin-left:250px;padding:30px 34px 50px}.admin-topbar{margin-bottom:26px;padding-bottom:22px;border-bottom:1px solid #e2e8f0}.eyebrow{color:#a17c0f}.admin-topbar h1{font-size:28px;margin:5px 0 3px}.admin-search{height:40px;border-radius:8px}.stat-grid{grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}.stat-card{border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 2px 5px rgba(15,23,42,.04);padding:18px}.stat-icon,.revenue .stat-icon,.orders .stat-icon,.books .stat-icon,.users .stat-icon{background:#fff1bd;color:#b17d00}.admin-panel{border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 2px 5px rgba(15,23,42,.04);padding:20px}.panel-icon,.panel-link,.money{color:#b17d00}.rank{background:#fff1bd;color:#a17c0f}.main-grid{grid-template-columns:1.65fr 1fr}.lower-grid{grid-template-columns:1.2fr 1fr}
@media(max-width:1000px){.admin-main{padding:25px 20px}.stat-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:650px){.admin-shell{display:block}.admin-sidebar{position:relative;width:100%;height:auto;min-height:auto;padding:12px}.admin-main{margin-left:0;padding:20px 14px 32px}.admin-nav{display:grid;grid-template-columns:repeat(2,1fr);gap:2px}.admin-nav a{margin:0;padding:9px;font-size:12px}.admin-logout{margin-top:8px}.admin-topbar{align-items:flex-start}.admin-header-tools{flex-wrap:wrap}}

</style>
<?php if (isset($conn)) mysqli_close($conn);
require './template/footer.php'; ?>
<style>
/* Dashboard viewport overflow fix */
html,body{margin:0!important;padding:0!important;width:100%;max-width:100%;overflow-x:hidden}.admin-shell{display:flex!important;width:100%!important;max-width:100%!important;min-height:100vh;overflow:hidden}.admin-sidebar{position:fixed!important;left:0;top:0;bottom:0;width:250px!important;height:100vh!important;box-sizing:border-box;flex:0 0 250px}.admin-main{display:block!important;width:calc(100% - 250px)!important;max-width:none!important;min-width:0!important;box-sizing:border-box;margin-left:250px!important;padding:30px 34px 50px!important;overflow:hidden}.admin-topbar{width:100%;box-sizing:border-box;min-width:0}.admin-topbar h1{white-space:nowrap}.admin-header-tools{min-width:0;max-width:100%}.admin-user{white-space:nowrap}.stat-grid{width:100%;max-width:100%;grid-template-columns:repeat(4,minmax(0,1fr));box-sizing:border-box}.stat-card{min-width:0;overflow:hidden}.stat-card strong{white-space:nowrap}.main-grid,.lower-grid{width:100%;max-width:100%;min-width:0;box-sizing:border-box}.admin-panel{min-width:0;overflow:hidden}.chart-wrap,.status-list,.admin-table-wrap{max-width:100%;overflow:hidden}.admin-table{width:100%;table-layout:fixed}.admin-table th,.admin-table td{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}@media(max-width:1100px){.stat-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.main-grid,.lower-grid{grid-template-columns:1fr}.admin-topbar h1{font-size:25px}.admin-user{display:none}}@media(max-width:650px){.admin-shell{display:block!important;overflow:visible}.admin-sidebar{position:relative!important;width:100%!important;height:auto!important;min-height:auto}.admin-main{width:100%!important;margin-left:0!important;padding:20px 14px 32px!important;overflow:visible}.admin-topbar{display:block}.admin-header-tools{margin-top:15px;justify-content:stretch}.admin-search{flex:1}.stat-grid{grid-template-columns:1fr 1fr}.main-grid,.lower-grid{grid-template-columns:1fr}.admin-table{table-layout:auto;min-width:650px}.admin-table-wrap{overflow-x:auto}}
</style>

<style>
/* Remove the empty strip above dashboard content */
body:has(.admin-shell) .clear-fix,body:has(.admin-shell) .site-footer-spacer,body:has(.admin-shell) .pt-5{display:none!important}body:has(.admin-shell) .admin-shell{margin-top:0!important;padding-top:0!important}.admin-main{padding-top:0!important}.admin-topbar{margin-top:0!important;padding-top:30px!important}
</style>
