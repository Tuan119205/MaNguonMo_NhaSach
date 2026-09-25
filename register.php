<?php
	session_start();
	if(isset($_SESSION['user']) && $_SESSION['user'] == true){
		header('location:index.php');
	}
	$title = "Đăng Ký Tài Khoản";
	require_once "./template/header.php";
?>
<div class="row justify-content-center my-5">
	<div class="col-lg-4 col-md-6 col-sm-10 col-xs-12">
		<div class="card rounded-0 shadow">
			<div class="card-header">
				<div class="card-title text-center h4 fw-bolder">Tạo Tài Khoản Mới</div>
			</div>
			<div class="card-body">
				<div class="container-fluid">
					<?php if(isset($_SESSION['err_register'])): ?>
						<div class="alert alert-danger rounded-0">
							<?= $_SESSION['err_register'] ?>
						</div>
					<?php 
						unset($_SESSION['err_register']);
						endif;
					?>
					<?php if(isset($_SESSION['success_register'])): ?>
						<div class="alert alert-success rounded-0">
							<?= $_SESSION['success_register'] ?>
							<br><br>
							<a href="login.php" class="btn btn-sm btn-primary">Đi đến Đăng Nhập</a>
						</div>
					<?php 
						unset($_SESSION['success_register']);
						endif;
					?>
					<form class="form-horizontal" method="post" action="register_verify.php">
						<div class="mb-3">
							<label for="fullname" class="control-label">Họ Tên</label>
							<input type="text" name="fullname" class="form-control rounded-0" required>
						</div>
						<div class="mb-3">
							<label for="email" class="control-label">Email</label>
							<input type="email" name="email" class="form-control rounded-0" required>
						</div>
						<div class="mb-3">
							<label for="username" class="control-label">Tên Đăng Nhập</label>
							<input type="text" name="username" class="form-control rounded-0" required>
						</div>
						<div class="mb-3">
							<label for="phone" class="control-label">Số Điện Thoại (Không Bắt Buộc)</label>
							<input type="tel" name="phone" class="form-control rounded-0">
						</div>
						<div class="mb-3">
							<label for="password" class="control-label">Mật Khẩu</label>
							<input type="password" name="password" class="form-control rounded-0" required>
						</div>
						<div class="mb-3">
							<label for="confirm_password" class="control-label">Xác Nhận Mật Khẩu</label>
							<input type="password" name="confirm_password" class="form-control rounded-0" required>
						</div>
						<div class="mb-3 d-grid">
							<input type="submit" name="submit" class="btn btn-primary rounded-0" value="Đăng Ký">
						</div>
						<div class="text-center">
							<small>Đã có tài khoản? <a href="login.php">Đăng nhập tại đây</a></small>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
	require_once "./template/footer.php";
?>
