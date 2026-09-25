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
	$stockFilter = isset($_GET['stock']) ? $_GET['stock'] : '';
	$stockFilterSql = $stockFilter === 'out' ? ' inventory <= 0' : ($stockFilter === 'low' ? ' inventory > 0 AND inventory <= 10' : '');
	$filterParts = array();
	if ($selectedGenre > 0) $filterParts[] = "genre_id = {$selectedGenre}";
	if ($stockFilterSql !== '') $filterParts[] = $stockFilterSql;
	$genreFilterSql = count($filterParts) > 0 ? ' WHERE ' . implode(' AND ', $filterParts) : '';
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
	require_once "./template/admin_layout.php";
	admin_layout_start('admin_book', 'Quản lý sách', 'KHO SÁCH', 'Quản lý toàn bộ sách trong hệ thống', '');
?>
	<?php if(isset($_SESSION['book_success'])): ?><div class="modern-alert success"><i class="fas fa-check-circle"></i><?= htmlspecialchars($_SESSION['book_success']) ?></div><?php unset($_SESSION['book_success']); endif; ?>
	<?php if(!empty($err)): ?><div class="modern-alert danger"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($err) ?></div><?php endif; ?>
	<section class="modern-books-title"><div><h2>Danh sách</h2><p><?= $totalBooks ?> đầu sách đang được quản lý</p></div><a class="modern-primary-btn" href="admin_add.php"><i class="fas fa-plus"></i> Thêm sách</a></section>
	<?php $totalStock = 0; foreach ($modernResult as $stockBook) { $totalStock += (int)$stockBook['inventory']; } mysqli_data_seek($modernResult, 0); ?>
	<section class="modern-stats"><div><span class="modern-stat-icon yellow"><i class="fas fa-books"></i></span><div><strong><?= mysqli_num_rows($modernResult) ?></strong><small>Tổng sách</small></div></div><div><span class="modern-stat-icon green"><i class="fas fa-store"></i></span><div><strong><?= mysqli_num_rows($modernResult) ?></strong><small>Đang bán</small></div></div><div><span class="modern-stat-icon orange"><i class="fas fa-boxes-stacked"></i></span><div><strong><?= number_format($totalStock) ?></strong><small>Tổng tồn kho</small></div></div><div><span class="modern-stat-icon red"><i class="fas fa-box-open"></i></span><div><strong>0</strong><small>Hết hàng</small></div></div></section>
	<section class="modern-filter"><div class="modern-search"><i class="fas fa-search"></i><input id="bookSearch" type="search" placeholder="Tìm kiếm sách theo tên, mã sách hoặc tác giả..."></div><select id="bookGenre" onchange="window.location.href=this.value"><option value="admin_book.php">Tất cả thể loại</option><?php while($genre = mysqli_fetch_assoc($genreOptions)): ?><option value="admin_book.php?genre=<?= (int)$genre['genre_id'] ?>" <?= $selectedGenre === (int)$genre['genre_id'] ? 'selected' : '' ?>><?= htmlspecialchars($genre['genre_name']) ?></option><?php endwhile; ?></select><select id="bookStatus"><option value="">Tất cả trạng thái</option><option value="available">Đang bán</option><option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Sắp hết</option></select><select id="bookSort"><option value="title">Tên sách A–Z</option><option value="price">Giá cao đến thấp</option></select></section>
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

