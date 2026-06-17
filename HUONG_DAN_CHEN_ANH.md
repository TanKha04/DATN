# 🖼️ HƯỚNG DẪN CHÈN HÌNH ẢNH CHO CHƯƠNG 2

Tài liệu này hướng dẫn chi tiết vị trí, ý nghĩa và cách chèn **8 sơ đồ công nghệ** vào Chương 2 ("Cơ sở lý thuyết và công nghệ sử dụng") trong báo cáo đồ án tốt nghiệp của bạn dưới góc nhìn chuyên gia CNTT.

---

## 🎯 Danh sách 8 sơ đồ và Vị trí chèn chi tiết

### HÌNH 2.1: Sơ đồ tiến trình chuẩn hóa cơ sở dữ liệu
* **Mục:** **2.1.2** (Mô hình thực thể liên kết ERD và CSDL quan hệ RDBMS).
* **Vị trí chèn:** Ngay sau đoạn kết thúc định nghĩa **Dạng chuẩn 3 (3NF)**.
* **File ảnh gốc:** `images/db_normalization.svg`
* **Ý nghĩa học thuật:** Trực quan hóa tiến trình chuẩn hóa từ dạng bảng chưa cấu trúc (UNF) qua dạng nguyên tố (1NF), loại bỏ phụ thuộc bộ phận (2NF) và phụ thuộc bắc cầu (3NF) để minh chứng cơ sở dữ liệu của đồ án đã được thiết kế tối ưu, chống dư thừa dữ liệu.

### HÌNH 2.2: Luồng giao tiếp dữ liệu RESTful API qua HTTP và phản hồi JSON
* **Mục:** **2.2.1** (Bộ ba công nghệ nền tảng HTML5, CSS3 và JavaScript).
* **Vị trí chèn:** Ngay sau phần mô tả về **JavaScript và cơ chế bất đồng bộ AJAX**.
* **File ảnh gốc:** `images/restful_api_flow.svg`
* **Ý nghĩa học thuật:** Mô tả luồng hoạt động bất đồng bộ của ứng dụng. Client (Fetch API/AJAX) gửi các yêu cầu CRUD (GET, POST, PUT, DELETE) lên server và nhận về cấu trúc dữ liệu JSON để thay đổi giao diện động mà không cần tải lại trang.

### HÌNH 2.3: Sơ đồ tương tác cấu trúc Client-Server 3 tầng (3-Tier Architecture)
* **Mục:** **2.3.1** (Ngôn ngữ lập trình PHP và kiến trúc hoạt động).
* **Vị trí chèn:** Phía dưới mục PHP và trước mục cơ chế Session.
* **File ảnh gốc:** `images/client_server_3_tier.svg`
* **Ý nghĩa học thuật:** Biểu diễn kiến trúc hệ thống chia thành 3 lớp độc lập: Presentation Layer (Client), Logic Layer (Apache/PHP Web Server) và Data Layer (MySQL Database) nhằm tăng tính modular và bảo mật.

### HÌNH 2.4: Sơ đồ tuần tự quy trình xác thực phiên làm việc (Session Authentication)
* **Mục:** **2.3.1** (Ngôn ngữ lập trình PHP và kiến trúc hoạt động).
* **Vị trí chèn:** Ngay dưới phần giải thích cơ chế **Session** (Kết thúc mục 2.3.1).
* **File ảnh gốc:** `images/session_auth_flow.svg`
* **Ý nghĩa học thuật:** Mô tả tuần tự các bước xác thực phiên: Client đăng nhập thành công $\rightarrow$ Server khởi tạo Session và gửi Session ID về lưu ở Cookie máy khách $\rightarrow$ Các request sau tự động gửi kèm Session ID để xác thực quyền truy cập.

### HÌNH 2.5: Sơ đồ cơ chế hoạt động của PDO Prepared Statements chống SQL Injection
* **Mục:** **2.3.2** (Kết nối CSDL an toàn bằng PHP Data Objects).
* **Vị trí chèn:** Ngay dưới đoạn mã code PHP minh họa prepared statements.
* **File ảnh gốc:** `images/prepared_statements.svg`
* **Ý nghĩa học thuật:** So sánh trực quan sự nguy hiểm của cơ chế nối chuỗi trực tiếp (dẫn tới lỗ hổng SQL Injection làm thay đổi cấu trúc câu lệnh) đối lập với cơ chế an toàn của Prepared Statements (CSDL biên dịch khung SQL trước, nạp tham số thô sau nên an toàn tuyệt đối).

