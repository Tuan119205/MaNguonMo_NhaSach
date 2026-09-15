<?php
function admin_layout_start($activePage, $title, $eyebrow = 'TỔNG QUAN HỆ THỐNG', $subtitle = '', $headerRight = '') {
    $navItems = [
        'admin_dashboard' => ['label' => 'Dashboard', 'icon' => 'fa-chart-pie', 'href' => 'admin_dashboard.php'],
        'admin_customer' => ['label' => 'Quản lý người dùng', 'icon' => 'fa-users', 'href' => 'admin_customer.php'],
        'admin_book' => ['label' => 'Quản lý sách', 'icon' => 'fa-book', 'href' => 'admin_book.php'],
        'orders' => ['label' => 'Quản lý đơn hàng', 'icon' => 'fa-shopping-bag', 'href' => 'orders.php'],
        'admin_promotions' => ['label' => 'Quản lý khuyến mãi', 'icon' => 'fa-tags', 'href' => 'admin_promotions.php'],
        'admin_reports' => ['label' => 'Thống kê và báo cáo', 'icon' => 'fa-chart-column', 'href' => 'admin_reports.php'],
        'admin_chatbot' => ['label' => 'Quản lý Chatbot', 'icon' => 'fa-robot', 'href' => 'admin_chatbot.php'],
    ];

    echo '<div class="admin-shell">';
    echo '  <aside class="admin-sidebar">';
    echo '    <div class="admin-brand"><span class="brand-mark">VL</span><div><strong>Nhà sách Việt Long</strong><small>Quản trị viên</small></div></div>';
    echo '    <nav class="admin-nav">';
    foreach ($navItems as $key => $nav) {
        $activeClass = ($activePage === $key) ? 'active' : '';
        echo '<a class="' . $activeClass . '" href="' . $nav['href'] . '"><i class="fa ' . $nav['icon'] . '"></i>' . $nav['label'] . '</a>';
    }
    echo '    </nav>';
    echo '    <a class="admin-logout" href="admin_signout.php"><i class="fa fa-sign-out-alt"></i>Đăng xuất</a>';
    echo '  </aside>';
    echo '  <main class="admin-main">';
    echo '    <div class="admin-topbar admin-topbar-' . htmlspecialchars($activePage, ENT_QUOTES, 'UTF-8') . '">';
    echo '      <div>';
    echo '        <span class="eyebrow">' . htmlspecialchars($eyebrow) . '</span>';
    echo '        <h1>' . htmlspecialchars($title) . '</h1>';
    if ($subtitle !== '') {
        echo '        <p class="admin-subtitle">' . htmlspecialchars($subtitle) . '</p>';
    }
    echo '      </div>';
    echo '      <div class="admin-header-tools">' . $headerRight . '</div>';
    echo '    </div>';
}

