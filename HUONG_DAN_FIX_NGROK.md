# Hướng dẫn sửa lỗi Ngrok

## Vấn đề
Lỗi `ERR_NGROK_8012` xảy ra vì ngrok không kết nối được với localhost:80

## Giải pháp

### Bước 1: Kiểm tra Docker đang chạy
```cmd
docker ps
```
Đảm bảo container web đang chạy trên port 8080

### Bước 2: Dừng ngrok cũ (nếu đang chạy)
Nhấn `Ctrl+C` trong terminal ngrok

### Bước 3: Chạy ngrok với đúng port
```cmd
ngrok http 8080
```

### Bước 4: Copy URL ngrok mới
Sau khi ngrok chạy, bạn sẽ thấy URL dạng:
```
https://xxxx-xxxx-xxxx.ngrok-free.app
```

### Bước 5: Cập nhật config.php
Mở file `config.php` và thay đổi dòng:
```php
define('SITE_URL', 'https://speakable-rosamaria-foxily.ngrok-free.dev');
```
Thành URL ngrok mới của bạn.

## Nếu dùng XAMPP (không dùng Docker)
```cmd
ngrok http 80
```

## Kiểm tra
Truy cập URL ngrok mới trong trình duyệt để kiểm tra.
