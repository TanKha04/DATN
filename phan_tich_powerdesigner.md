# Phân Tích Kiểu Dữ Liệu Cho PowerDesigner (CDM)

## Hướng dẫn nhập vào PowerDesigner
- **Name**: Tên thuộc tính
- **Code**: Mã thuộc tính (thường giống Name)
- **Data Type**: Kiểu dữ liệu
- **Length**: Độ dài
- **P (Primary)**: Khóa chính
- **M (Mandatory)**: Bắt buộc (NOT NULL)
- **D (Displayed)**: Hiển thị

---

## 1. ENTITY: USERS (Người dùng)

| Name | Code | Data Type | Length | P | M | Domain |
|------|------|-----------|--------|---|---|--------|
| id | id | Integer | | ✓ | ✓ | |
| name | name | Variable characters | 150 | | | |
| username | username | Variable characters | 50 | | ✓ | |
| email | email | Variable characters | 100 | | ✓ | |
| password | password | Variable characters | 255 | | ✓ | |
| role | role | Variable characters | 20 | | | ENUM |
| phone | phone | Variable characters | 30 | | | |
| school | school | Variable characters | 255 | | | |
| verified | verified | Integer | 1 | | | Boolean |
| can_post | can_post | Integer | 1 | | | Boolean |
| created_at | created_at | Timestamp | | | | |

**Domain cho role:** patient, student, admin

---

## 2. ENTITY: POSTS (Bài đăng)

| Name | Code | Data Type | Length | P | M | Domain |
|------|------|-----------|--------|---|---|--------|
| id | id | Integer | | ✓ | ✓ | |
| user_id | user_id | Integer | | | ✓ | FK |
| title | title | Variable characters | 255 | | ✓ | |
| content | content | Text | | | ✓ | |
| type | type | Variable characters | 20 | | | ENUM |
| category | category | Variable characters | 100 | | | |
| area | area | Variable characters | 255 | | | |
| status | status | Variable characters | 20 | | | ENUM |
| created_at | created_at | Timestamp | | | | |
| updated_at | updated_at | Timestamp | | | | |

**Domain cho type:** recruitment, application
**Domain cho status:** open, closed, completed, inactive, taken

---

## 3. ENTITY: COMMENTS (Bình luận)

| Name | Code | Data Type | Length | P | M | Domain |
|------|------|-----------|--------|---|---|--------|
| id | id | Integer | | ✓ | ✓ | |
| post_id | post_id | Integer | | | ✓ | FK |
| user_id | user_id | Integer | | | ✓ | FK |
| content | content | Text | | | ✓ | |
| created_at | created_at | Timestamp | | | | |

---

## 4. ENTITY: RATINGS (Đánh giá)

| Name | Code | Data Type | Length | P | M | Domain |
|------|------|-----------|--------|---|---|--------|
| id | id | Integer | | ✓ | ✓ | |
| user_id | user_id | Integer | | | ✓ | FK |
| rated_user_id | rated_user_id | Integer | | | ✓ | FK |
| rating | rating | Integer | 1 | | ✓ | 1-5 |
| comment | comment | Text | | | | |
| created_at | created_at | Timestamp | | | | |

---

## 5. ENTITY: FRIENDSHIPS (Kết bạn)

| Name | Code | Data Type | Length | P | M | Domain |
|------|------|-----------|--------|---|---|--------|
| id | id | Integer | | ✓ | ✓ | |
| user_id | user_id | Integer | | | ✓ | FK |
| friend_id | friend_id | Integer | | | ✓ | FK |
| status | status | Variable characters | 20 | | | ENUM |
| created_at | created_at | Timestamp | | | | |

**Domain cho status:** pending, accepted, blocked

---

## 6. ENTITY: VERIFICATIONS (Xác minh)

| Name | Code | Data Type | Length | P | M | Domain |
|------|------|-----------|--------|---|---|--------|
| id | id | Integer | | ✓ | ✓ | |
| user_id | user_id | Integer | | | ✓ | FK |
| document_type | document_type | Variable characters | 50 | | ✓ | |
| document_path | document_path | Variable characters | 255 | | ✓ | |
| status | status | Variable characters | 20 | | | ENUM |
| created_at | created_at | Timestamp | | | | |

