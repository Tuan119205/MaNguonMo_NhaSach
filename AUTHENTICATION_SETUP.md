# Hệ Thống Đăng Ký & Đăng Nhập - Hướng Dẫn Thiết Lập

## 📋 Tổng Quan
Hệ thống đăng ký/đăng nhập mới hỗ trợ (🇻🇳 **TOÀN TIẾNG VIỆT**):
- **Admin**: Đăng nhập quản lý sách
- **Khách hàng**: Đăng ký tài khoản và đăng nhập mua sách

## 🗄️ Bước 1: Cập Nhật Database

### Chạy file migration SQL:
1. Mở **phpMyAdmin** (http://localhost/phpmyadmin)
2. Chọn database `obs_db`
3. Vào tab **SQL**
4. Copy nội dung từ file `database/users_migration.sql`
5. Paste vào SQL console và click **Execute**

### Hoặc chạy lệnh trực tiếp:
```sql
CREATE TABLE IF NOT EXISTS `users` (
  `userid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `email` varchar(100) NOT NULL UNIQUE,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(15),
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` boolean DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
```

## 🔐 Tài Khoản Demo

### Admin Login:
- **URL**: http://localhost/obs/admin.php
- **Tên Đăng Nhập**: `admin`
- **Mật Khẩu**: `admin` (SHA1)

### Khách Hàng Demo:
- **URL**: http://localhost/obs/login.php
- **Tên Đăng Nhập**: `customer1`
- **Email**: `customer@example.com`
- **Mật Khẩu**: `123456`

## 📁 Các File Mới Tạo

| File | Mô Tả |
|------|-------|
| `register.php` | Trang đăng ký khách hàng (Tiếng Việt) |
| `register_verify.php` | Xử lý xác minh đăng ký (Tiếng Việt) |
| `login.php` | Trang đăng nhập khách hàng (Tiếng Việt) |
| `login_verify.php` | Xử lý xác minh đăng nhập (Tiếng Việt) |
| `customer_signout.php` | Đăng xuất khách hàng |
| `database/users_migration.sql` | File cập nhật database |

## 🔄 Luồng Hoạt Động

### Đăng Ký Khách Hàng:
```
register.php → register_verify.php → Lưu vào DB → Chuyển hướng đến login.php
```

### Đăng Nhập Khách Hàng:
```
login.php → login_verify.php → Kiểm tra DB → Lưu Session → Chuyển hướng index.php
```

### Đăng Xuất:
```
customer_signout.php → Xóa Session → Chuyển hướng index.php
```

## 🔒 Bảo Mật

- **Mật khẩu**: Được mã hóa bằng MD5
- **SQL Injection**: Sử dụng `mysqli_real_escape_string()`
- **Kiểm tra**: 
  - Email không được trùng lặp
  - Tên đăng nhập không được trùng lặp
  - Mật khẩu phải ≥ 6 ký tự
  - Các trường bắt buộc phải điền

## 🎨 Cập Nhật Navigation (Header.php)

**Header.php** đã được cập nhật để hiển thị (Tiếng Việt):

### 1. **Nếu Admin đăng nhập**:
   - Danh Sách Sách
   - Thêm Sách Mới
   - Đăng Xuất Admin

### 2. **Nếu Khách hàng đăng nhập**:
   - Nhà Xuất Bản
   - Sách
   - Giỏ Hàng
   - Tên Người Dùng (hiển thị)
   - Đăng Xuất

### 3. **Nếu chưa đăng nhập**:
   - Nhà Xuất Bản
   - Sách
   - Giỏ Hàng
   - Dropdown "Tài Khoản":
     - Đăng Nhập Khách Hàng
     - Đăng Ký
     - Đăng Nhập Admin

## 💡 Hướng Dẫn Sử Dụng

### Khách hàng mới:
1. Truy cập: http://localhost/obs/register.php
2. Điền form đăng ký (Hộ Tên, Email, Tên Đăng Nhập, Số Điện Thoại, Mật Khẩu)
3. Click nút "Đăng Ký"
4. Hệ thống chuyển đến trang login
5. Nhập tên đăng nhập/email và mật khẩu
6. Đăng nhập thành công

### Admin:
1. Truy cập: http://localhost/obs/admin.php
2. Nhập tài khoản admin
3. Truy cập quản lý sách

## ⚙️ Tùy Chỉnh

### Đổi phương pháp mã hóa mật khẩu:
- Hiện tại: `md5()` - có thể thay bằng `password_hash()` và `password_verify()` để bảo mật hơn

### Thêm tính năng:
- Quên mật khẩu / Reset password
- Xác thực email
- Thay đổi mật khẩu
- Quản lý hồ sơ khách hàng

## 📞 Hỗ Trợ Lỗi

| Lỗi | Giải Pháp |
|-----|----------|
| "Email này đã được đăng ký" | Email đã được dùng, dùng email khác |
| "Tên đăng nhập này đã được sử dụng" | Tên đăng nhập đã được dùng, chọn tên khác |
| "Mật khẩu không khớp" | Nhập lại và kiểm tra mật khẩu có giống nhau |
| "Tên đăng nhập/Email hoặc mật khẩu không chính xác" | Kiểm tra tên đăng nhập/email và mật khẩu |
| "Tất cả các trường bắt buộc phải được điền" | Điền đầy đủ tất cả các thông tin yêu cầu |
| Lỗi kết nối database | Kiểm tra MySQL/phpMyAdmin đang chạy |

---

✅ **Hoàn tất!** Hệ thống đăng ký/đăng nhập **100% TIẾNG VIỆT** đã sẵn sàng sử dụng!
