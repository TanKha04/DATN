# Phân Tích Kiểu Dữ Liệu Theo ERD Diagram

## 1. BẢNG USERS (Người dùng)

| Cột | Kiểu dữ liệu ERD | Kiểu dữ liệu đề xuất | Ghi chú |
|-----|------------------|---------------------|---------|
| id | PK | INT(11) AUTO_INCREMENT | Khóa chính |
| name | VARCHAR(150) | VARCHAR(150) | Tên hiển thị |
| username | NOT NULL | VARCHAR(50) NOT NULL UNIQUE | Tên đăng nhập |
| email | NOT NULL | VARCHAR(100) NOT NULL UNIQUE | Email |
| password | NOT NULL | VARCHAR(255) NOT NULL | Mật khẩu hash |
| role | ENUM(patient,student,admin) | ENUM('patient','student','admin') DEFAULT 'student' | Vai trò |
| phone | VARCHAR(30) | VARCHAR(30) | Số điện thoại |
| school | VARCHAR(255) | VARCHAR(255) | Trường học |
| verified | TINYINT | TINYINT(1) DEFAULT 0 | Đã xác minh |
| can_post | TINYINT | TINYINT(1) DEFAULT 0 | Quyền đăng bài |
| created_at | TIMESTAMP | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Ngày tạo |

---

## 2. BẢNG POSTS (Bài đăng)

| Cột | Kiểu dữ liệu ERD | Kiểu dữ liệu đề xuất | Ghi chú |
|-----|------------------|---------------------|---------|
| id | PK | INT(11) AUTO_INCREMENT | Khóa chính |
| user_id | FK NOT NULL | INT(11) NOT NULL | FK → users(id) |
| title | NOT NULL | VARCHAR(255) NOT NULL | Tiêu đề |
| content | NOT NULL | TEXT NOT NULL | Nội dung |
| type | ENUM(recruitment,application) | ENUM('recruitment','application') DEFAULT 'recruitment' | Loại bài |
| category | VARCHAR(100) | VARCHAR(100) | Danh mục |
| area | VARCHAR(255) | VARCHAR(255) | Khu vực |
| status | ENUM(open,closed,...) | ENUM('open','closed','completed','inactive','taken') DEFAULT 'open' | Trạng thái |
| created_at | TIMESTAMP | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Ngày tạo |
| updated_at | TIMESTAMP | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Ngày cập nhật |

---

## 3. BẢNG COMMENTS (Bình luận)

| Cột | Kiểu dữ liệu ERD | Kiểu dữ liệu đề xuất | Ghi chú |
|-----|------------------|---------------------|---------|
| id | PK | INT(11) AUTO_INCREMENT | Khóa chính |
| post_id | FK NOT NULL | INT(11) NOT NULL | FK → posts(id) |
| user_id | FK NOT NULL | INT(11) NOT NULL | FK → users(id) |
| content | NOT NULL | TEXT NOT NULL | Nội dung bình luận |
| created_at | TIMESTAMP | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Ngày tạo |

---

## 4. BẢNG RATINGS (Đánh giá)

| Cột | Kiểu dữ liệu ERD | Kiểu dữ liệu đề xuất | Ghi chú |
|-----|------------------|---------------------|---------|
| id | PK | INT(11) AUTO_INCREMENT | Khóa chính |
| user_id | FK NOT NULL | INT(11) NOT NULL | FK → users(id) - Người đánh giá |
| rated_user_id | FK NOT NULL | INT(11) NOT NULL | FK → users(id) - Người được đánh giá |
| rating | TINYINT NOT NULL | TINYINT(1) NOT NULL CHECK (rating BETWEEN 1 AND 5) | Điểm đánh giá (1-5) |
| comment | TEXT | TEXT DEFAULT NULL | Nhận xét |
| created_at | TIMESTAMP | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Ngày tạo |

---

## 5. BẢNG FRIENDSHIPS (Kết bạn)

| Cột | Kiểu dữ liệu ERD | Kiểu dữ liệu đề xuất | Ghi chú |
|-----|------------------|---------------------|---------|
| id | PK | INT(11) AUTO_INCREMENT | Khóa chính |
| user_id | FK NOT NULL | INT(11) NOT NULL | FK → users(id) - Người gửi |
| friend_id | FK NOT NULL | INT(11) NOT NULL | FK → users(id) - Người nhận |
| status | ENUM | ENUM('pending','accepted','blocked') DEFAULT 'pending' | Trạng thái |
| created_at | TIMESTAMP | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Ngày tạo |
| accepted_at | TIMESTAMP | TIMESTAMP NULL DEFAULT NULL | Ngày chấp nhận |