**Domain cho status:** pending, approved, rejected

---

## 7. ENTITY: POSTING_REQUESTS (Yêu cầu đăng bài)

| Name | Code | Data Type | Length | P | M | Domain |
|------|------|-----------|--------|---|---|--------|
| id | id | Integer | | ✓ | ✓ | |
| user_id | user_id | Integer | | | ✓ | FK |
| full_name | full_name | Variable characters | 150 | | ✓ | |
| student_code | student_code | Variable characters | 100 | | ✓ | |
| status | status | Variable characters | 20 | | | ENUM |
| created_at | created_at | Timestamp | | | | |

**Domain cho status:** pending, approved, rejected

---

## 8. ENTITY: REPORTS (Báo cáo)

| Name | Code | Data Type | Length | P | M | Domain |
|------|------|-----------|--------|---|---|--------|
| id | id | Integer | | ✓ | ✓ | |
| reporter_id | reporter_id | Integer | | | ✓ | FK |
| reported_user_id | reported_user_id | Integer | | | | FK |
| post_id | post_id | Integer | | | | FK |
| reason | reason | Variable characters | 255 | | ✓ | |
| status | status | Variable characters | 20 | | | ENUM |
| created_at | created_at | Timestamp | | | | |

**Domain cho status:** pending, reviewed, resolved

---

## 9. ENTITY: NOTIFICATIONS (Thông báo)

| Name | Code | Data Type | Length | P | M | Domain |
|------|------|-----------|--------|---|---|--------|
| id | id | Integer | | ✓ | ✓ | |
| user_id | user_id | Integer | | | ✓ | FK |
| type | type | Variable characters | 50 | | ✓ | |
| message | message | Text | | | ✓ | |
| is_read | is_read | Integer | 1 | | | Boolean |
| created_at | created_at | Timestamp | | | | |

---

## 10. ENTITY: MESSAGES (Tin nhắn)

| Name | Code | Data Type | Length | P | M | Domain |
|------|------|-----------|--------|---|---|--------|
| id | id | Integer | | ✓ | ✓ | |
| sender_id | sender_id | Integer | | | ✓ | FK |
| receiver_id | receiver_id | Integer | | | ✓ | FK |
| message | message | Text | | | ✓ | |
| is_read | is_read | Integer | 1 | | | Boolean |
| created_at | created_at | Timestamp | | | | |

---

## 11. ENTITY: FAVORITES (Yêu thích)

| Name | Code | Data Type | Length | P | M | Domain |
|------|------|-----------|--------|---|---|--------|
| id | id | Integer | | ✓ | ✓ | |
| user_id | user_id | Integer | | | ✓ | FK |
| post_id | post_id | Integer | | | ✓ | FK |
| created_at | created_at | Timestamp | | | | |

---

## 12. ENTITY: APPOINTMENTS (Lịch hẹn)

| Name | Code | Data Type | Length | P | M | Domain |
|------|------|-----------|--------|---|---|--------|
| id | id | Integer | | ✓ | ✓ | |
| patient_id | patient_id | Integer | | | ✓ | FK |
| student_id | student_id | Integer | | | ✓ | FK |
| appointment_date | appointment_date | Date & Time | | | | |
| status | status | Variable characters | 20 | | | ENUM |
| notes | notes | Text | | | | |
| created_at | created_at | Timestamp | | | | |

**Domain cho status:** pending, confirmed, completed, cancelled

---

## RELATIONSHIPS (Quan hệ) - PHÂN TÍCH CHI TIẾT

---

### 1. Quan hệ: Đánh giá (USERS ↔ RATINGS)

```
┌─────────┐      1,1      ┌───────────┐      0,n      ┌─────────┐
│ RATINGS │───────────────│  Đánh giá │───────────────│  USERS  │
└─────────┘               └───────────┘               └─────────┘
```

| Phía | Bản số | Ý nghĩa |
|------|--------|---------|
| RATINGS | 1,1 | Mỗi đánh giá **bắt buộc** thuộc về **đúng 1** người dùng |
| USERS | 0,n | 1 người dùng có thể tạo **0 hoặc nhiều** lượt đánh giá |

