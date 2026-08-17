<?php
	header('Content-Type: text/html; charset=utf-8');
	session_start();

	require_once "./functions/database_functions.php";
	require_once "./functions/cart_functions.php";
	require_once "./db_migration.php";

	runDatabaseMigrations();

	$title = "Xác Nhận Đặt Hàng";
	require "./template/header.php";

	$conn = db_connect();

	// Check if this is a POST request to place an order
	$orderSuccess = false;
	$createdOrderId = 0;
	$placedOrder = null;
	$placedItems = array();

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		// Must have active session user and cart
		if (!isset($_SESSION['user']) || $_SESSION['user'] !== true || !isset($_SESSION['userid'])) {
			echo '<div class="container py-5"><div class="alert alert-danger rounded-4">Vui lòng đăng nhập để hoàn tất đặt hàng. <a href="auth.php">Đăng nhập ngay</a></div></div>';
			if(isset($conn)){ mysqli_close($conn); }
			require_once "./template/footer.php";
			exit;
		}

		if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
			echo '<div class="container py-5"><div class="alert alert-warning rounded-4">Giỏ hàng của bạn đang trống. <a href="books.php">Tiếp tục mua sách</a></div></div>';
			if(isset($conn)){ mysqli_close($conn); }
			require_once "./template/footer.php";
			exit;
		}

		$userid = intval($_SESSION['userid']);
		$name = trim($_POST['name'] ?? '');
		$phone = trim($_POST['phone'] ?? '');
		$address = trim($_POST['address'] ?? '');
		$city = trim($_POST['city'] ?? '');
		$zip_code = trim($_POST['zip_code'] ?? '');
		$country = trim($_POST['country'] ?? 'Việt Nam');
		$payment_method = trim($_POST['payment_method'] ?? 'cod');
		$notes = trim($_POST['notes'] ?? '');
		$saveAddress = isset($_POST['save_to_address_book']) && $_POST['save_to_address_book'] == '1';

		if (empty($name) || empty($phone) || empty($address) || empty($city)) {
			echo '<div class="container py-5"><div class="alert alert-danger rounded-4">Vui lòng điền đầy đủ Họ tên, Số điện thoại và Địa chỉ giao hàng. <a href="checkout.php">Quay lại thanh toán</a></div></div>';
			if(isset($conn)){ mysqli_close($conn); }
			require_once "./template/footer.php";
			exit;
		}

		// Optionally save new address to user_addresses
		if ($saveAddress) {
			$chkQuery = "SELECT address_id FROM user_addresses WHERE userid = $userid AND street_address = '" . mysqli_real_escape_string($conn, $address) . "' AND city = '" . mysqli_real_escape_string($conn, $city) . "' LIMIT 1";
			$chkRes = mysqli_query($conn, $chkQuery);
			if (!$chkRes || mysqli_num_rows($chkRes) === 0) {
				$insertAddr = "INSERT INTO user_addresses (userid, full_name, phone, street_address, city, postal_code, country, payment_method, is_default)
				VALUES ($userid, '" . mysqli_real_escape_string($conn, $name) . "', '" . mysqli_real_escape_string($conn, $phone) . "', '" . mysqli_real_escape_string($conn, $address) . "', '" . mysqli_real_escape_string($conn, $city) . "', '" . mysqli_real_escape_string($conn, $zip_code) . "', '" . mysqli_real_escape_string($conn, $country) . "', '" . mysqli_real_escape_string($conn, $payment_method) . "', 0)";
				mysqli_query($conn, $insertAddr);
			}
		}

		// Calculate total amount
			$cartTotals = current_cart_totals($_SESSION['cart']);
		$cartTotal = $cartTotals['subtotal'];
		$discountAmount = $cartTotals['discount'];
		$totalAmount = $cartTotals['total'];
		$date = date("Y-m-d H:i:s");

		// Insert into orders table
		$createdOrderId = insertIntoOrder($conn, $userid, $totalAmount, $date, $name, $address, $city, $zip_code, $country, $payment_method, 'chờ_xử_lý', $phone, $notes);

		if ($createdOrderId > 0) {
			// Insert each item into order_items
			foreach ($_SESSION['cart'] as $isbn => $qty) {
				$bookprice = floatval(getbookprice($isbn));
				$qty = intval($qty);
				$escapedIsbn = mysqli_real_escape_string($conn, $isbn);

				$itemQuery = "INSERT INTO order_items (orderid, book_isbn, item_price, quantity)
				VALUES ('$createdOrderId', '$escapedIsbn', '$bookprice', '$qty')";
				mysqli_query($conn, $itemQuery);

				// Keep items in memory for receipt display
				$bookInfo = mysqli_fetch_assoc(getBookByIsbn($conn, $isbn));
				$placedItems[] = [
					'isbn' => $isbn,
					'title' => $bookInfo['book_title'] ?? 'Sách',
					'author' => $bookInfo['book_author'] ?? '',
					'image' => !empty($bookInfo['book_image']) ? './bootstrap/img/' . $bookInfo['book_image'] : './bootstrap/img/default-book.jpg',
					'price' => $bookprice,
					'qty' => $qty,
					'line_total' => $bookprice * $qty
				];
			}

			// Store summary for page view
			$placedOrder = [
				'orderid' => $createdOrderId,
				'date' => $date,
				'amount' => $totalAmount,
				'payment_method' => $payment_method,
				'name' => $name,
				'phone' => $phone,
				'address' => $address,
				'city' => $city,
				'notes' => $notes,
				'discount_amount' => $discountAmount,
				'cart_total' => $cartTotal
			];

			// Clear cart and checkout session
			unset($_SESSION['cart'], $_SESSION['total_price'], $_SESSION['total_items'], $_SESSION['voucher_code'], $_SESSION['discount_percent'], $_SESSION['ship']);
			$orderSuccess = true;
		} else {
			echo '<div class="container py-5"><div class="alert alert-danger rounded-4">Có lỗi xảy ra trong quá trình lưu đơn hàng. Vui lòng thử lại. <a href="checkout.php">Quay lại thanh toán</a></div></div>';
			if(isset($conn)){ mysqli_close($conn); }
			require_once "./template/footer.php";
			exit;
		}
	} else {
		// Not a POST request - direct navigation
		header("Location: orders.php");
		exit;
	}
