<?php
	// the shopping cart needs sessions, to start one
	/*
Array of session(
cart => array (
book_isbn (get from $_POST['book_isbn']) => number of books
),
items => 0,
total_price => '0.00'
)
*/
	session_start();
	require_once "./functions/database_functions.php";
	require_once "./functions/cart_functions.php";

	// Handle AJAX requests for quantity updates
	if (isset($_POST['action']) && $_POST['action'] == 'update_qty') {
		header('Content-Type: application/json; charset=UTF-8');
		$isbn = $_POST['isbn'] ?? '';
		$qty = intval($_POST['qty'] ?? 0);

		if (!empty($isbn) && isset($_SESSION['cart'])) {
			if ($qty <= 0) {
				unset($_SESSION['cart'][$isbn]);
			} else {
				$_SESSION['cart'][$isbn] = $qty;
			}

			// Get book price for line total
			$lineTotal = 0;
			if ($qty > 0) {
				$bookprice = getbookprice($isbn);
				$lineTotal = $bookprice * $qty;
			}

			// Recalculate totals
			$_SESSION['total_price'] = total_price($_SESSION['cart']);
			$_SESSION['total_items'] = total_items($_SESSION['cart']);
			$updatedTotals = current_cart_totals($_SESSION['cart']);

			echo json_encode([
				'success' => true,
				'total_items' => $_SESSION['total_items'],
				'total_price' => number_format($_SESSION['total_price'], 0, ',', '.'),
				'final_total' => number_format($updatedTotals['total'], 0, ',', '.'),
				'line_total' => number_format($lineTotal, 0, ',', '.'),
				'message' => 'Cập nhật thành công'
			]);
		} else {
			echo json_encode([
				'success' => false,
				'message' => 'Lỗi cập nhật giỏ hàng'
			]);
		}
		exit;
	}

	// Handle AJAX requests for voucher code
	if (isset($_POST['action']) && $_POST['action'] == 'apply_voucher') {
		header('Content-Type: application/json; charset=UTF-8');
		$voucher_code = trim($_POST['voucher_code'] ?? '');

		if (empty($voucher_code)) {
			echo json_encode([
				'success' => false,
				'message' => 'Vui lòng nhập mã giảm giá'
			]);
			exit;
		}

		// Không cho áp dụng mã khi giỏ hàng trống
		if (empty($_SESSION['cart']) || !isset($_SESSION['total_price']) || $_SESSION['total_price'] <= 0) {
			echo json_encode([
				'success' => false,
				'message' => 'Giỏ hàng trống, không thể áp dụng mã giảm giá'
			]);
			exit;
		}

		$voucherResult = calculate_voucher_discount($voucher_code, (float)$_SESSION['total_price'], $_SESSION['cart'], (int)($_SESSION['userid'] ?? 0));
		if (!$voucherResult['valid']) {
			echo json_encode(['success' => false, 'message' => $voucherResult['message']]);
			exit;
		}

		$_SESSION['voucher_code'] = $voucherResult['code'];
		$_SESSION['voucher_type'] = $voucherResult['type'];
		$_SESSION['discount_percent'] = $voucherResult['type'] === 'percent' ? (float)$voucherResult['value'] : 0;
		$discountAmount = (float)$voucherResult['discount'];
		$finalPrice = max(0, (float)$_SESSION['total_price'] - $discountAmount);

		echo json_encode([
			'success' => true,
			'discount_percent' => $_SESSION['discount_percent'],
			'discount_amount' => number_format($discountAmount, 0, ',', '.'),
			'final_price' => number_format($finalPrice, 0, ',', '.'),
			'voucher_type' => $voucherResult['type'],
			'message' => $voucherResult['message']
		]);
		exit;
	}

	// Handle AJAX requests for removing items
	if (isset($_POST['action']) && $_POST['action'] == 'remove_item') {
		header('Content-Type: application/json; charset=UTF-8');
		$isbn = $_POST['isbn'] ?? '';

		if (!empty($isbn) && isset($_SESSION['cart'])) {
			unset($_SESSION['cart'][$isbn]);

			// Recalculate totals
			if (count($_SESSION['cart']) > 0) {
				$_SESSION['total_price'] = total_price($_SESSION['cart']);
				$_SESSION['total_items'] = total_items($_SESSION['cart']);
			} else {
				$_SESSION['total_price'] = '0.00';
				$_SESSION['total_items'] = 0;
			}
			$updatedTotals = current_cart_totals($_SESSION['cart']);

			echo json_encode([
				'success' => true,
				'total_items' => $_SESSION['total_items'],
				'total_price' => number_format($_SESSION['total_price'], 0, ',', '.'),
				'final_total' => number_format($updatedTotals['total'], 0, ',', '.'),
				'message' => 'Đã xóa khỏi giỏ hàng'
			]);
		} else {
			echo json_encode([
				'success' => false,
				'message' => 'Lỗi xóa sản phẩm'
			]);
		}
		exit;
	}

	// Handle removing voucher code
	if (isset($_POST['action']) && $_POST['action'] == 'remove_voucher') {
		header('Content-Type: application/json; charset=UTF-8');

		unset($_SESSION['voucher_code']);
		unset($_SESSION['discount_percent']);
		unset($_SESSION['voucher_type']);

		echo json_encode([
			'success' => true,
			'total_price' => number_format($_SESSION['total_price'], 0, ',', '.'),
			'message' => 'Đã xóa mã giảm giá'
		]);
		exit;
	}

	if (isset($_POST['bookisbn'])) {
		$book_isbn = $_POST['bookisbn'];
	}

	if (isset($book_isbn)) {
		if (!isset($_SESSION['cart'])) {
			$_SESSION['cart'] = array();
			$_SESSION['total_items'] = 0;
			$_SESSION['total_price'] = '0.00';
		}

		if (!isset($_SESSION['cart'][$book_isbn])) {
			$_SESSION['cart'][$book_isbn] = 1;
		} elseif (isset($_POST['cart'])) {
			$_SESSION['cart'][$book_isbn]++;
			unset($_POST);
		}
	}

	if (isset($_POST['clear_cart'])) {
		unset($_SESSION['cart']);
		$_SESSION['total_items'] = 0;
		$_SESSION['total_price'] = '0.00';
		header('Location: cart.php');
		exit();
	}

	$title = "Giỏ hàng của bạn";
	require "./template/header.php";
	?>
