<?php
	if(!isset($_SESSION['admin']) || $_SESSION['admin'] !== true){
	$_SESSION['err_login'] = "Bạn cần đăng nhập bằng tài khoản Admin để truy cập khu vực quản trị.";
		header("Location: admin.php");
		exit;
	}
?>