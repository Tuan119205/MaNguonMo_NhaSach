<?php
session_start();
require_once "./functions/database_functions.php";
require_once "./functions/cart_functions.php";
$conn = db_connect();

$title = "Chương Trình Khuyến Mãi";
require_once "./db_migration.php";
runDatabaseMigrations();
require "./template/header.php";

// Promotions are managed by Admin and read from the database.
$promotions = array();
$promotionResult = mysqli_query($conn, "SELECT * FROM promotions WHERE active = 1 AND expires_at >= CURDATE() ORDER BY id DESC");
if ($promotionResult) {
    while ($promotion = mysqli_fetch_assoc($promotionResult)) {
        $promotions[] = array(
            'code' => $promotion['code'],
            'name' => $promotion['name'],
            'description' => $promotion['min_order'] > 0 ? 'Áp dụng cho đơn hàng từ ' . number_format($promotion['min_order'], 0, ',', '.') . 'đ' : 'Ưu đãi đang được áp dụng trên hệ thống',
            'discount' => (float)$promotion['value'],
            'type' => $promotion['type'],
            'icon' => $promotion['type'] === 'shipping' ? 'fa-truck' : ($promotion['type'] === 'fixed' ? 'fa-money-bill' : 'fa-percent'),
            'color' => $promotion['type'] === 'fixed' ? 'info' : ($promotion['type'] === 'shipping' ? 'success' : 'primary'),
            'expiry' => date('d/m/Y', strtotime($promotion['expires_at'])),
            'badge' => null
        );
    }
}
/* Legacy fallback is intentionally removed: only active database promotions are shown. */
/*
    array(
        'code' => 'SAVE10',
        'name' => 'Giảm 10%',
        'description' => 'Giảm 10% cho tất cả các đơn hàng',
        'discount' => 10,
        'type' => 'percent',
        'icon' => 'fa-percent',
        'color' => 'primary',
        'expiry' => '31/12/2026',
        'badge' => 'HOT'
    ),
    array(
        'code' => 'SAVE20',
        'name' => 'Giảm 20%',
        'description' => 'Giảm 20% cho đơn hàng từ 500K trở lên',
        'discount' => 20,
        'type' => 'percent',
        'icon' => 'fa-star',
        'color' => 'warning',
        'expiry' => '31/12/2026',
        'badge' => 'VIP'
    ),
    array(
        'code' => 'BOOK5OFF',
        'name' => 'Mua 3 Tặng 1',
        'description' => 'Mua 3 cuốn sách được tặng 1 cuốn cùng giá thấp nhất',
        'discount' => 25,
        'type' => 'special',
        'icon' => 'fa-gift',
        'color' => 'danger',
        'expiry' => '15/09/2026',
        'badge' => 'SPECIAL'
    ),
    array(
        'code' => 'FREESHIP',
        'name' => 'Miễn Phí Vận Chuyển',
        'description' => 'Miễn phí vận chuyển cho tất cả đơn hàng',
        'discount' => 0,
        'type' => 'shipping',
        'icon' => 'fa-truck',
        'color' => 'success',
        'expiry' => '30/09/2026',
        'badge' => 'NEW'
    ),
    array(
        'code' => 'FIRST50',
        'name' => 'Khách Hàng Mới',
        'description' => 'Giảm 50.000đ cho đơn hàng đầu tiên',
        'discount' => 50000,
        'type' => 'fixed',
        'icon' => 'fa-heart',
        'color' => 'info',
        'expiry' => '31/10/2026',
        'badge' => null
    ),
    array(
        'code' => 'WEEKEND',
        'name' => 'Cuối Tuần Hợp Lý',
        'description' => 'Giảm 15% mua sách vào thứ 6, 6 và Chủ Nhật',
        'discount' => 15,
        'type' => 'percent',
        'icon' => 'fa-calendar',
        'color' => 'secondary',
        'expiry' => '30/12/2026',
        'badge' => null
    )
); */
?>

