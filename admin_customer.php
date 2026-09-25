<?php
	// Phải gửi header trước mọi output HTML/PHP để trình duyệt giải mã UTF-8.
	header('Content-Type: text/html; charset=UTF-8');
	session_start();
	require_once "./functions/admin.php";
	$title = "Quản Lý Tài Khoản Khách Hàng";
	require_once "./functions/database_functions.php";
	$conn = db_connect();

	if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle']) && (int)$_POST['toggle'] > 0){
	$userid = (int)$_GET['toggle'];
	$currentStmt = mysqli_prepare($conn, "SELECT is_active FROM users WHERE userid = ? LIMIT 1");
	mysqli_stmt_bind_param($currentStmt, 'i', $userid);
	mysqli_stmt_execute($currentStmt);
	$currentResult = mysqli_stmt_get_result($currentStmt);
	$currentUser = $currentResult ? mysqli_fetch_assoc($currentResult) : null;
	if (!$currentUser) {
	$_SESSION['customer_error'] = "Không tìm thấy tài khoản cần cập nhật.";
	} else {
	$newStatus = ((int)$currentUser['is_active'] === 1) ? 0 : 1;
	$stmt = mysqli_prepare($conn, "UPDATE users SET is_active = ? WHERE userid = ?");
	mysqli_stmt_bind_param($stmt, 'ii', $newStatus, $userid);
	if(mysqli_stmt_execute($stmt)){
	$_SESSION['customer_success'] = $newStatus === 1 ? "Tài khoản đã được kích hoạt." : "Tài khoản đã bị vô hiệu hóa. Người dùng sẽ không thể đăng nhập.";
	} else {
	$_SESSION['customer_error'] = "Không thể cập nhật trạng thái tài khoản: " . mysqli_error($conn);
	}
	}
	header("Location: admin_customer.php");
	exit();
	}

	if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && (int)$_POST['delete'] > 0){
	$userid = (int)$_POST['delete'];
	$orderCheck = mysqli_prepare($conn, "SELECT 1 FROM orders WHERE customerid = ? LIMIT 1");
	mysqli_stmt_bind_param($orderCheck, 'i', $userid);
	mysqli_stmt_execute($orderCheck);
	if (mysqli_stmt_get_result($orderCheck) && mysqli_num_rows(mysqli_stmt_get_result($orderCheck)) > 0) {
	$_SESSION['customer_error'] = "Không thể xóa người dùng đã có lịch sử đơn hàng. Hãy vô hiệu hóa tài khoản.";
	header("Location: admin_customer.php");
	exit();
	}
	$stmt = mysqli_prepare($conn, "DELETE FROM users WHERE userid = ?");
	mysqli_stmt_bind_param($stmt, 'i', $userid);
	if(mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) === 1){
	$_SESSION['customer_success'] = "Đã xóa người dùng thành công.";
	} else {
	$_SESSION['customer_error'] = "Không thể xóa người dùng. Người dùng có thể đang liên kết với dữ liệu khác.";
	}
		header("Location: admin_customer.php");
		exit();
	}

	require_once "./template/header.php";
	require_once "./template/admin_layout.php";
	$customerResult = mysqli_query($conn, "SELECT userid, email, username, fullname, phone, created_at, is_active FROM users ORDER BY userid DESC");
	$customerCount = mysqli_num_rows($customerResult);
	admin_layout_start('admin_customer', 'Quản Lý Khách Hàng', 'QUẢN TRỊ HỆ THỐNG', '', '');
?>
	<div class="customer-toolbar"><div class="customer-search"><i class="fas fa-search"></i><input id="customerSearch" type="search" placeholder="Tìm theo tên, email hoặc số điện thoại..." aria-label="Tìm khách hàng"></div><select id="customerStatus" class="customer-filter" aria-label="Lọc trạng thái"><option value="">Tất cả trạng thái</option><option value="active">Hoạt động</option><option value="inactive">Vô hiệu hóa</option></select><select id="customerSort" class="customer-filter" aria-label="Sắp xếp"><option value="newest">Mới nhất</option><option value="oldest">Cũ nhất</option><option value="name">Tên A–Z</option></select><a href="admin_customer_add.php" class="btn customer-add"><i class="fas fa-plus"></i> Thêm khách hàng</a></div>


				<?php if(isset($_SESSION['customer_success'])): ?>
			<div class="alert alert-success" style="border-left: 4px solid #38ef7d; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); animation: slideInAlert 0.3s ease-out;">
			<i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['customer_success']) ?>
			</div>
			<?php unset($_SESSION['customer_success']); ?>
			<?php endif; ?>
			<?php if(isset($_SESSION['customer_error'])): ?>
			<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['customer_error']) ?></div>
			<?php unset($_SESSION['customer_error']); ?>
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
												<form method="post" action="admin_customer.php" style="display:inline;"><input type="hidden" name="toggle" value="<?= (int)$row['userid'] ?>"><button type="submit" class="btn btn-sm <?= $row['is_active'] == 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>" style="border-radius: 8px; font-weight: 600;">
											<?php if($row['is_active'] == 1): ?>
											<i class="fas fa-user-slash"></i> Vô hiệu hóa
											<?php else: ?>
											<i class="fas fa-user-check"></i> Kích hoạt
											<?php endif; ?>
												</button></form>

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
							<div id="customerPagination" class="customer-pagination" aria-label="Phân trang người dùng"></div>

							</div>
							</div>
							</div>

