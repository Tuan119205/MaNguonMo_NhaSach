<?php
	session_start();

	if(!isset($_POST['submit'])){
		echo "Có lỗi xảy ra! Vui lòng kiểm tra lại!";
		exit;
	}

	require_once "./functions/database_functions.php";
	$conn = db_connect();

	$fullname = trim($_POST['fullname']);
	$email = trim($_POST['email']);
	$username = trim($_POST['username']);
	$phone = trim($_POST['phone'] ?? '');
	$password = trim($_POST['password']);
	$confirm_password = trim($_POST['confirm_password']);

	if($fullname == "" || $email == "" || $username == "" || $password == ""){
		$_SESSION['err_register'] = "Tất cả các trường bắt buộc phải được điền!";
		$_SESSION['auth_tab'] = "register";
		header("Location: auth.php");
		exit;
	}

	if($password !== $confirm_password){
		$_SESSION['err_register'] = "Mật khẩu không khớp!";
		$_SESSION['auth_tab'] = "register";
		header("Location: auth.php");
		exit;
	}

	if(strlen($password) < 6){
		$_SESSION['err_register'] = "Mật khẩu phải có ít nhất 6 ký tự!";
		$_SESSION['auth_tab'] = "register";
		header("Location: auth.php");
		exit;
	}

	$fullname = mysqli_real_escape_string($conn, $fullname);
	$email = mysqli_real_escape_string($conn, $email);
	$username = mysqli_real_escape_string($conn, $username);
	$phone = mysqli_real_escape_string($conn, $phone);
	$passwordHash = md5($password);

	$check_email = "SELECT userid FROM users WHERE email = '{$email}'";
	$result_email = mysqli_query($conn, $check_email);
	if($result_email && $result_email->num_rows > 0){
		$_SESSION['err_register'] = "Email này đã được đăng ký!";
		$_SESSION['auth_tab'] = "register";
		header("Location: auth.php");
		exit;
	}

	$check_username = "SELECT userid FROM users WHERE username = '{$username}'";
	$result_username = mysqli_query($conn, $check_username);
	if($result_username && $result_username->num_rows > 0){
		$_SESSION['err_register'] = "Tên đăng nhập này đã được sử dụng!";
		$_SESSION['auth_tab'] = "register";
		header("Location: auth.php");
		exit;
	}

	$query = "INSERT INTO users (email, username, password, fullname, phone)
			  VALUES ('{$email}', '{$username}', '{$passwordHash}', '{$fullname}', '{$phone}')";

	$result = mysqli_query($conn, $query);

	if(!$result){
		$_SESSION['err_register'] = "Đăng ký thất bại! " . mysqli_error($conn);
		$_SESSION['auth_tab'] = "register";
		header("Location: auth.php");
		exit;
	}

	if(isset($conn)) {mysqli_close($conn);}

	$_SESSION['success_register'] = "Đăng ký thành công! Vui lòng đăng nhập với thông tin đăng ký của bạn.";
	$_SESSION['auth_tab'] = "login";
	header("Location: auth.php");
?>