---

## 6. BẢNG VERIFICATIONS (Xác minh)

| Cột | Kiểu dữ liệu ERD | Kiểu dữ liệu đề xuất | Ghi chú |
|-----|------------------|---------------------|---------|
| id | PK | INT(11) AUTO_INCREMENT | Khóa chính |
| user_id | FK NOT NULL | INT(11) NOT NULL | FK → users(id) |
| document_type | NOT NULL | VARCHAR(50) NOT NULL | Loại tài liệu |
| document_path | NOT NULL | VARCHAR(255) NOT NULL | Đường dẫn file |
| status | ENUM | ENUM('pending','approved','rejected') DEFAULT 'pending' | Trạng thái |
| admin_notes | TEXT | TEXT DEFAULT NULL | Ghi chú admin |
| created_at | TIMESTAMP | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Ngày tạo |
| processed_at | TIMESTAMP | TIMESTAMP NULL DEFAULT NULL | Ngày xử lý |

---

## 7. BẢNG POSTING_REQUESTS (Yêu cầu đăng bài)

| Cột | Kiểu dữ liệu ERD | Kiểu dữ liệu đề xuất | Ghi chú |
|-----|------------------|---------------------|---------|
| id | PK | INT(11) AUTO_INCREMENT | Khóa chính |
| user_id | FK NOT NULL | INT(11) NOT NULL | FK → users(id) |
| full_name | NOT NULL | VARCHAR(150) NOT NULL | Họ tên đầy đủ |
| student_code | NOT NULL | VARCHAR(100) NOT NULL | Mã sinh viên |
| class_name | VARCHAR | VARCHAR(100) DEFAULT NULL | Tên lớp |
| address | VARCHAR | VARCHAR(255) DEFAULT NULL | Địa chỉ |
| document_card | VARCHAR | VARCHAR(255) DEFAULT NULL | Ảnh thẻ SV |
| document_internship | VARCHAR | VARCHAR(255) DEFAULT NULL | Giấy thực tập |
| status | ENUM | ENUM('pending','approved','rejected') DEFAULT 'pending' | Trạng thái |
| admin_note | TEXT | TEXT DEFAULT NULL | Ghi chú admin |
| created_at | TIMESTAMP | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Ngày tạo |
| processed_at | TIMESTAMP | TIMESTAMP NULL DEFAULT NULL | Ngày xử lý |

---

## 8. BẢNG REPORTS (Báo cáo vi phạm)

| Cột | Kiểu dữ liệu ERD | Kiểu dữ liệu đề xuất | Ghi chú |
|-----|------------------|---------------------|---------|
| id | PK | INT(11) AUTO_INCREMENT | Khóa chính |
| reporter_id | FK NOT NULL | INT(11) NOT NULL | FK → users(id) - Người báo cáo |
| reported_user_id | FK NULL | INT(11) DEFAULT NULL | FK → users(id) - Người bị báo cáo |
| post_id | FK NULL | INT(11) DEFAULT NULL | FK → posts(id) - Bài viết bị báo cáo |
| reason | NOT NULL | VARCHAR(255) NOT NULL | Lý do |
| description | TEXT | TEXT DEFAULT NULL | Mô tả chi tiết |
| status | ENUM | ENUM('pending','reviewed','resolved') DEFAULT 'pending' | Trạng thái |
| admin_notes | TEXT | TEXT DEFAULT NULL | Ghi chú admin |
| created_at | TIMESTAMP | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Ngày tạo |
| resolved_at | TIMESTAMP | TIMESTAMP NULL DEFAULT NULL | Ngày giải quyết |

---

## 9. BẢNG NOTIFICATIONS (Thông báo)

| Cột | Kiểu dữ liệu ERD | Kiểu dữ liệu đề xuất | Ghi chú |
|-----|------------------|---------------------|---------|
| id | PK | INT(11) AUTO_INCREMENT | Khóa chính |
| user_id | FK NOT NULL | INT(11) NOT NULL | FK → users(id) |
| type | NOT NULL | VARCHAR(50) NOT NULL | Loại thông báo |
| title | VARCHAR | VARCHAR(255) NOT NULL | Tiêu đề |
| message | NOT NULL | TEXT NOT NULL | Nội dung |
| link | VARCHAR | VARCHAR(255) DEFAULT NULL | Đường dẫn |
| is_read | TINYINT | TINYINT(1) DEFAULT 0 | Đã đọc |
| created_at | TIMESTAMP | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Ngày tạo |

