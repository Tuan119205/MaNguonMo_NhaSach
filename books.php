<?php
  session_start();
  $count = 0;
  // connecto database
  require_once "./functions/database_functions.php";
  $conn = db_connect();

  $searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
  $genreId = isset($_GET['genre']) ? max(0, (int) $_GET['genre']) : 0;
  $maxPrice = isset($_GET['max_price']) ? max(0, (float) $_GET['max_price']) : 0;

  $where = [];
  $params = [];
  $types = '';
  if (!empty($searchTerm)) {
    $where[] = '(b.book_title LIKE ? OR b.book_author LIKE ?)';
    $searchTermLike = '%' . $searchTerm . '%';
    $params[] = $searchTermLike;
    $params[] = $searchTermLike;
    $types .= 'ss';
  }
  if ($genreId > 0) {
    $where[] = 'b.genre_id = ?';
    $params[] = $genreId;
    $types .= 'i';
  }
  if ($maxPrice > 0) {
    $where[] = 'b.book_price <= ?';
    $params[] = $maxPrice;
    $types .= 'd';
  }

  $query = "SELECT b.book_isbn, b.book_image, b.book_title, b.book_author, b.book_price, g.genre_name
            FROM books b LEFT JOIN genres g ON g.genre_id = b.genre_id";
  if (!empty($where)) {
    $query .= ' WHERE ' . implode(' AND ', $where);
  }
  $query .= ' ORDER BY b.book_title ASC';
  $stmt = mysqli_prepare($conn, $query);
  if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
  }
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  if(!$result){
    echo "Can't retrieve data " . mysqli_error($conn);
    exit;
  }

  $title = "List of Books";
  require_once "./template/header.php";
?>
  <div class="book-filter-bar">
    <label for="genreFilter">Lọc theo thể loại:</label>
    <select id="genreFilter" onchange="window.location.href=this.value">
      <option value="books.php<?= !empty($searchTerm) ? '?q=' . urlencode($searchTerm) : '' ?><?= $maxPrice > 0 ? (!empty($searchTerm) ? '&' : '?') . 'max_price=' . urlencode((string)$maxPrice) : '' ?>">Tất cả thể loại</option>
      <?php
        $genreOptions = mysqli_query($conn, "SELECT genre_id, genre_name FROM genres ORDER BY genre_id ASC");
        while ($genreOption = mysqli_fetch_assoc($genreOptions)):
          $genreUrl = 'books.php?genre=' . (int)$genreOption['genre_id'] . (!empty($searchTerm) ? '&q=' . urlencode($searchTerm) : '') . ($maxPrice > 0 ? '&max_price=' . urlencode((string)$maxPrice) : '');
      ?>
        <option value="<?= htmlspecialchars($genreUrl) ?>" <?= $genreId === (int)$genreOption['genre_id'] ? 'selected' : '' ?>><?= htmlspecialchars($genreOption['genre_name']) ?></option>
      <?php endwhile; ?>
    </select>
  </div>


  <?php if (mysqli_num_rows($result) === 0): ?>
    <div class="alert alert-warning text-center">Không tìm thấy sách nào phù hợp với từ khóa "<?php echo htmlspecialchars($searchTerm); ?>".</div>
  <?php else: ?>
    <?php for($i = 0; $i < mysqli_num_rows($result); $i++){ ?>
      <div class="row g-4">
        <?php while($book = mysqli_fetch_assoc($result)){ ?>
          <?php
            $price = isset($book['book_price']) ? (float) $book['book_price'] : 0;
          ?>
          <div class="col-lg-3 col-md-4 col-sm-6 col-xs-12">
            <div class="book-card">
              <a href="book.php?bookisbn=<?php echo $book['book_isbn']; ?>" class="book-card-link text-reset text-decoration-none">
                <div class="book-card-image-wrap">
                  <img class="book-card-image" src="./bootstrap/img/<?php echo rawurlencode(basename($book['book_image'])); ?>" alt="<?php echo htmlspecialchars($book['book_title']); ?>" onerror="this.onerror=null;this.src='./bootstrap/img/default-book.jpg';">
                  <div class="book-card-overlay">
                    <span>Xem chi tiết</span>
                  </div>
                </div>
                <div class="book-card-body">
                  <h5 class="book-card-title"><?= htmlspecialchars($book['book_title']) ?></h5>
                  <?php if (!empty($book['genre_name'])): ?>
                    <span class="book-card-tag"><?php echo htmlspecialchars($book['genre_name']); ?></span>
                  <?php endif; ?>
                  <?php if (!empty($book['book_author'])): ?>
                    <div class="book-card-author"><?php echo htmlspecialchars($book['book_author']); ?></div>
                  <?php endif; ?>
                </div>
              </a>

              <div class="book-card-footer">
                <div class="book-card-price-box">
                    <span class="book-card-new-price single-price"><?php echo number_format($price, 0, ',', '.'); ?>đ</span>
                </div>

                <form method="post" action="cart.php" id="buy-form-<?php echo $book['book_isbn']; ?>" class="book-card-buy-form">
                  <input type="hidden" name="bookisbn" value="<?php echo $book['book_isbn']; ?>">
                </form>
                <button type="button" class="book-card-buy-btn" onclick="if (<?php echo (isset($_SESSION['user']) && $_SESSION['user'] == true) ? 'true' : 'false'; ?>) { document.getElementById('buy-form-<?php echo $book['book_isbn']; ?>').submit(); } else { window.location.href = 'auth.php'; }">
                  Mua
                </button>
              </div>
            </div>
          </div>
        <?php
          $count++;
          if($count >= 4){
              $count = 0;
              break;
            }
          } ?>
      </div>
    <?php
      }
    ?>
  <?php endif; ?>

