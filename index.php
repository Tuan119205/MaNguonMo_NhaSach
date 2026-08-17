<?php
  session_start();
  $count = 0;

  $title = "Home";
  require_once "./template/header.php";
  require_once "./functions/database_functions.php";
  $conn = db_connect();
  $row = select4LatestBook($conn);

  // Get the requested book genres for the sidebar.
  $genreQuery = "SELECT genre_id, genre_name FROM genres ORDER BY genre_id ASC";
  $genreResult = mysqli_query($conn, $genreQuery);
  $genres = [];
  if($genreResult) {
    while($genre = mysqli_fetch_assoc($genreResult)) {
      $genres[] = $genre;
    }
  }
  $maxPriceResult = mysqli_query($conn, "SELECT COALESCE(MAX(book_price), 0) AS max_price FROM books");
  $maxPriceRow = $maxPriceResult ? mysqli_fetch_assoc($maxPriceResult) : ['max_price' => 0];
  $maxBookPrice = max(1000, (int) ceil((float) $maxPriceRow['max_price'] / 10000) * 10000);
?>

<!-- Hero Section -->
<?php
  $heroSlides = [];
  if (!empty($row)) {
    foreach (array_slice($row, 0, 4) as $index => $book) {
      if (empty($book) || !isset($book['book_image'])) continue;
      $heroSlides[] = [
        'title' => $index === 0 ? 'Hành Trình Qua Những Trang Vô Tận' : 'Khám Phá Những Cuốn Sách Mới',
        'subtitle' => $index === 0
          ? 'Chào mừng đến với thư viện của chúng tôi, nơi mỗi cuốn sách là cánh cửa dẫn tới một thế giới khác và những cuộc phiêu lưu vô tận.'
          : 'Tận hưởng những đầu sách hot, mới nhất và được yêu thích nhất trong bộ sưu tập của chúng tôi.',
        'button' => 'Mua Ngay',
        'link' => 'books.php',
        'image' => './bootstrap/img/' . $book['book_image'],
        'alt' => htmlspecialchars($book['book_title'] ?? 'Book cover')
      ];
    }
  }

  if (empty($heroSlides)) {
    $heroSlides = [[
      'title' => 'Hành Trình Qua Những Trang Vô Tận',
      'subtitle' => 'Chào mừng đến với thư viện của chúng tôi, nơi mỗi cuốn sách là cánh cửa dẫn tới một thế giới khác và những cuộc phiêu lưu vô tận.',
      'button' => 'Mua Ngay',
      'link' => 'books.php',
      'image' => '',
      'alt' => 'Banner'
    ]];
  }
?>
<div class="hero-banner">
  <div class="hero-slider" aria-label="Banner quảng cáo sách">
    <?php foreach ($heroSlides as $index => $slide): ?>
      <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>">
        <div class="hero-content">
          <h1 class="hero-title"><?php echo htmlspecialchars($slide['title']); ?></h1>
          <p class="hero-subtitle"><?php echo htmlspecialchars($slide['subtitle']); ?></p>
          <a href="<?php echo htmlspecialchars($slide['link']); ?>" class="hero-btn"><?php echo htmlspecialchars($slide['button']); ?></a>
        </div>
        <div class="hero-images">
          <?php if (!empty($slide['image'])): ?>
            <img src="<?php echo htmlspecialchars($slide['image']); ?>" alt="<?php echo $slide['alt']; ?>" class="hero-feature-image">
          <?php else: ?>
            <div class="hero-feature-image hero-feature-placeholder">
              <i class="fas fa-book-open"></i>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="hero-dots" aria-label="Chọn banner">
      <?php foreach ($heroSlides as $index => $slide): ?>
        <button type="button" class="hero-dot <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>" aria-label="Chuyển đến slide <?php echo $index + 1; ?>"></button>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.hero-dot');

    if (!slides.length) return;

    let currentSlide = 0;
    let autoPlayTimer = null;

    function showSlide(index) {
      currentSlide = (index + slides.length) % slides.length;

      slides.forEach((slide, i) => {
        slide.classList.toggle('active', i === currentSlide);
      });

      dots.forEach((dot, i) => {
        dot.classList.toggle('active', i === currentSlide);
      });
    }

    function startAutoPlay() {
      clearInterval(autoPlayTimer);
      autoPlayTimer = setInterval(() => {
        showSlide(currentSlide + 1);
      }, 5000);
    }

    dots.forEach((dot) => {
      dot.addEventListener('click', () => {
        showSlide(Number(dot.dataset.slide));
        startAutoPlay();
      });
    });

    startAutoPlay();
  });
</script>

