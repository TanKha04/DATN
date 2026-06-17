# 🤖 HƯỚNG DẪN KÍCH HOẠT AI CHO TRỢ LÝ Y TẾ

## ✅ Trạng thái hiện tại
- ✅ Code AI đã được tích hợp
- ✅ Widget chatbot đã hiển thị
- ⚠️ **Đang chạy chế độ Fallback** (chưa có API key)

## 🚀 Cách kích hoạt AI thật (5 phút)

### Bước 1: Lấy API Key MIỄN PHÍ

1. Truy cập: **https://huggingface.co/**
2. Nhấn **Sign Up** (Đăng ký) - dùng email bất kỳ
3. Xác nhận email và đăng nhập
4. Nhấn vào **avatar** góc trên phải → chọn **Settings**
5. Chọn **Access Tokens** ở menu bên trái
6. Nhấn **Create new token** hoặc **New token**
7. Điền:
   - Name: `yte-chatbot` (hoặc tên bất kỳ)
   - Role: chọn **Read** (đủ dùng)
8. Nhấn **Generate token**
9. **Copy token** (dạng: `hf_xxxxxxxxxxxxxxxxxxxxxx`)

### Bước 2: Thêm vào hệ thống

Mở file **config.php** (dòng 52), tìm dòng:
```php
define('HUGGINGFACE_API_KEY', getenv('HUGGINGFACE_API_KEY') ?: 'YOUR_API_KEY_HERE');
```

Thay `YOUR_API_KEY_HERE` bằng token vừa copy:
```php
define('HUGGINGFACE_API_KEY', getenv('HUGGINGFACE_API_KEY') ?: 'hf_xxxxxxxxxxxxxxxxxxxxxx');
```

**Lưu file** và **refresh** trang dashboard.

### Bước 3: Kiểm tra

1. Mở dashboard bệnh nhân
2. Nhấn icon **Trợ lý Y tế** (👨‍⚕️) góc dưới phải
3. Gõ: **"Xin chào"**
4. Nếu AI trả lời mượt mà → **Thành công!** 🎉

## 🔍 So sánh Fallback vs AI thật

### Chế độ Fallback (hiện tại - không cần API key)
- ✅ Hoạt động ngay lập tức
- ✅ Trả lời nhanh
- ⚠️ Chỉ trả lời được câu hỏi có từ khóa cố định
- ⚠️ Không hiểu ngữ cảnh
- ⚠️ Không học được

**Ví dụ:**
- "đăng tin" → Trả lời được ✅
- "làm sao để tạo bài viết tuyển người" → Không hiểu ❌

### Chế độ AI thật (sau khi có API key)
- ✅ Hiểu ngữ cảnh câu hỏi
- ✅ Trả lời linh hoạt, tự nhiên
- ✅ Có thể suy luận
- ✅ Học từ context
- ⚠️ Cần kết nối internet
- ⚠️ Chậm hơn 1-2 giây

**Ví dụ:**
- "đăng tin" → Trả lời được ✅
- "làm sao để tạo bài viết tuyển người" → Hiểu và trả lời ✅
- "tôi cần tìm người chăm sóc mẹ già" → Tư vấn chi tiết ✅

## 🎯 AI Model đang dùng

**BlenderBot-400M-distill** (Facebook/Meta)
- Model chatbot mã nguồn mở
- Miễn phí 100%
- Hỗ trợ đa ngôn ngữ (bao gồm tiếng Việt)
- Phù hợp cho tư vấn y tế cơ bản

## 💡 Mẹo sử dụng

### Câu hỏi AI trả lời tốt:
- "Triệu chứng đau đầu là gì?"
- "Cách chăm sóc người bệnh tiểu đường"
- "Tôi cần tìm sinh viên y khoa ở Hà Nội"
- "Làm sao để đánh giá sinh viên sau khi hoàn thành công việc?"

### Câu hỏi nên hỏi bác sĩ:
- Chẩn đoán bệnh cụ thể
- Kê đơn thuốc
- Tư vấn phẫu thuật
- Các trường hợp khẩn cấp

## 🔒 Bảo mật

- ✅ API key được lưu trong `config.php` (không commit lên Git)
- ✅ File `config.php` đã có trong `.gitignore`
- ✅ Không lưu trữ nội dung chat
- ✅ Tuân thủ GDPR

## ❓ Troubleshooting

### "Hệ thống AI chưa được cấu hình"
→ Chưa thêm API key vào config.php

### "Không thể kết nối với hệ thống AI"
→ Kiểm tra kết nối internet

### "Hệ thống AI đang bận"
→ Hugging Face API quá tải, tự động chuyển sang Fallback

### AI trả lời bằng tiếng Anh
→ Bình thường, model ưu tiên tiếng Anh. Hệ thống fallback sẽ bắt và trả lời tiếng Việt

## 📞 Hỗ trợ

Email: tramtankhatv@gmail.com

---

**Lưu ý:** API key Hugging Face hoàn toàn MIỄN PHÍ và không giới hạn request cho các model mã nguồn mở như BlenderBot.