<script>
(function(){
  const search=document.getElementById('customerSearch'),status=document.getElementById('customerStatus'),sort=document.getElementById('customerSort'),body=document.querySelector('.customer-card tbody');
  if(!search||!status||!sort||!body)return;
  const pageSize=10; let page=1;
  function render(){
    const term=search.value.toLowerCase().trim(),selected=status.value;
    const rows=[...body.querySelectorAll('.customer-row')].filter(row=>!term||row.dataset.search.includes(term)).filter(row=>!selected||row.dataset.status===selected);
    rows.sort((a,b)=>sort.value==='name'?a.dataset.name.localeCompare(b.dataset.name,'vi'):sort.value==='oldest'?a.dataset.date.localeCompare(b.dataset.date):b.dataset.date.localeCompare(a.dataset.date));
    const pages=Math.max(1,Math.ceil(rows.length/pageSize)); page=Math.min(page,pages);
    document.querySelectorAll('.customer-row').forEach(row=>row.style.display='none');
    rows.slice((page-1)*pageSize,page*pageSize).forEach(row=>row.style.display='');
    const pagination=document.getElementById('customerPagination');
    if(pagination){
      pagination.innerHTML='';
      if(pages>1){
        for(let number=1;number<=pages;number++){
          const button=document.createElement('button');
          button.type='button'; button.textContent=number;
          button.className=number===page?'active':'';
          button.addEventListener('click',()=>{page=number;render();});
          pagination.appendChild(button);
        }
      }
    }
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

<?php admin_layout_end(); ?>
<?php
	if(isset($conn)) { mysqli_close($conn); }
	require_once "./template/footer.php";
?>
tomer-toolbar{align-items:stretch;flex-direction:column}.modern-users-main .customer-search{width:100%}.modern-users-main .customer-add{text-align:center}}
</style>

<style>
/* Fix sidebar typography and spacing for the customer admin page */
.modern-users-admin .modern-admin-sidebar{display:flex;flex-direction:column;color:#fff}
.modern-users-admin .modern-admin-brand{display:flex;align-items:center;gap:10px;padding:0 10px 24px;border-bottom:1px solid #3b4149;color:#fff}.modern-users-admin .modern-brand-mark{width:38px;height:38px;border-radius:10px;background:#f0b90b;color:#20242b;display:grid;place-items:center;font-weight:800;flex:0 0 auto}.modern-users-admin .modern-admin-brand strong,.modern-users-admin .modern-admin-brand small{display:block}
.modern-users-admin .modern-admin-brand strong{font-size:16px;color:#fff;line-height:1.3}.modern-users-admin .modern-admin-brand small{font-size:16px;color:#aeb5bf;margin-top:10px}
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
.customer-toolbar{flex-wrap:wrap}.customer-filter{height:40px;padding:0 12px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;color:#475569;font-size:13px;outline:0}.customer-filter:focus{border-color:#b17d00;box-shadow:0 0 0 3px rgba(240,185,11,.14)}.customer-pagination{display:flex;justify-content:center;gap:6px;padding:18px 0 4px}.customer-pagination button{cursor:pointer;min-width:36px;height:36px;padding:0 10px;border:1px solid #e2e8f0;border-radius:7px;background:#fff;color:#64748b;font-size:13px;font-weight:600}.customer-pagination button:hover,.customer-pagination button.active{background:#f0b90b;border-color:#f0b90b;color:#20242b}.customer-pagination button:disabled{cursor:not-allowed}
@media(max-width:900px){.customer-filter{flex:1;min-width:150px}}@media(max-width:700px){.customer-filter{width:100%;flex:initial}.customer-toolbar .customer-add{width:100%}}
</style>
<style>
/* Nhận diện sidebar Admin đồng bộ */
.admin-brand,.modern-admin-brand,.promo-admin-brand,.report-brand{display:flex!important;align-items:center;gap:10px!important;padding:0 10px 20px!important;border-bottom:1px solid #3b4149!important;min-width:0}.admin-brand>div,.modern-admin-brand>div,.promo-admin-brand>div,.report-brand>div{min-width:0;flex:1}
.brand-mark,.modern-brand-mark,.promo-brand-mark,.report-brand-mark{width:38px!important;height:38px!important;min-width:38px;border-radius:10px!important;background:#f0b90b!important;color:#20242b!important;display:grid!important;place-items:center;font-size:18px;font-weight:800}
.admin-brand strong,.modern-admin-brand strong,.promo-admin-brand strong,.report-brand b{display:block!important;color:#fff!important;font-size:15px!important;line-height:1.25!important;white-space:nowrap}.admin-brand small,.modern-admin-brand small,.promo-admin-brand small,.report-brand small{display:block!important;color:#aeb5bf!important;font-size:12px!important;line-height:1.25!important;margin-top:4px!important;white-space:nowrap}
@media(max-width:650px){.admin-brand,.modern-admin-brand,.promo-admin-brand,.report-brand{padding:4px 8px 14px!important;gap:12px!important}.brand-mark,.modern-brand-mark,.promo-brand-mark,.report-brand-mark{width:46px!important;height:46px!important;min-width:46px;border-radius:13px!important;font-size:22px}.admin-brand strong,.modern-admin-brand strong,.promo-admin-brand strong,.report-brand b{font-size:16px!important}.admin-brand small,.modern-admin-brand small,.promo-admin-brand small,.report-brand small{font-size:13px!important;margin-top:5px!important}}
</style>
