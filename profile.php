<?php
  session_start();

  if (!isset($_SESSION['user']) || $_SESSION['user'] !== true) {
    header('Location: auth.php');
    exit;
  }

  require_once "./functions/database_functions.php";
  $conn = db_connect();
  $title = "Thông tin cá nhân";

  $userid = isset($_SESSION['userid']) ? intval($_SESSION['userid']) : 0;
  $success = '';
  $error = '';

  $infoQuery = "SELECT userid, fullname, email, username, phone FROM users WHERE userid = '$userid' LIMIT 1";
  $infoResult = mysqli_query($conn, $infoQuery);
  $userInfo = mysqli_fetch_assoc($infoResult);

  if (!$userInfo) {
    header('Location: index.php');
    exit;
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if ($fullname === '' || $email === '') {
      $error = 'Họ tên và email không được để trống.';
    } else {
      $email = mysqli_real_escape_string($conn, $email);
      $fullname = mysqli_real_escape_string($conn, $fullname);
      $phone = mysqli_real_escape_string($conn, $phone);

      $checkEmail = "SELECT userid FROM users WHERE email = '$email' AND userid != '$userid' LIMIT 1";
      $checkResult = mysqli_query($conn, $checkEmail);

      if ($checkResult && mysqli_num_rows($checkResult) > 0) {
        $error = 'Email này đã được sử dụng bởi tài khoản khác.';
      } else {
        $updateQuery = "UPDATE users SET fullname = '$fullname', email = '$email', phone = '$phone' WHERE userid = '$userid'";
        if (mysqli_query($conn, $updateQuery)) {
          $_SESSION['fullname'] = $fullname;
          $_SESSION['email'] = $email;
          $userInfo['fullname'] = $fullname;
          $userInfo['email'] = $email;
          $userInfo['phone'] = $phone;
          $success = 'Cập nhật thông tin thành công.';
        } else {
          $error = 'Cập nhật thất bại. Vui lòng thử lại.';
        }
      }
    }
  }

  require_once "./template/header.php";
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4">
          <h3 class="mb-4 text-center">Thông tin cá nhân</h3>

          <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
          <?php endif; ?>

          <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
          <?php endif; ?>

          <form method="post" action="profile.php">
            <div class="mb-3">
              <label class="form-label">Tên đăng nhập</label>
              <input type="text" class="form-control" value="<?php echo htmlspecialchars($userInfo['username']); ?>" disabled>
            </div>

            <div class="mb-3">
              <label for="fullname" class="form-label">Họ tên</label>
              <input type="text" name="fullname" id="fullname" class="form-control" value="<?php echo htmlspecialchars($userInfo['fullname']); ?>" required>
            </div>

            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($userInfo['email']); ?>" required>
            </div>

            <div class="mb-3">
              <label for="phone" class="form-label">Số điện thoại</label>
              <input type="text" name="phone" id="phone" class="form-control" value="<?php echo htmlspecialchars($userInfo['phone'] ?? ''); ?>">
            </div>

            <div class="d-grid gap-2">
              <button type="submit" name="update_profile" class="btn btn-warning">Lưu thay đổi</button>
              <a href="delivery_management.php" class="btn btn-outline-info">
                <i class="fa fa-map-marker-alt"></i> Địa Chỉ
              </a>
              <a href="orders.php" class="btn btn-outline-primary">
                <i class="fa fa-box"></i> Đơn Hàng
              </a>
              <a href="index.php" class="btn btn-outline-secondary">Quay lại</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
  if(isset($conn)) { mysqli_close($conn); }
  require_once "./template/footer.php";
?>
