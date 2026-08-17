<?php
	header('Content-Type: text/html; charset=utf-8');
	session_start();
	require_once "./functions/database_functions.php";
	require_once "./functions/cart_functions.php";
	require_once "./db_migration.php";

	runDatabaseMigrations();

	$title = "Thanh Toán Đơn Hàng";
	require "./template/header.php";

	$conn = db_connect();

	// Check if user is logged in
	if (!isset($_SESSION['user']) || $_SESSION['user'] !== true || !isset($_SESSION['userid'])) {
		?>
		<div class="container py-5">
			<div class="row justify-content-center">
				<div class="col-lg-6 col-md-8 text-center">
					<div class="card shadow-sm border-0 p-4 rounded-4">
						<div class="mb-3 text-warning">
							<i class="fa fa-user-lock fa-3x"></i>
						</div>
						<h3 class="fw-bold mb-3">Vui lòng đăng nhập</h3>
						<p class="text-muted mb-4">Bạn cần đăng nhập tài khoản để tiếp tục thanh toán và theo dõi đơn hàng của mình.</p>
						<div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
							<a href="auth.php" class="btn btn-warning btn-lg px-4 gap-3 fw-bold">
								<i class="fa fa-sign-in-alt me-2"></i>Đăng Nhập / Đăng Ký
							</a>
							<a href="cart.php" class="btn btn-outline-secondary btn-lg px-4">
								<i class="fa fa-arrow-left me-2"></i>Quay lại Giỏ hàng
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		if(isset($conn)){ mysqli_close($conn); }
		require_once "./template/footer.php";
		exit;
	}

	$userid = intval($_SESSION['userid']);

	// Fetch all addresses for this user
	$userAddresses = array();
	$addressQuery = mysqli_query($conn, "SELECT * FROM user_addresses WHERE userid = '$userid' ORDER BY is_default DESC, address_id DESC");
	if ($addressQuery) {
		while ($row = mysqli_fetch_assoc($addressQuery)) {
			$userAddresses[] = $row;
		}
	}

	// Calculate totals and discounts
	$cartTotals = current_cart_totals($_SESSION['cart'] ?? array());
	$cartTotal = $cartTotals['subtotal'];
	$discountAmount = $cartTotals['discount'];
	$discountPercent = (!empty($cartTotals['voucher']) && $cartTotals['voucher']['type'] === 'percent') ? $cartTotals['voucher']['value'] : 0;
	$voucherCode = $_SESSION['voucher_code'] ?? '';
	$finalTotal = $cartTotals['total'];
?>
<div class="container checkout-page py-4">
	<div class="checkout-breadcrumb mb-3">
		<a href="index.php" class="text-decoration-none text-muted">Trang chủ</a>
		<span class="mx-2 text-muted">&rsaquo;</span>
		<a href="cart.php" class="text-decoration-none text-muted">Giỏ hàng</a>
		<span class="mx-2 text-muted">&rsaquo;</span>
		<span class="fw-bold text-dark">Thanh toán</span>
	</div>

	<h2 class="checkout-main-title mb-4">
		<i class="fa fa-credit-card text-warning me-2"></i>Tiến Hành Thanh Toán
	</h2>