<style>
  .book-filter-bar { display: flex; align-items: center; justify-content: center; gap: 12px; margin: 18px 0 24px; }
  .book-filter-bar label { margin: 0; font-weight: 700; color: #374151; }
  .book-filter-bar select { min-width: 250px; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 10px; background: #fff; color: #374151; }
  @media (max-width: 576px) { .book-filter-bar { align-items: stretch; flex-direction: column; } .book-filter-bar select { width: 100%; } }
  .book-card {
    display: block;
    background: #fff;
    border: 1px solid rgba(0,0,0,0.06);
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 8px 22px rgba(17, 24, 39, 0.08);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    height: 100%;
  }

  .book-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 32px rgba(17, 24, 39, 0.12);
  }

  .book-card-link {
    display: block;
    text-decoration: none;
  }

  .book-card-image-wrap {
    position: relative;
    overflow: hidden;
    background: #f7f7f7;
    aspect-ratio: 3 / 4;
  }

  .book-card-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.35s ease;
  }

  .book-card:hover .book-card-image {
    transform: scale(1.05);
  }

  .book-card-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.45);
    color: #fff;
    font-weight: 600;
    opacity: 0;
    transition: opacity 0.25s ease;
  }

  .book-card:hover .book-card-overlay {
    opacity: 1;
  }

  .book-card-body {
    padding: 16px 14px 10px;
    display: flex;
    flex-direction: column;
    min-height: 150px;
  }

  .book-card-tag {
    display: inline-block;
    background: #fff3cd;
    color: #8a6400;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 5px 8px;
    border-radius: 999px;
    margin-bottom: 10px;
    letter-spacing: 0.03em;
  }

  .book-card-title {
    margin: 0 0 8px;
    font-size: 1.05rem;
    font-weight: 700;
    line-height: 1.35;
    color: #1f2937;
    min-height: 3em;
  }

  .book-card-author {
    color: #6b7280;
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 10px;
  }

  .book-card-price-box {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    gap: 2px;
    text-align: left;
    min-height: 52px;
    flex: 1;
  }

  .book-card-image-wrap {
    position: relative;
    overflow: hidden;
    background: #f7f7f7;
    aspect-ratio: 3 / 4;
  }

  .book-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 0 14px 16px;
    margin-top: -2px;
  }

  .book-card-buy-form {
    margin: 0;
    display: none;
  }

  .book-card-price-row {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 10px;
    flex-wrap: wrap;
  }

  .book-card-old-price {
    color: #9ca3af;
    text-decoration: line-through;
    font-size: 0.8rem;
    font-weight: 600;
    line-height: 1.2;
  }

  .book-card-new-price {
    color: #d97706;
    font-size: 1.3rem;
    font-weight: 900;
    line-height: 1.2;
  }

  .book-card-new-price.single-price {
    color: #111827;
    font-size: 1.2rem;
    font-weight: 800;
  }

  .book-card-buy-btn {
    border: none;
    background: linear-gradient(135deg, #ff7a18, #ff4d4f);
    color: #fff;
    font-size: 0.95rem;
    font-weight: 700;
    padding: 10px 18px;
    border-radius: 12px;
    min-width: 92px;
    line-height: 1;
    box-shadow: 0 10px 20px rgba(255, 96, 57, 0.25);
    transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
  }

  .book-card-buy-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 24px rgba(255, 96, 57, 0.32);
    filter: brightness(1.05);
  }
</style>
<?php
  if(isset($conn)) { mysqli_close($conn); }
  require_once "./template/footer.php";
?>