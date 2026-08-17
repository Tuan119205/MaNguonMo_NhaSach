<?php
	session_start();

	if((isset($_SESSION['admin']) && $_SESSION['admin'] == true) ||
	   (isset($_SESSION['user']) && $_SESSION['user'] == true)){
		header('location:index.php');
	}

	$authTab = isset($_SESSION['auth_tab']) ? $_SESSION['auth_tab'] : 'login';
	if (isset($_SESSION['err_register'])) {
	$authTab = 'register';
	}
	unset($_SESSION['auth_tab']);

	$title = "Đăng Nhập / Đăng Ký";
	require_once "./template/header.php";
?>

<div class="container-fluid auth-page">
	<div class="auth-shell">
		<h2 class="auth-title"><?php echo $authTab === 'register' ? 'Đăng ký tài khoản' : 'Đăng nhập'; ?></h2>
		<div class="auth-divider"></div>

		<div class="tab-content" id="loginRegisterContent">
			<div class="tab-pane fade <?php echo $authTab === 'login' ? 'show active' : ''; ?>" id="login" role="tabpanel" aria-labelledby="login-tab">
					<?php if(isset($_SESSION['err_login'])): ?>
				<div class="alert alert-danger auth-alert">
				<i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['err_login']) ?>
				</div>
				<?php unset($_SESSION['err_login']); ?>
				<?php endif; ?>
				<?php if(isset($_SESSION['success_register'])): ?>
				<div class="alert alert-success auth-alert">
				<i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success_register']) ?>
				</div>
				<?php unset($_SESSION['success_register']); ?>
				<?php endif; ?>

				<form class="auth-form" method="post" action="login_verify.php">
					<div class="mb-3">
						<label for="username-user" class="auth-label">Username or email</label>
						<input type="text" name="username" id="username-user" class="auth-input" placeholder="admin or customer1" required>
					</div>
					<div class="mb-3">
						<label for="password-user" class="auth-label">Password</label>
						<input type="password" name="password" id="password-user" class="auth-input" placeholder="Enter password" required>
					</div>
					<div class="auth-row">
						<label class="auth-remember">
							<input type="checkbox">
							<span>Remember me</span>
						</label>
						<a href="#" class="auth-link">Forgot your password?</a>
					</div>
					<button type="submit" name="submit" class="auth-submit">Đăng nhập</button>
				</form>
			</div>

			<div class="tab-pane fade <?php echo $authTab === 'register' ? 'show active' : ''; ?>" id="register" role="tabpanel" aria-labelledby="register-tab">
				<?php if(isset($_SESSION['err_register'])): ?>
						<div class="alert alert-danger auth-alert">
					<i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['err_register']) ?>
					</div>
					<?php unset($_SESSION['err_register']); ?>
				<?php endif; ?>


				<form class="auth-form" method="post" action="register_verify.php">
					<div class="mb-3">
						<label for="fullname" class="auth-label">Họ và tên</label>
						<input type="text" name="fullname" id="fullname" class="auth-input" placeholder="Enter your full name" required>
					</div>
					<div class="mb-3">
						<label for="email" class="auth-label">Email</label>
						<input type="email" name="email" id="email" class="auth-input" placeholder="example@email.com" required>
					</div>
					<div class="mb-3">
						<label for="username" class="auth-label">Tên đăng nhập</label>
						<input type="text" name="username" id="username" class="auth-input" placeholder="Choose a username" required>
					</div>
					<div class="mb-3">
						<label for="phone" class="auth-label">Số điện thoại</label>
						<input type="text" name="phone" id="phone" class="auth-input" placeholder="0123456789">
					</div>
					<div class="mb-3">
						<label for="password" class="auth-label">Mật khẩu</label>
						<input type="password" name="password" id="password" class="auth-input" placeholder="At least 6 characters" required>
					</div>
					<div class="mb-4">
						<label for="confirm_password" class="auth-label">Xác nhận mật khẩu</label>
						<input type="password" name="confirm_password" id="confirm_password" class="auth-input" placeholder="Re-enter your password" required>
					</div>
					<button type="submit" name="submit" class="auth-submit auth-submit-register">Tạo tài khoản</button>
					</form>
				</div>
				</div>

		<div class="auth-toggle-wrap">
				<button class="auth-toggle <?php echo $authTab === 'login' ? 'active' : ''; ?>" type="button" data-bs-toggle="tab" data-bs-target="#login" data-target="login" id="login-tab">Đăng nhập</button>
			<button class="auth-toggle <?php echo $authTab === 'register' ? 'active' : ''; ?>" type="button" data-bs-toggle="tab" data-bs-target="#register" data-target="register" id="register-tab">Đăng ký</button>
		</div>
	</div>
