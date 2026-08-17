<?php
	session_start();
	if(isset($_SESSION['user']) && $_SESSION['user'] == true){
		header('location:index.php');
	}
	$title = "Đăng Nhập Khách Hàng";
	require_once "./template/header.php";
?>
<div class="row justify-content-center my-5">
	<div class="col-lg-4 col-md-6 col-sm-10 col-xs-12">
		<div class="card rounded-0 shadow">
			<div class="card-header">
				<div class="card-title text-center h4 fw-bolder">Đăng Nhập Khách Hàng</div>
			</div>
			<div class="card-body">
				<div class="container-fluid">
					<?php if(isset($_SESSION['err_login'])): ?>
						<div class="alert alert-danger rounded-0">
							<?= $_SESSION['err_login'] ?>
						</div>
					<?php 
						unset($_SESSION['err_login']);
						endif;
					?>
					<form class="form-horizontal" method="post" action="login_verify.php">
						<div class="mb-3">
							<label for="username" class="control-label">Tên Đăng Nhập hoặc Email</label>
							<input type="text" name="username" class="form-control rounded-0" required>
						</div>
						<div class="mb-3">
							<label for="password" class="control-label">Mật Khẩu</label>
							<input type="password" name="password" class="form-control rounded-0" required>
						</div>
						<div class="mb-3 d-grid">
							<input type="submit" name="submit" class="btn btn-primary rounded-0" value="Đăng Nhập">
						</div>
						<div class="text-center">
							<small>Chưa có tài khoản? <a href="register.php">Đăng ký tại đây</a></small>
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