<div class="container promotions-page">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fa fa-tag"></i>
            Chương Trình Khuyến Mãi
        </h1>
        <p class="page-subtitle">Khám phá các ưu đãi đặc biệt dành cho bạn</p>
    </div>

    <div class="promotions-banner">
        <div class="banner-content">
            <h2>Flash Sale Tuần Này</h2>
            <p>Nhận voucher độc quyền và tiết kiệm tới 50% cho các sách yêu thích</p>
            <a href="books.php" class="banner-btn">Mua Ngay</a>
        </div>
    </div>

    <div class="promotions-container">
        <div class="promo-filter">
            <button class="filter-btn active" data-filter="all">Tất Cả</button>
            <button class="filter-btn" data-filter="percent">% Giảm Giá</button>
            <button class="filter-btn" data-filter="special">Ưu Đãi Đặc Biệt</button>
            <button class="filter-btn" data-filter="shipping">Vận Chuyển</button>
        </div>

        <div class="promotions-grid">
            <?php foreach($promotions as $promo): ?>
            <div class="promotion-card" data-type="<?php echo $promo['type']; ?>">
                <?php if($promo['badge']): ?>
                <div class="promo-badge <?php echo strtolower($promo['badge']); ?>">
                    <?php echo $promo['badge']; ?>
                </div>
                <?php endif; ?>

                <div class="promo-header bg-<?php echo $promo['color']; ?>">
                    <div class="promo-icon">
                        <i class="fa <?php echo $promo['icon']; ?>"></i>
                    </div>
                    <div class="promo-discount">
                        <?php if($promo['type'] === 'percent'): ?>
                            <span class="discount-value"><?php echo $promo['discount']; ?>%</span>
                        <?php elseif($promo['type'] === 'fixed'): ?>
                            <span class="discount-value"><?php echo number_format($promo['discount']); ?>đ</span>
                        <?php else: ?>
                            <span class="discount-value">Miễn Phí</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="promo-body">
                    <h3 class="promo-name"><?php echo htmlspecialchars($promo['name']); ?></h3>
                    <p class="promo-description"><?php echo htmlspecialchars($promo['description']); ?></p>

                    <div class="promo-code-section">
                        <div class="promo-code-label">Mã Khuyến Mãi:</div>
                        <div class="promo-code-display">
                            <code class="promo-code"><?php echo $promo['code']; ?></code>
                            <button type="button" class="copy-code-btn" data-code="<?php echo $promo['code']; ?>" title="Sao chép">
                                <i class="fa fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <div class="promo-expiry">
                        <i class="fa fa-clock"></i>
                        <span>Hết hạn: <?php echo $promo['expiry']; ?></span>
                    </div>
                </div>

                <div class="promo-footer">
                    <a href="books.php" class="promo-btn">Áp Dụng Ngay</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="promo-info-section">
        <div class="info-container">
            <div class="info-box">
                <i class="fa fa-check-circle"></i>
                <h4>Dễ Dàng Áp Dụng</h4>
                <p>Sao chép mã khuyến mãi và dán vào giỏ hàng khi thanh toán</p>
            </div>
            <div class="info-box">
                <i class="fa fa-layers"></i>
                <h4>Có Thể Kết Hợp</h4>
                <p>Một số mã có thể kết hợp để tiết kiệm tối đa</p>
            </div>
            <div class="info-box">
                <i class="fa fa-bell"></i>
                <h4>Thông Báo Mới</h4>
                <p>Đăng ký để nhận thông báo về các ưu đãi mới nhất</p>
            </div>
        </div>
    </div>

</div>

<style>
.promotions-page {
    padding: 40px 20px;
    min-height: 100vh;
    background: #f8f9fa;
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: #111827;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.page-title i {
    color: #ff6b35;
    font-size: 2.8rem;
}

.page-subtitle {
    font-size: 1.1rem;
    color: #6b7280;
    margin: 0;
}

.promotions-banner {
    background: linear-gradient(135deg, #ff6b35 0%, #ff8a5b 100%);
    border-radius: 16px;
    padding: 40px;
    margin-bottom: 40px;
    color: white;
    text-align: center;
    box-shadow: 0 10px 30px rgba(255, 107, 53, 0.2);
    overflow: hidden;
    position: relative;
}

.promotions-banner::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text x="50" y="50" font-size="80" opacity="0.1" text-anchor="middle">%</text></svg>');
    pointer-events: none;
}

