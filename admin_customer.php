<?php
	// Phải gửi header trước mọi output HTML/PHP để trình duyệt giải mã UTF-8.
	header('Content-Type: text/html; charset=UTF-8');
	session_start();
	require_once "./functions/admin.php";
	$title = "Quản Lý Tài Khoản Khách Hàng";
	require_once "./template/header.php";
	require_once "./functions/database_functions.php";
	$conn = db_connect();

	if(isset($_GET['toggle']) && !empty($_GET['toggle'])){
		$userid = intval($_GET['toggle']);
		$status = isset($_GET['status']) ? intval($_GET['status']) : 1;
		$newStatus = $status == 1 ? 0 : 1;
		$query = "UPDATE users SET is_active = '{$newStatus}' WHERE userid = '{$userid}'";
		$result = mysqli_query($conn, $query);
		if($result){
			$_SESSION['customer_success'] = $newStatus == 1 ? "Tài khoản đã được kích hoạt lại." : "Tài khoản đã bị vô hiệu hóa.";
		} else {
			$_SESSION['customer_success'] = "Không thể cập nhật trạng thái tài khoản.";
		}
		header("Location: admin_customer.php");
		exit();
	}

	$customerResult = mysqli_query($conn, "SELECT userid, email, username, fullname, phone, created_at, is_active FROM users ORDER BY userid DESC");
	$customerCount = mysqli_num_rows($customerResult);
?>

