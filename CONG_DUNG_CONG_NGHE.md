# VAI TRÒ VÀ CÔNG DỤNG CỦA CÁC CÔNG NGHỆ TRONG WEBSITE KẾT NỐI Y TẾ

Tài liệu này giải thích chi tiết, dễ hiểu về vai trò thực tế của từng công nghệ (đã nêu trong Chương 2) đối với sự vận hành của website **Kết nối sinh viên y khoa và người cần chăm sóc sức khỏe tại nhà**. Mỗi công nghệ đều kèm theo ví dụ thực tế trong hệ thống.

---

### 1. Ngôn ngữ mô hình hóa UML (Unified Modeling Language)
* **Giúp ích gì cho website:** Giống như bản vẽ thiết kế kỹ thuật trước khi xây nhà. UML giúp lập trình viên định hình rõ website có những chức năng gì, ai được dùng chức năng nào và mã nguồn PHP được tổ chức như thế nào để không bị chồng chéo.
* **Ví dụ thực tế:** 
  * Sơ đồ Use Case giúp phân định: **Bệnh nhân** có quyền đăng tin tuyển dụng và đánh giá sinh viên; **Sinh viên y khoa** có quyền nộp hồ sơ ứng tuyển và điểm danh; **Admin** có quyền duyệt bài viết và xác minh thông tin.
  * Sơ đồ Class định nghĩa cấu trúc: Lớp `User` là lớp cha, lớp `Patient` và `Student` kế thừa lại các thuộc tính như `email`, `password_hash` để tiết kiệm mã nguồn.

### 2. Mô hình ERD và Cơ sở dữ liệu quan hệ (MySQL - InnoDB)
* **Giúp ích gì cho website:** Là kho lưu trữ toàn bộ dữ liệu của website (tài khoản, bài đăng, lịch hẹn, lịch sử chat) một cách khoa học, không bị trùng lặp, mất mát và đảm bảo tính an toàn dữ liệu khi có giao dịch.
* **Ví dụ thực tế:** 
  * **Tránh trùng lặp (Chuẩn hóa 3NF):** Khi bệnh nhân đăng bài tuyển dụng, hệ thống chỉ lưu mã số bệnh nhân (`patient_id`) trong bảng bài đăng, chứ không lưu lại họ tên, số điện thoại của bệnh nhân vào bảng đó. Nếu bệnh nhân đổi số điện thoại, hệ thống chỉ cần cập nhật ở bảng `users`, số điện thoại mới sẽ tự động hiển thị đúng ở mọi bài đăng.
  * **Tính giao dịch (ACID):** Khi bệnh nhân xác nhận thuê sinh viên, hệ thống phải thực hiện đồng thời: (1) Đổi trạng thái lịch hẹn thành "Đã nhận", (2) Gửi thông báo cho sinh viên. Công cụ **InnoDB** đảm bảo cả 2 việc này phải thành công cùng lúc. Nếu bước 2 lỗi, bước 1 sẽ tự động hủy (Rollback) để tránh tình trạng lịch hẹn đã nhận nhưng sinh viên không biết.

### 3. Bộ ba HTML5, CSS3 và JavaScript (Fetch API / AJAX)
* **Giúp ích gì cho website:** HTML5 tạo ra bộ khung của trang web, CSS3 làm cho giao diện đẹp đẽ, chuyên nghiệp, còn JavaScript giúp người dùng tương tác cực kỳ nhanh chóng mà không cần chờ tải lại trang.
* **Ví dụ thực tế:**
  * **HTML5:** Cung cấp các ô nhập lịch hẹn định dạng ngày/giờ chuẩn xác và các thẻ ngữ nghĩa giúp website dễ tìm thấy trên Google (SEO).
  * **CSS3:** Tạo ra các nút bấm nổi bật khi di chuột qua, các thông báo chuyển động mượt mà.
  * **JavaScript (AJAX):** Khi bệnh nhân và sinh viên nhắn tin trò chuyện với nhau, tin nhắn mới gửi đi và nhận về sẽ lập tức xuất hiện trên màn hình chat nhờ Fetch API chạy ngầm, người dùng không cần bấm F5 để tải lại trang.

### 4. Framework Bootstrap 5.3 (Responsive Design)
* **Giúp ích gì cho website:** Đảm bảo giao diện website hiển thị đẹp mắt và hoạt động hoàn hảo trên mọi thiết bị, đặc biệt là điện thoại di động (vì người nhà bệnh nhân và sinh viên thường dùng điện thoại khi chăm sóc y tế).
* **Ví dụ thực tế:**
  * Trên máy tính màn hình lớn, thanh menu quản lý (Sidebar) nằm bên trái và danh sách bài đăng chiếm phần lớn màn hình bên phải.
  * Khi mở bằng điện thoại, menu bên trái tự động thu gọn vào một nút bấm biểu tượng ba gạch (Hamburger menu) và các bài đăng tự động co lại thành một cột dọc vừa khít màn hình, chữ không bị tràn hay quá nhỏ.

### 5. Ngôn ngữ lập trình PHP (Session & Cookie)
* **Giúp ích gì cho website:** Là bộ não xử lý toàn bộ logic nghiệp vụ ở phía máy chủ. PHP chịu trách nhiệm nhận yêu cầu từ người dùng, kiểm tra quyền, truy vấn MySQL và quyết định hiển thị nội dung gì. Cơ chế Session giúp giữ trạng thái đăng nhập cho người dùng.
* **Ví dụ thực tế:**
  * Khi bạn đăng nhập tài khoản bệnh nhân thành công, PHP tạo một phiên làm việc riêng (Session) lưu trên máy chủ và gửi về trình duyệt một mã số (Session ID). 
  * Khi bạn bấm sang trang "Đăng tin tuyển dụng", PHP đọc mã số này để biết bạn đã đăng nhập và cho phép truy cập. Nếu người dùng chưa đăng nhập, PHP sẽ tự động chặn và chuyển hướng về trang Login.

