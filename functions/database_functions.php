<?php
	function db_connect(){
		$conn = mysqli_connect("localhost", "root", "", "obs_db");
		if(!$conn){
			echo "Can't connect database " . mysqli_connect_error($conn);
			exit;
		}
			// Đồng bộ charset kết nối để MySQL trả về đúng tiếng Việt 4-byte.
		if (!mysqli_set_charset($conn, "utf8mb4")) {
			echo "Không thể thiết lập charset utf8mb4: " . mysqli_error($conn);
			exit;
		}
		mysqli_query($conn, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
			mysqli_query($conn, "SET collation_connection = 'utf8mb4_unicode_ci'");
		ensure_book_genre_schema($conn);
		return $conn;
		}

		function ensure_book_genre_schema($conn){
		mysqli_query($conn, "CREATE TABLE IF NOT EXISTS genres (
		genre_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		genre_name VARCHAR(100) NOT NULL,
		PRIMARY KEY (genre_id),
		UNIQUE KEY uq_genre_name (genre_name)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

		$genres = ['Truyện tranh', 'Văn học Việt Nam', 'Văn học nước ngoài', 'Manga', 'Sách thanh niên'];
		foreach ($genres as $genreName) {
		$escapedName = mysqli_real_escape_string($conn, $genreName);
		mysqli_query($conn, "INSERT IGNORE INTO genres (genre_name) VALUES ('$escapedName')");
		}

		$result = mysqli_query($conn, "SHOW COLUMNS FROM books LIKE 'genre_id'");
		if ($result && mysqli_num_rows($result) === 0) {
		mysqli_query($conn, "ALTER TABLE books ADD COLUMN genre_id INT UNSIGNED NULL AFTER publisherid");
		}
		}

	function ensure_user_profile_schema($conn){
		mysqli_query($conn, "CREATE TABLE IF NOT EXISTS user_addresses (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			userid INT UNSIGNED NOT NULL,
			fullname VARCHAR(100) NOT NULL,
			phone VARCHAR(15) DEFAULT NULL,
			address TEXT NOT NULL,
			ward VARCHAR(100) DEFAULT NULL,
			city VARCHAR(100) NOT NULL,
			is_default TINYINT(1) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_userid (userid)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

		mysqli_query($conn, "CREATE TABLE IF NOT EXISTS user_orders (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id INT UNSIGNED NOT NULL,
			order_code VARCHAR(50) NOT NULL,
			total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
			payment_method VARCHAR(30) NOT NULL DEFAULT 'cod',
			order_status VARCHAR(30) NOT NULL DEFAULT 'Chờ xử lý',
			notes TEXT DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_user_order (user_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

		$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'default_address_id'");
		if(mysqli_num_rows($result) == 0){
			mysqli_query($conn, "ALTER TABLE users ADD COLUMN default_address_id INT UNSIGNED NULL DEFAULT NULL AFTER phone");
		}

		$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'preferred_payment_method'");
		if(mysqli_num_rows($result) == 0){
			mysqli_query($conn, "ALTER TABLE users ADD COLUMN preferred_payment_method VARCHAR(20) NOT NULL DEFAULT 'cod' AFTER default_address_id");
		}

		$result = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'order_status'");
		if(mysqli_num_rows($result) == 0){
			mysqli_query($conn, "ALTER TABLE orders ADD COLUMN order_status VARCHAR(30) NOT NULL DEFAULT 'Chờ xử lý' AFTER amount");
		}

		$result = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'payment_method'");
		if(mysqli_num_rows($result) == 0){
			mysqli_query($conn, "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(30) NOT NULL DEFAULT 'cod' AFTER order_status");
		}
	}

	function select4LatestBook($conn){
		$row = array();
		$query = "SELECT book_isbn, book_image, book_title FROM books ORDER BY abs(unix_timestamp(created_at)) DESC";
		$result = mysqli_query($conn, $query);
		if(!$result){
		    echo "Can't retrieve data " . mysqli_error($conn);
		    exit;
		}
		for($i = 0; $i < 10; $i++){
			array_push($row, mysqli_fetch_assoc($result));
		}
		return $row;
	}

	function getBookByIsbn($conn, $isbn){
		$query = "SELECT book_title, book_author, book_price FROM books WHERE book_isbn = '$isbn'";
		$result = mysqli_query($conn, $query);
		if(!$result){
			echo "Can't retrieve data " . mysqli_error($conn);
			exit;
		}
		return $result;
	}

	function getOrderId($conn, $customerid){
		$customerid = intval($customerid);
		$query = "SELECT orderid FROM orders WHERE customerid = '$customerid' ORDER BY orderid DESC LIMIT 1";
		$result = mysqli_query($conn, $query);
		if(!$result || mysqli_num_rows($result) === 0){
			return null;
		}
		$row = mysqli_fetch_assoc($result);
		return $row['orderid'];
	}

	function insertIntoOrder($conn, $customerid, $total_price, $date, $ship_name, $ship_address, $ship_city, $ship_zip_code, $ship_country, $payment_method = 'cod', $status = 'chờ_xử_lý', $ship_phone = '', $notes = ''){
		$customerid = intval($customerid);
		$total_price = floatval($total_price);
		$ship_name = mysqli_real_escape_string($conn, trim($ship_name));
		$ship_address = mysqli_real_escape_string($conn, trim($ship_address));
		$ship_city = mysqli_real_escape_string($conn, trim($ship_city));
		$ship_zip_code = mysqli_real_escape_string($conn, trim($ship_zip_code));
		$ship_country = mysqli_real_escape_string($conn, trim($ship_country ?: 'Việt Nam'));
		$ship_phone = mysqli_real_escape_string($conn, trim($ship_phone));
		$notes = mysqli_real_escape_string($conn, trim($notes));

		$payment_method = in_array($payment_method, ['cod', 'bank_transfer', 'transfer'], true) ? $payment_method : 'cod';

		$valid_statuses = ['chờ_xử_lý', 'đang_giao', 'đã_giao', 'đã_hủy', 'pending', 'shipping', 'delivered', 'cancelled'];
		$status = in_array($status, $valid_statuses, true) ? $status : 'chờ_xử_lý';

		$query = "INSERT INTO orders (customerid, amount, order_status, payment_method, date, ship_name, ship_phone, ship_address, ship_city, ship_zip_code, ship_country, notes)
		VALUES ('$customerid', '$total_price', '$status', '$payment_method', '$date', '$ship_name', '$ship_phone', '$ship_address', '$ship_city', '$ship_zip_code', '$ship_country', '$notes')";
		$result = mysqli_query($conn, $query);
		if(!$result){
			echo "Insert orders failed: " . mysqli_error($conn);
			exit;
		}
		return mysqli_insert_id($conn);
	}

	function getUserOrders($conn, $user_id){
		$user_id = intval($user_id);
		$query = "SELECT orderid, customerid, amount, order_status, payment_method, date, ship_name, ship_phone, ship_address, ship_city, ship_zip_code, ship_country, notes
				FROM orders
				WHERE customerid = '$user_id'
				ORDER BY date DESC, orderid DESC";
		$result = mysqli_query($conn, $query);
		if(!$result){
			return array();
		}
		return mysqli_fetch_all($result, MYSQLI_ASSOC);
	}

	function getbookprice($isbn){
		$conn = db_connect();
		$query = "SELECT book_price FROM books WHERE book_isbn = '$isbn'";
		$result = mysqli_query($conn, $query);
		if(!$result){
			echo "get book price failed! " . mysqli_error($conn);
			exit;
		}
		$row = mysqli_fetch_assoc($result);
		return $row['book_price'];
	}

	function getCustomerId($name, $address, $city, $zip_code, $country){
		$conn = db_connect();
		$query = "SELECT customerid from customers WHERE
		`name` = '$name' AND
		`address`= '$address' AND
		city = '$city' AND
		zip_code = '$zip_code' AND
		country = '$country'";
		$result = mysqli_query($conn, $query);
		// if there is customer in db, take it out
		if($result->num_rows > 0){
			$row = mysqli_fetch_assoc($result);
			return $row['customerid'];
		} else {
			return null;
		}
	}

	function setCustomerId($name, $address, $city, $zip_code, $country){
		$conn = db_connect();
		$query = "INSERT INTO customers VALUES
			('', '" . $name . "', '" . $address . "', '" . $city . "', '" . $zip_code . "', '" . $country . "')";

		$result = mysqli_query($conn, $query);
		if(!$result){
			echo "insert false !" . mysqli_error($conn);
			exit;
		}
		$customerid = mysqli_insert_id($conn);
		return $customerid;
	}

	function getPubName($conn, $pubid){
		$query = "SELECT publisher_name FROM publisher WHERE publisherid = '$pubid'";
		$result = mysqli_query($conn, $query);
		if(!$result){
			echo "Can't retrieve data " . mysqli_error($conn);
			exit;
		}
		if(mysqli_num_rows($result) == 0){
			echo "Empty books ! Something wrong! check again";
			exit;
		}

		$row = mysqli_fetch_assoc($result);
		return $row['publisher_name'];
	}

	function ensure_book_inventory_schema($conn){
	$result = mysqli_query($conn, "SHOW COLUMNS FROM books LIKE 'inventory'");
	if($result && mysqli_num_rows($result) === 0){
	mysqli_query($conn, "ALTER TABLE books ADD COLUMN inventory INT UNSIGNED NOT NULL DEFAULT 1 AFTER book_price");
	mysqli_query($conn, "UPDATE books SET inventory = FLOOR(1 + RAND() * 100) WHERE inventory = 1");
	}
	}

	function getAll($conn){
	$query = "SELECT * from books ORDER BY book_isbn DESC";
		$result = mysqli_query($conn, $query);
		if(!$result){
			echo "Can't retrieve data " . mysqli_error($conn);
			exit;
		}
		return $result;
	}
?>