.banner-content {
    position: relative;
    z-index: 1;
}

.promotions-banner h2 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 10px;
}

.promotions-banner p {
    font-size: 1.05rem;
    margin-bottom: 20px;
    opacity: 0.95;
}

.banner-btn {
    display: inline-block;
    background: white;
    color: #ff6b35;
    padding: 12px 30px;
    border-radius: 50px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.3s ease;
}

.banner-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.promotions-container {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.promo-filter {
    display: flex;
    gap: 12px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 10px 20px;
    border: 2px solid #e5e7eb;
    background: white;
    border-radius: 50px;
    cursor: pointer;
    font-weight: 600;
    color: #6b7280;
    transition: all 0.3s ease;
}

.filter-btn:hover,
.filter-btn.active {
    background: #ff6b35;
    color: white;
    border-color: #ff6b35;
}

.promotions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
    margin-bottom: 30px;
}

.promotion-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
    display: flex;
    flex-direction: column;
}

.promotion-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
    border-color: #ff6b35;
}

.promo-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    z-index: 10;
    letter-spacing: 0.5px;
}

.promo-badge.hot {
    background: #fee2e2;
    color: #dc2626;
}

.promo-badge.vip {
    background: #fef3c7;
    color: #d97706;
}

.promo-badge.special {
    background: #f3e8ff;
    color: #7c3aed;
}

.promo-badge.new {
    background: #dcfce7;
    color: #16a34a;
}

.promo-header {
    padding: 30px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: white;
}

.bg-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

.bg-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.bg-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.bg-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.bg-info {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
}

