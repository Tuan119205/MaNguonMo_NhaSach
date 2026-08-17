<?php
session_start();
require_once './functions/admin.php';
require_once './functions/database_functions.php';
$conn = db_connect();
$title = 'Thêm khách hàng';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullname = trim($_POST['fullname'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $username = trim($_POST['username'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $password = $_POST['password'] ?? '';
  if ($fullname === '' || $email === '' || $username === '' || strlen($password) < 6) {
    $err = 'Vui lòng nhập đủ thông tin và mật khẩu ít nhất 6 ký tự.';
  } else {
    $fullname = mysqli_real_escape_string($conn, $fullname);
    $email = mysqli_real_escape_string($conn, $email);
    $username = mysqli_real_escape_string($conn, $username);
    $phone = mysqli_real_escape_string($conn, $phone);
    $passwordHash = md5($password);
    $check = mysqli_query($conn, "SELECT userid FROM users WHERE email='{$email}' OR username='{$username}' LIMIT 1");
    if ($check && mysqli_num_rows($check) > 0) {
      $err = 'Email hoặc tên đăng nhập đã tồn tại.';
    } else {
      $query = "INSERT INTO users (email, username, password, fullname, phone) VALUES ('{$email}', '{$username}', '{$passwordHash}', '{$fullname}', '{$phone}')";
      if (mysqli_query($conn, $query)) {
        $_SESSION['customer_success'] = 'Thêm khách hàng thành công.';
        header('Location: admin_customer.php');
        exit;
      }
      $err = 'Không thể thêm khách hàng: ' . mysqli_error($conn);
    }
  }
}
require_once './template/header.php';
?>
<div class="container py-4"><div class="row justify-content-center"><div class="col-lg-7"><div class="card shadow-sm border-0"><div class="card-body p-4"><h3 class="mb-4">Thêm khách hàng mới</h3><?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?><form method="post"><div class="mb-3"><label class="form-label">Họ và tên</label><input class="form-control" name="fullname" required></div><div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div><div class="mb-3"><label class="form-label">Tên đăng nhập</label><input class="form-control" name="username" required></div><div class="mb-3"><label class="form-label">Số điện thoại</label><input class="form-control" name="phone"></div><div class="mb-3"><label class="form-label">Mật khẩu</label><input class="form-control" type="password" name="password" minlength="6" required></div><button class="btn btn-primary" type="submit">Lưu khách hàng</button> <a class="btn btn-secondary" href="admin_customer.php">Hủy</a></form></div></div></div></div></div>
<?php require_once './template/footer.php'; ?>