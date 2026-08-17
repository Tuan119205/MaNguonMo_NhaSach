<?php
  header('Content-Type: text/html; charset=utf-8');
  session_start();

  if (!isset($_SESSION['user']) || $_SESSION['user'] !== true) {
    header('Location: auth.php');
    exit;
  }

  require_once "./functions/database_functions.php";
  $conn = db_connect();
  $title = "Quản lý giao hàng";

  $userid = isset($_SESSION['userid']) ? intval($_SESSION['userid']) : 0;
  $success = '';
  $error = '';
  $addresses = array();

  // Ensure proper table structure
  $checkTableQuery = "SELECT 1 FROM information_schema.TABLES WHERE TABLE_NAME='user_addresses' AND TABLE_SCHEMA=DATABASE()";
  $tableCheckResult = mysqli_query($conn, $checkTableQuery);
  
  if (!$tableCheckResult || mysqli_num_rows($tableCheckResult) === 0) {
    // Table doesn't exist, create it
    $createTableQuery = "CREATE TABLE user_addresses (
      address_id INT AUTO_INCREMENT PRIMARY KEY,
      userid INT NOT NULL,
      full_name VARCHAR(100) NOT NULL,
      phone VARCHAR(20) NOT NULL,
      street_address VARCHAR(255) NOT NULL,
      city VARCHAR(50) NOT NULL,
      postal_code VARCHAR(20) NOT NULL,
      country VARCHAR(50) NOT NULL DEFAULT 'Việt Nam',
      is_default BOOLEAN DEFAULT FALSE,
      payment_method ENUM('cod', 'transfer') DEFAULT 'cod',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      KEY `userid_idx` (userid)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    if (!mysqli_query($conn, $createTableQuery)) {
      $error = 'Lỗi tạo bảng dữ liệu: ' . htmlspecialchars(mysqli_error($conn));
    }
  } else {
    // Table exists, verify columns exist
    $checkColumnsQuery = "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_NAME='user_addresses' AND TABLE_SCHEMA=DATABASE()";
    $columnsResult = mysqli_query($conn, $checkColumnsQuery);
    $existingColumns = array();
    
    if ($columnsResult) {
      while ($col = mysqli_fetch_assoc($columnsResult)) {
        $existingColumns[] = $col['COLUMN_NAME'];
      }
    }
    
    // Check if critical columns exist
    $requiredColumns = array('address_id', 'userid', 'full_name', 'phone', 'street_address', 'city', 'postal_code');
    $missingColumns = array_diff($requiredColumns, $existingColumns);
    
    if (!empty($missingColumns)) {
      // Drop and recreate table if columns are missing
      mysqli_query($conn, "DROP TABLE IF EXISTS user_addresses");
      
      $createTableQuery = "CREATE TABLE user_addresses (
        address_id INT AUTO_INCREMENT PRIMARY KEY,
        userid INT NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        street_address VARCHAR(255) NOT NULL,
        city VARCHAR(50) NOT NULL,
        postal_code VARCHAR(20) NOT NULL,
        country VARCHAR(50) NOT NULL DEFAULT 'Việt Nam',
        is_default BOOLEAN DEFAULT FALSE,
        payment_method ENUM('cod', 'transfer') DEFAULT 'cod',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY `userid_idx` (userid)
      ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
      
      if (!mysqli_query($conn, $createTableQuery)) {
        $error = 'Lỗi cấu hình bảng dữ liệu: ' . htmlspecialchars(mysqli_error($conn));
      }
    }
  }

  // Get all addresses
  $getAddressesQuery = "SELECT * FROM user_addresses WHERE userid = $userid ORDER BY is_default DESC, created_at DESC";
  $addressesResult = mysqli_query($conn, $getAddressesQuery);
  if ($addressesResult) {
    while ($addr = mysqli_fetch_assoc($addressesResult)) {
      $addresses[] = $addr;
    }
  }

  // Handle delete address
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_address'])) {
    $address_id = intval($_POST['address_id']);
    $deleteQuery = "DELETE FROM user_addresses WHERE address_id = $address_id AND userid = $userid";
    if (mysqli_query($conn, $deleteQuery)) {
      $success = 'Xóa địa chỉ thành công.';
      header("Refresh:1; url=delivery_management.php");
    } else {
      $error = 'Xóa địa chỉ thất bại.';
    }
  }

  // Handle add/edit address
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_address'])) {
    $address_id = isset($_POST['address_id']) && !empty($_POST['address_id']) ? intval($_POST['address_id']) : 0;
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $street_address = trim($_POST['street_address']);
    $city = trim($_POST['city']);
    $postal_code = trim($_POST['postal_code']);
    $country = trim($_POST['country']) ?: 'Việt Nam';
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    $payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : 'cod';

    if ($full_name === '' || $phone === '' || $street_address === '' || $city === '') {
      $error = 'Tất cả các trường không được để trống.';
    } else {
      $full_name = mysqli_real_escape_string($conn, $full_name);
      $phone = mysqli_real_escape_string($conn, $phone);
      $street_address = mysqli_real_escape_string($conn, $street_address);
      $city = mysqli_real_escape_string($conn, $city);
      $postal_code = mysqli_real_escape_string($conn, $postal_code);
      $country = mysqli_real_escape_string($conn, $country);

      if ($address_id > 0) {
        // Update existing address
        $updateQuery = "UPDATE user_addresses SET 
          full_name = '$full_name',
          phone = '$phone',
          street_address = '$street_address',
          city = '$city',
          postal_code = '$postal_code',
          country = '$country',
          payment_method = '$payment_method',
          is_default = $is_default
          WHERE address_id = $address_id AND userid = $userid";
        if (mysqli_query($conn, $updateQuery)) {
          $success = 'Cập nhật địa chỉ thành công.';
        } else {
          $error = 'Cập nhật địa chỉ thất bại: ' . htmlspecialchars(mysqli_error($conn));
        }
      } else {
        // Insert new address
        $insertQuery = "INSERT INTO user_addresses 
          (userid, full_name, phone, street_address, city, postal_code, country, payment_method, is_default)
          VALUES ($userid, '$full_name', '$phone', '$street_address', '$city', '$postal_code', '$country', '$payment_method', $is_default)";
        if (mysqli_query($conn, $insertQuery)) {
          $success = 'Thêm địa chỉ thành công.';
        } else {
          $error = 'Thêm địa chỉ thất bại: ' . htmlspecialchars(mysqli_error($conn));
        }
      }
    }
  }

  // Get address for edit
  $editAddress = null;
  if (isset($_GET['edit'])) {
    $address_id = intval($_GET['edit']);
    $editQuery = "SELECT * FROM user_addresses WHERE address_id = $address_id AND userid = $userid LIMIT 1";
    $editResult = mysqli_query($conn, $editQuery);
    if ($editResult && mysqli_num_rows($editResult) > 0) {
      $editAddress = mysqli_fetch_assoc($editResult);
    }
  }

  require_once "./template/header.php";