<!-- Features Section -->
<div class="features-section">
  <div class="feature-card">
    <div class="feature-icon">
      <i class="fas fa-shipping-fast"></i>
    </div>
    <h3>Miễn Phí Vận Chuyển</h3>
    <p>Vận chuyển miễn phí và hoàn trả miễn phí cho tất cả đơn hàng</p>
  </div>
  <div class="feature-card">
    <div class="feature-icon">
      <i class="fas fa-undo"></i>
    </div>
    <h3>Hoàn Trả Trong 30 Ngày</h3>
    <p>Trả lại sản phẩm trong vòng 30 ngày để nhận hoàn tiền đầy đủ</p>
  </div>
  <div class="feature-card">
    <div class="feature-icon">
      <i class="fas fa-lock"></i>
    </div>
    <h3>Thanh Toán An Toàn</h3>
    <p>Trải nghiệm sự yên tâm với hệ thống thanh toán an toàn của chúng tôi</p>
  </div>
  <div class="feature-card">
    <div class="feature-icon">
      <i class="fas fa-headset"></i>
    </div>
    <h3>Hỗ Trợ 24/7</h3>
    <p>Đội hỗ trợ chuyên dụng của chúng tôi luôn sẵn sàng giúp đỡ</p>
  </div>
</div>

<!-- Main Content with Sidebar -->
<div class="homepage-content">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-lg-3 col-md-4">
      <div class="sidebar">
        <h5 class="sidebar-title">Duyệt Danh Mục</h5>
        <ul class="category-list">
          <li><a href="books.php" class="category-link"><i class="fas fa-book"></i> Tất Cả Sách</a></li>
          <?php foreach($genres as $genre): ?>
            <li><a href="books.php?genre=<?php echo $genre['genre_id']; ?>" class="category-link">
              <i class="fas fa-bookmark"></i> <?php echo htmlspecialchars($genre['genre_name']); ?>
            </a></li>
          <?php endforeach; ?>
        </ul>


      </div>
    </div>

    <!-- Main Content Area -->
    <div class="col-lg-9 col-md-8">
      <!-- Best Sellers Section -->
      <div class="best-sellers-section">
        <h2 class="section-title">Sách Bán Chạy Nhất</h2>
        <p class="section-subtitle">Khám Phá Các Cuốn Sách Best Seller: Tìm Kiếm Những Mục Hàng Nóng Nhất Của Bộ Sưu Tập.</p>

        <div class="row">
          <?php foreach($row as $book) {
            if($book === null) continue;
          ?>
            <div class="col-lg-3 col-md-4 col-sm-6 col-xs-12 py-3 mb-4">
              <a href="book.php?bookisbn=<?php echo $book['book_isbn']; ?>" class="book-card text-reset text-decoration-none">
                <div class="book-cover-wrapper">
                  <img class="book-cover" src="./bootstrap/img/<?php echo $book['book_image']; ?>" alt="<?php echo htmlspecialchars($book['book_title']); ?>">
                  <div class="book-overlay">
                    <button class="btn-view">Xem Chi Tiết</button>
                  </div>
                </div>
                <div class="book-info">
                  <h5 class="book-title"><?= htmlspecialchars($book['book_title']) ?></h5>
                </div>
              </a>
            </div>
          <?php } ?>
        </div>
      </div>

      <!-- Load More Section -->
      <div class="load-more-section">
        <a href="books.php" class="btn btn-load-more">Xem Tất Cả Sách</a>
      </div>
    </div>
  </div>
</div>