<?php
if (isset($_SESSION['cart']) && (array_count_values($_SESSION['cart']))) {
	$_SESSION['total_price'] = total_price($_SESSION['cart']);
	$_SESSION['total_items'] = total_items($_SESSION['cart']);
?>
	<div class="container cart-page">
		<div class="cart-breadcrumb">Trang chủ <span>›</span> Giỏ hàng</div>
		<div class="alert alert-success cart-alert" role="alert">
			<i class="fa-solid fa-circle-check"></i>
			<span>Giỏ hàng của bạn có <strong><?php echo $_SESSION['total_items']; ?> sản phẩm</strong></span>
		</div>
		<div class="cart-container">
			<div class="cart-left">
				<div class="cart-box" style="border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; background: #fff;">
					<div class="cart-box-header">
						<span class="cart-bullet"></span>
						<span>Giỏ hàng của bạn</span>
						<span class="cart-count"><?php echo $_SESSION['total_items']; ?> sản phẩm</span>
					</div>



					<?php
					foreach ($_SESSION['cart'] as $isbn => $qty) {
						$conn = db_connect();
						$book = mysqli_fetch_assoc(getBookByIsbn($conn, $isbn));
						$bookImage = !empty($book['book_image'])
	? './bootstrap/img/' . rawurlencode(basename($book['book_image']))
	: './bootstrap/img/dark-bg.jpg';
						$lineTotal = $qty * $book['book_price'];
					?>
						<div class="cart-item-row">
							<button type="button" class="remove-btn" data-isbn="<?php echo $isbn; ?>" title="Xóa sản phẩm" aria-label="Xóa sản phẩm">
								<i class="fa-solid fa-trash"></i>
							</button>
							<div class="product-cell">
								<div class="product-thumb">
									<img src="<?php echo htmlspecialchars($bookImage, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($book['book_title']); ?>" onerror="this.onerror=null;this.src='./bootstrap/img/dark-bg.jpg';">
								</div>
								<div class="product-info">
									<h5><?php echo htmlspecialchars($book['book_title']); ?></h5>
									<p><?php echo htmlspecialchars($book['book_author']); ?></p>
									<span class="stock-tag">Còn hàng</span>
								</div>
							</div>

							<div class="price-cell"><?php echo number_format($book['book_price'], 0, ',', '.'); ?>đ</div>

							<div class="qty-cell">
								<div class="quantity-control">
									<button type="button" class="qty-btn" data-action="minus" data-isbn="<?php echo $isbn; ?>">−</button>
									<input type="number" min="1" value="<?php echo $qty; ?>" data-isbn="<?php echo $isbn; ?>" class="qty-input">
									<button type="button" class="qty-btn" data-action="plus" data-isbn="<?php echo $isbn; ?>">+</button>
								</div>
							</div>

							<div class="total-cell"><?php echo number_format($lineTotal, 0, ',', '.'); ?>đ</div>
						</div>
					<?php } ?>
				</div>
				<div class="cart-actions-row">

				</div>
			</div>

			<div class="cart-right">
				<div class="summary-box">
					<div class="summary-title">Tóm tắt đơn hàng</div>
					<div class="summary-line">
						<span>Tạm tính</span>
						<strong><?php echo number_format($_SESSION['total_price'], 0, ',', '.'); ?>đ</strong>
					</div>

					<?php
					$cartTotals = current_cart_totals($_SESSION['cart']);
					$final_total = $cartTotals['total'];
					$discount = $cartTotals['discount'];
					$activeVoucher = $cartTotals['voucher'];
					if ($activeVoucher):
					?>
						<?php if ($discount > 0): ?>
						<div class="summary-line discount-line" id="discount-line">
							<span>Giảm giá (<?php echo $activeVoucher['type'] === 'percent' ? $activeVoucher['value'] . '%' : ($activeVoucher['type'] === 'fixed' ? 'cố định' : 'ưu đãi'); ?>)</span>
							<strong class="discount-value">-<?php echo number_format($discount, 0, ',', '.'); ?>đ</strong>
						</div>
						<?php endif; ?>
						<div class="summary-line voucher-applied">
							<span>Mã: <strong><?php echo htmlspecialchars($activeVoucher['code']); ?></strong></span>
							<button type="button" class="remove-voucher-btn" title="Hủy mã giảm giá">×</button>
						</div>
					<?php endif; ?>

					<div class="summary-line discount-line">
						<span>Phí vận chuyển</span>
						<strong class="free-text"><?php echo $cartTotals['shipping'] > 0 ? number_format($cartTotals['shipping'], 0, ',', '.') . 'đ' : 'Miễn phí'; ?></strong>
					</div>
					<div class="summary-total">
						<span>Tổng cộng</span>
						<strong id="final-total"><?php echo number_format($final_total, 0, ',', '.'); ?>đ</strong>
					</div>

					<div class="voucher-section">
						<?php if (!$activeVoucher): ?>
						<div class="voucher-input-group">
							<input type="text" id="voucher-code" placeholder="Nhập mã giảm giá" class="voucher-input" maxlength="50">
							<button type="button" id="apply-voucher-btn" class="apply-voucher-btn">Áp dụng</button>
						</div>
						<?php else: ?>
						<div class="voucher-message success">Mã giảm giá đã được áp dụng. Bạn không cần nhập lại khi thanh toán.</div>
						<?php endif; ?>
						<div id="voucher-message" class="voucher-message"></div>
					</div>

					<a href="checkout.php" class="checkout-btn">Tiến hành thanh toán</a>
				</div>
			</div>
		</div>

		<div class="features-row">
			<div class="feature-item">
				<i class="fa-solid fa-shield-halved"></i>
				<span>Sản phẩm chính hãng</span>
			</div>
			<div class="feature-item">
				<i class="fa-solid fa-arrow-rotate-left"></i>
				<span>Đổi trả dễ dàng</span>
			</div>
			<div class="feature-item">
				<i class="fa-solid fa-truck-fast"></i>
				<span>Giao hàng nhanh chóng</span>
			</div>
			<div class="feature-item">
				<i class="fa-solid fa-headset"></i>
				<span>Hỗ trợ 24/7</span>
			</div>
		</div>
	</div>
<?php
} else {
?>
	<div class="container cart-page empty-page">
		<div class="empty-cart-state">
			<div class="empty-cart-icon"><i class="fa-solid fa-cart-shopping"></i></div>
			<h2>Giỏ hàng đang trống</h2>
			<p>Hãy khám phá những cuốn sách yêu thích và thêm sản phẩm vào giỏ hàng nhé.</p>
			<a href="books.php" class="empty-cart-button"><i class="fa-solid fa-book-open"></i> Khám phá sách</a>
		</div>
	</div>
<?php
}
if (isset($conn)) {
	mysqli_close($conn);
}
require_once "./template/footer.php";
?>