### HÌNH 2.6: Sơ đồ luồng tín hiệu báo hiệu (Signaling) và thiết lập kết nối WebRTC P2P
* **Mục:** **2.4.2** (Quy trình Signaling và vượt tường lửa NAT Traversal).
* **Vị trí chèn:** Ngay sau phần giải thích về hạ tầng vượt tường lửa **STUN/TURN và ICE**.
* **File ảnh gốc:** `images/webrtc_flow.svg`
* **Ý nghĩa học thuật:** Mô tả quy trình thiết lập cuộc gọi video: Hai trình duyệt trao đổi các gói SDP Offer/Answer và ICE Candidates thông qua Signaling Server (PHP), sau khi bắt tay thành công sẽ thiết lập đường truyền video/audio trực tiếp P2P không qua server.

### HÌNH 2.7: Sơ đồ luồng dự phòng chịu lỗi (AI Fallback & Local Fallback Flow) của trợ lý ảo
* **Mục:** **2.5.3** (Hugging Face Inference API và Cơ chế dự phòng chịu lỗi).
* **Vị trí chèn:** Cuối mục 2.5.3 (sau phần mô tả logic try-catch).
* **File ảnh gốc:** `images/ai_fallback_flow.svg`
* **Ý nghĩa học thuật:** Minh chứng kiến trúc chịu lỗi (Fault Tolerance) của hệ thống: Khi API chính Google Gemini gặp sự cố, hệ thống tự động bắt ngoại lệ chuyển tiếp sang Hugging Face (BlenderBot), nếu vẫn lỗi sẽ kích hoạt dữ liệu phản hồi offline nội bộ.

### HÌNH 2.8: Sơ đồ so sánh kiến trúc giữa máy ảo Virtual Machine và Docker Container
* **Mục:** **2.6.1** (Công nghệ Containerization và ảo hóa cấp độ hệ điều hành).
* **Vị trí chèn:** Ngay sau đoạn phân tích so sánh giữa Container và VM.
* **File ảnh gốc:** `images/docker_vs_vm.svg`
* **Ý nghĩa học thuật:** Thể hiện rõ ưu thế hiệu năng của Docker: thay vì chạy một hệ điều hành khách (Guest OS) cồng kềnh như VM, Docker dùng chung nhân Host OS thông qua namespace/cgroup giúp khởi động nhanh và cực nhẹ.

---

## 🛠️ Hướng dẫn chèn ảnh vào Microsoft Word (Dễ dàng nhất)

Để giữ nguyên định dạng, cỡ chữ Times New Roman 13pt và các thụt đầu dòng chuẩn học thuật khi đưa vào Word, bạn hãy thực hiện theo cách sau:

1. **Mở file HTML bằng trình duyệt:**
   * Tìm file `CHUONG_2_WORD.html` trong thư mục dự án và nhấp đúp để mở bằng Chrome hoặc Edge.
2. **Sao chép nội dung:**
   * Nhấn tổ hợp phím **Ctrl + A** (chọn toàn bộ trang) và nhấn **Ctrl + C** (sao chép).
3. **Dán vào Microsoft Word:**
   * Mở file Word đồ án của bạn, đặt con trỏ tại Chương 2 và nhấn **Ctrl + V** (hoặc chọn kiểu dán *Keep Source Formatting*).
4. **Chèn ảnh vào khung trống:**
   * Tại các khung viền nét đứt có ghi `[KHOẢNG TRỐNG CHÈN ẢNH VÀO WORD]`, hãy xóa dòng chữ đó đi.
   * Chọn thẻ **Insert** $\rightarrow$ **Pictures** trên Word và tìm chọn file ảnh tương ứng trong thư mục `images/` của dự án (Word hỗ trợ chèn trực tiếp file định dạng `.svg` rất sắc nét không bị vỡ hình).
   * Căn giữa hình ảnh và điều chỉnh kích thước vừa vặn với trang viết.
