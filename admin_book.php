<?php
	session_start();
	require_once "./functions/admin.php";
	$title = "Danh Sách Sách";
	require_once "./template/header.php";
	require_once "./functions/database_functions.php";
	$conn = db_connect();

	// Xử lý thêm sách mới
	$err = '';
	if(isset($_POST['add'])){
		$isbn = trim($_POST['isbn']);
		$isbn = mysqli_real_escape_string($conn, $isbn);

		$title_book = trim($_POST['title']);
		$title_book = mysqli_real_escape_string($conn, $title_book);

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

		$publisherid = 0;

			$image = '';
		// Nhận ảnh từ máy người dùng và lưu vào thư mục public của dự án
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

		$query = "INSERT INTO books (`book_isbn`, `book_title`, `book_author`, `book_image`, `book_descr`, `book_price`, `publisherid`) VALUES ('" . $isbn . "', '" . $title_book . "', '" . $author . "', '" . $image . "', '" . $descr . "', '" . $price . "', '" . $publisherid . "')";
		$result = mysqli_query($conn, $query);
		if($result){
			$_SESSION['book_success'] = "Sách mới đã được thêm thành công!";
			header("Location: admin_book.php");
			exit();
		} else {
			$err =  "Không thể thêm dữ liệu: " . mysqli_error($conn);
		}
	}

	$selectedGenre = isset($_GET['genre']) ? max(0, (int) $_GET['genre']) : 0;
	$genreFilterSql = $selectedGenre > 0 ? " WHERE genre_id = {$selectedGenre}" : '';
	$booksPerPage = 10;
	$bookPage = max(1, (int)($_GET['page'] ?? 1));
	$totalBooksResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM books{$genreFilterSql}");
	$totalBooks = $totalBooksResult ? (int)mysqli_fetch_assoc($totalBooksResult)['total'] : 0;
	$totalBookPages = max(1, (int)ceil($totalBooks / $booksPerPage));
	$bookPage = min($bookPage, $totalBookPages);
	$bookOffset = ($bookPage - 1) * $booksPerPage;
	$result = mysqli_query($conn, "SELECT * FROM books{$genreFilterSql} ORDER BY book_isbn DESC LIMIT {$booksPerPage} OFFSET {$bookOffset}");
	$customerCount = 0;
	if(mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'users'")) > 0){
		$customerResult = mysqli_query($conn, "SELECT userid FROM users");
		if($customerResult){
			$customerCount = mysqli_num_rows($customerResult);
		}
	}
	ensure_book_inventory_schema($conn);
	$modernResult = mysqli_query($conn, "SELECT * FROM books{$genreFilterSql} ORDER BY book_isbn DESC LIMIT {$booksPerPage} OFFSET {$bookOffset}");
	$genreOptions = mysqli_query($conn, "SELECT genre_id, genre_name FROM genres ORDER BY genre_id ASC");
?>

