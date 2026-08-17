<?php
	session_start();

	if(!isset($_POST['submit'])){
		echo "Có lỗi xảy ra! Vui lòng kiểm tra lại!";
		exit;
	}

	require_once "./functions/database_functions.php";
	$conn = db_connect();

	$username = trim($_POST['username']);
	$password = trim($_POST['password']);

	if($username == "" || $password == ""){
		$_SESSION['err_login'] = "Tên đăng nhập và mật khẩu là bắt buộc!";
		$_SESSION['auth_tab'] = "login";
		header("Location: auth.php");
		exit;
	}

	$username = mysqli_real_escape_string($conn, $username);
	$passwordPlain = $password;
	$passwordUserHash = md5($passwordPlain);
	$passwordAdminHash = sha1($passwordPlain);

	$adminQuery = "SELECT `name` FROM `admin` WHERE `name` = '{$username}' AND `pass` = '{$passwordAdminHash}'";
	$adminResult = mysqli_query($conn, $adminQuery);

	if($adminResult && $adminResult->num_rows > 0){
		if(isset($conn)) {mysqli_close($conn);}
		$_SESSION['admin'] = true;
		$_SESSION['auth_tab'] = "login";
		header("Location: admin_book.php");
		exit;
	}

	$query = "SELECT userid, username, email, fullname FROM users 
			  WHERE (username = '{$username}' OR email = '{$username}') 
			  AND password = '{$passwordUserHash}'
			  AND is_active = 1";

	$result = mysqli_query($conn, $query);

	if(!$result){
		$_SESSION['err_login'] = "Lỗi cơ sở dữ liệu: " . mysqli_error($conn);
		$_SESSION['auth_tab'] = "login";
		header("Location: auth.php");
		exit;
	}

	if($result->num_rows <= 0){
		$_SESSION['err_login'] = "Tên đăng nhập/Email hoặc mật khẩu không chính xác!";
		$_SESSION['auth_tab'] = "login";
		header("Location: auth.php");
		exit;
	}

	$user_data = mysqli_fetch_assoc($result);

	if(isset($conn)) {mysqli_close($conn);}

	$_SESSION['user'] = true;
	$_SESSION['userid'] = $user_data['userid'];
	$_SESSION['username'] = $user_data['username'];
	$_SESSION['email'] = $user_data['email'];
	$_SESSION['fullname'] = $user_data['fullname'];
	$_SESSION['auth_tab'] = "login";

	header("Location: index.php");
?>