</div>

<style>
	body {
		background: #f3efe8;
	}

	.auth-page {
		min-height: 100vh;
		background-image: radial-gradient(#d9d3c8 0.7px, transparent 0.7px);
		background-size: 14px 14px;
		padding: 40px 15px;
	}

	.auth-shell {
		max-width: 540px;
		margin: 0 auto;
		background: rgba(255,255,255,0.22);
		border: 1px solid rgba(101, 90, 72, 0.2);
		padding: 12px 22px 18px;
		box-shadow: none;
	}

	.auth-title {
		font-size: 2.1rem;
		font-weight: 700;
		color: #222;
		text-align: center;
		margin: 8px 0 10px;
		font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
	}

	.auth-divider {
		height: 2px;
		background: rgba(76, 71, 176, 0.8);
		margin: 0 0 18px;
	}

	.auth-toggle-wrap {
		display: flex;
		gap: 10px;
		margin-bottom: 18px;
	}

	.auth-toggle {
		flex: 1;
		padding: 10px 12px;
		background: transparent;
		border: none;
		font-weight: 600;
		color: #444;
		cursor: pointer;
		transition: 0.2s ease;
	}

	.auth-toggle.active {
		background: rgba(122, 127, 179, 0.08);
		color: #222;
		border-bottom: 2px solid #3d4a9a;
	}

	.tab-pane {
		display: none;
	}

	.tab-pane.show,
	.tab-pane.active {
		display: block;
	}

	.auth-form {
		margin-top: 10px;
	}

	.auth-label {
		display: block;
		margin-bottom: 10px;
		font-size: 1.05rem;
		font-weight: 500;
		color: #222;
	}

	.auth-input {
		width: 100%;
		height: 54px;
		padding: 0 14px;
		border: 2px solid #d7d2c8;
		border-radius: 0;
		background: transparent;
		outline: none;
		font-size: 1.05rem;
		color: #111;
	}

	.auth-input:focus {
		border-color: #6a5acd;
		box-shadow: none;
	}

	.auth-row {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin: 8px 0 18px;
		font-size: 0.95rem;
	}

	.auth-remember {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		color: #333;
	}

	.auth-remember input {
		width: 16px;
		height: 16px;
		accent-color: #0d6efd;
	}

	.auth-link {
		color: #2c4a8c;
		text-decoration: none;
	}

	.auth-submit {
		width: 100%;
		height: 52px;
		border: none;
		background: #1b9a54;
		color: #fff;
		font-size: 1.15rem;
		font-weight: 700;
		cursor: pointer;
	}

	.auth-submit:hover {
		opacity: 0.95;
	}

	.auth-submit-register {
		background: #1b9a54;
	}

	.auth-alert {
		margin-bottom: 18px;
		border-radius: 0;
		background: #fff5f5;
		border: 1px solid #f5c2c7;
		color: #842029;
	}

	@media (max-width: 576px) {
		.auth-shell {
			padding-left: 14px;
			padding-right: 14px;
		}

		.auth-title {
			font-size: 1.8rem;
		}

		.auth-row {
			flex-direction: column;
			align-items: flex-start;
			gap: 8px;
		}
	}
</style>

<script>
	(function() {
		const tabs = document.querySelectorAll('.auth-toggle');
		const panes = document.querySelectorAll('.tab-pane');

		function switchAuthTab(targetId) {
			tabs.forEach(function(btn) {
				const isActive = btn.dataset.target === targetId;
				btn.classList.toggle('active', isActive);
			});

			panes.forEach(function(panel) {
				const isActive = panel.id === targetId;
				panel.classList.toggle('show', isActive);
				panel.classList.toggle('active', isActive);
			});
		}

		tabs.forEach(function(btn) {
			btn.addEventListener('click', function(e) {
				e.preventDefault();
				switchAuthTab(this.dataset.target);
			});
		});
	})();
</script>

<?php require_once "./template/footer.php"; ?>