### 6. Thư viện PDO Prepared Statements
* **Giúp ích gì cho website:** Bảo vệ website khỏi cuộc tấn công mạng nguy hiểm nhất thế giới web là **SQL Injection** (kẻ xấu chèn mã độc vào các ô nhập liệu để đánh cắp tài khoản hoặc xóa cơ sở dữ liệu).
* **Ví dụ thực tế:**
  * Nếu không dùng PDO, kẻ xấu có thể nhập vào ô tìm kiếm: `' OR '1'='1` để vượt qua vòng đăng nhập mà không cần mật khẩu.
  * Khi dùng PDO Prepared Statements, hệ thống sẽ gửi câu lệnh SQL rỗng lên cơ sở dữ liệu trước. Dữ liệu người dùng nhập (cho dù có chứa mã độc) cũng chỉ được MySQL hiểu là một chuỗi chữ thô vô hại để tìm kiếm, loại bỏ hoàn toàn khả năng mã độc bị thực thi.

### 7. Công nghệ truyền thông thời gian thực WebRTC
* **Giúp ích gì cho website:** Cho phép bệnh nhân/người nhà và sinh viên thực hiện các cuộc gọi video hoặc cuộc gọi thoại trực tiếp với nhau ngay trên trình duyệt web để trao đổi nhanh về tình hình bệnh án mà không cần cài đặt thêm phần mềm như Zoom hay Zalo.
* **Ví dụ thực tế:** 
  * Người nhà bệnh nhân bấm nút "Gọi Video" trên trang cá nhân của sinh viên đang chăm sóc. 
  * Hai bên có thể nhìn thấy và nói chuyện trực tiếp với nhau thông qua Camera và Mic của thiết bị. Đường truyền video/audio này chạy trực tiếp giữa hai máy khách (Peer-to-Peer) nên có độ trễ cực thấp và không làm tốn băng thông máy chủ của bạn.

### 8. Google Gemini API & Rào cản an toàn y tế (Guardrails)
* **Giúp ích gì cho website:** Cung cấp một trợ lý ảo y tế AI thông minh túc trực 24/7 để tư vấn sức khỏe ban đầu cho bệnh nhân và định hướng kỹ năng lâm sàng cho sinh viên, đồng thời bảo vệ người dùng bằng các quy tắc an toàn y khoa.
* **Ví dụ thực tế:**
  * Nếu người dùng hỏi: *"Làm thế nào để đăng tin tuyển sinh viên?"*, AI sẽ hướng dẫn từng bước thao tác trên website.
  * Nếu hỏi: *"Tôi bị đau đầu thì uống thuốc gì?"*, AI sẽ từ chối kê đơn cụ thể mà chỉ đưa ra lời khuyên chăm sóc sức khỏe tổng quát và khuyên đi khám bác sĩ.
  * Nếu phát hiện từ khóa nguy hiểm như *"đau ngực dữ dội, khó thở"*, AI lập tức dừng tư vấn thông thường và hiện cảnh báo đỏ: *"Đây là triệu chứng nguy kịch, hãy gọi cấp cứu 115 ngay lập tức!"*.

### 9. Cơ chế dự phòng chịu lỗi AI (Fallback Mechanism)
* **Giúp ích gì cho website:** Đảm bảo tính sẵn sàng cao (High Availability). Trợ lý AI vẫn hoạt động bình thường ngay cả khi mất kết nối mạng, hết hạn ngạch API miễn phí của Google Gemini hoặc máy chủ AI quá tải.
* **Ví dụ thực tế:**
  * Người dùng nhắn tin cho trợ lý ảo. 
  * Hệ thống gọi đến Google Gemini API nhưng gặp lỗi quá tải (Error 503).
  * Backend PHP ngay lập tức bắt lỗi này (Try-Catch) và tự động gửi yêu cầu sang máy chủ dự phòng Hugging Face (sử dụng mô hình BlenderBot).
  * Nếu Hugging Face cũng mất kết nối, hệ thống sẽ gọi hàm nội bộ tự động phân tích từ khóa trong câu hỏi để đưa ra câu trả lời offline soạn sẵn phù hợp nhất. Người dùng không hề biết có sự cố xảy ra.

### 10. Đóng gói Container Docker & Docker Compose
* **Giúp ích gì cho website:** Đóng gói toàn bộ mã nguồn website, cấu hình máy chủ Apache, phiên bản PHP 8.2 và MySQL thành một khối thống nhất. Giúp dự án chạy ổn định ở mọi máy tính của các thành viên khác hoặc máy chấm đồ án của giảng viên mà không bị lỗi cấu hình môi trường.
* **Ví dụ thực tế:**
  * Thay vì giảng viên phải cài đặt XAMPP thủ công, cấu hình Virtual Host, import database phức tạp (dễ bị lỗi do lệch phiên bản PHP hoặc MySQL hệ thống).
  * Giảng viên chỉ cần cài Docker và chạy duy nhất lệnh: `docker-compose up --build`. Chỉ sau 1 phút, toàn bộ website cùng cơ sở dữ liệu sẽ tự động khởi chạy hoàn hảo giống hệt 100% như trên máy tính của bạn.