<style>
	.cart-page {
	padding-top: 0;
	padding-bottom: 40px;
	color: #1f2937;
	}

	.cart-breadcrumb {
		font-size: 0.95rem;
		color: #6b7280;
		margin-bottom: 18px;
		font-weight: 500;
	}

	.cart-breadcrumb span {
		margin: 0 8px;
		color: #9ca3af;
	}

	.cart-box {
		background: #f4f7f2;
		border: 1px solid #dfe8dc;
		border-radius: 18px;
		padding: 0;
		overflow: hidden;
	}

	.cart-box-header {
		display: flex;
		align-items: center;
		gap: 10px;
		padding: 18px 20px;
		background: #eaf5e8;
		font-size: 1.05rem;
		font-weight: 700;
		color: #1d4d2d;
	}

	.cart-bullet {
		display: inline-block;
		width: 10px;
		height: 10px;
		background: #22a65a;
		border-radius: 50%;
		box-shadow: 0 0 0 5px rgba(34, 166, 90, 0.18);
	}

	.cart-count {
		margin-left: auto;
		font-size: 0.85rem;
		font-weight: 600;
		color: #4b5563;
	}

	.cart-list-header {
		display: grid;
		grid-template-columns: auto 2.3fr 0.9fr 0.9fr 0.8fr;
		gap: 16px;
		padding: 16px 20px 12px;
		font-size: 0.85rem;
		font-weight: 700;
		color: #4b5563;
		text-transform: uppercase;
		letter-spacing: 0.02em;
		border-bottom: 1px solid #e5e7eb;
		background: #f9fafb;
	}

	.cart-item-row {
		display: grid;
		grid-template-columns: auto 2.3fr 0.9fr 0.9fr 0.8fr;
		gap: 16px;
		align-items: center;
		padding: 18px 20px;
		border-bottom: 1px solid #e5e7eb;
		background: #fff;
	}

	.product-cell {
		display: flex;
		align-items: center;
		gap: 16px;
	}

	.product-thumb {
		width: 70px;
		height: 90px;
		border-radius: 12px;
		overflow: hidden;
		background: #f3f4f6;
		border: 1px solid #e5e7eb;
		flex-shrink: 0;
	}

	.product-thumb img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		display: block;
	}

	.product-info h5 {
		margin: 0 0 6px;
		font-size: 1.05rem;
		font-weight: 700;
		line-height: 1.4;
		color: #111827;
	}

	.product-info p {
		margin: 0 0 8px;
		font-size: 0.85rem;
		color: #6b7280;
	}

	.stock-tag {
		display: inline-block;
		font-size: 0.8rem;
		color: #16a34a;
		font-weight: 600;
	}

	.price-cell,
	.total-cell {
		font-size: 1rem;
		font-weight: 700;
		color: #111827;
	}

	.quantity-control {
		display: inline-flex;
		align-items: center;
		border: 1px solid #d1d5db;
		border-radius: 12px;
		overflow: hidden;
		background: #fff;
	}

	.qty-btn {
		width: 30px;
		height: 34px;
		border: none;
		background: #f3f4f6;
		color: #374151;
		font-size: 1.2rem;
		font-weight: 700;
		cursor: pointer;
	}

	.qty-btn:hover {
		background: #e5e7eb;
	}

	.qty-input {
		width: 54px;
		height: 34px;
		border: none;
		text-align: center;
		font-weight: 700;
		color: #111827;
		background: transparent;
		appearance: textfield;
		-moz-appearance: textfield;
	}

	.qty-input::-webkit-outer-spin-button,
	.qty-input::-webkit-inner-spin-button {
		-webkit-appearance: none;
		margin: 0;
	}

	.remove-btn {
		width: 36px;
		height: 36px;
		border: 1px solid #fecaca;
		border-radius: 10px;
		background: #fff1f2;
		color: #ef4444;
		font-size: 0.9rem;
		cursor: pointer;
		padding: 0;
		display: flex;
		align-items: center;
		justify-content: center;
		transition: all 0.2s ease;
	}

	.remove-btn:hover {
		background: #fee2e2;
		border-color: #f87171;
	}

	.header-delete {
		width: 36px;
		height: 36px;
	}

	.cart-actions-row .btn {
		border-radius: 999px;
		padding: 10px 18px;
		font-weight: 600;
	}

	.cart-actions-row {
		flex-wrap: wrap;
		gap: 10px;
	}

	.action-link {
		display: inline-block;
		margin-right: 20px;
		padding: 0;
		background: none;
		border: none;
		color: #2563eb;
		cursor: pointer;
		text-decoration: none;
		font-size: 0.95rem;
		font-weight: 500;
	}

	.action-link:hover {
		color: #1d4ed8;
		text-decoration: underline;
	}

	.delete-link {
		color: #ef4444;
	}

	.delete-link:hover {
		color: #dc2626;
	}

	.cart-alert {
		display: flex;
		align-items: center;
		gap: 10px;
		margin-bottom: 20px;
		background: #dcfce7;
		border: 1px solid #86efac;
		color: #166534;
		padding: 12px 16px;
		border-radius: 12px;
	}

	.cart-alert i {
		color: #22c55e;
	}

	.free-text {
		color: #16a34a;
		font-weight: 700;
	}

	.summary-box {
		padding: 24px 20px;
		background: #f9fafb;
		border-radius: 8px;
		border: 1px solid #e5e7eb;
		position: sticky;
		top: 20px;
		height: fit-content;
	}

	.summary-title {
		font-size: 1.2rem;
		font-weight: 800;
		margin-bottom: 20px;
		color: #ff6b35;
		text-transform: uppercase;
	}

	.summary-line,
	.summary-total {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 12px 0;
		font-size: 0.95rem;
		color: #374151;
	}

	.summary-total {
		margin-top: 16px;
		padding-top: 16px;
		border-top: 1px solid #e5e7eb;
		font-weight: 800;
		font-size: 1.35rem;
		color: #111827;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.summary-total strong {
		color: #ff0000;
		font-size: 1.5rem;
	}

	.discount-line {
		color: #6b7280;
	}

	.checkout-btn {
		display: block;
		width: 100%;
		margin-top: 20px;
		padding: 15px 16px;
		text-align: center;
		background: #ff0000;
		color: #fff;
		border: none;
		border-radius: 8px;
		font-weight: 700;
		font-size: 1.05rem;
		text-decoration: none;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		transition: all 0.3s ease;
		cursor: pointer;
	}

	.checkout-btn:hover {
		background: #e50000;
	}

	.voucher-section {
		margin: 20px 0 16px;
		padding: 16px 0;
		border-top: 1px solid #e5e7eb;
		border-bottom: 1px solid #e5e7eb;
	}

	.voucher-input-group {
		display: flex;
		gap: 8px;
		margin-bottom: 10px;
	}

	.voucher-input {
		flex: 1;
		padding: 10px 12px;
		border: 1px solid #d1d5db;
		border-radius: 6px;
		font-size: 0.9rem;
		background: #fff;
		color: #111827;
	}

	.voucher-input:focus {
		outline: none;
		border-color: #ff6b35;
		box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
	}

	.apply-voucher-btn {
		padding: 10px 16px;
		background: #ff6b35;
		color: #fff;
		border: none;
		border-radius: 6px;
		font-weight: 600;
		font-size: 0.9rem;
		cursor: pointer;
		transition: all 0.3s ease;
		white-space: nowrap;
	}

	.apply-voucher-btn:hover {
		background: #e55a24;
	}

	.voucher-applied {
		background: #ecfdf5;
		padding: 10px 12px;
		border-radius: 6px;
		margin: 8px 0;
		justify-content: space-between;
	}

	.voucher-applied strong {
		color: #059669;
		font-weight: 700;
	}

	.remove-voucher-btn {
		background: none;
		border: none;
		color: #ef4444;
		font-size: 1.5rem;
		cursor: pointer;
		padding: 0;
		line-height: 1;
	}

	.remove-voucher-btn:hover {
		color: #dc2626;
	}

	.discount-value {
		color: #059669 !important;
		font-weight: 700;
	}

	.voucher-message {
		font-size: 0.85rem;
		padding: 8px 10px;
		border-radius: 4px;
		text-align: center;
		margin-top: 8px;
		display: none;
	}

	.voucher-message.success {
		display: block;
		background: #ecfdf5;
		color: #059669;
		border: 1px solid #d1fae5;
	}

	.voucher-message.error {
		display: block;
		background: #fef2f2;
		color: #dc2626;
		border: 1px solid #fee2e2;
	}

	.features-row {
		display: grid;
		grid-template-columns: repeat(4, minmax(0, 1fr));
		gap: 16px;
		margin-top: 28px;
	}

	.feature-item {
		display: flex;
		align-items: center;
		gap: 10px;
		justify-content: center;
		padding: 18px 14px;
		border-radius: 14px;
		background: #fff;
		border: 1px solid #e5e7eb;
		color: #374151;
		font-weight: 600;
	}

	.feature-item i {
		font-size: 1.2rem;
		color: #f59e0b;
	}

	.empty-page {
	padding-top: 0;
	}

	.empty-cart-state {
		max-width: 680px;
		margin: 36px auto 40px;
		padding: 52px 24px;
		text-align: center;
		background: #fff;
		border: 1px solid #b8d3f2;
		border-radius: 18px;
		box-shadow: 0 10px 26px rgba(83, 125, 180, .1);
	}

	.empty-cart-icon {
		display: grid;
		place-items: center;
		width: 76px;
		height: 76px;
		margin: 0 auto 18px;
		border-radius: 50%;
		background: #fff4cc;
		color: #e3a900;
		font-size: 2rem;
	}

	.empty-cart-state h2 {
		margin: 0 0 10px;
		color: #1f2937;
		font-size: 1.55rem;
		font-weight: 800;
	}

	.empty-cart-state p {
		max-width: 460px;
		margin: 0 auto 24px;
		color: #64748b;
		line-height: 1.6;
	}

	.empty-cart-button {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		padding: 11px 20px;
		border-radius: 10px;
		background: #f0b90b;
		color: #20242b;
		font-weight: 800;
		text-decoration: none;
		transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
	}

	.empty-cart-button:hover {
		background: #e3a900;
		color: #20242b;
		transform: translateY(-2px);
		box-shadow: 0 8px 16px rgba(227, 169, 0, .22);
	}

	@media (max-width: 991px) {
		.cart-list-header {
			display: none;
		}

		.cart-item-row {
			grid-template-columns: 1fr;
			gap: 12px;
		}

		.product-cell {
			align-items: flex-start;
		}

		.features-row {
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}
	}

	@media (max-width: 575px) {
		.features-row {
			grid-template-columns: 1fr;
		}

		.cart-actions-row {
			flex-direction: column;
			align-items: stretch;
		}
	}
