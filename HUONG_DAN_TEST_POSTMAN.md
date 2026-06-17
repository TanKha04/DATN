# Hướng dẫn Test API bằng Postman

## Chuẩn bị

### 1. Cài đặt Postman
- Tải về tại: https://www.postman.com/downloads/
- Hoặc dùng Postman Web: https://web.postman.com/

### 2. Khởi động Server
**Nếu dùng Docker:**
```cmd
docker-compose up -d
```
Server chạy tại: `http://localhost:8080`

**Nếu dùng XAMPP:**
- Khởi động Apache và MySQL trong XAMPP Control Panel
- Server chạy tại: `http://localhost/DACN2`

### 3. Base URL
- **Docker**: `http://localhost:8080`
- **XAMPP**: `http://localhost/DACN2`
- **Ngrok**: `https://your-ngrok-url.ngrok-free.app`

---

## API Endpoints có sẵn

### 1. Posts API (`/api/posts.php`)
### 2. Comments API (`/api/comments.php`)
### 3. Chat API (`/api/chat.php`)
### 4. Students API (`/api/students.php`)
### 5. Update Privacy API (`/api/update_privacy.php`)

---

## Chi tiết Test từng API

## 1️⃣ POSTS API

### ✅ GET - Lấy danh sách bài đăng

**Request:**
```
Method: GET
URL: http://localhost:8080/api/posts.php
```

**Query Parameters (Optional):**
- `page`: Số trang (mặc định: 1)
- `limit`: Số bài/trang (mặc định: 10, max: 100)
- `status`: Lọc theo trạng thái (open, closed)
- `type`: Lọc theo loại (recruitment, application)
- `category`: Lọc theo danh mục

**Ví dụ với parameters:**
```
http://localhost:8080/api/posts.php?page=1&limit=5&status=open&type=recruitment
```

**Response mẫu:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "user_id": 2,
            "title": "Tìm sinh viên Y khoa hỗ trợ",
            "content": "Cần tìm sinh viên...",
            "type": "recruitment",
            "status": "open",
            "created_at": "2024-01-15 10:30:00",
            "author_name": "Nguyễn Văn A",
            "author_username": "nguyenvana"
        }
    ],
    "pagination": {
        "page": 1,
        "limit": 5,
        "total": 25,
        "total_pages": 5
    }
}
```

---

### ✅ GET - Lấy 1 bài đăng theo ID

**Request:**
```
Method: GET
URL: http://localhost:8080/api/posts.php?id=1
```

**Response mẫu:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "user_id": 2,
        "title": "Tìm sinh viên Y khoa",
        "content": "Nội dung chi tiết...",
        "type": "recruitment",
        "category": "Chăm sóc tại nhà",
        "area": "Hà Nội",
        "status": "open",
        "created_at": "2024-01-15 10:30:00",
        "author_name": "Nguyễn Văn A"
    }
}
```

---

### ✅ POST - Tạo bài đăng mới

**Request:**
```
Method: POST
URL: http://localhost:8080/api/posts.php
Headers:
  Content-Type: application/json
```

**Body (raw JSON):**
```json
{
    "user_id": 2,
    "title": "Cần tìm sinh viên Y khoa hỗ trợ chăm sóc",
    "content": "Tôi cần tìm sinh viên Y khoa có kinh nghiệm để hỗ trợ chăm sóc người già tại nhà.",
    "type": "recruitment",
    "category": "Chăm sóc tại nhà",
    "area": "Hà Nội",
    "status": "open"
}
```

**Response mẫu:**
```json
{
    "success": true,
    "message": "Post created successfully",
    "data": {
        "id": 26
    }
}
```

---

### ✅ PUT - Cập nhật bài đăng

**Request:**
```
Method: PUT
URL: http://localhost:8080/api/posts.php?id=26
Headers:
  Content-Type: application/json
```

**Body (raw JSON):**
```json
{
    "title": "Cần tìm sinh viên Y khoa - CẬP NHẬT",
    "content": "Nội dung đã được cập nhật",
    "status": "closed"
}
```

**Response mẫu:**
```json
{
    "success": true,
    "message": "Post updated successfully"
}
```

---

### ✅ DELETE - Xóa bài đăng

**Request:**
```
Method: DELETE
URL: http://localhost:8080/api/posts.php?id=26
```

**Response mẫu:**
```json
{
    "success": true,
    "message": "Post 'Cần tìm sinh viên Y khoa' deleted successfully"
}
```

---

## 2️⃣ COMMENTS API

### ✅ GET - Lấy danh sách bình luận

**Request:**
```
Method: GET
URL: http://localhost:8080/api/comments.php?action=get&post_id=1
```

**Response mẫu:**
```json
{
    "success": true,
    "comments": [
        {
            "id": 1,
            "post_id": 1,
            "user_id": 3,
            "content": "Tôi có thể giúp bạn!",
            "author_name": "Trần Thị B",
            "author_avatar": null,
            "like_count": 5,
            "user_liked": 0,
            "created_at": "2024-01-15 11:00:00",
            "replies": []
        }
    ]
}
```

---

### ✅ POST - Thêm bình luận

**Request:**
```
Method: POST
URL: http://localhost:8080/api/comments.php
Headers:
  Content-Type: application/json
```

**Body (raw JSON):**
```json
{
    "action": "add",
    "post_id": 1,
    "content": "Đây là bình luận của tôi"
}
```

