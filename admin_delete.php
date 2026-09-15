<?php
	session_start();
	require_once "./functions/admin.php";
	$book_isbn = $_GET['bookisbn'] ?? '';

	require_once "./functions/database_functions.php";
	$conn = db_connect();

	$book_isbn = mysqli_real_escape_string($conn, $book_isbn);
	$historyCheck = mysqli_query($conn, "SELECT 1 FROM order_items WHERE book_isbn = '$book_isbn' LIMIT 1");
	if ($historyCheck && mysqli_num_rows($historyCheck) > 0) {
	$_SESSION['book_error'] = 'Không thể xóa sách đã xuất hiện trong lịch sử đơn hàng. Hãy chỉnh tồn kho về 0 hoặc ẩn sách.';
		header("Location: admin_book.php");
		exit;
	}
	$query = "DELETE FROM books WHERE book_isbn = '$book_isbn'";
	$result = mysqli_query($conn, $query);
	if(!$result){
		echo "delete data unsuccessfully " . mysqli_error($conn);
		exit;
	}
	header("Location: admin_book.php");
	exit;
?>