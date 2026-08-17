<?php
	session_start();
	if(!isset($_POST['submit'])){
		echo "Có lỗi xảy ra! Vui lòng kiểm tra lại!";
		exit;
	}
	require_once "./functions/database_functions.php";
	$conn = db_connect();

	$name = trim($_POST['name']);
	$pass = trim($_POST['pass']);

	if($name == "" || $pass == ""){
		$_SESSION['err_login'] = "Tên đăng nhập hoặc mật khẩu không được để trống!";
		$_SESSION['auth_tab'] = "login";
		header("Location: auth.php");
		exit;
	}

	$name = mysqli_real_escape_string($conn, $name);
	$pass = mysqli_real_escape_string($conn, $pass);
	$passHash = sha1($pass);

	$query = "SELECT `name`, `pass` FROM `admin` WHERE `name` = '{$name}' AND `pass` = '{$passHash}'";
	$result = mysqli_query($conn, $query);
	if($result->num_rows <= 0){
		$_SESSION['err_login'] = "Tên đăng nhập hoặc mật khẩu không chính xác";
		$_SESSION['auth_tab'] = "login";
		header("Location: auth.php");
		exit;
	}
	if(isset($conn)) {mysqli_close($conn);}
	$_SESSION['admin'] = true;
	$_SESSION['auth_tab'] = "login";
	header("Location: admin_book.php");
?>