.bg-secondary {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

.promo-icon {
    font-size: 2.5rem;
}

.promo-discount {
    text-align: right;
}

.discount-value {
    font-size: 1.8rem;
    font-weight: 800;
    display: block;
    line-height: 1;
}

.promo-body {
    padding: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.promo-name {
    font-size: 1.2rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 8px;
}

.promo-description {
    font-size: 0.9rem;
    color: #6b7280;
    margin: 0 0 16px;
    flex: 1;
}

.promo-code-section {
    margin: 16px 0;
    padding: 12px;
    background: #f9fafb;
    border-radius: 8px;
}

.promo-code-label {
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 6px;
    letter-spacing: 0.5px;
}

.promo-code-display {
    display: flex;
    align-items: center;
    gap: 8px;
}

.promo-code {
    background: white;
    border: 2px solid #e5e7eb;
    padding: 8px 12px;
    border-radius: 6px;
    font-family: 'Courier New', monospace;
    font-weight: 700;
    color: #ff6b35;
    font-size: 0.95rem;
    flex: 1;
    user-select: all;
}

.copy-code-btn {
    background: #ff6b35;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.copy-code-btn:hover {
    background: #e55a24;
    transform: scale(1.05);
}

.promo-expiry {
    font-size: 0.85rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 12px 0;
}

.promo-footer {
    padding: 16px 20px 20px;
    border-top: 1px solid #f3f4f6;
}

.promo-btn {
    display: block;
    text-align: center;
    background: #ff6b35;
    color: white;
    padding: 12px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 700;
    transition: all 0.3s ease;
}

.promo-btn:hover {
    background: #e55a24;
    color: white;
    text-decoration: none;
}

.promo-info-section {
    margin-top: 50px;
    background: linear-gradient(135deg, #f8f9fa 0%, #eff6ff 100%);
    padding: 40px 20px;
    border-radius: 16px;
}

.info-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
}

.info-box {
    text-align: center;
    padding: 20px;
}

.info-box i {
    font-size: 2.5rem;
    color: #ff6b35;
    margin-bottom: 12px;
    display: block;
}

.info-box h4 {
    font-size: 1.1rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 8px;
}

.info-box p {
    font-size: 0.9rem;
    color: #6b7280;
    margin: 0;
}

@media (max-width: 768px) {
    .page-title {
        font-size: 1.8rem;
    }

    .promotions-banner {
        padding: 30px 20px;
    }

    .promotions-banner h2 {
        font-size: 1.5rem;
    }

    .promotions-grid {
        grid-template-columns: 1fr;
    }

    .promotions-container {
        padding: 20px;
    }

    .promo-filter {
        gap: 8px;
        justify-content: center;
    }

    .filter-btn {
        padding: 8px 16px;
        font-size: 0.9rem;
    }
}
/* User promotions interface */
.promotions-page{max-width:1240px!important;padding:34px 24px 55px!important;background:#f8fafc!important}.promotions-page .page-header{margin-bottom:26px}.promotions-page .page-title{font-size:30px;color:#172033;justify-content:flex-start;margin-bottom:6px}.promotions-page .page-title i{font-size:27px;color:#4f46e5}.promotions-page .page-subtitle{text-align:left;font-size:14px;color:#64748b}.promotions-banner{display:flex;align-items:center;min-height:205px;padding:34px 42px;margin-bottom:24px;border-radius:18px;background:linear-gradient(110deg,#4f46e5,#7c3aed 58%,#a855f7)!important;box-shadow:0 14px 30px rgba(79,70,229,.2);text-align:left}.promotions-banner h2{font-size:27px;margin-bottom:8px}.promotions-banner p{max-width:520px;margin-bottom:18px;font-size:14px}.banner-btn{padding:10px 22px;color:#4f46e5;font-size:13px;border-radius:8px}.promotions-container{padding:22px!important;border:1px solid #e2e8f0;box-shadow:0 5px 16px rgba(15,23,42,.04)}.promo-filter{margin-bottom:22px}.filter-btn{padding:8px 15px;border:1px solid #e2e8f0;font-size:12px;border-radius:8px}.filter-btn:hover,.filter-btn.active{background:#4f46e5;border-color:#4f46e5}.promotions-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.promotion-card{border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 3px 9px rgba(15,23,42,.04)}.promotion-card:hover{border-color:#818cf8;transform:translateY(-4px);box-shadow:0 12px 22px rgba(79,70,229,.12)}.promo-header{padding:23px 18px}.promo-body{padding:17px}.promo-name{font-size:16px}.promo-description{font-size:13px}.promo-code-section{margin:12px 0;padding:10px}.promo-code{padding:7px 9px;font-size:12px;color:#4f46e5}.copy-code-btn,.promo-btn{background:#4f46e5}.copy-code-btn:hover,.promo-btn:hover{background:#4338ca}.promo-footer{padding:13px 17px 17px}.promo-info-section{margin-top:24px;padding:22px;border:1px solid #e2e8f0;background:#fff}.info-container{gap:12px}.info-box{padding:15px}.info-box i{font-size:25px;color:#4f46e5}.info-box h4{font-size:14px}.info-box p{font-size:12px}@media(max-width:950px){.promotions-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.promotions-page{padding:24px 12px 35px!important}.promotions-page .page-title{font-size:24px}.promotions-banner{padding:25px 22px;min-height:180px}.promotions-banner h2{font-size:22px}.promotions-grid{grid-template-columns:1fr}.promotions-container{padding:15px!important}.promo-filter{gap:7px}}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy code functionality
    const copyBtns = document.querySelectorAll('.copy-code-btn');
    copyBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const code = this.dataset.code;
            navigator.clipboard.writeText(code).then(() => {
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="fa fa-check"></i>';
                this.style.background = '#10b981';

                setTimeout(() => {
                    this.innerHTML = originalHTML;
                    this.style.background = '#ff6b35';
                }, 2000);
            });
        });
    });

    // Filter functionality
    const filterBtns = document.querySelectorAll('.filter-btn');
    const promoCards = document.querySelectorAll('.promotion-card');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active from all buttons
            filterBtns.forEach(b => b.classList.remove('active'));
            // Add active to clicked button
            this.classList.add('active');

            const filter = this.dataset.filter;

            // Filter cards
            promoCards.forEach(card => {
                if (filter === 'all') {
                    card.style.display = 'flex';
                } else {
                    const type = card.dataset.type;
                    if (type === filter) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        });
    });
});
</script>

<?php
require_once "./template/footer.php";
?>