**Ví dụ:** Bệnh nhân A đánh giá sinh viên B (5 sao), sinh viên C (4 sao) → 1 user tạo 2 ratings

---

### 2. Quan hệ: Kết bạn (USERS ↔ FRIENDSHIPS)

```
┌─────────────┐    1,1    ┌───────────┐    0,n    ┌─────────┐
│ FRIENDSHIPS │───────────│  Kết bạn  │───────────│  USERS  │
└─────────────┘           └───────────┘           └─────────┘
```

| Phía | Bản số | Ý nghĩa |
|------|--------|---------|
| FRIENDSHIPS | 1,1 | Mỗi friendship **bắt buộc** thuộc về **đúng 1** người dùng |
| USERS | 0,n | 1 người dùng có thể gửi **0 hoặc nhiều** lời mời kết bạn |

**Ví dụ:** User A gửi lời mời kết bạn cho B, C, D → 1 user tạo 3 friendships

---

### 3. Quan hệ: Xác minh (USERS ↔ VERIFICATIONS)

```
┌───────────────┐   1,1   ┌───────────┐   0,n   ┌─────────┐
│ VERIFICATIONS │─────────│  Xác minh │─────────│  USERS  │
└───────────────┘         └───────────┘         └─────────┘
```

| Phía | Bản số | Ý nghĩa |
|------|--------|---------|
| VERIFICATIONS | 1,1 | Mỗi verification **bắt buộc** thuộc về **đúng 1** người dùng |
| USERS | 0,n | 1 người dùng có thể gửi **0 hoặc nhiều** yêu cầu xác minh |

**Ví dụ:** Sinh viên A gửi 1 yêu cầu xác minh thẻ sinh viên

---

### 4. Quan hệ: Yêu cầu đăng bài (USERS ↔ POSTING_REQUESTS)

```
┌──────────────────┐  1,1  ┌─────────┐  0,n  ┌─────────┐
│ POSTING_REQUESTS │───────│ Yêu cầu │───────│  USERS  │
└──────────────────┘       └─────────┘       └─────────┘
```

| Phía | Bản số | Ý nghĩa |
|------|--------|---------|
| POSTING_REQUESTS | 1,1 | Mỗi request **bắt buộc** thuộc về **đúng 1** người dùng |
| USERS | 0,n | 1 người dùng có thể gửi **0 hoặc nhiều** yêu cầu quyền đăng bài |

**Ví dụ:** Sinh viên A gửi yêu cầu xin quyền đăng bài

---

### 5. Quan hệ: Báo cáo (USERS ↔ REPORTS)

```
┌─────────┐      1,1      ┌─────────┐      0,n      ┌─────────┐
│ REPORTS │───────────────│ Báo cáo │───────────────│  USERS  │
└─────────┘               └─────────┘               └─────────┘
```

| Phía | Bản số | Ý nghĩa |
|------|--------|---------|
| REPORTS | 1,1 | Mỗi report **bắt buộc** thuộc về **đúng 1** người dùng (người báo cáo) |
| USERS | 0,n | 1 người dùng có thể gửi **0 hoặc nhiều** báo cáo vi phạm |

**Ví dụ:** User A báo cáo bài viết spam, báo cáo user B lừa đảo → 1 user tạo 2 reports

---

### 6. Quan hệ: Nhận thông báo (USERS ↔ NOTIFICATIONS)

```
┌───────────────┐   1,1   ┌─────────┐   0,n   ┌─────────┐
│ NOTIFICATIONS │─────────│  Nhận   │─────────│  USERS  │
└───────────────┘         └─────────┘         └─────────┘
```

| Phía | Bản số | Ý nghĩa |
|------|--------|---------|
| NOTIFICATIONS | 1,1 | Mỗi notification **bắt buộc** thuộc về **đúng 1** người dùng |
| USERS | 0,n | 1 người dùng có thể nhận **0 hoặc nhiều** thông báo |

**Ví dụ:** User A nhận thông báo: có comment mới, có tin nhắn mới → 1 user có nhiều notifications

---