function admin_layout_end() {
    echo '  </main>';
    echo '</div>';
}
?>
<style>
  :root {
    --admin-yellow: #f0b90b;
    --admin-yellow-soft: #fff5d2;
    --admin-ink: #20242b;
    --admin-muted: #77808d;
    --admin-bg: #f5f6f8;
    --admin-panel: #ffffff;
    --admin-border: #e8eaee;
    --admin-shadow: 0 2px 8px rgba(20, 30, 40, 0.035);
    --admin-sidebar: #20242b;
    --admin-sidebar-text: #bdc3cc;
    --admin-sidebar-active: #343a43;
    --admin-radius: 12px;
  }

  .admin-shell {
    display: flex;
    min-height: calc(100vh - 70px);
    background: var(--admin-bg);
    font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    color: var(--admin-ink);
  }

  .admin-sidebar {
    width: 245px;
    background: var(--admin-sidebar);
    color: #fff;
    padding: 24px 14px;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    box-sizing: border-box;
  }

  .admin-brand {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 0 12px 28px;
    border-bottom: 1px solid #3a3f47;
  }

  .brand-mark {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: var(--admin-yellow);
    color: #20242b;
    font-weight: 800;
    display: grid;
    place-items: center;
    flex-shrink: 0;
  }

  .admin-brand strong,
  .admin-brand small {
    display: block;
    line-height: 1.2;
  }

  .admin-brand strong {
    font-size: 15px;
    color: #fff;
  }

  .admin-brand small {
    color: #aeb5bf;
    font-size: 12px;
    margin-top: 4px;
  }

  .admin-nav {
    padding-top: 20px;
  }

  .admin-nav a,
  .admin-logout {
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--admin-sidebar-text);
    text-decoration: none;
    padding: 12px 14px;
    border-radius: 9px;
    margin-bottom: 5px;
    font-size: 14px;
    transition: 0.2s ease;
  }

  .admin-nav a i,
  .admin-logout i {
    width: 18px;
    text-align: center;
  }

  .admin-nav a:hover,
  .admin-nav a.active,
  .admin-logout:hover {
    background: var(--admin-sidebar-active);
    color: #fff0b3;
  }

  .admin-nav a.active {
    box-shadow: inset 3px 0 var(--admin-yellow);
    color: #ffd45b;
  }

  .admin-logout {
    margin-top: auto;
    border-top: 1px solid #3a3f47;
    border-radius: 0;
    padding-top: 20px;
  }

  .admin-main {
    flex: 1;
    width: 100%;
    max-width: 1240px;
    margin: 0 auto;
    padding: 32px 34px 48px;
    box-sizing: border-box;
  }

  .admin-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
    gap: 16px;
  }

  .eyebrow {
    color: #a07b10;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
  }

  .admin-topbar h1 {
    font-size: 30px;
    margin: 5px 0 0;
    font-weight: 800;
    color: var(--admin-ink);
  }

  .admin-subtitle {
    margin: 8px 0 0;
    color: var(--admin-muted);
    font-size: 13px;
  }

  .admin-header-tools {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .admin-card,
  .admin-panel,
  .card,
  .customer-card,
  .promo-admin-card,
  .report-card {
    background: var(--admin-panel);
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius);
    box-shadow: var(--admin-shadow);
  }

  .admin-button,
  .modern-primary-btn,
  .customer-add,
  .promo-view,
  .admin-toolbar-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 10px 16px;
    border: 1px solid transparent;
    border-radius: 8px;
    background: var(--admin-yellow);
    color: var(--admin-ink);
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .admin-button:hover,
  .modern-primary-btn:hover,
  .customer-add:hover,
  .promo-view:hover,
  .admin-toolbar-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 18px rgba(240, 185, 11, 0.18);
    text-decoration: none;
    color: var(--admin-ink);
  }

  .admin-input,
  .admin-select,
  .modern-search input,
  .customer-search input,
  .modern-filter select,
  .customer-filter,
  .promo-admin-card input,
  .promo-admin-card select,
  .promo-admin-card textarea,
  .report-header input {
    width: 100%;
    min-height: 42px;
    padding: 10px 12px;
    border: 1px solid #dfe3e9;
    border-radius: 8px;
    background: #fff;
    color: #344054;
    font-size: 14px;
    box-sizing: border-box;
  }

  .admin-input:focus,
  .admin-select:focus,
  .customer-search input:focus,
  .modern-filter select:focus,
  .customer-filter:focus,
  .promo-admin-card input:focus,
  .promo-admin-card select:focus,
  .promo-admin-card textarea:focus,
  .report-header input:focus {
    outline: none;
    border-color: #b17d00;
    box-shadow: 0 0 0 3px rgba(240, 185, 11, 0.14);
  }

  .admin-table,
  .modern-books-table,
  .customer-table,
  .promo-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
  }

  .admin-table th,
  .modern-books-table th,
  .customer-table th,
  .promo-table th {
    text-align: left;
    color: #9299a3;
    font-size: 11px;
    font-weight: 700;
    padding: 10px 8px;
    border-bottom: 1px solid #edf0f2;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .admin-table td,
  .modern-books-table td,
  .customer-table td,
  .promo-table td {
    padding: 13px 8px;
    border-bottom: 1px solid #f0f1f3;
    vertical-align: middle;
  }

  .admin-table tr:last-child td,
  .modern-books-table tr:last-child td,
  .customer-table tr:last-child td,
  .promo-table tr:last-child td {
    border-bottom: 0;
  }

  .admin-badge,
  .status-badge,
  .badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 24px;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
  }

  .admin-badge.success,
  .status-success,
  .bg-success {
    background: #dcfce7;
    color: #166534;
  }

  .admin-badge.warning,
  .status-warning,
  .bg-warning {
    background: #fff3cc;
    color: #8a5a00;
  }

  .admin-badge.danger,
  .status-danger,
  .bg-danger {
    background: #fee2e2;
    color: #991b1b;
  }

  .admin-badge.info,
  .status-info,
  .bg-info {
    background: #dff1ff;
    color: #0f4c81;
  }

  .admin-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
    flex-wrap: wrap;
  }

  .admin-section h2 {
    margin: 0;
    font-size: 17px;
    font-weight: 800;
    color: var(--admin-ink);
  }

  .admin-section p {
    margin: 4px 0 0;
    color: var(--admin-muted);
    font-size: 12px;
  }

  @media (max-width: 980px) {
    .admin-shell {
      display: block;
    }

    .admin-sidebar {
      width: 100%;
      min-height: auto;
      padding: 20px 14px 16px;
      position: relative;
    }

    .admin-main {
      padding: 24px 18px 40px;
    }
  }

  @media (max-width: 640px) {
    .admin-topbar {
      flex-direction: column;
      align-items: flex-start;
    }

    .admin-sidebar {
      padding: 16px 10px;
    }

    .admin-main {
      padding: 18px 12px 28px;
    }

    .admin-shell .admin-nav a,
    .admin-shell .admin-logout {
      font-size: 12px;
      padding: 10px 10px;
    }
  }
</style>