<div class="modern-books-admin">
	<aside class="modern-admin-sidebar">
	<div class="modern-admin-brand"><span class="modern-brand-mark">VL</span><div><strong>Nhà Sách Việt Long</strong><small>Admin Panel</small></div></div>
	<nav><a href="admin_dashboard.php"><i class="fas fa-chart-pie"></i>Dashboard</a><a href="admin_customer.php"><i class="fas fa-users"></i>Quản lý người dùng</a><a class="active" href="admin_book.php"><i class="fas fa-book"></i>Quản lý sách</a><a href="orders.php"><i class="fas fa-shopping-bag"></i>Quản lý đơn hàng</a></nav>
	<a class="modern-logout" href="admin_signout.php"><i class="fas fa-sign-out-alt"></i>Đăng xuất</a>
	</aside>
	<main class="modern-books-main">
	<header class="modern-books-header"><div><span class="modern-eyebrow">KHO SÁCH</span><h1>Quản lý sách</h1><p>Quản lý toàn bộ sách trong hệ thống</p></div></header>
	<?php if(isset($_SESSION['book_success'])): ?><div class="modern-alert success"><i class="fas fa-check-circle"></i><?= htmlspecialchars($_SESSION['book_success']) ?></div><?php unset($_SESSION['book_success']); endif; ?>
	<?php if(!empty($err)): ?><div class="modern-alert danger"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($err) ?></div><?php endif; ?>
	<section class="modern-books-title"><div><h2>Danh sách</h2><p><?= $totalBooks ?> đầu sách đang được quản lý</p></div><a class="modern-primary-btn" href="admin_add.php"><i class="fas fa-plus"></i> Thêm sách</a></section>
	<?php $totalStock = 0; foreach ($modernResult as $stockBook) { $totalStock += (int)$stockBook['inventory']; } mysqli_data_seek($modernResult, 0); ?>
	<section class="modern-stats"><div><span class="modern-stat-icon yellow"><i class="fas fa-books"></i></span><div><strong><?= mysqli_num_rows($modernResult) ?></strong><small>Tổng sách</small></div></div><div><span class="modern-stat-icon green"><i class="fas fa-store"></i></span><div><strong><?= mysqli_num_rows($modernResult) ?></strong><small>Đang bán</small></div></div><div><span class="modern-stat-icon orange"><i class="fas fa-boxes-stacked"></i></span><div><strong><?= number_format($totalStock) ?></strong><small>Tổng tồn kho</small></div></div><div><span class="modern-stat-icon red"><i class="fas fa-box-open"></i></span><div><strong>0</strong><small>Hết hàng</small></div></div></section>
	<section class="modern-filter"><div class="modern-search"><i class="fas fa-search"></i><input id="bookSearch" type="search" placeholder="Tìm kiếm sách theo tên, mã sách hoặc tác giả..."></div><select id="bookGenre" onchange="window.location.href=this.value"><option value="admin_book.php">Tất cả thể loại</option><?php while($genre = mysqli_fetch_assoc($genreOptions)): ?><option value="admin_book.php?genre=<?= (int)$genre['genre_id'] ?>" <?= $selectedGenre === (int)$genre['genre_id'] ? 'selected' : '' ?>><?= htmlspecialchars($genre['genre_name']) ?></option><?php endwhile; ?></select><select id="bookStatus"><option value="">Tất cả trạng thái</option><option value="available">Đang bán</option><option value="low">Sắp hết</option></select><select id="bookSort"><option value="title">Tên sách A–Z</option><option value="price">Giá cao đến thấp</option></select></section>
	<section class="modern-table-card"><div class="modern-table-wrap"><table class="modern-books-table"><thead><tr><th>Sách</th><th>Mã sách</th><th>Tác giả</th><th>Giá bán</th><th>Tồn kho</th><th>Trạng thái</th><th class="actions-col">Thao tác</th></tr></thead><tbody id="modernBookRows">
	<?php if(mysqli_num_rows($modernResult) > 0): while($book = mysqli_fetch_assoc($modernResult)): ?>
	<?php $stock = (int)$book['inventory']; $stockClass = $stock <= 10 ? 'low' : 'available'; $stockStatus = $stock <= 10 ? 'Sắp hết' : 'Đang bán'; ?>
	<tr class="modern-book-row" data-search="<?= htmlspecialchars(strtolower($book['book_title'].' '.$book['book_isbn'].' '.$book['book_author'])) ?>" data-price="<?= (float)$book['book_price'] ?>" data-stock="<?= $stock ?>">
	<td class="modern-book-cell"><img src="<?= !empty($book['book_image']) ? './bootstrap/img/'.rawurlencode(basename($book['book_image'])) : './bootstrap/img/dark-bg.jpg' ?>" alt=""><span><strong><?= htmlspecialchars($book['book_title']) ?></strong><small><?= htmlspecialchars($book['book_isbn']) ?></small></span></td>
	<td><a class="modern-isbn" href="book.php?bookisbn=<?= urlencode($book['book_isbn']) ?>" target="_blank"><?= htmlspecialchars($book['book_isbn']) ?></a></td>
	<td><?= htmlspecialchars($book['book_author']) ?></td>
	<td class="modern-price"><?= number_format($book['book_price'], 0, ',', '.') ?> đ</td>
	<td><span class="modern-stock <?= $stockClass ?>"><?= $stock ?></span></td>
	<td><span class="modern-status <?= $stockClass ?>"><?= $stockStatus ?></span></td>
	<td class="modern-actions"><a href="admin_edit.php?bookisbn=<?= urlencode($book['book_isbn']) ?>" title="Sửa"><i class="fas fa-pen"></i></a><a class="danger-action" href="admin_delete.php?bookisbn=<?= urlencode($book['book_isbn']) ?>" title="Xóa" onclick="return confirm('Bạn chắc chắn muốn xóa sách này?');"><i class="fas fa-trash"></i></a></td>
	</tr>
	<?php endwhile; else: ?><tr><td colspan="7" class="modern-empty"><i class="fas fa-book-open"></i><strong>Chưa có sách nào</strong><span>Hãy thêm sách mới để bắt đầu quản lý kho.</span></td></tr><?php endif; ?>
	</tbody></table></div><div class="modern-table-footer"><span>Hiển thị <?= mysqli_num_rows($modernResult) ?> sách trên trang <?= $bookPage ?></span><span>Dữ liệu được lấy trực tiếp từ hệ thống</span></div><?php if($totalBookPages > 1): ?><nav class="books-pagination" aria-label="Phân trang sách"><?php for($pageNumber = 1; $pageNumber <= $totalBookPages; $pageNumber++): ?><a class="<?= $pageNumber === $bookPage ? 'active' : '' ?>" href="admin_book.php?page=<?= $pageNumber ?>&genre=<?= $selectedGenre ?>"><?= $pageNumber ?></a><?php endfor; ?></nav><?php endif; ?></section>
	</main>