<style>
  /* Hero Banner */
  .hero-banner {
    margin: -15px 0 60px;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    color: white;
    overflow: hidden;
    border-radius: 0 0 18px 18px;
  }

  .hero-slider {
    position: relative;
    min-height: 480px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 80px 40px;
  }

  .hero-slide {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 40px;
    padding: 80px 40px;
    opacity: 0;
    visibility: hidden;
    transform: translateX(20px);
    transition: opacity 0.6s ease, transform 0.6s ease, visibility 0.6s ease;
    flex-wrap: wrap;
  }

  .hero-slide.active {
    opacity: 1;
    visibility: visible;
    transform: translateX(0);
  }

  .hero-content {
    flex: 1;
    min-width: 300px;
    max-width: 560px;
    z-index: 1;
  }

  .hero-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 20px;
    line-height: 1.2;
    color: #fff;
  }

  .hero-subtitle {
    font-size: 1.1rem;
    margin-bottom: 30px;
    color: #ccc;
    max-width: 500px;
  }

  .hero-btn {
    display: inline-block;
    padding: 12px 40px;
    background: #ffc107;
    color: #000;
    text-decoration: none;
    font-weight: 600;
    border-radius: 4px;
    transition: 0.3s;
  }

  .hero-btn:hover {
    background: #ffb300;
    transform: translateY(-2px);
  }

  .hero-images {
    flex: 1;
    min-width: 300px;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    height: 320px;
    z-index: 1;
  }

  .hero-feature-image {
    width: min(100%, 340px);
    height: 260px;
    object-fit: cover;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
    border: 4px solid rgba(255,255,255,0.2);
    background: #f8f9fa;
  }

  .hero-feature-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    color: #1a1a2e;
    background: linear-gradient(135deg, #fff3bf 0%, #ffe082 100%);
  }

  .hero-dots {
    position: absolute;
    left: 50%;
    bottom: 20px;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 2;
  }

  .hero-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: none;
    background: rgba(255,255,255,0.5);
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 0;
  }

  .hero-dot.active {
    width: 28px;
    border-radius: 999px;
    background: #ffc107;
  }

  /* Features Section */
  .features-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 30px;
    padding: 60px 40px;
    background: #f8f9fa;
    margin-bottom: 60px;
  }

  .feature-card {
    text-align: center;
    padding: 30px;
  }

  .feature-icon {
    font-size: 2.5rem;
    color: #ffc107;
    margin-bottom: 15px;
  }

  .feature-card h3 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 10px;
    color: #222;
  }

  .feature-card p {
    color: #666;
    font-size: 0.95rem;
  }

  /* Sidebar */
  .sidebar {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 30px;
  }

  .sidebar-title {
    font-weight: 700;
    color: #222;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #ddd;
  }

  .category-list {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .category-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    color: #555;
    text-decoration: none;
    border-radius: 4px;
    transition: 0.3s;
    margin-bottom: 8px;
  }

  .category-link:hover {
    background: #e9ecef;
    color: #000;
    padding-left: 20px;
  }

  .sidebar-widget {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
  }

  .widget-title {
    font-weight: 600;
    margin-bottom: 15px;
    color: #222;
  }

  .price-range { padding: 10px 0 2px; }
  .price-slider { width: 100%; margin: 8px 0 12px; accent-color: #1b9a54; cursor: pointer; }
  .price-values { display: flex; justify-content: space-between; align-items: center; gap: 8px; color: #6b7280; font-size: .85rem; }
  .price-values strong { color: #1f2937; font-size: .9rem; }
  .price-filter-button { display: block; margin-top: 14px; padding: 9px 12px; border-radius: 7px; background: #1b9a54; color: #fff; text-align: center; text-decoration: none; font-size: .85rem; font-weight: 600; transition: background .2s ease; }
  .price-filter-button:hover { background: #157a47; color: #fff; }

  /* Best Sellers */
  .best-sellers-section {
    margin-bottom: 50px;
  }

  .section-title {
    font-size: 2rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 10px;
    color: #222;
  }

  .section-subtitle {
    text-align: center;
    color: #666;
    margin-bottom: 40px;
    font-size: 0.95rem;
  }

  .book-card {
    transition: 0.3s;
    display: block;
  }

  .book-cover-wrapper {
    position: relative;
    overflow: hidden;
    background: #f0f0f0;
    aspect-ratio: 3/4;
    border-radius: 4px;
    margin-bottom: 15px;
  }

  .book-cover {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: 0.3s;
  }

  .book-card:hover .book-cover {
    transform: scale(1.05);
  }

  .book-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: 0.3s;
  }

  .book-card:hover .book-overlay {
    opacity: 1;
  }

  .btn-view {
    padding: 10px 25px;
    background: #ffc107;
    border: none;
    color: #000;
    font-weight: 600;
    border-radius: 4px;
    cursor: pointer;
    transition: 0.3s;
  }

  .btn-view:hover {
    background: #ffb300;
    transform: scale(1.05);
  }

  .book-info {
    padding: 10px 0;
  }

  .book-title {
    font-weight: 600;
    color: #222;
    font-size: 1rem;
    line-height: 1.3;
    margin: 0;
  }

  /* Load More Section */
  .load-more-section {
    text-align: center;
    padding: 40px 0;
  }

  .btn-load-more {
    padding: 14px 50px;
    font-size: 1.1rem;
    font-weight: 600;
    background: #1b9a54;
    color: white;
    border: none;
    border-radius: 4px;
    transition: 0.3s;
    text-decoration: none;
    display: inline-block;
  }

  .btn-load-more:hover {
    background: #157a47;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(27, 154, 84, 0.3);
  }

  /* Responsive */
  @media (max-width: 768px) {
    .hero-banner {
      padding: 40px 20px;
      flex-direction: column;
    }

    .hero-title {
      font-size: 2rem;
    }

    .hero-images {
      height: 300px;
    }

    .book-stack {
      width: 200px;
      height: 250px;
    }

    .stack-book {
      width: 80px;
      height: 120px;
    }

    .features-section {
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
      padding: 40px 20px;
    }

    .feature-card {
      padding: 20px;
    }
  }

  @media (max-width: 576px) {
    .hero-banner {
      padding: 30px 15px;
    }

    .hero-title {
      font-size: 1.5rem;
    }

    .hero-subtitle {
      font-size: 1rem;
    }

    .features-section {
      grid-template-columns: 1fr;
      padding: 30px 15px;
    }

    .section-title {
      font-size: 1.5rem;
    }
  }
</style>

<?php
  if(isset($conn)) {mysqli_close($conn);}
  require_once "./template/footer.php";
?>