<?php
session_start();
if(isset($_SESSION['admin']) && $_SESSION['admin'] == true){
	header('Location:admin_dashboard.php');
	exit;
}
	$title = "Bảng Điều Khiển Admin";
	require_once "./template/header.php";
?>
<main class="admin-auth-page">
	<div class="admin-auth-shell">
	<div class="admin-auth-brand"><i class="fas fa-user-shield"></i><span>QUẢN TRỊ VIÊN</span></div>
	<h1>Đăng nhập</h1>
	<p class="admin-auth-subtitle">Đăng nhập để tiếp tục quản lý nhà sách</p>
	<div class="admin-auth-divider"></div>
	<?php if(isset($_SESSION['err_login'])): ?><div class="alert alert-danger admin-auth-alert"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($_SESSION['err_login']) ?></div><?php unset($_SESSION['err_login']); ?><?php endif; ?>
	<form class="admin-auth-form" method="post" action="admin_verify.php">
	<div class="admin-auth-field"><label for="name">Tên đăng nhập</label><div class="admin-auth-input"><i class="fas fa-user"></i><input type="text" name="name" id="name" placeholder="Nhập tên đăng nhập" autocomplete="username" required></div></div>
	<div class="admin-auth-field"><label for="pass">Mật khẩu</label><div class="admin-auth-input"><i class="fas fa-lock"></i><input type="password" name="pass" id="pass" placeholder="Nhập mật khẩu" autocomplete="current-password" required></div></div>
	<button type="submit" name="submit" class="admin-auth-submit"><i class="fas fa-right-to-bracket"></i>Đăng nhập</button>
	</form>
	<a class="admin-auth-back" href="index.php"><i class="fas fa-arrow-left"></i>Về trang chủ</a>
	</div>
</main>
<style>body{background:#f3efe8}.admin-auth-page{min-height:calc(100vh - 30px);display:grid;place-items:center;padding:40px 15px;background-image:radial-gradient(#d9d3c8 .7px,transparent .7px);background-size:14px 14px}.admin-auth-shell{width:100%;max-width:500px;padding:34px 38px;background:rgba(255,255,255,.92);border:1px solid rgba(101,90,72,.18);box-shadow:0 18px 45px rgba(43,37,28,.12)}.admin-auth-brand{display:flex;align-items:center;gap:9px;color:#4c47b0;font-size:.78rem;font-weight:800;letter-spacing:.14em}.admin-auth-brand i{font-size:1.15rem}.admin-auth-shell h1{margin:22px 0 5px;color:#222;font-size:2.1rem;font-weight:700}.admin-auth-subtitle{margin:0;color:#6d6a66}.admin-auth-divider{height:2px;margin:22px 0;background:rgba(76,71,176,.8)}.admin-auth-field{margin-bottom:18px}.admin-auth-field label{display:block;margin-bottom:8px;color:#222;font-weight:600}.admin-auth-input{display:flex;align-items:center;height:52px;border:2px solid #d7d2c8;background:#fff}.admin-auth-input:focus-within{border-color:#6a5acd;box-shadow:0 0 0 3px rgba(106,90,205,.12)}.admin-auth-input i{width:45px;text-align:center;color:#8d88bd}.admin-auth-input input{width:100%;height:100%;border:0;outline:0;padding-right:12px;font-size:1rem}.admin-auth-submit{width:100%;height:52px;margin-top:8px;border:0;background:#1b9a54;color:#fff;font-size:1.05rem;font-weight:700;cursor:pointer}.admin-auth-submit i{margin-right:8px}.admin-auth-submit:hover{background:#168347}.admin-auth-alert{border-radius:0;margin-bottom:20px}.admin-auth-back{display:block;margin-top:22px;color:#4c47b0;text-align:center;text-decoration:none}.admin-auth-back:hover{color:#332e91}@media(max-width:576px){.admin-auth-shell{padding:28px 20px}.admin-auth-shell h1{font-size:1.8rem}}</style>


<?php
	require_once "./template/footer.php";
?>