### 7. Quan hệ: Gửi tin nhắn (USERS ↔ MESSAGES)

```
┌──────────┐      1,1      ┌─────────┐      0,n      ┌─────────┐
│ MESSAGES │───────────────│   Gửi   │───────────────│  USERS  │
└──────────┘               └─────────┘               └─────────┘
```

| Phía | Bản số | Ý nghĩa |
|------|--------|---------|
| MESSAGES | 1,1 | Mỗi message **bắt buộc** thuộc về **đúng 1** người dùng (người gửi) |
| USERS | 0,n | 1 người dùng có thể gửi **0 hoặc nhiều** tin nhắn |

**Ví dụ:** User A gửi tin nhắn cho B, C, D → 1 user tạo nhiều messages

---

### 8. Quan hệ: Đăng bài (USERS ↔ POSTS)

```
┌─────────┐      1,1      ┌───────────┐      0,n      ┌─────────┐
│  POSTS  │───────────────│ Đăng bài  │───────────────│  USERS  │
└─────────┘               └───────────┘               └─────────┘
```

| Phía | Bản số | Ý nghĩa |
|------|--------|---------|
| POSTS | 1,1 | Mỗi bài viết **bắt buộc** thuộc về **đúng 1** người dùng |
| USERS | 0,n | 1 người dùng có thể đăng **0 hoặc nhiều** bài viết |

**Ví dụ:** Bệnh nhân A đăng 3 bài tìm sinh viên hỗ trợ → 1 user tạo 3 posts

---

### 9. Quan hệ: Viết bình luận (USERS ↔ COMMENTS)

```
┌──────────┐      1,1      ┌─────────┐      0,n      ┌─────────┐
│ COMMENTS │───────────────│  Viết   │───────────────│  USERS  │
└──────────┘               └─────────┘               └─────────┘
```

| Phía | Bản số | Ý nghĩa |
|------|--------|---------|
| COMMENTS | 1,1 | Mỗi comment **bắt buộc** thuộc về **đúng 1** người dùng |
| USERS | 0,n | 1 người dùng có thể viết **0 hoặc nhiều** bình luận |

**Ví dụ:** Sinh viên A bình luận vào 5 bài viết khác nhau → 1 user tạo 5 comments

---

### 10. Quan hệ: Yêu thích (USERS ↔ FAVORITES)

```
┌───────────┐     1,1     ┌───────────┐     0,n     ┌─────────┐
│ FAVORITES │─────────────│ Yêu thích │─────────────│  USERS  │
└───────────┘             └───────────┘             └─────────┘
```

| Phía | Bản số | Ý nghĩa |
|------|--------|---------|
| FAVORITES | 1,1 | Mỗi favorite **bắt buộc** thuộc về **đúng 1** người dùng |
| USERS | 0,n | 1 người dùng có thể yêu thích **0 hoặc nhiều** bài viết |

**Ví dụ:** User A yêu thích bài viết 1, 2, 3 → 1 user tạo 3 favorites

---

### 11. Quan hệ: Đặt lịch hẹn (USERS ↔ APPOINTMENTS)

```
┌──────────────┐   1,1   ┌───────────┐   0,n   ┌─────────┐
│ APPOINTMENTS │─────────│ Đặt lịch  │─────────│  USERS  │
└──────────────┘         └───────────┘         └─────────┘
```

| Phía | Bản số | Ý nghĩa |
|------|--------|---------|
| APPOINTMENTS | 1,1 | Mỗi appointment **bắt buộc** thuộc về **đúng 1** người dùng (bệnh nhân) |
| USERS | 0,n | 1 người dùng có thể đặt **0 hoặc nhiều** lịch hẹn |

**Ví dụ:** Bệnh nhân A đặt lịch với sinh viên B, C → 1 user tạo 2 appointments

---

### 12. Quan hệ: Có bình luận (POSTS ↔ COMMENTS)

```
┌──────────┐      1,1      ┌─────────┐      0,n      ┌─────────┐
│ COMMENTS │───────────────│   Có    │───────────────│  POSTS  │
└──────────┘               └─────────┘               └─────────┘
```

