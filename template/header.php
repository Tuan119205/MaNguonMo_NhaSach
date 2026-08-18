<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php
      // Auto-run database migrations
      if (!isset($_SESSION['db_migration_done'])) {
        require_once __DIR__ . '/../db_migration.php';
        runDatabaseMigrations();
        $_SESSION['db_migration_done'] = true;
      }
    ?>

    <title><?php echo $title; ?></title>

    <!-- Google Fonts for crisp Vietnamese typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link href="./bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="./bootstrap/css/styles.css" rel="stylesheet">
    <style>
      .user-account-menu .user-menu-toggle { display: inline-flex; align-items: center; gap: 7px; border: 1px solid rgba(0,0,0,.12); border-radius: 999px; padding: 7px 12px; background: rgba(255,255,255,.35); color: #212529; font-size: .9rem; }
      .user-account-menu .user-menu-toggle:hover, .user-account-menu .user-menu-toggle:focus { background: rgba(255,255,255,.7); color: #212529; box-shadow: none; }
      .user-account-menu .dropdown-menu { min-width: 170px; margin-top: 8px; padding: 6px; border: 0; border-radius: 10px; box-shadow: 0 8px 22px rgba(0,0,0,.15); }
      .user-account-menu .dropdown-item { border-radius: 7px; padding: 8px 10px; font-size: .9rem; }
      .user-account-menu .dropdown-item:hover { background: #f8f1d7; }
      .user-account-menu .dropdown-item.text-danger:hover { background: #fff0f0; }
      @media (min-width: 992px) {
        .navbar > .container { flex-wrap: nowrap; }
        .navbar-brand { flex: 0 0 auto; white-space: nowrap; margin-right: 24px !important; }
        #topNav { display: flex; align-items: center; min-width: 0; flex-wrap: nowrap; }
        #topNav > .navbar-nav.me-auto { display: flex; flex: 1 1 auto; flex-wrap: nowrap; margin-right: 12px !important; min-width: 0; }
        #topNav > .navbar-nav .nav-item { flex: 0 0 auto; }
        #topNav > .navbar-nav .nav-link { display: flex; align-items: center; gap: 6px; white-space: nowrap; padding-left: 8px; padding-right: 8px; }
        #topNav > form[role="search"] { flex: 0 1 245px; width: 245px; margin-left: 8px; margin-right: 10px !important; }
        #topNav > form[role="search"] input { min-width: 0; }
        #topNav > form[role="search"] .btn { flex: 0 0 auto; }
        #topNav > a.nav-link { white-space: nowrap; padding-left: 8px; padding-right: 8px; }
        #topNav > .user-account-menu { flex: 0 0 auto; margin-left: 4px; }
      }
      @media (max-width: 991.98px) {
        #topNav > form[role="search"] { margin: 10px 0; }
        #topNav > .navbar-nav { display: flex; flex-direction: row; flex-wrap: nowrap; justify-content: space-between; align-items: center; width: 100%; gap: 4px; overflow-x: auto; }
        #topNav > .navbar-nav .nav-item { flex: 1 1 auto; min-width: max-content; }
        #topNav > .navbar-nav .nav-link { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 8px; white-space: nowrap; font-size: 1rem; }
        #topNav > .navbar-nav .nav-link i { flex: 0 0 auto; font-size: 1.05rem; }
      }
      @media (max-width: 575.98px) {
        #topNav > .navbar-nav .nav-link { padding-left: 6px; padding-right: 6px; font-size: .95rem; }
      }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/js/all.min.js" integrity="sha512-6PM0qYu5KExuNcKt5bURAoT6KCThUmHRewN3zUFNaoI6Di7XJPTMoT6K0nsagZKk2OB4L7E3q1uQKHNHd4stIQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script type="text/javascript" src="./bootstrap/js/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="./bootstrap/js/bootstrap.bundle.min.js"></script>
  </head>

  <body>
    <?php
      $currentPage = basename($_SERVER['PHP_SELF']);
      $isAuthPage = ($currentPage === 'auth.php');
      // Chỉ các trang quản trị mới ẩn menu người dùng. Không dựa vào session admin,
      // để admin vẫn nhìn thấy giao diện người dùng khi quay về trang chủ.
      $adminPages = ['admin.php', 'admin_dashboard.php', 'admin_book.php', 'admin_customer.php', 'admin_customer_add.php', 'admin_add.php', 'admin_promotions.php', 'admin_reports.php', 'admin_edit.php', 'admin_delete.php', 'admin_verify.php'];
      // orders.php dùng chung cho người dùng và Admin: chỉ ẩn navbar khi Admin thật sự ở trang này.
      $isAdminPage = in_array($currentPage, $adminPages, true) || ($currentPage === 'orders.php' && isset($_SESSION['admin']) && $_SESSION['admin'] === true);
    ?>
    <?php if(!$isAdminPage): ?>
    <nav class="navbar navbar-expand-lg navbar-light bg-warning bg-gradient">
      <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">Nhà Sách Việt Long</a>
        <?php if(!$isAuthPage): ?>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav" aria-label="Mở menu"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="topNav">
          <ul class="navbar-nav me-auto">
            <li class="nav-item"><a class="nav-link" href="index.php"><i class="fa fa-house"></i> Trang chủ</a></li>
            <li class="nav-item"><a class="nav-link" href="books.php"><i class="fa fa-book"></i> Sách</a></li>
            <li class="nav-item"><a class="nav-link <?php echo $currentPage === 'promotions.php' ? 'active' : ''; ?>" href="promotions.php"><i class="fa fa-tag"></i> Khuyến mãi</a></li>
            <?php if(isset($_SESSION['admin']) && $_SESSION['admin'] == true): ?>
              <li class="nav-item"><a class="nav-link" href="admin_dashboard.php"><i class="fa fa-chart-pie"></i> Dashboard</a></li>
              <li class="nav-item"><a class="nav-link" href="admin_customer.php"><i class="fa fa-users"></i> Người dùng</a></li>
              <li class="nav-item"><a class="nav-link" href="admin_book.php"><i class="fa fa-book"></i> Quản lý sách</a></li>
              <li class="nav-item"><a class="nav-link" href="orders.php"><i class="fa fa-box"></i> Đơn hàng</a></li>
            <?php elseif(isset($_SESSION['user']) && $_SESSION['user'] == true): ?>
              <li class="nav-item"><a class="nav-link" href="orders.php"><i class="fa fa-box"></i> Đơn hàng</a></li>
              <li class="nav-item"><a class="nav-link" href="cart.php"><i class="fa fa-shopping-cart"></i> Giỏ hàng</a></li>
            <?php endif; ?>
          </ul>
          <form class="d-flex me-3" method="get" action="books.php" role="search"><input class="form-control form-control-sm" name="q" placeholder="Tìm sách..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>"><button class="btn btn-sm btn-outline-dark ms-1" type="submit"><i class="fa fa-search"></i></button></form>
          <?php if(isset($_SESSION['admin']) && $_SESSION['admin'] == true): ?>
            <a class="nav-link" href="admin.php"><i class="fa fa-user-shield"></i> Admin</a><a class="nav-link text-danger" href="admin_signout.php">Đăng xuất</a>
          <?php elseif(isset($_SESSION['user']) && $_SESSION['user'] == true): ?>
            <div class="user-account-menu dropdown">
              <button class="user-menu-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa fa-user"></i> <?= htmlspecialchars($_SESSION['fullname']); ?>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="profile.php"><i class="fa fa-id-card me-2"></i>Hồ sơ cá nhân</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="customer_signout.php"><i class="fa fa-right-from-bracket me-2"></i>Đăng xuất</a></li>
              </ul>
            </div>
          <?php else: ?><a class="nav-link" href="auth.php"><i class="fa fa-user"></i> Tài khoản</a><?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </nav>
    <?php endif; ?>
    <?php
      if(isset($title) && $title == "Home" && !$isAdminPage) {
    ?>
    <!-- Main jumbotron for a primary marketing message or call to action -->
      <div class="container">
        <hr>
      </div>
    <?php } ?>

    <div class="container" id="pageContent">