<div class="modern-books-admin modern-users-admin">
	<aside class="modern-admin-sidebar">
	<div class="modern-admin-brand"><span class="modern-brand-mark">VL</span><div><strong>Nhà Sách Việt Long</strong><small>Admin Panel</small></div></div>
	<nav><a href="admin_dashboard.php"><i class="fas fa-chart-pie"></i>Dashboard</a><a class="active" href="admin_customer.php"><i class="fas fa-users"></i>Quản lý người dùng</a><a href="admin_book.php"><i class="fas fa-book"></i>Quản lý sách</a><a href="orders.php"><i class="fas fa-shopping-bag"></i>Quản lý đơn hàng</a></nav>
	<a class="modern-logout" href="admin_signout.php"><i class="fas fa-sign-out-alt"></i>Đăng xuất</a>
	</aside>
	<main class="modern-books-main modern-users-main">
	<header class="modern-books-header customer-header">
	<div><span class="customer-eyebrow">QUẢN TRỊ HỆ THỐNG</span><h1><i class="fas fa-users"></i> Quản Lý Tài Khoản Khách Hàng</h1></div>
	<div class="customer-admin"><span class="customer-avatar"><i class="fas fa-user-shield"></i></span><span>Quản trị viên</span><a href="admin_signout.php" title="Đăng xuất"><i class="fas fa-sign-out-alt"></i></a></div>
	</header>
	<div class="customer-toolbar"><div class="customer-search"><i class="fas fa-search"></i><input id="customerSearch" type="search" placeholder="Tìm theo tên, email hoặc số điện thoại..." aria-label="Tìm khách hàng"></div><select id="customerStatus" class="customer-filter" aria-label="Lọc trạng thái"><option value="">Tất cả trạng thái</option><option value="active">Hoạt động</option><option value="inactive">Vô hiệu hóa</option></select><select id="customerSort" class="customer-filter" aria-label="Sắp xếp"><option value="newest">Mới nhất</option><option value="oldest">Cũ nhất</option><option value="name">Tên A–Z</option></select><a href="admin_add.php" class="btn customer-add"><i class="fas fa-plus"></i> Thêm mới</a></div>


			<?php if(isset($_SESSION['customer_success'])): ?>
				<div class="alert alert-success" style="border-left: 4px solid #38ef7d; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); animation: slideInAlert 0.3s ease-out;">
					<i class="fas fa-check-circle"></i> <?= $_SESSION['customer_success'] ?>
				</div>
				<?php unset($_SESSION['customer_success']); ?>
			<?php endif; ?>

				<div class="customer-card">
			<div class="customer-card-title"><div><h2>Danh sách khách hàng</h2><p><?= $customerCount ?> tài khoản trong hệ thống</p></div><i class="fas fa-user-friends"></i></div>
			<div class="customer-table-wrap">
					<?php if(mysqli_num_rows($customerResult) > 0): ?>
						<table class="table table-hover table-striped mb-0" style="border-collapse: collapse;">
							<thead>
								<tr style="background: #f8f9fa; border-bottom: 2px solid #e9ecef;">
									<th style="padding: 1rem; font-weight: 600; color: #667eea;">#</th>
									<th style="padding: 1rem; font-weight: 600; color: #667eea;">Họ Tên</th>
									<th style="padding: 1rem; font-weight: 600; color: #667eea;">Email</th>
									<th style="padding: 1rem; font-weight: 600; color: #667eea;">Tên Đăng Nhập</th>
									<th style="padding: 1rem; font-weight: 600; color: #667eea;">Số Điện Thoại</th>
									<th style="padding: 1rem; font-weight: 600; color: #667eea;">Ngày Tạo</th>
									<th style="padding: 1rem; font-weight: 600; color: #667eea;">Trạng Thái</th>
									<th style="padding: 1rem; font-weight: 600; color: #667eea; text-align: center;">Hành Động</th>
								</tr>
							</thead>
							<tbody>
								<?php while($row = mysqli_fetch_assoc($customerResult)){ ?>
									<tr class="customer-row" data-search="<?= htmlspecialchars(strtolower($row['fullname'].' '.$row['email'].' '.$row['username'].' '.$row['phone'])) ?>" data-status="<?= (int)$row['is_active'] === 1 ? 'active' : 'inactive' ?>" data-date="<?= htmlspecialchars($row['created_at']) ?>" data-name="<?= htmlspecialchars(strtolower($row['fullname'])) ?>" style="border-bottom: 1px solid #e9ecef; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#f8f9fa';" onmouseout="this.style.backgroundColor='transparent';">
										<td style="padding: 1rem; vertical-align: middle; font-weight: 600;">#<?= htmlspecialchars($row['userid']) ?></td>
										<td style="padding: 1rem; vertical-align: middle;"><?= htmlspecialchars($row['fullname']) ?></td>
										<td style="padding: 1rem; vertical-align: middle;"><?= htmlspecialchars($row['email']) ?></td>
										<td style="padding: 1rem; vertical-align: middle;"><?= htmlspecialchars($row['username']) ?></td>
										<td style="padding: 1rem; vertical-align: middle;">
											<?= !empty($row['phone']) ? htmlspecialchars($row['phone']) : '<span class="text-muted">--</span>' ?>
										</td>
										<td style="padding: 1rem; vertical-align: middle;">
											<?= date('d/m/Y', strtotime($row['created_at'])) ?>
										</td>
										<td style="padding: 1rem; vertical-align: middle;">
											<?php if($row['is_active'] == 1): ?>
												<span class="badge bg-success">Hoạt Động</span>
											<?php else: ?>
												<span class="badge bg-secondary">Vô Hiệu Hóa</span>
											<?php endif; ?>
										</td>
										<td style="padding: 1rem; vertical-align: middle; text-align: center;">
											<a href="admin_customer.php?toggle=<?= $row['userid'] ?>&status=<?= $row['is_active'] ?>" class="btn btn-sm <?= $row['is_active'] == 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>" style="border-radius: 8px; font-weight: 600;">
												<?php if($row['is_active'] == 1): ?>
													<i class="fas fa-user-slash"></i> Vô Hiệu Hóa
												<?php else: ?>
													<i class="fas fa-user-check"></i> Kích Hoạt
												<?php endif; ?>
											</a>
										</td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					<?php else: ?>
						<div class="text-center py-5">
							<i class="fas fa-users-slash" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem; display: block;"></i>
							<h5 style="color: #999;">Chưa có khách hàng nào</h5>
						</div>
					<?php endif; ?>
							</div>
						<nav class="customer-pagination" id="customerPagination" aria-label="Phân trang"></nav>
						</div>
						</div>
						</div>

<script>
(function(){
  const search=document.getElementById('customerSearch'),status=document.getElementById('customerStatus'),sort=document.getElementById('customerSort'),body=document.querySelector('.customer-card tbody'),pagination=document.getElementById('customerPagination');
  if(!search||!status||!sort||!body||!pagination)return;
  const pageSize=10; let page=1;
  function render(){
    const term=search.value.toLowerCase().trim(),selected=status.value;
    const rows=[...body.querySelectorAll('.customer-row')].filter(row=>!term||row.dataset.search.includes(term)).filter(row=>!selected||row.dataset.status===selected);
    rows.sort((a,b)=>sort.value==='name'?a.dataset.name.localeCompare(b.dataset.name,'vi'):sort.value==='oldest'?a.dataset.date.localeCompare(b.dataset.date):b.dataset.date.localeCompare(a.dataset.date));
    const pages=Math.max(1,Math.ceil(rows.length/pageSize)); page=Math.min(page,pages);
    document.querySelectorAll('.customer-row').forEach(row=>row.style.display='none');
    rows.slice((page-1)*pageSize,page*pageSize).forEach(row=>row.style.display='');
    pagination.innerHTML='';
    const prev=document.createElement('button');prev.type='button';prev.textContent='Trước';prev.disabled=page===1;prev.onclick=()=>{page--;render()};pagination.appendChild(prev);
    for(let i=1;i<=pages;i++){const button=document.createElement('button');button.type='button';button.textContent=i;button.className=i===page?'active':'';button.onclick=()=>{page=i;render()};pagination.appendChild(button)}
    const next=document.createElement('button');next.type='button';next.textContent='Sau';next.disabled=page===pages;next.onclick=()=>{page++;render()};pagination.appendChild(next);
  }
  [search,status,sort].forEach(control=>control.addEventListener('input',()=>{page=1;render()}));
  render();
})();
</script>
<style>
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

	.btn:hover {
		transform: translateY(-2px);
		box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
	}
</style>

<?php
	if(isset($conn)) { mysqli_close($conn); }
	require_once "./template/footer.php";
?>
<style>
html,body{margin:0!important;padding:0!important;background:#f5f6f8}.modern-users-admin{display:flex;min-height:100vh;width:100vw;background:#f5f6f8;color:#20242b;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.modern-users-admin .modern-admin-sidebar{position:fixed;inset:0 auto 0 0;z-index:20;width:250px;height:100vh;box-sizing:border-box;background:#20242b;padding:24px 14px}.modern-users-main{max-width:none;width:auto;min-width:0;margin-left:250px;padding:30px 34px 50px}.modern-users-main .customer-header{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:28px;padding:0 0 22px;border-bottom:1px solid #e2e8f0}.modern-users-main .customer-eyebrow{font-size:11px;font-weight:800;letter-spacing:.12em;color:#a17c0f}.modern-users-main .customer-header h1{font-size:28px;margin:5px 0 3px;color:#20242b;font-weight:800}.modern-users-main .customer-header h1 i{color:#b17d00;margin-right:8px}.modern-users-main .customer-admin{display:flex;align-items:center;gap:10px;color:#555e69;font-size:13px;font-weight:600}.modern-users-main .customer-avatar{display:grid;place-items:center;width:38px;height:38px;border-radius:50%;background:#fff1bd;color:#b17d00}.modern-users-main .customer-toolbar{display:flex;justify-content:space-between;align-items:center;gap:16px;margin:0 0 18px}.modern-users-main .customer-search{display:flex;align-items:center;gap:10px;width:min(480px,100%);height:40px;padding:0 13px;background:#fff;border:1px solid #e2e8f0;border-radius:8px}.modern-users-main .customer-search i{color:#94a3b8}.modern-users-main .customer-search input{width:100%;border:0;outline:0}.modern-users-main .customer-add{padding:11px 17px;background:#f0b90b;border:0;color:#20242b;border-radius:8px;font-size:13px;font-weight:700}.modern-users-main .customer-add:hover{background:#dca900;color:#20242b}.modern-users-main .customer-card{overflow:hidden;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 2px 5px rgba(15,23,42,.04)}.modern-users-main .customer-card-title{display:flex;justify-content:space-between;align-items:center;padding:20px 22px;border-bottom:1px solid #e2e8f0}.modern-users-main .customer-card-title h2{margin:0 0 4px;font-size:17px}.modern-users-main .customer-card-title p{margin:0;color:#7c8590;font-size:12px}.modern-users-main .customer-card-title>i{color:#b17d00}.modern-users-main .customer-table-wrap{overflow-x:auto;padding:0 18px}.modern-users-main .customer-card table{min-width:900px;margin:0}.modern-users-main .customer-card table th{padding:14px 10px;color:#7c8590;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:11px;white-space:nowrap}.modern-users-main .customer-card table td{padding:15px 10px;color:#334155;vertical-align:middle;font-size:13px}.modern-users-main .customer-card table tbody tr:nth-child(even){background:#fafbfc}.modern-users-main .customer-card table tbody tr:hover{background:#f8f1d9}.modern-users-main .customer-card table td:first-child{font-weight:700;color:#b17d00}.modern-users-main .customer-pagination{display:flex;justify-content:flex-end;gap:5px;padding:18px 0}.modern-users-main .customer-pagination button{padding:7px 11px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;color:#64748b;font-size:12px}.modern-users-main .customer-pagination button.active,.modern-users-main .customer-pagination button:hover:not(:disabled){background:#f0b90b;color:#20242b;border-color:#f0b90b}@media(max-width:700px){.modern-users-admin{display:block}.modern-users-admin .modern-admin-sidebar{position:relative;width:100%;height:auto;min-height:auto;padding:12px}.modern-users-admin .modern-admin-sidebar nav{display:grid;grid-template-columns:1fr 1fr;gap:2px}.modern-users-admin .modern-admin-sidebar nav a{margin:0;padding:9px;font-size:12px}.modern-users-main{margin-left:0;padding:20px 14px 32px}.modern-users-main .customer-header{align-items:flex-start}.modern-users-main .customer-header h1{font-size:22px}.modern-users-main .customer-toolbar{align-items:stretch;flex-direction:column}.modern-users-main .customer-search{width:100%}.modern-users-main .customer-add{text-align:center}}
</style>

<style>
/* Fix sidebar typography and spacing for the customer admin page */
.modern-users-admin .modern-admin-sidebar{display:flex;flex-direction:column;color:#fff}
.modern-users-admin .modern-admin-brand{display:flex;align-items:center;gap:10px;padding:0 10px 24px;border-bottom:1px solid #3b4149;color:#fff}
.modern-users-admin .modern-brand-mark{width:38px;height:38px;border-radius:10px;background:#f0b90b;color:#20242b;display:grid;place-items:center;font-weight:800;flex:0 0 auto}
.modern-users-admin .modern-admin-brand strong,.modern-users-admin .modern-admin-brand small{display:block}
.modern-users-admin .modern-admin-brand strong{font-size:13px;color:#fff;line-height:1.3}
.modern-users-admin .modern-admin-brand small{font-size:11px;color:#aeb5bf;margin-top:3px}
.modern-users-admin .modern-admin-sidebar nav{display:block;padding-top:20px}
.modern-users-admin .modern-admin-sidebar nav a,.modern-users-admin .modern-logout{display:flex;align-items:center;gap:11px;width:auto;box-sizing:border-box;color:#bcc3cc;text-decoration:none;padding:12px 13px;margin:0 0 4px;border-radius:8px;font-size:13px;line-height:1.35;font-weight:400;transition:background .2s,color .2s}
.modern-users-admin .modern-admin-sidebar nav a i,.modern-users-admin .modern-logout i{width:17px;min-width:17px;text-align:center;font-size:14px}
.modern-users-admin .modern-admin-sidebar nav a:hover,.modern-users-admin .modern-admin-sidebar nav a.active{background:#343a43;color:#ffd45b;box-shadow:inset 3px 0 #f0b90b}
.modern-users-admin .modern-logout{margin-top:auto;border-top:1px solid #3b4149;border-radius:0;padding-top:20px;color:#bcc3cc}
.modern-users-admin .modern-logout:hover{color:#ffd45b;background:#343a43}
@media(max-width:700px){.modern-users-admin .modern-admin-sidebar nav a,.modern-users-admin .modern-logout{font-size:12px}.modern-users-admin .modern-admin-sidebar nav a{margin:0;padding:9px}}
</style>

<style>
/* Keep customer admin content inside viewport */
html,body{width:100%;max-width:100%;overflow-x:hidden}.modern-users-admin{display:block!important;width:100%;min-height:100vh;overflow:hidden}.modern-users-admin .modern-admin-sidebar{position:fixed!important;left:0;top:0;bottom:0;width:250px!important;box-sizing:border-box}.modern-users-main{display:block!important;width:calc(100% - 250px)!important;max-width:none!important;min-width:0!important;margin-left:250px!important;box-sizing:border-box;overflow:hidden}.modern-users-main .customer-header,.modern-users-main .customer-toolbar,.modern-users-main .customer-card{max-width:100%;box-sizing:border-box}.modern-users-main .customer-table-wrap{max-width:100%;overflow-x:auto}@media(max-width:700px){.modern-users-admin{display:block!important;overflow:visible}.modern-users-admin .modern-admin-sidebar{position:relative!important;width:100%!important;height:auto!important;min-height:auto}.modern-users-main{width:100%!important;margin-left:0!important;overflow:visible}}
</style>

<style>
/* Remove the empty top strip from customer admin */
body:has(.modern-users-admin) .clear-fix,body:has(.modern-users-admin) .site-footer-spacer,body:has(.modern-users-admin) .pt-5{display:none!important}body:has(.modern-users-admin) .modern-users-admin{margin-top:0!important;padding-top:0!important}.modern-users-main{padding-top:0!important}.modern-users-main .customer-header{padding-top:30px!important}
</style>

<style>
/* Admin pages do not use the public footer */
body:has(.modern-users-admin) .site-footer,body:has(.modern-users-admin) .site-footer-spacer{display:none!important}body:has(.modern-users-admin) #pageContent{margin:0!important;padding:0!important;max-width:none!important}
</style>

<style>
.customer-toolbar{flex-wrap:wrap}.customer-filter{height:40px;padding:0 12px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;color:#475569;font-size:13px;outline:0}.customer-filter:focus{border-color:#b17d00;box-shadow:0 0 0 3px rgba(240,185,11,.14)}.customer-pagination button{cursor:pointer}.customer-pagination button:disabled{cursor:not-allowed}
@media(max-width:900px){.customer-filter{flex:1;min-width:150px}}@media(max-width:700px){.customer-filter{width:100%;flex:initial}.customer-toolbar .customer-add{width:100%}}
</style>
