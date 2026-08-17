<?php
	session_start();
	
	// Unset all user-related session variables
	unset($_SESSION['user']);
	unset($_SESSION['userid']);
	unset($_SESSION['username']);
	unset($_SESSION['email']);
	unset($_SESSION['fullname']);
	
	// Destroy the session
	session_destroy();
	
	// Redirect to home page
	header("Location: index.php");
?>