</div>
<script>
document.addEventListener('DOMContentLoaded',function(){const search=document.getElementById('bookSearch'),status=document.getElementById('bookStatus'),sort=document.getElementById('bookSort'),body=document.getElementById('modernBookRows');if(!search||!status||!sort||!body)return;function filter(){const q=(search.value||'').toLowerCase().trim(),selected=status.value;[...body.querySelectorAll('.modern-book-row')].forEach(r=>{const matchesSearch=!q||r.dataset.search.includes(q);const stock=Number(r.dataset.stock||0);const matchesStatus=!selected||(selected==='available'&&stock>10)||(selected==='low'&&stock<=10);r.style.display=matchesSearch&&matchesStatus?'':'none';});}search.addEventListener('input',filter);status.addEventListener('change',filter);sort.addEventListener('change',function(){const rows=[...body.querySelectorAll('.modern-book-row')];rows.sort((a,b)=>sort.value==='price'?Number(b.dataset.price)-Number(a.dataset.price):a.dataset.search.localeCompare(b.dataset.search,'vi'));rows.forEach(r=>body.appendChild(r));filter();});filter();});
</script>
<div class="legacy-books-layout">
	<div class="row g-4">
		<!-- Sidebar trái -->
		<div class="col-lg-3 col-md-4">
			<div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden; background: linear-gradient(180deg, #f8f9ff 0%, #ffffff 100%);">
				<div class="card-header border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.25rem 1rem;">
					<h5 class="mb-0 fw-bold">
						<i class="fas fa-cog me-2"></i> Admin Panel
					</h5>
				</div>
				<div class="list-group list-group-flush">
					<a href="admin_book.php" class="list-group-item list-group-item-action active" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 1rem 1.1rem;">
						<i class="fas fa-book me-2"></i> Danh Sách Sách
					</a>
					<a href="admin_customer.php" class="list-group-item list-group-item-action" style="padding: 1rem 1.1rem; color: #333; border: none; background: #fff;">
						<i class="fas fa-users me-2"></i> Quản Lý Tài Khoản Khách Hàng
					</a>
				</div>
				<div class="card-body border-top" style="background: #f8f9ff;">
					<div class="d-flex justify-content-between align-items-center mb-2">
						<span class="text-muted small">Tổng sách</span>
						<span class="badge bg-primary rounded-pill"><?= mysqli_num_rows($result) ?></span>
					</div>
					<div class="d-flex justify-content-between align-items-center">
						<span class="text-muted small">Khách hàng</span>
						<span class="badge bg-success rounded-pill"><?= $customerCount ?></span>
					</div>
				</div>
			</div>
		</div>

		<!-- Nội dung bên phải -->
		<div class="col-lg-9 col-md-8">
			<!-- Header với Button Thêm Sách -->
			<div class="row align-items-center mb-4">
				<div class="col-md-6">
				<div style="display: flex; align-items: center; gap: 14px; background: linear-gradient(135deg, #eef2ff 0%, #f8f9ff 100%); border: 1px solid #e5e7eb; border-radius: 14px; padding: 0.9rem 1.2rem; box-shadow: 0 6px 18px rgba(102, 126, 234, 0.08);">
					<i class="fas fa-list" style="font-size: 1.8rem; color: #667eea;"></i>
					<h2 class="mb-0" style="color: #1f2937; font-weight: 800; letter-spacing: 0.4px; font-size: 2rem; margin: 0; line-height: 1.2;">
							Danh Sách Sách
						</h2>
					</div>
				</div>
				<div class="col-md-6 text-end">
					<button type="button" class="btn btn-lg" data-bs-toggle="modal" data-bs-target="#addBookModal" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; font-weight: 600; padding: 0.8rem 1.5rem; border-radius: 8px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);">
						<i class="fas fa-plus-circle" style="margin-right: 8px;"></i> Thêm Sách Mới
					</button>
				</div>
			</div>

			<!-- Alert Thành Công -->
			<?php if(isset($_SESSION['book_success'])): ?>
				<div class="alert alert-success" style="border-left: 4px solid #38ef7d; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); animation: slideInAlert 0.3s ease-out;">
					<i class="fas fa-check-circle"></i> <?= $_SESSION['book_success'] ?>
				</div>
				<?php unset($_SESSION['book_success']); ?>
			<?php endif; ?>

			<!-- Alert Lỗi -->
			<?php if(!empty($err)): ?>
				<div class="alert alert-danger" style="border-left: 4px solid #f5576c; background: linear-gradient(135deg, #fff5f5 0%, #ffe0e0 100%); animation: slideInAlert 0.3s ease-out;">
					<i class="fas fa-exclamation-circle"></i> <?= $err ?>
				</div>
			<?php endif; ?>

			<!-- Card Danh Sách Sách -->
			<div class="card shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
				<div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1.3rem 1.5rem; color: white; border-bottom: none;">
					<h5 class="mb-0" style="font-weight: 800; letter-spacing: 0.5px; font-size: 1.2rem; display: flex; align-items: center; gap: 10px;">
						<i class="fas fa-book"></i> Quản Lý Sách
					</h5>
				</div>
				<div class="card-body" style="padding: 1.5rem; overflow-x: auto;">
					<?php if(mysqli_num_rows($result) > 0): ?>
						<table class="table table-hover table-striped mb-0" style="border-collapse: collapse;">
							<colgroup>
								<col width="6%">
								<col width="10%">
								<col width="10%">
								<col width="13%">
								<col width="16%">
								<col width="13%">
								<col width="10%">
								<col width="22%">
							</colgroup>
							<thead>
								<tr style="background: #f8f9fa; border-bottom: 2px solid #e9ecef;">
									<th style="padding: 1rem; font-weight: 600; color: #667eea;">
										<i class="fas fa-barcode"></i> Mã sách
									</th>
									<th style="padding: 1rem; font-weight: 600; color: #667eea;">
										<i class="fas fa-heading"></i> Tiêu Đề
									</th>
									<th style="padding: 1rem; font-weight: 600; color: #667eea;">
										<i class="fas fa-pen"></i> Tác Giả
									</th>
									<th style="padding: 1rem; font-weight: 600; color: #667eea;">
										<i class="fas fa-image"></i> Ảnh
									</th>
									<th style="padding: 1rem; font-weight: 600; color: #667eea;">
										<i class="fas fa-align-left"></i> Mô Tả
									</th>
									<th style="padding: 1rem; font-weight: 600; color: #667eea;">
										<i class="fas fa-dollar-sign"></i> Giá
									</th>

									<th style="padding: 1rem; font-weight: 600; color: #667eea; text-align: center;">
										<i class="fas fa-tools"></i> Hành Động
									</th>
								</tr>
							</thead>
							<tbody>
								<?php while($row = mysqli_fetch_assoc($result)){ ?>
									<tr style="border-bottom: 1px solid #e9ecef; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#f8f9fa';" onmouseout="this.style.backgroundColor='transparent';">
										<td style="padding: 1rem; vertical-align: middle;">
											<a href="book.php?bookisbn=<?php echo $row['book_isbn']; ?>" target="_blank" style="color: #667eea; text-decoration: none; font-weight: 500;">
												<?php echo $row['book_isbn']; ?>
											</a>
										</td>
										<td style="padding: 1rem; vertical-align: middle; font-weight: 500;">
											<?php echo htmlspecialchars($row['book_title']); ?>
										</td>
										<td style="padding: 1rem; vertical-align: middle;">
											<?php echo htmlspecialchars($row['book_author']); ?>
										</td>
										<td style="padding: 1rem; vertical-align: middle; text-align: center;">
											<?php if($row['book_image']): ?>
												<span class="badge bg-success">✓</span>
											<?php else: ?>
												<span class="badge bg-secondary">-</span>
											<?php endif; ?>
										</td>
										<td style="padding: 1rem; vertical-align: top; min-width: 220px;">
											<div style="color: #666; font-size: 0.9rem; line-height: 1.6; max-height: 120px; overflow: auto; word-break: break-word; white-space: normal; text-align: left; padding-right: 6px;">
												<?php echo nl2br(htmlspecialchars($row['book_descr'])); ?>
											</div>
										</td>
										<td style="padding: 1rem; vertical-align: middle; font-weight: 600; color: #38ef7d;">
											<?php echo number_format($row['book_price'], 0, ',', '.'); ?> ₫
										</td>

										<td style="padding: 1rem; vertical-align: middle; text-align: center;">
											<div class="btn-group" role="group">
												<a href="admin_edit.php?bookisbn=<?php echo $row['book_isbn']; ?>" class="btn btn-sm btn-outline-primary" title="Chỉnh Sửa" style="border-radius: 6px 0 0 6px; border: 2px solid #667eea; color: #667eea; font-weight: 600; transition: all 0.3s ease;">
													<i class="fas fa-edit"></i>
												</a>
												<a href="admin_delete.php?bookisbn=<?php echo $row['book_isbn']; ?>" class="btn btn-sm btn-outline-danger" title="Xóa" style="border-radius: 0 6px 6px 0; border: 2px solid #f5576c; color: #f5576c; font-weight: 600; transition: all 0.3s ease; margin-left: -2px;" onclick="if(confirm('Bạn chắc chắn muốn xóa sách này?') === false) event.preventDefault()">
													<i class="fas fa-trash"></i>
												</a>
											</div>
										</td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					<?php else: ?>
						<div class="text-center py-5">
							<i class="fas fa-inbox" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem; display: block;"></i>
							<h5 style="color: #999;">Không có sách nào trong danh sách</h5>
							<p style="color: #bbb;">Hãy thêm sách mới bằng cách nhấn nút "Thêm Sách Mới"</p>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Modal Thêm Sách Mới -->
<div class="modal fade" id="addBookModal" tabindex="-1" aria-labelledby="addBookModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered">
		<div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
			<div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 1.5rem;">
				<h5 class="modal-title" id="addBookModalLabel" style="font-weight: 700;">
					<i class="fas fa-plus-circle"></i> Thêm Sách Mới
				</h5>
				<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body" style="padding: 2rem;">
				<form method="post" action="admin_book.php" enctype="multipart/form-data" id="addBookForm">
					<div class="row">
						<div class="col-md-6">
							<div class="mb-3">
								<label class="form-label" style="font-weight: 600; color: #333; display: flex; align-items: center;">
									<span style="display: inline-block; width: 4px; height: 4px; background: #667eea; border-radius: 50%; margin-right: 8px;"></span>
									Mã sách
										</label>
								<div class="input-group">
									<span class="input-group-text" style="background: transparent; border: 2px solid #e9ecef;">
										<i class="fas fa-barcode" style="color: #667eea;"></i>
									</span>
									<input type="text" name="isbn" class="form-control" style="border: 2px solid #e9ecef; border-left: none;" placeholder="123-456-789">
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="mb-3">
								<label class="form-label" style="font-weight: 600; color: #333; display: flex; align-items: center;">
									<span style="display: inline-block; width: 4px; height: 4px; background: #667eea; border-radius: 50%; margin-right: 8px;"></span>
									Tiêu Đề <span style="color: #f5576c;">*</span>
								</label>
								<div class="input-group">
									<span class="input-group-text" style="background: transparent; border: 2px solid #e9ecef;">
										<i class="fas fa-heading" style="color: #667eea;"></i>
									</span>
									<input type="text" name="title" class="form-control" style="border: 2px solid #e9ecef; border-left: none;" placeholder="Nhập tiêu đề sách" required>
								</div>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-6">
							<div class="mb-3">
								<label class="form-label" style="font-weight: 600; color: #333; display: flex; align-items: center;">
									<span style="display: inline-block; width: 4px; height: 4px; background: #667eea; border-radius: 50%; margin-right: 8px;"></span>
									Tác Giả <span style="color: #f5576c;">*</span>
								</label>
								<div class="input-group">
									<span class="input-group-text" style="background: transparent; border: 2px solid #e9ecef;">
										<i class="fas fa-pen" style="color: #667eea;"></i>
									</span>
									<input type="text" name="author" class="form-control" style="border: 2px solid #e9ecef; border-left: none;" placeholder="Nhập tên tác giả" required>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="mb-3">
								<label class="form-label" style="font-weight: 600; color: #333; display: flex; align-items: center;">
									<span style="display: inline-block; width: 4px; height: 4px; background: #667eea; border-radius: 50%; margin-right: 8px;"></span>
									Giá <span style="color: #f5576c;">*</span>
								</label>
								<div class="input-group">
									<span class="input-group-text" style="background: transparent; border: 2px solid #e9ecef;">
										<i class="fas fa-dollar-sign" style="color: #667eea;"></i>
									</span>
									<input type="text" name="price" class="form-control" style="border: 2px solid #e9ecef; border-left: none;" placeholder="0" required>
								</div>
							</div>
						</div>
					</div>

					<div class="mb-3">
						<label class="form-label" style="font-weight: 600; color: #333; display: flex; align-items: center;">
							<span style="display: inline-block; width: 4px; height: 4px; background: #667eea; border-radius: 50%; margin-right: 8px;"></span>
							Mô Tả
						</label>
						<textarea name="descr" class="form-control" rows="4" style="border: 2px solid #e9ecef; resize: none;" placeholder="Nhập mô tả sách..."></textarea>
					</div>

					<div class="mb-3">
						<label class="form-label" style="font-weight: 600; color: #333; display: flex; align-items: center;">
							<span style="display: inline-block; width: 4px; height: 4px; background: #667eea; border-radius: 50%; margin-right: 8px;"></span>
							Hình Ảnh
						</label>
						<input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" style="border: 2px solid #e9ecef; padding: 0.6rem;" required>
					</div>

					<div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 1rem; gap: 8px;">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: #e9ecef; color: #333; border: none; font-weight: 600;">
							<i class="fas fa-times"></i> Hủy
						</button>
						<button type="submit" name="add" class="btn btn-primary" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; font-weight: 600;">
							<i class="fas fa-save"></i> Lưu Sách
						</button>
					</div>
				</form>
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

<style>
html,body{margin:0!important;padding:0!important;background:#f5f7fb}.legacy-books-layout,#addBookModal{display:none!important}.modern-books-admin{min-height:100vh;width:100vw;background:#f5f7fb}.modern-admin-sidebar{position:fixed;inset:0 auto 0 0;z-index:20;height:100vh}.modern-books-main{max-width:none;width:auto;min-width:0;margin-left:250px;padding:30px 34px 50px}.modern-books-admin{display:block}
.modern-books-admin{display:flex;min-height:calc(100vh - 70px);background:#f5f6f8;color:#20242b;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.modern-admin-sidebar{width:250px;flex:0 0 250px;background:#20242b;color:#fff;padding:24px 14px;display:flex;flex-direction:column}.modern-admin-brand{display:flex;align-items:center;gap:10px;padding:0 10px 24px;border-bottom:1px solid #3b4149}.modern-brand-mark{width:38px;height:38px;border-radius:10px;background:#f0b90b;color:#20242b;display:grid;place-items:center;font-weight:800}.modern-admin-brand strong,.modern-admin-brand small{display:block}.modern-admin-brand strong{font-size:13px}.modern-admin-brand small{font-size:11px;color:#aeb5bf;margin-top:3px}.modern-admin-sidebar nav{padding-top:20px}.modern-admin-sidebar nav a,.modern-logout{display:flex;align-items:center;gap:11px;color:#bcc3cc;text-decoration:none;padding:12px 13px;border-radius:8px;margin-bottom:4px;font-size:13px;transition:.2s}.modern-admin-sidebar nav a i,.modern-logout i{width:17px;text-align:center}.modern-admin-sidebar nav a:hover,.modern-admin-sidebar nav a.active{background:#343a43;color:#ffd45b;box-shadow:inset 3px 0 #f0b90b}.modern-logout{margin-top:auto;border-top:1px solid #3b4149;border-radius:0;padding-top:20px}.modern-books-main{max-width:none;width:auto;margin-left:250px;padding:30px 34px 50px}.modern-books-header,.modern-books-title{display:flex;justify-content:space-between;align-items:center;gap:20px}.modern-books-header{margin-bottom:28px}.modern-eyebrow{font-size:11px;font-weight:800;letter-spacing:.12em;color:#a17c0f}.modern-books-header h1{font-size:28px;margin:5px 0 3px;font-weight:800}.modern-books-header p,.modern-books-title p{margin:0;color:#7c8590;font-size:13px}.modern-admin-account{display:flex;align-items:center;gap:10px;color:#555e69;font-size:13px;font-weight:600}.modern-avatar{display:grid;place-items:center;width:38px;height:38px;border-radius:50%;background:#fff1bd;color:#b17d00}.modern-alert{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px}.modern-alert.success{background:#eaf8f0;color:#217347}.modern-alert.danger{background:#fff0f0;color:#a43d3d}.modern-alert i{margin-right:8px}.modern-books-title{margin-bottom:18px}.modern-books-title h2{font-size:18px;margin:0 0 4px}.modern-primary-btn{display:inline-flex;align-items:center;gap:7px;border:0;background:#f0b90b;color:#20242b;border-radius:8px;padding:11px 16px;font-weight:750;font-size:13px;text-decoration:none;transition:.2s}.modern-primary-btn:hover{background:#dba800;transform:translateY(-1px)}.modern-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px}.modern-stats>div{background:#fff;border:1px solid #e6e9ed;border-radius:10px;padding:16px;display:flex;align-items:center;gap:12px;box-shadow:0 2px 7px rgba(20,30,40,.035)}.modern-stat-icon{width:38px;height:38px;border-radius:9px;display:grid;place-items:center}.modern-stat-icon.yellow{background:#fff4ce;color:#b17d00}.modern-stat-icon.green{background:#e6f7ed;color:#2b9760}.modern-stat-icon.orange{background:#fff1d9;color:#c58214}.modern-stat-icon.red{background:#ffeb;color:#c55252}.modern-stats strong,.modern-stats small{display:block}.modern-stats strong{font-size:19px}.modern-stats small{font-size:11px;color:#818a95;margin-top:3px}.modern-filter{background:#fff;border:1px solid #e6e9ed;border-radius:10px;padding:13px;display:flex;gap:10px;margin-bottom:18px}.modern-search{display:flex;align-items:center;gap:8px;border:1px solid #dfe3e8;border-radius:7px;padding:0 11px;flex:1;min-width:220px}.modern-search i{color:#9aa2ab;font-size:13px}.modern-search input,.modern-filter select{border:0;outline:0;background:#fff;color:#4c5661;font-size:13px;height:36px}.modern-search input{width:100%}.modern-filter select{border:1px solid #dfe3e8;border-radius:7px;padding:0 10px;min-width:150px}.modern-table-card{background:#fff;border:1px solid #e6e9ed;border-radius:10px;overflow:hidden;box-shadow:0 2px 7px rgba(20,30,40,.035)}.modern-table-wrap{overflow-x:auto}.modern-books-table{width:100%;min-width:900px;border-collapse:collapse;font-size:13px}.modern-books-table th{background:#fafbfc;color:#7b8490;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.04em;padding:13px 16px;border-bottom:1px solid #e8ebee;white-space:nowrap}.modern-books-table td{padding:13px 16px;border-bottom:1px solid #f0f2f4;vertical-align:middle;color:#4d5661}.modern-book-row:hover{background:#fffdf5}.modern-book-cell{display:flex;align-items:center;gap:10px;min-width:220px}.modern-book-cell img{width:50px;height:70px;object-fit:cover;border-radius:5px;background:#f0f1f2;flex:0 0 50px}.modern-book-cell strong{display:block;color:#252b33;max-width:170px;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.modern-book-cell small{display:block;color:#9199a3;font-size:11px;margin-top:4px}.modern-isbn{color:#a87800;text-decoration:none;font-size:12px}.modern-price{font-weight:750;color:#a87800;white-space:nowrap}.modern-status,.modern-stock{display:inline-block;padding:5px 8px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap}.modern-status.neutral{background:#e9f7ef;color:#25784c}.modern-stock.muted{background:#f1f3f5;color:#8b949e}.modern-actions{white-space:nowrap}.modern-actions a{display:inline-grid;place-items:center;width:29px;height:29px;border:1px solid #e0e4e8;border-radius:6px;color:#65707b;text-decoration:none;margin-right:4px;transition:.2s}.modern-actions a:hover{background:#fff4ce;border-color:#e2b21b;color:#a87800}.modern-actions a.danger-action:hover{background:#ffeded;border-color:#d95a5a;color:#b93c3c}.modern-empty{text-align:center!important;padding:50px!important;color:#8e97a2!important}.modern-empty i,.modern-empty strong,.modern-empty span{display:block}.modern-empty i{font-size:32px;margin-bottom:10px;color:#c5cbd1}.modern-empty strong{color:#4b545e;margin-bottom:5px}.modern-empty span{font-size:12px}.modern-table-footer{display:flex;justify-content:space-between;padding:13px 16px;color:#89929c;font-size:12px}.legacy-books-layout{display:none}@media(max-width:950px){.modern-admin-sidebar{width:210px;flex-basis:210px}.modern-books-main{padding:24px 20px}.modern-stats{grid-template-columns:repeat(2,1fr)}.modern-books-table{min-width:850px}}@media(max-width:620px){.modern-books-admin{display:block}.modern-admin-sidebar{width:100%;padding:12px;min-height:auto}.modern-admin-brand{padding:3px 8px 12px}.modern-admin-sidebar nav{display:grid;grid-template-columns:repeat(2,1fr);gap:3px;padding-top:10px}.modern-admin-sidebar nav a{margin:0;padding:9px;font-size:12px}.modern-logout{margin-top:8px;padding:10px 8px}.modern-books-main{padding:20px 12px 30px}.modern-books-header{align-items:flex-start;margin-bottom:20px}.modern-books-header h1{font-size:23px}.modern-admin-account span:last-child{display:none}.modern-books-title{align-items:flex-start}.modern-primary-btn{padding:10px 12px}.modern-filter{display:block}.modern-search{margin-bottom:9px}.modern-filter select{width:100%;margin-bottom:8px}.modern-stats{gap:9px}.modern-stats>div{padding:12px 9px;gap:8px}.modern-stat-icon{width:32px;height:32px}.modern-stats strong{font-size:16px}.modern-stats small{font-size:10px}.modern-table-footer{display:block}.modern-table-footer span{display:block;margin-bottom:4px}}

	@keyframes slideInAlert {
		from {
			opacity: 0;
			transform: translateX(-20px);
		}
		to {
			opacity: 1;
			transform: translateX(0);
		}
	}

	.table tbody tr:hover {
		background-color: #f8f9fa !important;
	}

	.btn:hover {
		transform: translateY(-2px);
		box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
	}

	button[data-bs-toggle="modal"]:hover {
		background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
		transform: translateY(-3px);
		box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4) !important;
	}
</style>

<?php
	if(isset($conn)) {mysqli_close($conn);}
	require_once "./template/footer.php";
?>
<style>
/* Prevent the admin shell from overflowing the viewport */
html,body{width:100%;max-width:100%;overflow-x:hidden}.modern-books-admin{display:block!important;width:100%;min-height:100vh;overflow:hidden}.modern-admin-sidebar{position:fixed!important;left:0;top:0;bottom:0;width:250px!important;box-sizing:border-box}.modern-books-main{display:block!important;width:calc(100% - 250px)!important;max-width:none!important;min-width:0!important;margin-left:250px!important;box-sizing:border-box;padding:30px 34px 50px!important;overflow:hidden}.modern-stats{width:100%;box-sizing:border-box}.modern-filter,.modern-table-card{max-width:100%;box-sizing:border-box}.modern-table-wrap{max-width:100%;overflow-x:auto}.modern-books-table{min-width:900px}.modern-books-header,.modern-books-title{max-width:100%;box-sizing:border-box}.site-footer,.site-footer-spacer{display:none!important}body:has(.modern-books-admin)>.site-footer,body:has(.modern-books-admin)>.site-footer-spacer{display:none!important}@media(max-width:950px){.modern-admin-sidebar{width:210px!important}.modern-books-main{width:calc(100% - 210px)!important;margin-left:210px!important;padding:24px 20px!important}}@media(max-width:620px){.modern-books-admin{display:block!important;overflow:visible}.modern-admin-sidebar{position:relative!important;width:100%!important;height:auto!important;min-height:auto}.modern-books-main{width:100%!important;margin-left:0!important;padding:20px 12px 30px!important;overflow:visible}}
</style>

<style>
/* Remove the empty top strip from book management */
body:has(.modern-books-admin) .clear-fix,body:has(.modern-books-admin) .site-footer-spacer,body:has(.modern-books-admin) .pt-5{display:none!important}body:has(.modern-books-admin) .modern-books-admin{margin-top:0!important;padding-top:0!important}.modern-books-main{padding-top:0!important}.modern-books-main .modern-books-header{padding-top:30px!important}
</style>

<style>
.modern-stock.available{background:#e6f7ed;color:#25784c}.modern-stock.low{background:#fff1d9;color:#a55d00}.modern-status.available{background:#e6f7ed;color:#25784c}.modern-status.low{background:#fff1d9;color:#a55d00}.books-pagination{display:flex;justify-content:center;gap:6px;padding:18px 0}.books-pagination a{display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;padding:0 10px;border:1px solid #e2e8f0;border-radius:7px;background:#fff;color:#64748b;text-decoration:none;font-weight:600;font-size:13px}.books-pagination a:hover,.books-pagination a.active{background:#f0b90b;border-color:#f0b90b;color:#20242b}
</style>

<style>
/* Căn lại cụm tìm kiếm và bộ lọc sách */
.modern-filter{display:grid!important;grid-template-columns:minmax(280px,1fr) 270px 230px;align-items:center;gap:14px;padding:18px!important;margin-bottom:18px;border:1px solid #e1e6ec;border-radius:12px;background:#fff;box-sizing:border-box}.modern-search{width:100%;min-width:0;height:54px;box-sizing:border-box;padding:0 16px!important;border:1px solid #d8dfe7;border-radius:10px;background:#fff}.modern-search input,.modern-filter select{height:52px!important;box-sizing:border-box;font-size:16px!important;color:#344054}.modern-search input::placeholder{color:#98a2b3}.modern-filter select{width:100%;min-width:0!important;padding:0 14px!important;border:1px solid #d8dfe7!important;border-radius:10px!important;background:#fff}.modern-search:focus-within,.modern-filter select:focus{border-color:#b17d00;box-shadow:0 0 0 3px rgba(240,185,11,.14)}@media(max-width:900px){.modern-filter{grid-template-columns:minmax(220px,1fr) 1fr 1fr}}@media(max-width:620px){.modern-filter{display:flex!important;flex-direction:column;align-items:stretch;gap:10px;padding:12px!important}.modern-search,.modern-filter select{width:100%;height:48px!important}.modern-search input,.modern-filter select{height:46px!important;font-size:14px!important}}
</style>

<style>
/* Make the book filters compact and prevent overflow */
.modern-filter{display:flex!important;align-items:center;gap:10px;padding:12px!important;overflow:hidden}.modern-search{flex:1 1 0!important;min-width:0!important;width:auto!important;height:42px!important;padding:0 12px!important}.modern-search input{min-width:0!important;width:100%!important;height:40px!important;font-size:14px!important;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.modern-filter select{flex:0 1 220px!important;width:220px!important;min-width:0!important;height:42px!important;padding:0 10px!important;font-size:14px!important;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.modern-filter select:last-child{flex-basis:190px!important;width:190px!important}@media(max-width:760px){.modern-filter{gap:8px}.modern-filter select{width:170px!important;flex-basis:170px!important}.modern-filter select:last-child{width:155px!important;flex-basis:155px!important}}@media(max-width:580px){.modern-filter{display:grid!important;grid-template-columns:1fr 1fr;overflow:visible}.modern-search{grid-column:1/-1;width:100%!important}.modern-filter select,.modern-filter select:last-child{width:100%!important;flex-basis:auto!important}}
</style>