---

## 10. BẢNG MESSAGES (Tin nhắn)

| Cột | Kiểu dữ liệu ERD | Kiểu dữ liệu đề xuất | Ghi chú |
|-----|------------------|---------------------|---------|
| id | PK | INT(11) AUTO_INCREMENT | Khóa chính |
| sender_id | FK NOT NULL | INT(11) NOT NULL | FK → users(id) - Người gửi |
| receiver_id | FK NOT NULL | INT(11) NOT NULL | FK → users(id) - Người nhận |
| subject | VARCHAR | VARCHAR(255) DEFAULT NULL | Chủ đề |
| message | NOT NULL | TEXT NOT NULL | Nội dung |
| is_read | TINYINT | TINYINT(1) DEFAULT 0 | Đã đọc |
| created_at | TIMESTAMP | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Ngày tạo |

---

## 11. BẢNG FAVORITES (Yêu thích)

| Cột | Kiểu dữ liệu ERD | Kiểu dữ liệu đề xuất | Ghi chú |
|-----|------------------|---------------------|---------|
| id | PK | INT(11) AUTO_INCREMENT | Khóa chính |
| user_id | FK NOT NULL | INT(11) NOT NULL | FK → users(id) |
| post_id | FK NOT NULL | INT(11) NOT NULL | FK → posts(id) |
| created_at | TIMESTAMP | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Ngày tạo |

**Ràng buộc:** UNIQUE KEY (user_id, post_id) - Mỗi user chỉ yêu thích 1 bài 1 lần

---

## 12. BẢNG APPOINTMENTS (Lịch hẹn)

| Cột | Kiểu dữ liệu ERD | Kiểu dữ liệu đề xuất | Ghi chú |
|-----|------------------|---------------------|---------|
| id | PK | INT(11) AUTO_INCREMENT | Khóa chính |
| patient_id | FK NOT NULL | INT(11) NOT NULL | FK → users(id) - Bệnh nhân |
| student_id | FK NOT NULL | INT(11) NOT NULL | FK → users(id) - Sinh viên |
| appointment_date | DATETIME | DATETIME DEFAULT NULL | Ngày hẹn |
| status | ENUM | ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending' | Trạng thái |
| notes | TEXT | TEXT DEFAULT NULL | Ghi chú |
| created_at | TIMESTAMP | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Ngày tạo |
| updated_at | TIMESTAMP | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Ngày cập nhật |

---

## Tổng Kết Các Kiểu Dữ Liệu Sử Dụng

| Kiểu dữ liệu | Mô tả | Ví dụ sử dụng |
|--------------|-------|---------------|
| INT(11) | Số nguyên | id, user_id, post_id |
| VARCHAR(n) | Chuỗi có độ dài tối đa n | username(50), email(100), title(255) |
| TEXT | Văn bản dài | content, message, comment |
| TINYINT(1) | Boolean (0/1) | is_read, verified, can_post |
| ENUM | Danh sách giá trị cố định | status, role, type |
| TIMESTAMP | Thời gian | created_at, updated_at |
| DATETIME | Ngày giờ | appointment_date |

---

## Quan Hệ Giữa Các Bảng (Theo ERD)

```
USERS (1) ←→ (n) RATINGS (đánh giá)
USERS (1) ←→ (n) FRIENDSHIPS (kết bạn)
USERS (1) ←→ (n) VERIFICATIONS (xác minh)
USERS (1) ←→ (n) POSTING_REQUESTS (yêu cầu)
USERS (1) ←→ (n) REPORTS (báo cáo)
USERS (1) ←→ (n) NOTIFICATIONS (nhận)
USERS (1) ←→ (n) MESSAGES (gửi)
USERS (1) ←→ (n) POSTS (đăng bài)
USERS (1) ←→ (n) COMMENTS (viết)
USERS (1) ←→ (n) FAVORITES (yêu thích)
USERS (1) ←→ (n) APPOINTMENTS (đặt lịch)
POSTS (1) ←→ (n) COMMENTS (có)
POSTS (1) ←→ (n) FAVORITES (được yêu thích)
POSTS (0,1) ←→ (n) REPORTS (bị báo cáo)
```
