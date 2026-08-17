<?php
	session_start();
	require_once "./functions/admin.php";
	require_once "./functions/admin.php";
	$title = "Add new book";
	require "./template/header.php";
	require "./functions/database_functions.php";
	$conn = db_connect();

	$nextIsbn = 1;
	$isbnResult = mysqli_query($conn, "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books'");
	if ($isbnResult && ($isbnRow = mysqli_fetch_assoc($isbnResult)) && !empty($isbnRow['AUTO_INCREMENT'])) {
	$nextIsbn = (int)$isbnRow['AUTO_INCREMENT'];
	}
	$generatedIsbn = (string)$nextIsbn;

	if(isset($_POST['add'])){
			$isbn = trim($_POST['isbn'] ?? '');
		if ($isbn === '') {
		$isbn = $generatedIsbn;
		}
		$isbn = mysqli_real_escape_string($conn, $isbn);

		$title = trim($_POST['title']);
		$title = mysqli_real_escape_string($conn, $title);

		$author = trim($_POST['author']);
		$author = mysqli_real_escape_string($conn, $author);

		$descr = trim($_POST['descr']);
		$descr = mysqli_real_escape_string($conn, $descr);

		$basePrice = floatval(trim($_POST['price']));
		$discountPercent = isset($_POST['discount_percent']) ? floatval(trim($_POST['discount_percent'])) : 0;
		$discountAmount = isset($_POST['discount_amount']) ? floatval(trim($_POST['discount_amount'])) : 0;
		$price = $basePrice;
		if ($discountPercent > 0) {
			$price = $price * (1 - ($discountPercent / 100));
		}
		if ($discountAmount > 0) {
			$price = $price - $discountAmount;
		}
		$price = max(0, $price);
		$price = mysqli_real_escape_string($conn, $price);

		$inventory = max(0, min(100, intval($_POST['inventory'] ?? random_int(1, 100))));

	$publisherid = 0;
	$genreid = max(1, intval($_POST['genre'] ?? 0));

				// Nhận ảnh từ máy người dùng và lưu vào thư mục public của dự án
			$image = '';
			if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
			$extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
			$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
			if (in_array($extension, $allowedExtensions, true) && @getimagesize($_FILES['image']['tmp_name'])) {
			$image = uniqid('book_', true) . '.' . $extension;
			$uploadDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR;
			if (!is_dir($uploadDirectory)) {
			mkdir($uploadDirectory, 0755, true);
			}
			if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDirectory . $image)) {
			$image = '';
			}
			}
			}

		$query = "INSERT INTO books (`book_isbn`, `book_title`, `book_author`, `book_image`, `book_descr`, `book_price`, `inventory`, `publisherid`, `genre_id`) VALUES ('" . $isbn . "', '" . $title . "', '" . $author . "', '" . $image . "', '" . $descr . "', '" . $price . "', '" . $inventory . "', '" . $publisherid . "', '" . $genreid . "')";
		$result = mysqli_query($conn, $query);
		if($result){
			$_SESSION['book_success'] = "New Book has been added successfully";
			header("Location: admin_book.php");
		} else {
			$err =  "Can't add new data " . mysqli_error($conn);

		}
	}
?>
	<div class="add-book-page">
	<div class="row justify-content-center">
	<div class="col-lg-8 col-md-10 col-sm-12">
	<div class="card add-book-card">
				<div class="card-body">
					<div class="container-fluid">
						<?php if(isset($err)): ?>
							<div class="alert alert-danger rounded-0">
								<?= htmlspecialchars($err) ?>
							</div>
						<?php
							endif;
						?>
						<form method="post" action="admin_add.php" enctype="multipart/form-data" class="add-book-form">
								
								<div class="mb-3">
									<label class="control-label">Tên sách</label>
									<input class="form-control rounded-0" type="text" name="title" required>
								</div>
								<div class="mb-3">
									<label class="control-label">Tác giả</label>
									<input class="form-control rounded-0" type="text" name="author" required>
								</div>

								<div class="mb-3">
									<label class="control-label">Ảnh bìa</label>
									<input class="form-control rounded-0" type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" required>
								</div>
								<div class="mb-3">
									<label class="control-label">Mô tả sách</label>
									<textarea class="form-control rounded-0" name="descr" cols="40" rows="5"></textarea>
								</div>
								<div class="mb-3">
									<label class="control-label">Giá gốc</label>
									<input id="base_price" class="form-control rounded-0" type="number" min="0" step="1000" name="price" required>
								</div>
								<div class="row">
									<div class="col-md-6">
										<div class="mb-3">
											<label class="control-label">Khuyến mại (%)</label>
											<input id="discount_percent" class="form-control rounded-0" type="number" min="0" max="100" step="0.01" name="discount_percent" value="0">
										</div>
									</div>
									<div class="col-md-6">
										<div class="mb-3">
											<label class="control-label">Giảm trực tiếp (VNĐ)</label>
											<input id="discount_amount" class="form-control rounded-0" type="number" min="0" step="1000" name="discount_amount" value="0">
										</div>
									</div>
								</div>
								<div class="mb-3">
									<label class="control-label">Giá sau khi giảm</label>
									<input id="final_price_preview" class="form-control rounded-0" type="text" readonly value="0 ₫">
								</div>
									<div class="mb-3">
								<label class="control-label">Thể loại</label>
								<select class="form-select rounded-0" name="genre" required>
								<option value="" disabled selected>Vui lòng chọn thể loại</option>
								<?php
								$genreSql = mysqli_query($conn, "SELECT genre_id, genre_name FROM genres ORDER BY genre_id ASC");
								while($genreRow = mysqli_fetch_assoc($genreSql)):
								?>
								<option value="<?= $genreRow['genre_id'] ?>"><?= htmlspecialchars($genreRow['genre_name']) ?></option>
								<?php endwhile; ?>
								</select>
								</div>

								<div class="text-center">
									<button type="submit" name="add"  class="btn btn-primary btn-sm rounded-0"><i class="fas fa-save me-1"></i>Lưu sách</button>
									<button type="reset" class="btn btn-default btn-sm rounded-0 border"><i class="fas fa-rotate-left me-1"></i>Nhập lại</button>
								</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		</div>
	</div>
	</div>
	</div>
