<?php
	session_start();
	ob_start();
	require_once "./functions/admin.php";
	require_once "./functions/admin.php";
	$title = "Sửa thông tin sách";
	require_once "./template/header.php";
	require_once "./functions/database_functions.php";
	$conn = db_connect();

	if(isset($_GET['bookisbn']) && trim($_GET['bookisbn']) !== ''){
	$book_isbn = trim($_GET['bookisbn']);
	} else {
		echo "Empty query!";
		exit;
	}

	if(!isset($book_isbn)){
		echo "Empty isbn! check again!";
		exit;
	}

	// get book data
	$query = "SELECT * FROM books WHERE book_isbn = '{$book_isbn}'";
	$result = mysqli_query($conn, $query);
	if(!$result){
		echo $err = "Can't retrieve data ";
		exit;
	}else{
		$row = mysqli_fetch_assoc($result);
	}
	if(isset($_POST['edit'])){
		$book_isbn = trim($_GET['bookisbn']);
		$basePrice = isset($_POST['book_price']) ? floatval(trim($_POST['book_price'])) : 0;
		$discountPercent = isset($_POST['discount_percent']) ? floatval(trim($_POST['discount_percent'])) : 0;
		$discountAmount = isset($_POST['discount_amount']) ? floatval(trim($_POST['discount_amount'])) : 0;
		$finalPrice = $basePrice;
		if ($discountPercent > 0) {
			$finalPrice = $finalPrice * (1 - ($discountPercent / 100));
		}
		if ($discountAmount > 0) {
			$finalPrice = $finalPrice - $discountAmount;
		}
		$_POST['book_price'] = max(0, $finalPrice);
			$editableFields = ['book_title', 'book_author', 'book_descr', 'book_price', 'inventory', 'genre_id'];
		$updates = [];
		foreach ($editableFields as $field) {
		if (isset($_POST[$field])) {
		$value = mysqli_real_escape_string($conn, trim((string) $_POST[$field]));
		$updates[] = "`{$field}` = '{$value}'";
		}
		}
			if (empty($updates)) {
		$err = "Bạn chưa thay đổi thông tin nào.";
		} else {
		$data = implode(', ', $updates);
		$escapedIsbn = mysqli_real_escape_string($conn, $book_isbn);
		$query = "UPDATE books SET $data WHERE book_isbn = '{$escapedIsbn}'";
		$result = mysqli_query($conn, $query);
		}
			if (!empty($updates)) {
		if ($result) {
		$_SESSION['book_success'] = "Cập nhật thông tin sách thành công!";
			header("Location: admin_book.php");
			exit();
		}
		$err = "Không thể cập nhật sách: " . mysqli_error($conn);
		}
	}
?>
	<h4 class="fw-bolder text-center">Sửa thông tin sách</h4>
	<center>
	<hr class="bg-warning" style="width:5em;height:3px;opacity:1">
	</center>
	<div class="row justify-content-center">
		<div class="col-lg-6 col-md-8 col-sm-10 col-xs-12">
			<div class="card rounded-0 shadow">
				<div class="card-body">
					<div class="container-fluid">
						<?php if(isset($err)): ?>
								<div class="alert alert-danger rounded-0">
							<?= htmlspecialchars($err) ?>
							</div>
						<?php
							endif;
						?>
						<form method="post" action="admin_edit.php?bookisbn=<?php echo $row['book_isbn'];?>" enctype="multipart/form-data">

								<div class="mb-3">
									<label class="control-label">Tên sách</label>
									<input class="form-control rounded-0" type="text" name="book_title" value="<?php echo $row['book_title'];?>" required>
								</div>
								<div class="mb-3">
									<label class="control-label">Tác giả</label>
									<input class="form-control rounded-0" type="text" name="book_author" value="<?php echo $row['book_author'];?>" required>
								</div>
								<div class="mb-3">
									<label class="control-label">Mô tả sách</label>
									<textarea class="form-control rounded-0" name="book_descr" cols="40" rows="5"><?php echo $row['book_descr'];?></textarea>
								</div>
									<div class="mb-3">
								<label class="control-label">Số lượng tồn kho</label>
								<input class="form-control rounded-0" type="number" name="inventory" min="0" max="100" step="1" value="<?php echo (int)$row['inventory'];?>" required>
								</div>
									<div class="mb-3">
								<label class="control-label">Giá gốc</label>
								<input id="base_price" class="form-control rounded-0" type="text" inputmode="numeric" name="book_price" value="<?php echo number_format((float)$row['book_price'], 0, '.', '');?>" required>
								</div>
								<div class="row">
									<div class="col-md-6">
										<div class="mb-3">
											<label class="control-label">Khuyến mãi (%)</label>
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
									<label class="control-label">Giá bán sau khi giảm</label>
									<input id="final_price_preview" class="form-control rounded-0" type="text" readonly value="<?php echo number_format($row['book_price'], 0, ',', '.'); ?> ₫">
								</div>
									<div class="mb-3">
								<label class="control-label">Thể loại sách</label>
								<select class="form-select rounded-0" name="genre_id" required>
								<option value="" disabled>Vui lòng chọn thể loại</option>
								<?php
								$genreSql = mysqli_query($conn, "SELECT genre_id, genre_name FROM genres ORDER BY genre_id ASC");
								while($genre = mysqli_fetch_assoc($genreSql)):
								?>
								<option value="<?= $genre['genre_id'] ?>" <?= (int)$genre['genre_id'] === (int)$row['genre_id'] ? 'selected' : '' ?>><?= htmlspecialchars($genre['genre_name']) ?></option>
								<?php endwhile; ?>
								</select>
								</div>

								<div class="text-center">
										<button type="submit" name="edit" class="btn btn-primary btn-sm rounded-0">Lưu thay đổi</button>
									<a href="admin_book.php" class="btn btn-secondary btn-sm rounded-0">Hủy</a>
								</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
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
	ob_end_flush();
	require "./template/footer.php"
?>