?>

<div class="container py-5 process-success-page">
	<div class="row justify-content-center">
		<div class="col-lg-8 col-md-10">
			<!-- Success Header Banner -->
			<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
				<div class="card-body p-4 p-md-5 text-center bg-white">
					<div class="success-icon-wrap mb-3">
						<i class="fa fa-check-circle text-success" style="font-size: 4.5rem;"></i>
					</div>
					<h2 class="fw-bold text-dark mb-2">Đặt Hàng Thành Công!</h2>
					<p class="text-muted fs-6 mb-3">
						Cảm ơn bạn đã mua hàng tại <strong>Nhà Sách Việt Long</strong>. Mã đơn hàng của bạn là:
					</p>
					<div class="d-inline-block bg-warning bg-opacity-10 text-dark border border-warning px-4 py-2 rounded-3 fw-bold fs-5 mb-3">
						#DH<?php echo str_pad($placedOrder['orderid'], 5, '0', STR_PAD_LEFT); ?>
					</div>
					<div class="text-muted small">
						Thời gian đặt: <?php echo date('d/m/Y H:i', strtotime($placedOrder['date'])); ?> &bull; Trạng thái: <span class="badge bg-warning text-dark">Chờ xử lý</span>
					</div>
				</div>
			</div>

			<!-- Bank Transfer Details Card (if chosen) -->
			<?php if ($placedOrder['payment_method'] === 'bank_transfer'): ?>
			<div class="card border-primary border-2 shadow-sm rounded-4 mb-4">
				<div class="card-header bg-primary text-white py-3">
					<h5 class="mb-0 fw-bold"><i class="fa fa-university me-2"></i>Thông Tin Chuyển Khoản Ngân Hàng</h5>
				</div>
				<div class="card-body p-4 bg-light">
					<p class="text-dark small mb-3">Vui lòng chuyển khoản chính xác số tiền dưới đây kèm nội dung mã đơn hàng để đơn được xử lý nhanh nhất:</p>
					<div class="row g-3">
						<div class="col-sm-6">
							<div class="p-3 bg-white rounded-3 border">
								<div class="text-muted small">Ngân hàng:</div>
								<div class="fw-bold text-dark">MB Bank (Ngân hàng Quân Đội)</div>
								<div class="text-muted small mt-2">Số tài khoản:</div>
								<div class="fw-bold text-primary fs-5">0384163051</div>
								<div class="text-muted small mt-2">Chủ tài khoản:</div>
								<div class="fw-bold text-dark">NHA SACH VIET LONG</div>
							</div>
						</div>
						<div class="col-sm-6">
							<div class="p-3 bg-white rounded-3 border">
								<div class="text-muted small">Số tiền cần chuyển:</div>
								<div class="fw-bold text-danger fs-4"><?php echo number_format($placedOrder['amount'], 0, ',', '.'); ?>đ</div>
								<div class="text-muted small mt-2">Nội dung chuyển khoản (bắt buộc):</div>
								<div class="fw-bold text-success fs-5">DH<?php echo str_pad($placedOrder['orderid'], 5, '0', STR_PAD_LEFT); ?></div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<!-- Order Details Card -->
			<div class="card border-0 shadow-sm rounded-4 mb-4">
				<div class="card-header bg-white py-3 border-bottom">
					<h5 class="mb-0 fw-bold text-dark"><i class="fa fa-list-alt text-warning me-2"></i>Chi Tiết Đơn Hàng</h5>
				</div>
				<div class="card-body p-4">
					<!-- Recipient & Delivery Info -->
					<div class="row g-3 mb-4 pb-3 border-bottom">
						<div class="col-md-6">
							<h6 class="fw-bold text-muted small text-uppercase mb-2">Người nhận & Giao hàng</h6>
							<div class="fw-bold text-dark"><?php echo htmlspecialchars($placedOrder['name']); ?></div>
							<div class="text-secondary small"><i class="fa fa-phone me-1"></i><?php echo htmlspecialchars($placedOrder['phone']); ?></div>
							<div class="text-secondary small mt-1"><i class="fa fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($placedOrder['address'] . ', ' . $placedOrder['city']); ?></div>
						</div>
						<div class="col-md-6">
							<h6 class="fw-bold text-muted small text-uppercase mb-2">Hình thức thanh toán</h6>
							<div class="fw-bold text-dark">
								<?php echo ($placedOrder['payment_method'] === 'bank_transfer') ? 'Chuyển khoản ngân hàng' : 'Thanh toán khi nhận hàng (COD)'; ?>
							</div>
							<?php if (!empty($placedOrder['notes'])): ?>
								<div class="text-muted small mt-2">
									<strong>Ghi chú:</strong> <?php echo htmlspecialchars($placedOrder['notes']); ?>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<!-- Items List -->
					<h6 class="fw-bold text-muted small text-uppercase mb-3">Sản phẩm đã mua</h6>
					<div class="table-responsive">
						<table class="table table-hover align-middle">
							<thead class="table-light">
								<tr>
									<th>Sản phẩm</th>
									<th class="text-center">Số lượng</th>
									<th class="text-end">Đơn giá</th>
									<th class="text-end">Thành tiền</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($placedItems as $item): ?>
								<tr>
									<td>
										<div class="d-flex align-items-center gap-2">
											<img src="<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>"
												style="width: 40px; height: 50px; object-fit: cover; border-radius: 4px;">
											<div>
												<div class="fw-semibold text-dark small"><?php echo htmlspecialchars($item['title']); ?></div>
												<div class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($item['author']); ?></div>
											</div>
										</div>
									</td>
									<td class="text-center fw-semibold"><?php echo $item['qty']; ?></td>
									<td class="text-end small"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</td>
									<td class="text-end fw-bold"><?php echo number_format($item['line_total'], 0, ',', '.'); ?>đ</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
							<tfoot>
								<?php if ($placedOrder['discount_amount'] > 0): ?>
								<tr>
									<td colspan="3" class="text-end text-muted">Tạm tính:</td>
									<td class="text-end fw-semibold"><?php echo number_format($placedOrder['cart_total'], 0, ',', '.'); ?>đ</td>
								</tr>
								<tr>
									<td colspan="3" class="text-end text-success">Giảm giá mã ưu đãi:</td>
									<td class="text-end fw-bold text-success">-<?php echo number_format($placedOrder['discount_amount'], 0, ',', '.'); ?>đ</td>
								</tr>
								<?php endif; ?>
								<tr>
									<td colspan="3" class="text-end text-muted">Phí giao hàng:</td>
									<td class="text-end fw-bold text-success">Miễn phí</td>
								</tr>
								<tr class="table-warning">
									<td colspan="3" class="text-end fw-bold fs-6">Tổng thanh toán:</td>
									<td class="text-end fw-bold fs-5 text-danger"><?php echo number_format($placedOrder['amount'], 0, ',', '.'); ?>đ</td>
								</tr>
							</tfoot>
						</table>
					</div>
				</div>
			</div>

			<!-- Action Buttons -->
			<div class="d-grid gap-2 d-sm-flex justify-content-sm-between mt-4">
				<a href="orders.php" class="btn btn-warning btn-lg px-4 fw-bold">
					<i class="fa fa-box me-2"></i>Xem Lịch Sử Đơn Hàng
				</a>
				<a href="books.php" class="btn btn-outline-dark btn-lg px-4">
					<i class="fa fa-book me-2"></i>Tiếp Tục Mua Sắm
				</a>
			</div>
		</div>
	</div>
</div>

<style>
.process-success-page {
	font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}
</style>

<?php
	if(isset($conn)){
		mysqli_close($conn);
	}
	require_once "./template/footer.php";
?>