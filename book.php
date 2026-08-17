<?php
  session_start();
  $book_isbn = $_GET['bookisbn'];
  // connecto database
  require_once "./functions/database_functions.php";
  $conn = db_connect();

  $book_isbn = mysqli_real_escape_string($conn, $book_isbn);
  $query = "SELECT b.*, g.genre_name FROM books b LEFT JOIN genres g ON g.genre_id = b.genre_id WHERE b.book_isbn = '$book_isbn'";
  $result = mysqli_query($conn, $query);
  if(!$result){
    echo "Can't retrieve data " . mysqli_error($conn);
    exit;
  }

  $row = mysqli_fetch_assoc($result);
  if(!$row){
    echo "Empty book";
    exit;
  }

  $title = $row['book_title'];
  require "./template/header.php";
?>
      <!-- Example row of columns -->
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="books.php" class="text-decoration-none text-muted fw-light">Danh sách</a></li>
          <li class="breadcrumb-item active" aria-current="page"><?php echo $row['book_title']; ?></li>
        </ol>
      </nav>
      <div class="row">
        <div class="col-md-3 text-center book-item">
          <div class="img-holder overflow-hidden">
          <img class="img-top" src="./bootstrap/img/<?php echo rawurlencode(basename($row['book_image'])); ?>" alt="<?php echo htmlspecialchars($row['book_title']); ?>" onerror="this.onerror=null;this.src='./bootstrap/img/default-book.jpg';">
          </div>
        </div>
        <div class="col-md-9">
          <div class="card rounded-0 shadow">
            <div class="card-body">
              <div class="container-fluid">
                <h4><?= $row['book_title'] ?></h4>
                <hr>
                  <?php if (!empty($row['book_descr'])): ?>
                    <p class="book-description"><?php echo nl2br(htmlspecialchars($row['book_descr'])); ?></p>
                  <?php endif; ?>
                  <h4>Thông tin sách</h4>
                  <table class="table book-details-table">
                    <tr>
                      <td>Tác giả</td>
                      <td><?php echo htmlspecialchars($row['book_author'] ?? 'Chưa cập nhật'); ?></td>
                    </tr>
                    <tr>
                      <td>Giá bán</td>
                      <td><?php echo number_format((float)($row['book_price'] ?? 0), 0, ',', '.'); ?> ₫</td>
                    </tr>
                    <?php if (!empty($row['genre_name'])): ?>
                    <tr>
                      <td>Thể loại</td>
                      <td><?php echo htmlspecialchars($row['genre_name']); ?></td>
                    </tr>
                    <?php endif; ?>
                  </table>
              </div>
            </div>
          </div>
       	</div>
      </div>
<?php
  require "./template/footer.php";
?>