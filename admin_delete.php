<?php
	session_start();
	require_once "./functions/admin.php";
	$book_isbn = $_GET['bookisbn'] ?? '';

	require_once "./functions/database_functions.php";
	$conn = db_connect();

	$book_isbn = mysqli_real_escape_string($conn, $book_isbn);
	$query = "DELETE FROM books WHERE book_isbn = '$book_isbn'";
	$result = mysqli_query($conn, $query);
	if(!$result){
		echo "delete data unsuccessfully " . mysqli_error($conn);
		exit;
	}
	header("Location: admin_book.php");
	exit;
?>