<?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
	<form method="post" action="process.php" id="checkout-form" class="checkout-form">
		<div class="row g-4">
			<!-- Left Column: Shipping & Payment Info -->
			<div class="col-lg-7">
				<!-- Step 1: Address Selection -->
				<div class="checkout-section-card shadow-sm p-4 mb-4">
					<div class="section-card-header d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
						<h4 class="mb-0 fw-bold fs-5 text-dark">
							<i class="fa fa-map-marker-alt text-danger me-2"></i>1. Địa Chỉ Giao Hàng
						</h4>
						<a href="delivery_management.php" class="btn btn-sm btn-outline-warning text-dark fw-semibold">
							<i class="fa fa-cog me-1"></i>Quản lý địa chỉ
						</a>
					</div>

					<?php if (count($userAddresses) > 0): ?>
						<p class="text-muted small mb-3">Chọn địa chỉ nhận hàng từ sổ địa chỉ của bạn hoặc nhập địa chỉ mới bên dưới:</p>
						<div class="address-options-list mb-3">
							<?php foreach ($userAddresses as $idx => $addr): ?>
								<label class="address-card-label d-block p-3 border rounded-3 mb-2 <?php echo ($idx === 0) ? 'border-warning bg-light' : ''; ?>" style="cursor: pointer;">
									<div class="form-check">
										<input class="form-check-input address-radio" type="radio" name="selected_address_id" value="<?php echo $addr['address_id']; ?>"
											<?php echo ($idx === 0) ? 'checked' : ''; ?>
											data-name="<?php echo htmlspecialchars($addr['full_name']); ?>"
											data-phone="<?php echo htmlspecialchars($addr['phone']); ?>"
											data-address="<?php echo htmlspecialchars($addr['street_address']); ?>"
											data-city="<?php echo htmlspecialchars($addr['city']); ?>"
											data-zip="<?php echo htmlspecialchars($addr['postal_code']); ?>">
										<div class="d-inline-block ms-2">
											<div class="fw-bold text-dark">
												<?php echo htmlspecialchars($addr['full_name']); ?>
												<span class="text-muted ms-2 fw-normal"><i class="fa fa-phone small me-1"></i><?php echo htmlspecialchars($addr['phone']); ?></span>
												<?php if ($addr['is_default']): ?>
													<span class="badge bg-warning text-dark ms-2">Mặc định</span>
												<?php endif; ?>
											</div>
											<div class="text-secondary small mt-1">
												<?php echo htmlspecialchars($addr['street_address'] . ', ' . $addr['city']); ?>
												<?php if (!empty($addr['postal_code'])): ?>
													(Mã bưu chính: <?php echo htmlspecialchars($addr['postal_code']); ?>)
												<?php endif; ?>
											</div>
										</div>
									</div>
								</label>
							<?php endforeach; ?>

							<label class="address-card-label d-block p-3 border rounded-3 mb-2" style="cursor: pointer;">
								<div class="form-check">
									<input class="form-check-input address-radio" type="radio" name="selected_address_id" value="new" id="address-option-new">
									<label class="form-check-label fw-bold ms-2 text-dark" for="address-option-new">
										<i class="fa fa-plus-circle text-success me-1"></i> Nhập địa chỉ nhận hàng khác
									</label>
								</div>
							</label>
						</div>

						<!-- Hidden inputs holding selected address data for process.php -->
						<input type="hidden" name="name" id="ship_name" value="<?php echo htmlspecialchars($userAddresses[0]['full_name']); ?>">
						<input type="hidden" name="phone" id="ship_phone" value="<?php echo htmlspecialchars($userAddresses[0]['phone']); ?>">
						<input type="hidden" name="address" id="ship_address" value="<?php echo htmlspecialchars($userAddresses[0]['street_address']); ?>">
						<input type="hidden" name="city" id="ship_city" value="<?php echo htmlspecialchars($userAddresses[0]['city']); ?>">
						<input type="hidden" name="zip_code" id="ship_zip_code" value="<?php echo htmlspecialchars($userAddresses[0]['postal_code'] ?? ''); ?>">
						<input type="hidden" name="country" id="ship_country" value="Việt Nam">

						<!-- Custom Address Form (Collapsed by default) -->
						<div id="custom-address-container" class="custom-address-form bg-light p-3 rounded-3 border mt-3" style="display: none;">
							<h6 class="fw-bold mb-3 text-dark"><i class="fa fa-pen me-1"></i>Thông tin người nhận mới:</h6>
							<div class="row g-2">
								<div class="col-md-6 mb-2">
									<label class="form-label small fw-semibold">Họ và tên *</label>
									<input type="text" id="custom_name" class="form-control form-control-sm" placeholder="Nguyễn Văn A">
								</div>
								<div class="col-md-6 mb-2">
									<label class="form-label small fw-semibold">Số điện thoại *</label>
									<input type="text" id="custom_phone" class="form-control form-control-sm" placeholder="0912345678">
								</div>
								<div class="col-12 mb-2">
									<label class="form-label small fw-semibold">Địa chỉ chi tiết *</label>
									<input type="text" id="custom_address" class="form-control form-control-sm" placeholder="Số nhà, đường, phường/xã...">
								</div>
								<div class="col-md-6 mb-2">
									<label class="form-label small fw-semibold">Tỉnh / Thành phố *</label>
									<input type="text" id="custom_city" class="form-control form-control-sm" placeholder="Hà Nội, TP.HCM...">
								</div>
								<div class="col-md-6 mb-2">
									<label class="form-label small fw-semibold">Mã bưu điện</label>
									<input type="text" id="custom_zip" class="form-control form-control-sm" placeholder="100000">
								</div>
								<div class="col-12 mt-2">
									<div class="form-check">
										<input class="form-check-input" type="checkbox" name="save_to_address_book" id="save_to_address_book" value="1" checked>
										<label class="form-check-label small" for="save_to_address_book">
											Lưu địa chỉ này vào sổ địa chỉ để dùng cho lần sau
										</label>
									</div>
								</div>
							</div>
						</div>

					<?php else: ?>
						<!-- No saved address: Direct Form -->
						<div class="alert alert-info py-2 small mb-3">
							<i class="fa fa-info-circle me-1"></i> Bạn chưa có địa chỉ lưu sẵn. Vui lòng điền thông tin người nhận bên dưới:
						</div>
						<div class="row g-2">
							<div class="col-md-6 mb-2">
								<label class="form-label small fw-semibold">Họ và tên người nhận *</label>
								<input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($_SESSION['fullname'] ?? ''); ?>" required placeholder="Ví dụ: Nguyễn Văn A">
							</div>
							<div class="col-md-6 mb-2">
								<label class="form-label small fw-semibold">Số điện thoại *</label>
								<input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>" required placeholder="Ví dụ: 0912345678">
							</div>
							<div class="col-12 mb-2">
								<label class="form-label small fw-semibold">Địa chỉ giao hàng chi tiết *</label>
								<input type="text" name="address" class="form-control" required placeholder="Số nhà, tên đường, ngõ ngách, phường/xã...">
							</div>
							<div class="col-md-6 mb-2">
								<label class="form-label small fw-semibold">Tỉnh / Thành phố *</label>
								<input type="text" name="city" class="form-control" required placeholder="Ví dụ: Hà Nội, Đà Nẵng, TP.HCM...">
							</div>
							<div class="col-md-6 mb-2">
								<label class="form-label small fw-semibold">Mã bưu chính (Zip code)</label>
								<input type="text" name="zip_code" class="form-control" placeholder="Ví dụ: 100000">
							</div>
							<input type="hidden" name="country" value="Việt Nam">
							<div class="col-12 mt-2">
								<div class="form-check">
									<input class="form-check-input" type="checkbox" name="save_to_address_book" id="save_to_address_book_direct" value="1" checked>
									<label class="form-check-label small" for="save_to_address_book_direct">
										Lưu thông tin này vào sổ địa chỉ cá nhân
									</label>
								</div>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<!-- Step 2: Payment Method -->
				<div class="checkout-section-card shadow-sm p-4 mb-4">
					<div class="section-card-header mb-3 pb-2 border-bottom">
						<h4 class="mb-0 fw-bold fs-5 text-dark">
							<i class="fa fa-wallet text-success me-2"></i>2. Phương Thức Thanh Toán
						</h4>
					</div>

					<div class="payment-methods-list">
						<label class="payment-method-card d-block p-3 border rounded-3 mb-2 border-warning bg-light" style="cursor: pointer;">
							<div class="form-check d-flex align-items-center">
								<input class="form-check-input payment-radio me-3" type="radio" name="payment_method" value="cod" id="pay_cod" checked>
								<div class="flex-grow-1">
									<div class="fw-bold text-dark d-flex align-items-center justify-content-between">
										<span><i class="fa fa-truck text-warning me-2"></i>Thanh toán khi nhận hàng (COD)</span>
										<span class="badge bg-success">Phổ biến</span>
									</div>
									<div class="text-muted small mt-1">Bạn chỉ thanh toán bằng tiền mặt khi shipper giao sách tận nơi.</div>
								</div>
							</div>
						</label>

						<label class="payment-method-card d-block p-3 border rounded-3 mb-2" style="cursor: pointer;">
							<div class="form-check d-flex align-items-center">
								<input class="form-check-input payment-radio me-3" type="radio" name="payment_method" value="bank_transfer" id="pay_transfer">
								<div class="flex-grow-1">
									<div class="fw-bold text-dark">
										<i class="fa fa-university text-primary me-2"></i>Chuyển khoản ngân hàng (QR Code / Mobile Banking)
									</div>
									<div class="text-muted small mt-1">Chuyển khoản trực tiếp với nội dung mã đơn hàng. Sách sẽ được gửi ngay khi nhận chuyển khoản.</div>
								</div>
							</div>
						</label>
					</div>
				</div>

				<!-- Step 3: Order Notes -->
				<div class="checkout-section-card shadow-sm p-4 mb-4">
					<div class="section-card-header mb-3 pb-2 border-bottom">
						<h4 class="mb-0 fw-bold fs-5 text-dark">
							<i class="fa fa-pencil-alt text-secondary me-2"></i>3. Ghi Chú Đơn Hàng (Tùy chọn)
						</h4>
					</div>
					<textarea name="notes" class="form-control" rows="2" placeholder="Ví dụ: Giao vào giờ hành chính, gọi điện trước khi giao..."></textarea>
				</div>
			</div>

			<!-- Right Column: Order Summary -->
			<div class="col-lg-5">
				<div class="order-summary-sidebar shadow-sm p-4 rounded-3 border sticky-top" style="top: 80px; background: #fff;">
					<h4 class="fw-bold fs-5 mb-3 text-dark pb-2 border-bottom">
						<i class="fa fa-shopping-bag text-warning me-2"></i>Đơn Hàng (<?php echo count($_SESSION['cart']); ?> sản phẩm)
					</h4>

					<!-- Item List -->
					<div class="checkout-items-list mb-3" style="max-height: 280px; overflow-y: auto;">
						<?php
							foreach($_SESSION['cart'] as $isbn => $qty){
								$book = mysqli_fetch_assoc(getBookByIsbn($conn, $isbn));
								$bookImage = !empty($book['book_image']) ? './bootstrap/img/' . $book['book_image'] : './bootstrap/img/default-book.jpg';
								$itemLineTotal = $qty * $book['book_price'];
						?>
						<div class="d-flex align-items-center gap-3 py-2 border-bottom">
							<img src="<?php echo $bookImage; ?>" alt="<?php echo htmlspecialchars($book['book_title']); ?>"
								style="width: 50px; height: 65px; object-fit: cover; border-radius: 6px; border: 1px solid #e5e7eb; flex-shrink: 0;">
							<div class="flex-grow-1 min-w-0">
								<h6 class="mb-1 text-truncate fw-semibold text-dark" style="font-size: 0.9rem;" title="<?php echo htmlspecialchars($book['book_title']); ?>">
									<?php echo htmlspecialchars($book['book_title']); ?>
								</h6>
								<div class="text-muted small">
									Số lượng: <strong class="text-dark"><?php echo $qty; ?></strong> &times; <?php echo number_format($book['book_price'], 0, ',', '.'); ?>đ
								</div>
							</div>
							<div class="fw-bold text-end text-dark" style="font-size: 0.95rem; white-space: nowrap;">
								<?php echo number_format($itemLineTotal, 0, ',', '.'); ?>đ
							</div>
						</div>
						<?php } ?>
					</div>

					<!-- Pricing Breakdown -->
					<div class="pricing-breakdown pt-2">
						<div class="d-flex justify-content-between text-muted mb-2">
							<span>Tạm tính:</span>
							<span class="fw-semibold text-dark"><?php echo number_format($cartTotal, 0, ',', '.'); ?>đ</span>
						</div>

						<?php if ($discountAmount > 0): ?>
						<div class="d-flex justify-content-between text-success mb-2">
							<span><i class="fa fa-tag me-1"></i>Giảm giá (<?php echo $discountPercent; ?>%<?php echo !empty($voucherCode) ? " - $voucherCode" : ''; ?>):</span>
							<span class="fw-bold">-<?php echo number_format($discountAmount, 0, ',', '.'); ?>đ</span>
						</div>
						<?php endif; ?>

						<div class="d-flex justify-content-between text-muted mb-2">
							<span>Phí giao hàng:</span>
							<span class="text-success fw-bold">Miễn phí</span>
						</div>

						<hr class="my-3">

						<div class="d-flex justify-content-between align-items-center mb-4">
							<span class="fw-bold fs-6 text-dark">Tổng thanh toán:</span>
							<span class="fw-bold fs-4 text-danger"><?php echo number_format($finalTotal, 0, ',', '.'); ?>đ</span>
						</div>

						<button type="submit" id="btn-submit-order" class="btn btn-warning btn-lg w-100 fw-bold py-3 text-dark text-uppercase shadow-sm">
							<i class="fa fa-check-circle me-2"></i>Xác Nhận Đặt Hàng
						</button>

						<div class="text-center mt-3">
							<a href="cart.php" class="text-muted small text-decoration-none">
								<i class="fa fa-arrow-left me-1"></i>Chỉnh sửa giỏ hàng
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>