**Thêm reply (trả lời bình luận):**
```json
{
    "action": "add",
    "post_id": 1,
    "parent_id": 1,
    "content": "Đây là câu trả lời cho bình luận #1"
}
```

---

### ✅ POST - Like/Unlike bình luận

**Request:**
```
Method: POST
URL: http://localhost:8080/api/comments.php
Headers:
  Content-Type: application/json
```

**Body:**
```json
{
    "action": "like",
    "comment_id": 1,
    "reaction": "like"
}
```

---

### ✅ POST - Sửa bình luận

**Body:**
```json
{
    "action": "edit",
    "comment_id": 1,
    "content": "Nội dung đã được chỉnh sửa"
}
```

---

### ✅ POST - Xóa bình luận

**Body:**
```json
{
    "action": "delete",
    "comment_id": 1
}
```

---

### ✅ POST - Báo cáo bình luận

**Body:**
```json
{
    "action": "report",
    "comment_id": 1,
    "reason": "spam",
    "description": "Bình luận này là spam"
}
```

---

## 3️⃣ STUDENTS API

### ✅ GET - Lấy danh sách sinh viên

**Request:**
```
Method: GET
URL: http://localhost:8080/api/students.php
```

**Query Parameters:**
- `page`: Số trang
- `limit`: Số sinh viên/trang

**Ví dụ:**
```
http://localhost:8080/api/students.php?page=1&limit=10
```

---

### ✅ GET - Lấy thông tin 1 sinh viên

**Request:**
```
Method: GET
URL: http://localhost:8080/api/students.php?id=3
```

---

### ✅ POST - Tạo sinh viên mới

**Request:**
```
Method: POST
URL: http://localhost:8080/api/students.php
Headers:
  Content-Type: application/json
```

**Body:**
```json
{
    "name": "Nguyễn Văn Test",
    "email": "test@student.edu.vn",
    "password": "123456",
    "phone": "0123456789"
}
```

---

### ✅ PUT - Cập nhật sinh viên

**Request:**
```
Method: PUT
URL: http://localhost:8080/api/students.php?id=3
Headers:
  Content-Type: application/json
```

**Body:**
```json
{
    "name": "Nguyễn Văn Test Updated",
    "phone": "0987654321"
}
```

---

### ✅ DELETE - Xóa sinh viên

**Request:**
```
Method: DELETE
URL: http://localhost:8080/api/students.php?id=3
```

---

## 4️⃣ CHAT API

### ✅ GET - Lấy danh sách cuộc trò chuyện

**Request:**
```
Method: GET
URL: http://localhost:8080/api/chat.php?action=get_conversations
```

**Lưu ý:** Cần đăng nhập trước (có session)

---

### ✅ GET - Lấy tin nhắn với 1 người

**Request:**
```
Method: GET
URL: http://localhost:8080/api/chat.php?action=get_messages&user_id=5
```

---

### ✅ POST - Gửi tin nhắn

**Request:**
```
Method: POST
URL: http://localhost:8080/api/chat.php
Headers:
  Content-Type: application/x-www-form-urlencoded
```

**Body (x-www-form-urlencoded):**
```
action: send_message
user_id: 5
message: Xin chào, tôi muốn trao đổi về bài đăng của bạn
```

---

## 🔧 Tips & Tricks

### 1. Tạo Environment trong Postman
- Click vào "Environments" → "Create Environment"
- Tạo biến `base_url` với giá trị `http://localhost:8080`
- Sử dụng: `{{base_url}}/api/posts.php`

### 2. Tạo Collection
- Tạo folder "DACN2 API"
- Thêm tất cả requests vào collection
- Có thể export/import để chia sẻ

### 3. Test với Session (đăng nhập)
Một số API cần đăng nhập (Chat, Comments):
1. Đăng nhập qua trình duyệt trước
2. Mở DevTools → Application → Cookies
3. Copy cookie `PHPSESSID`
4. Trong Postman, thêm vào Headers:
   ```
   Cookie: PHPSESSID=your_session_id_here
   ```

### 4. Test Response
Trong Postman, tab "Tests", thêm:
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has success field", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('success');
});
```

---

## 🐛 Xử lý lỗi thường gặp

### Lỗi: "Connection refused"
- Kiểm tra server đã chạy chưa
- Kiểm tra port đúng chưa (8080 hoặc 80)

### Lỗi: "404 Not Found"
- Kiểm tra URL đúng chưa
- Kiểm tra file API có tồn tại không

### Lỗi: "Invalid JSON data"
- Kiểm tra Header: `Content-Type: application/json`
- Kiểm tra JSON syntax đúng chưa

### Lỗi: "User not found" / "Post not found"
- Kiểm tra ID có tồn tại trong database không
- Chạy query SQL để xem dữ liệu

---

## 📦 Import Collection vào Postman

Tôi có thể tạo file Postman Collection để bạn import trực tiếp. Bạn có muốn không?

---

## 🎯 Kết luận

Bây giờ bạn đã có thể:
- ✅ Test tất cả API endpoints
- ✅ Tạo, đọc, cập nhật, xóa dữ liệu
- ✅ Kiểm tra response và xử lý lỗi
- ✅ Sử dụng Postman hiệu quả

Chúc bạn test thành công! 🚀