| Phía | Bản số | Ý nghĩa |
|------|--------|---------|
| COMMENTS | 1,1 | Mỗi comment **bắt buộc** thuộc về **đúng 1** bài viết |
| POSTS | 0,n | 1 bài viết có thể có **0 hoặc nhiều** bình luận |

**Ví dụ:** Bài viết #1 có 10 comments → 1 post có nhiều comments

---

### 13. Quan hệ: Được yêu thích (POSTS ↔ FAVORITES)

```
┌───────────┐     1,1     ┌───────────┐     0,n     ┌─────────┐
│ FAVORITES │─────────────│ Được YT   │─────────────│  POSTS  │
└───────────┘             └───────────┘             └─────────┘
```

| Phía | Bản số | Ý nghĩa |
|------|--------|---------|
| FAVORITES | 1,1 | Mỗi favorite **bắt buộc** thuộc về **đúng 1** bài viết |
| POSTS | 0,n | 1 bài viết có thể được yêu thích bởi **0 hoặc nhiều** user |

**Ví dụ:** Bài viết #1 được 50 user yêu thích → 1 post có 50 favorites

---

### 14. Quan hệ: Bị báo cáo (POSTS ↔ REPORTS)

```
┌─────────┐      0,1      ┌───────────┐      0,n      ┌─────────┐
│ REPORTS │───────────────│  Bị BC    │───────────────│  POSTS  │
└─────────┘               └───────────┘               └─────────┘
```

| Phía | Bản số | Ý nghĩa |
|------|--------|---------|
| REPORTS | 0,1 | Mỗi report **có thể** liên quan đến **0 hoặc 1** bài viết (post_id có thể NULL) |
| POSTS | 0,n | 1 bài viết có thể bị báo cáo bởi **0 hoặc nhiều** user |

**Ví dụ:** Bài viết spam bị 3 user báo cáo → 1 post có 3 reports

---

## TỔNG HỢP BẢNG QUAN HỆ

| STT | Relationship | Entity 1 | Bản số 1 | Entity 2 | Bản số 2 |
|-----|--------------|----------|----------|----------|----------|
| 1 | Đánh giá | RATINGS | 1,1 | USERS | 0,n |
| 2 | Kết bạn | FRIENDSHIPS | 1,1 | USERS | 0,n |
| 3 | Xác minh | VERIFICATIONS | 1,1 | USERS | 0,n |
| 4 | Yêu cầu | POSTING_REQUESTS | 1,1 | USERS | 0,n |
| 5 | Báo cáo | REPORTS | 1,1 | USERS | 0,n |
| 6 | Nhận | NOTIFICATIONS | 1,1 | USERS | 0,n |
| 7 | Gửi | MESSAGES | 1,1 | USERS | 0,n |
| 8 | Đăng bài | POSTS | 1,1 | USERS | 0,n |
| 9 | Viết | COMMENTS | 1,1 | USERS | 0,n |
| 10 | Yêu thích | FAVORITES | 1,1 | USERS | 0,n |
| 11 | Đặt lịch | APPOINTMENTS | 1,1 | USERS | 0,n |
| 12 | Có | COMMENTS | 1,1 | POSTS | 0,n |
| 13 | Được YT | FAVORITES | 1,1 | POSTS | 0,n |
| 14 | Bị BC | REPORTS | 0,1 | POSTS | 0,n |

---

## QUY TẮC XÁC ĐỊNH BẢN SỐ

| FK trong Entity con | Bản số Entity con | Bản số Entity cha |
|---------------------|-------------------|-------------------|
| NOT NULL (bắt buộc) | **1,1** | 0,n hoặc 1,n |
| NULL (không bắt buộc) | **0,1** | 0,n |

---

## Kiểu dữ liệu PowerDesigner tương ứng

| PowerDesigner | MySQL | Mô tả |
|---------------|-------|-------|
| Integer | INT | Số nguyên |
| Variable characters (n) | VARCHAR(n) | Chuỗi độ dài n |
| Text | TEXT | Văn bản dài |
| Timestamp | TIMESTAMP | Thời gian |
| Date & Time | DATETIME | Ngày giờ |
| Integer (1) | TINYINT(1) | Boolean |