<?php else: ?>
	<div class="alert alert-warning py-4 text-center rounded-4 shadow-sm">
		<i class="fa fa-shopping-cart fa-2x mb-3 d-block text-warning"></i>
		<h4 class="fw-bold">Giỏ hàng của bạn đang trống</h4>
		<p class="text-muted">Vui lòng chọn thêm sách vào giỏ hàng trước khi tiến hành thanh toán.</p>
		<a href="books.php" class="btn btn-warning mt-2 fw-bold">
			<i class="fa fa-book me-2"></i>Khám Phá Sách Ngay
		</a>
	</div>
<?php endif; ?>

</div>

<style>
.checkout-page {
	font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
	color: #1f2937;
}

.checkout-main-title {
	font-weight: 800;
	color: #111827;
}

.checkout-section-card {
	background: #fff;
	border: 1px solid #e5e7eb;
	border-radius: 14px;
}

.address-card-label, .payment-method-card {
	transition: all 0.2s ease;
}

.address-card-label:hover, .payment-method-card:hover {
	border-color: #ffc107 !important;
}

.address-card-label.selected, .payment-method-card.selected {
	border-color: #ffc107 !important;
	background-color: #fffdf5 !important;
}

.order-summary-sidebar {
	border-radius: 14px;
	border: 1px solid #e5e7eb;
}