?>

<div class="container py-5">
  <div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-warning bg-gradient">
          <h3 class="mb-0"><i class="fa fa-map-marker-alt"></i> Quản Lý Giao Hàng</h3>
        </div>
        <div class="card-body p-4">
          <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <?php echo htmlspecialchars($success); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?php echo htmlspecialchars($error); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <div class="mb-4">
            <h5 class="mb-3">
              <i class="fa fa-plus-circle"></i>
              <?php echo $editAddress ? 'Chỉnh Sửa Địa Chỉ' : 'Thêm Địa Chỉ Mới'; ?>
            </h5>
            <form method="post" action="delivery_management.php" class="bg-light p-4 rounded">
              <?php if ($editAddress): ?>
                <input type="hidden" name="address_id" value="<?php echo $editAddress['address_id']; ?>">
              <?php endif; ?>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="full_name" class="form-label">Họ Tên *</label>
                  <input type="text" name="full_name" id="full_name" class="form-control" 
                    value="<?php echo $editAddress ? htmlspecialchars($editAddress['full_name']) : ''; ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                  <label for="phone" class="form-label">Số Điện Thoại *</label>
                  <input type="text" name="phone" id="phone" class="form-control" 
                    value="<?php echo $editAddress ? htmlspecialchars($editAddress['phone']) : ''; ?>" required>
                </div>
              </div>

              <div class="mb-3">
                <label for="street_address" class="form-label">Địa Chỉ Chi Tiết *</label>
                <input type="text" name="street_address" id="street_address" class="form-control" 
                  placeholder="Ví dụ: Số 123, Đường A, Quận B"
                  value="<?php echo $editAddress ? htmlspecialchars($editAddress['street_address']) : ''; ?>" required>
              </div>

              <div class="row">
                <div class="col-md-4 mb-3">
                  <label for="city" class="form-label">Thành Phố/Tỉnh *</label>
                  <input type="text" name="city" id="city" class="form-control" 
                    value="<?php echo $editAddress ? htmlspecialchars($editAddress['city']) : ''; ?>" required>
                </div>

                <div class="col-md-4 mb-3">
                  <label for="postal_code" class="form-label">Mã Bưu Chính *</label>
                  <input type="text" name="postal_code" id="postal_code" class="form-control" 
                    value="<?php echo $editAddress ? htmlspecialchars($editAddress['postal_code']) : ''; ?>" required>
                </div>

                <div class="col-md-4 mb-3">
                  <label for="country" class="form-label">Quốc Gia *</label>
                  <input type="text" name="country" id="country" class="form-control" 
                    value="<?php echo $editAddress ? htmlspecialchars($editAddress['country']) : 'Việt Nam'; ?>" required>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="payment_method" class="form-label">Phương Thức Thanh Toán *</label>
                  <select name="payment_method" id="payment_method" class="form-select" required>
                    <option value="cod" <?php echo (!$editAddress || $editAddress['payment_method'] === 'cod') ? 'selected' : ''; ?>>
                      Thanh Toán Khi Nhận Hàng (COD)
                    </option>
                    <option value="transfer" <?php echo ($editAddress && $editAddress['payment_method'] === 'transfer') ? 'selected' : ''; ?>>
                      Chuyển Khoản Ngân Hàng
                    </option>
                  </select>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label d-block">Tùy Chọn</label>
                  <div class="form-check">
                    <input type="checkbox" name="is_default" id="is_default" class="form-check-input" 
                      value="1" <?php echo ($editAddress && $editAddress['is_default']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_default">
                      Đặt làm địa chỉ mặc định
                    </label>
                  </div>
                </div>
              </div>

              <div class="d-grid gap-2 d-md-flex gap-md-2">
                <button type="submit" name="save_address" class="btn btn-warning btn-lg">
                  <i class="fa fa-save"></i> <?php echo $editAddress ? 'Cập Nhật' : 'Lưu Địa Chỉ'; ?>
                </button>
                <?php if ($editAddress): ?>
                  <a href="delivery_management.php" class="btn btn-outline-secondary btn-lg">
                    <i class="fa fa-times"></i> Hủy
                  </a>
                <?php endif; ?>
              </div>
            </form>
          </div>

          <hr>

          <div>
            <h5 class="mb-3">
              <i class="fa fa-list"></i>
              Danh Sách Địa Chỉ (<?php echo count($addresses); ?>)
            </h5>

            <?php if (count($addresses) > 0): ?>
              <div class="row g-3">
                <?php foreach ($addresses as $addr): ?>
                  <div class="col-md-6">
                    <div class="card h-100 border-warning">
                      <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                          <h6 class="card-title mb-0">
                            <?php echo htmlspecialchars($addr['full_name']); ?>
                            <?php if ($addr['is_default']): ?>
                              <span class="badge bg-warning text-dark ms-2">Mặc định</span>
                            <?php endif; ?>
                          </h6>
                        </div>

                        <p class="text-muted small mb-2">
                          <i class="fa fa-phone"></i> <?php echo htmlspecialchars($addr['phone']); ?>
                        </p>

                        <p class="text-muted small mb-2">
                          <i class="fa fa-map-marker-alt"></i><br>
                          <?php echo htmlspecialchars($addr['street_address']); ?><br>
                          <?php echo htmlspecialchars($addr['city']); ?>, <?php echo htmlspecialchars($addr['postal_code']); ?><br>
                          <?php echo htmlspecialchars($addr['country']); ?>
                        </p>

                        <p class="text-muted small mb-3">
                          <i class="fa fa-credit-card"></i>
                          <?php 
                            echo $addr['payment_method'] === 'cod' 
                              ? 'Thanh Toán Khi Nhận Hàng' 
                              : 'Chuyển Khoản Ngân Hàng'; 
                          ?>
                        </p>

                        <div class="d-flex gap-2">
                          <a href="delivery_management.php?edit=<?php echo $addr['address_id']; ?>" class="btn btn-sm btn-outline-warning">
                            <i class="fa fa-edit"></i> Sửa
                          </a>
                          <form method="post" action="delivery_management.php" style="display: inline;" 
                            onsubmit="return confirm('Bạn có chắc muốn xóa địa chỉ này?');">
                            <input type="hidden" name="address_id" value="<?php echo $addr['address_id']; ?>">
                            <button type="submit" name="delete_address" class="btn btn-sm btn-outline-danger">
                              <i class="fa fa-trash"></i> Xóa
                            </button>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                Bạn chưa có địa chỉ giao hàng. Vui lòng thêm địa chỉ mới.
              </div>
            <?php endif; ?>
          </div>

          <div class="mt-4">
            <a href="profile.php" class="btn btn-outline-secondary">
              <i class="fa fa-arrow-left"></i> Quay lại Thông tin cá nhân
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
  if(isset($conn)) { mysqli_close($conn); }
  require_once "./template/footer.php";
?>