</style>

<script>
	document.addEventListener('DOMContentLoaded', function() {
		const buttons = document.querySelectorAll('.qty-btn');
		const inputs = document.querySelectorAll('.qty-input');
		const removeButtons = document.querySelectorAll('.remove-btn');

		// Handle +/- button clicks
		buttons.forEach(function(button) {
			button.addEventListener('click', function(e) {
				e.preventDefault();
				const input = button.parentElement.querySelector('.qty-input');
				const action = button.dataset.action;
				const isbn = button.dataset.isbn;
				let value = Number(input.value) || 1;

				if (action === 'plus') value += 1;
				if (action === 'minus') value = Math.max(1, value - 1);

				input.value = value;
				updateCartQuantity(isbn, value);
			});
		});

		// Handle quantity input changes
		inputs.forEach(function(input) {
			input.addEventListener('change', function() {
				const isbn = input.dataset.isbn;
				let value = Number(input.value) || 1;
				value = Math.max(1, value);
				input.value = value;
				updateCartQuantity(isbn, value);
			});
		});

		// Handle remove button clicks
		removeButtons.forEach(function(button) {
			button.addEventListener('click', function(e) {
				e.preventDefault();
				const isbn = button.dataset.isbn;
				removeCartItem(isbn);
			});
		});
	});

	function updateCartQuantity(isbn, qty) {
		fetch('cart.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: 'action=update_qty&isbn=' + encodeURIComponent(isbn) + '&qty=' + qty
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					// Update line total for this item
					const row = document.querySelector('[data-isbn="' + isbn + '"]').closest('.cart-item-row');
					const totalCell = row.querySelector('.total-cell');
					if (totalCell && data.line_total) {
						totalCell.textContent = data.line_total + 'đ';
					}

					updateCartDisplay(data.total_items, data.total_price, data.final_total);
				} else {
					alert(data.message || 'Lỗi cập nhật giỏ hàng');
				}
			})
			.catch(error => {
				console.error('Lỗi:', error);
				alert('Lỗi cập nhật giỏ hàng');
			});
	}

	function removeCartItem(isbn) {
		if (confirm('Bạn chắc chắn muốn xóa sản phẩm này?')) {
			fetch('cart.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: 'action=remove_item&isbn=' + encodeURIComponent(isbn)
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						// Remove the row from DOM
						const row = document.querySelector('[data-isbn="' + isbn + '"]').closest('.cart-item-row');
						row.remove();

						// Remove voucher when item is deleted
						const discountLine = document.getElementById('discount-line');
						const voucherApplied = document.querySelector('.voucher-applied');
						if (discountLine) discountLine.remove();
						if (voucherApplied) voucherApplied.remove();

						// Update cart display
						updateCartDisplay(data.total_items, data.total_price, data.final_total);

						// Check if cart is empty
						if (data.total_items === 0) {
							location.reload();
						}
					} else {
						alert(data.message || 'Lỗi xóa sản phẩm');
					}
				})
				.catch(error => {
					console.error('Lỗi:', error);
					alert('Lỗi xóa sản phẩm');
				});
		}
	}

	function clearCart() {
		if (confirm('Bạn chắc chắn muốn xóa toàn bộ giỏ hàng?')) {
			const form = document.createElement('form');
			form.method = 'POST';
			form.action = 'cart.php';

			const input = document.createElement('input');
			input.type = 'hidden';
			input.name = 'clear_cart';
			input.value = '1';

			form.appendChild(input);
			document.body.appendChild(form);
			form.submit();
		}
	}

	function updateCartDisplay(totalItems, totalPrice, finalTotal) {
		// Update cart count in header
		const cartCounts = document.querySelectorAll('.cart-count');
		const cartAlertSpan = document.querySelector('.cart-alert strong');

		cartCounts.forEach(el => el.textContent = totalItems + ' sản phẩm');
		if (cartAlertSpan) cartAlertSpan.textContent = totalItems + ' sản phẩm';

		// Update total price
		const summaryLines = document.querySelectorAll('.summary-total strong');
		const summaryLines2 = document.querySelectorAll('.summary-line:first-of-type strong');

		summaryLines.forEach(el => el.textContent = (finalTotal || totalPrice) + 'đ');
		summaryLines2.forEach(el => el.textContent = totalPrice + 'đ');

		// Reset voucher display if cart is updated
		const discountLine = document.getElementById('discount-line');
		if (discountLine) {
			location.reload(); // Reload to recalculate discount
		}
	}

	// Voucher code handling
	document.addEventListener('DOMContentLoaded', function() {
		const applyVoucherBtn = document.getElementById('apply-voucher-btn');
		const voucherInput = document.getElementById('voucher-code');
		const removeVoucherBtn = document.querySelector('.remove-voucher-btn');

		if (applyVoucherBtn) {
			applyVoucherBtn.addEventListener('click', applyVoucher);

			voucherInput.addEventListener('keypress', function(e) {
				if (e.key === 'Enter') {
					applyVoucher();
				}
			});
		}

		if (removeVoucherBtn) {
			removeVoucherBtn.addEventListener('click', removeVoucher);
		}
	});

	function applyVoucher() {
		const voucherCode = document.getElementById('voucher-code').value.trim();
		const messageDiv = document.getElementById('voucher-message');

		if (!voucherCode) {
			messageDiv.textContent = 'Vui lòng nhập mã giảm giá';
			messageDiv.className = 'voucher-message error';
			return;
		}

		fetch('cart.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: 'action=apply_voucher&voucher_code=' + encodeURIComponent(voucherCode)
			})
			.then(response => response.json())
			.then(data => {
				const messageDiv = document.getElementById('voucher-message');

				if (data.success) {
					messageDiv.textContent = data.message;
					messageDiv.className = 'voucher-message success';
					document.getElementById('voucher-code').value = '';

					// Update display
					setTimeout(() => {
						location.reload();
					}, 800);
				} else {
					messageDiv.textContent = data.message;
					messageDiv.className = 'voucher-message error';
				}
			})
			.catch(error => {
				console.error('Lỗi:', error);
				const messageDiv = document.getElementById('voucher-message');
				messageDiv.textContent = 'Lỗi kết nối, vui lòng thử lại';
				messageDiv.className = 'voucher-message error';
			});
	}

	function removeVoucher() {
		if (confirm('Bạn muốn xóa mã giảm giá này?')) {
			fetch('cart.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: 'action=remove_voucher'
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						location.reload();
					}
				})
				.catch(error => {
					console.error('Lỗi:', error);
				});
		}
	}
</script>