#btn-submit-order {
	background: linear-gradient(135deg, #ffc107, #ff9800);
	border: none;
	transition: all 0.25s ease;
}

#btn-submit-order:hover {
	background: linear-gradient(135deg, #ffb300, #f57c00);
	transform: translateY(-2px);
	box-shadow: 0 6px 16px rgba(255, 152, 0, 0.35);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
	const addressRadios = document.querySelectorAll('.address-radio');
	const customContainer = document.getElementById('custom-address-container');
	const hiddenName = document.getElementById('ship_name');
	const hiddenPhone = document.getElementById('ship_phone');
	const hiddenAddress = document.getElementById('ship_address');
	const hiddenCity = document.getElementById('ship_city');
	const hiddenZip = document.getElementById('ship_zip_code');

	const customName = document.getElementById('custom_name');
	const customPhone = document.getElementById('custom_phone');
	const customAddress = document.getElementById('custom_address');
	const customCity = document.getElementById('custom_city');
	const customZip = document.getElementById('custom_zip');

	function updateAddressCards() {
		addressRadios.forEach(radio => {
			const label = radio.closest('.address-card-label');
			if (radio.checked) {
				label.classList.add('border-warning', 'bg-light');
			} else {
				label.classList.remove('border-warning', 'bg-light');
			}
		});
	}

	addressRadios.forEach(radio => {
		radio.addEventListener('change', function () {
			updateAddressCards();
			if (this.value === 'new') {
				if (customContainer) customContainer.style.display = 'block';
				if (hiddenName) hiddenName.value = customName ? customName.value : '';
				if (hiddenPhone) hiddenPhone.value = customPhone ? customPhone.value : '';
				if (hiddenAddress) hiddenAddress.value = customAddress ? customAddress.value : '';
				if (hiddenCity) hiddenCity.value = customCity ? customCity.value : '';
				if (hiddenZip) hiddenZip.value = customZip ? customZip.value : '';
			} else {
				if (customContainer) customContainer.style.display = 'none';
				if (hiddenName) hiddenName.value = this.dataset.name || '';
				if (hiddenPhone) hiddenPhone.value = this.dataset.phone || '';
				if (hiddenAddress) hiddenAddress.value = this.dataset.address || '';
				if (hiddenCity) hiddenCity.value = this.dataset.city || '';
				if (hiddenZip) hiddenZip.value = this.dataset.zip || '';
			}
		});
	});

	// Handle custom inputs typing
	if (customContainer) {
		[customName, customPhone, customAddress, customCity, customZip].forEach(input => {
			if (!input) return;
			input.addEventListener('input', function () {
				const isNew = document.getElementById('address-option-new')?.checked;
				if (isNew) {
					if (hiddenName) hiddenName.value = customName.value.trim();
					if (hiddenPhone) hiddenPhone.value = customPhone.value.trim();
					if (hiddenAddress) hiddenAddress.value = customAddress.value.trim();
					if (hiddenCity) hiddenCity.value = customCity.value.trim();
					if (hiddenZip) hiddenZip.value = customZip.value.trim();
				}
			});
		});
	}

	// Handle payment radios styling
	const paymentRadios = document.querySelectorAll('.payment-radio');
	paymentRadios.forEach(radio => {
		radio.addEventListener('change', function () {
			paymentRadios.forEach(r => {
				const label = r.closest('.payment-method-card');
				if (r.checked) {
					label.classList.add('border-warning', 'bg-light');
				} else {
					label.classList.remove('border-warning', 'bg-light');
				}
			});
		});
	});

	// Validate before submit
	const form = document.getElementById('checkout-form');
	if (form) {
		form.addEventListener('submit', function (e) {
			const isNew = document.getElementById('address-option-new')?.checked;
			if (isNew) {
				if (!customName.value.trim() || !customPhone.value.trim() || !customAddress.value.trim() || !customCity.value.trim()) {
					e.preventDefault();
					alert('Vui lòng điền đầy đủ Họ tên, Số điện thoại, Địa chỉ và Tỉnh/Thành phố của người nhận mới.');
					return false;
				}
			}
		});
	}
});
</script>

<?php
	if(isset($conn)){ mysqli_close($conn); }
	require_once "./template/footer.php";
?>