<style>
html,body{margin:0!important;padding:0!important;background:#f5f7fb}.legacy-books-layout,#addBookModal{display:none!important}.modern-books-admin{min-height:100vh;width:100vw;background:#f5f7fb}.modern-admin-sidebar{position:fixed;inset:0 auto 0 0;z-index:20;height:100vh}.modern-books-main{max-width:none;width:auto;min-width:0;margin-left:250px;padding:30px 34px 50px}.modern-books-admin{display:block}
.modern-books-admin{display:flex;min-height:calc(100vh - 70px);background:#f5f6f8;color:#20242b;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.modern-admin-sidebar{width:250px;flex:0 0 250px;background:#20242b;color:#fff;padding:24px 14px;display:flex;flex-direction:column}.modern-admin-brand{display:flex;align-items:center;gap:10px;padding:0 10px 24px;border-bottom:1px solid #3b4149}.modern-brand-mark{width:38px;height:38px;border-radius:10px;background:#f0b90b;color:#20242b;display:grid;place-items:center;font-weight:800}.modern-admin-brand strong,.modern-admin-brand small{display:block}.modern-brand-mark{display:none}.modern-admin-brand{gap:0}.modern-admin-brand strong{font-size:16px!important}.modern-admin-brand small{font-size:16px!important;margin-top:10px!important}.modern-admin-sidebar nav{padding-top:20px}.modern-admin-sidebar nav a,.modern-logout{display:flex;align-items:center;gap:11px;color:#bcc3cc;text-decoration:none;padding:12px 13px;border-radius:8px;margin-bottom:4px;font-size:13px;transition:.2s}.modern-admin-sidebar nav a i,.modern-logout i{width:17px;text-align:center}.modern-admin-sidebar nav a:hover,.modern-admin-sidebar nav a.active{background:#343a43;color:#ffd45b;box-shadow:inset 3px 0 #f0b90b}.modern-logout{margin-top:auto;border-top:1px solid #3b4149;border-radius:0;padding-top:20px}.modern-books-main{max-width:none;width:auto;margin-left:250px;padding:30px 34px 50px}.modern-books-header,.modern-books-title{display:flex;justify-content:space-between;align-items:center;gap:20px}.modern-books-header{margin-bottom:28px}.modern-eyebrow{font-size:11px;font-weight:800;letter-spacing:.12em;color:#a17c0f}.modern-books-header h1{font-size:28px;margin:5px 0 3px;font-weight:800}.modern-books-header p,.modern-books-title p{margin:0;color:#7c8590;font-size:13px}.modern-admin-account{display:flex;align-items:center;gap:10px;color:#555e69;font-size:13px;font-weight:600}.modern-avatar{display:grid;place-items:center;width:38px;height:38px;border-radius:50%;background:#fff1bd;color:#b17d00}.modern-alert{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px}.modern-alert.success{background:#eaf8f0;color:#217347}.modern-alert.danger{background:#fff0f0;color:#a43d3d}.modern-alert i{margin-right:8px}.modern-books-title{margin-bottom:18px}.modern-books-title h2{font-size:18px;margin:0 0 4px}.modern-primary-btn{display:inline-flex;align-items:center;gap:7px;border:0;background:#f0b90b;color:#20242b;border-radius:8px;padding:11px 16px;font-weight:750;font-size:13px;text-decoration:none;transition:.2s}.modern-primary-btn:hover{background:#dba800;transform:translateY(-1px)}.modern-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px}.modern-stats>div{background:#fff;border:1px solid #e6e9ed;border-radius:10px;padding:16px;display:flex;align-items:center;gap:12px;box-shadow:0 2px 7px rgba(20,30,40,.035)}.modern-stat-icon{width:38px;height:38px;border-radius:9px;display:grid;place-items:center}.modern-stat-icon.yellow{background:#fff4ce;color:#b17d00}.modern-stat-icon.green{background:#e6f7ed;color:#2b9760}.modern-stat-icon.orange{background:#fff1d9;color:#c58214}.modern-stat-icon.red{background:#ffeb;color:#c55252}.modern-stats strong,.modern-stats small{display:block}.modern-stats strong{font-size:19px}.modern-stats small{font-size:11px;color:#818a95;margin-top:3px}.modern-filter{background:#fff;border:1px solid #e6e9ed;border-radius:10px;padding:13px;display:flex;gap:10px;margin-bottom:18px}.modern-search{display:flex;align-items:center;gap:8px;border:1px solid #dfe3e8;border-radius:7px;padding:0 11px;flex:1;min-width:220px}.modern-search i{color:#9aa2ab;font-size:13px}.modern-search input,.modern-filter select{border:0;outline:0;background:#fff;color:#4c5661;font-size:13px;height:36px}.modern-search input{width:100%}.modern-filter select{border:1px solid #dfe3e8;border-radius:7px;padding:0 10px;min-width:150px}.modern-table-card{background:#fff;border:1px solid #e6e9ed;border-radius:10px;overflow:hidden;box-shadow:0 2px 7px rgba(20,30,40,.035)}.modern-table-wrap{overflow-x:auto}.modern-books-table{width:100%;min-width:900px;border-collapse:collapse;font-size:13px}.modern-books-table th{background:#fafbfc;color:#7b8490;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.04em;padding:13px 16px;border-bottom:1px solid #e8ebee;white-space:nowrap}.modern-books-table td{padding:13px 16px;border-bottom:1px solid #f0f2f4;vertical-align:middle;color:#4d5661}.modern-book-row:hover{background:#fffdf5}.modern-book-cell{display:flex;align-items:center;gap:10px;min-width:220px}.modern-book-cell img{width:50px;height:70px;object-fit:cover;border-radius:5px;background:#f0f1f2;flex:0 0 50px}.modern-book-cell strong{display:block;color:#252b33;max-width:170px;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.modern-book-cell small{display:block;color:#9199a3;font-size:11px;margin-top:4px}.modern-isbn{color:#a87800;text-decoration:none;font-size:12px}.modern-price{font-weight:750;color:#a87800;white-space:nowrap}.modern-status,.modern-stock{display:inline-block;padding:5px 8px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap}.modern-status.neutral{background:#e9f7ef;color:#25784c}.modern-stock.muted{background:#f1f3f5;color:#8b949e}.modern-actions{white-space:nowrap}.modern-actions a{display:inline-grid;place-items:center;width:29px;height:29px;border:1px solid #e0e4e8;border-radius:6px;color:#65707b;text-decoration:none;margin-right:4px;transition:.2s}.modern-actions a:hover{background:#fff4ce;border-color:#e2b21b;color:#a87800}.modern-actions a.danger-action:hover{background:#ffeded;border-color:#d95a5a;color:#b93c3c}.modern-empty{text-align:center!important;padding:50px!important;color:#8e97a2!important}.modern-empty i,.modern-empty strong,.modern-empty span{display:block}.modern-empty i{font-size:32px;margin-bottom:10px;color:#c5cbd1}.modern-empty strong{color:#4b545e;margin-bottom:5px}.modern-empty span{font-size:12px}.modern-table-footer{display:flex;justify-content:space-between;padding:13px 16px;color:#89929c;font-size:12px}.legacy-books-layout{display:none}@media(max-width:950px){.modern-admin-sidebar{width:210px;flex-basis:210px}.modern-books-main{padding:24px 20px}.modern-stats{grid-template-columns:repeat(2,1fr)}.modern-books-table{min-width:850px}}@media(max-width:620px){.modern-books-admin{display:block}.modern-admin-sidebar{width:100%;padding:12px;min-height:auto}.modern-admin-brand{padding:3px 8px 12px}.modern-admin-sidebar nav{display:grid;grid-template-columns:repeat(2,1fr);gap:3px;padding-top:10px}.modern-admin-sidebar nav a{margin:0;padding:9px;font-size:12px}.modern-logout{margin-top:8px;padding:10px 8px}.modern-books-main{padding:20px 12px 30px}.modern-books-header{align-items:flex-start;margin-bottom:20px}.modern-books-header h1{font-size:23px}.modern-admin-account span:last-child{display:none}.modern-books-title{align-items:flex-start}.modern-primary-btn{padding:10px 12px}.modern-filter{display:block}.modern-search{margin-bottom:9px}.modern-filter select{width:100%;margin-bottom:8px}.modern-stats{gap:9px}.modern-stats>div{padding:12px 9px;gap:8px}.modern-stat-icon{width:32px;height:32px}.modern-stats strong{font-size:16px}.modern-stats small{font-size:10px}.modern-table-footer{display:block}.modern-table-footer span{display:block;margin-bottom:4px}}

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