</div>
<style>
.add-book-page{max-width:920px;margin:28px auto 48px;padding:0 18px;color:#172033}.add-book-heading{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;padding:24px 28px;background:linear-gradient(135deg,#172033,#26334a);border-radius:18px;color:#fff;box-shadow:0 12px 28px rgba(15,23,42,.14)}.add-book-eyebrow{font-size:11px;font-weight:800;letter-spacing:.14em;color:#f6c344}.add-book-heading h1{margin:8px 0 5px;font-size:28px;font-weight:800}.add-book-heading p{margin:0;color:#cbd5e1;font-size:14px}.add-book-heading-icon{display:grid;place-items:center;width:64px;height:64px;border-radius:16px;background:#f6c344;color:#172033;font-size:27px}.add-book-card{border:0;border-radius:18px;box-shadow:0 8px 25px rgba(15,23,42,.09);overflow:hidden}.add-book-card .card-body{padding:30px}.add-book-form .mb-3{margin-bottom:20px!important}.add-book-form .control-label{display:block;margin-bottom:7px;color:#334155;font-size:13px;font-weight:700}.add-book-form .form-control,.add-book-form .form-select{min-height:44px;border:1px solid #dbe2ea;border-radius:10px!important;box-shadow:none}.add-book-form textarea.form-control{min-height:120px;resize:vertical}.add-book-form .form-control:focus,.add-book-form .form-select:focus{border-color:#4f46e5;box-shadow:0 0 0 3px rgba(79,70,229,.12)}.add-book-form .btn{padding:10px 18px;border-radius:9px!important;font-weight:700}.add-book-form .btn-primary{background:#4f46e5;border-color:#4f46e5}.add-book-form .btn-primary:hover{background:#4338ca;border-color:#4338ca}.add-book-form .alert{border:0;border-radius:10px}.add-book-form input[readonly]{background:#f8fafc;color:#4f46e5;font-weight:700}@media(max-width:600px){.add-book-heading{padding:20px}.add-book-heading h1{font-size:23px}.add-book-heading-icon{width:50px;height:50px;font-size:21px}.add-book-card .card-body{padding:20px}}
.add-book-page{position:relative;padding-top:28px;padding-bottom:48px}.add-book-page:before{content:"";position:absolute;z-index:-1;top:-28px;left:50%;width:100vw;height:calc(100% + 28px);transform:translateX(-50%);background:linear-gradient(135deg,#eef7ff 0%,#f7f3ff 48%,#fff8ec 100%)}.add-book-card{background:rgba(255,255,255,.96);border-top:5px solid #4f46e5}.add-book-card .card-body{background:linear-gradient(180deg,#fff 0%,#fbfcff 100%)}.add-book-form .control-label{position:relative;padding-left:12px;color:#27324a}.add-book-form .control-label:before{content:"";position:absolute;left:0;top:4px;width:4px;height:14px;border-radius:4px;background:linear-gradient(#4f46e5,#ec4899)}.add-book-form .form-control,.add-book-form .form-select{background:#f8fbff}.add-book-form input[type=file]{padding:8px 12px;background:linear-gradient(90deg,#eef2ff,#fff7ed)}.add-book-form textarea.form-control{background:linear-gradient(135deg,#f8fbff,#fff)}.add-book-form .row .mb-3{height:100%}.add-book-form .row .col-md-6:first-child .control-label:before{background:#f59e0b}.add-book-form .row .col-md-6:last-child .control-label:before{background:#ec4899}.add-book-form .text-center{padding-top:12px;border-top:1px solid #e8edf7}.add-book-form .btn-primary{background:linear-gradient(135deg,#4f46e5,#7c3aed);box-shadow:0 8px 16px rgba(79,70,229,.22)}.add-book-form .btn-default{background:#fff7ed;border-color:#fed7aa!important;color:#c2410c}
</style>
<script>
	document.addEventListener('DOMContentLoaded', function () {
		const basePriceInput = document.getElementById('base_price');
		const discountPercentInput = document.getElementById('discount_percent');
		const discountAmountInput = document.getElementById('discount_amount');
		const finalPricePreview = document.getElementById('final_price_preview');

		function calculateFinalPrice() {
			const basePrice = parseFloat(basePriceInput.value) || 0;
			const discountPercent = parseFloat(discountPercentInput.value) || 0;
			const discountAmount = parseFloat(discountAmountInput.value) || 0;

			let finalPrice = basePrice;
			if (discountPercent > 0) {
				finalPrice = finalPrice * (1 - (discountPercent / 100));
			}
			if (discountAmount > 0) {
				finalPrice = finalPrice - discountAmount;
			}
			if (finalPrice < 0) {
				finalPrice = 0;
			}

			finalPricePreview.value = finalPrice.toLocaleString('vi-VN', { maximumFractionDigits: 0 }) + ' ₫';
		}

		[basePriceInput, discountPercentInput, discountAmountInput].forEach(function (input) {
			if (input) {
				input.addEventListener('input', calculateFinalPrice);
			}
		});

		calculateFinalPrice();
	});
</script>
<?php
	if(isset($conn)) {mysqli_close($conn);}
	require_once "./template/footer.php";
?>