1.3. ĐỐI TƯỢNG NGHIÊN CỨU

    Nghiên cứu tập trung vào ba nhóm đối tượng cốt lõi sau đây nhằm xây dựng giải pháp kết nối y khoa tối ưu, an toàn và có tính thực tiễn cao:

1.3.1. Đối tượng người dùng chính của hệ thống

    - Đối tượng bệnh nhân và người thân bệnh nhân:
        + Đặc trưng: Là cá nhân hoặc hộ gia đình có nhu cầu tìm kiếm người hỗ trợ chăm sóc sức khỏe cơ bản, điều dưỡng hoặc phục hồi chức năng tại gia đình (đặc biệt hướng tới người cao tuổi, người mắc bệnh mãn tính, hoặc người bệnh sau phẫu thuật cần hỗ trợ y tế cơ bản).
        + Hành vi nghiên cứu: Khảo sát nhu cầu tìm kiếm thực tế, phương thức đăng tải tin tuyển dụng kèm bệnh án minh chứng, tính minh bạch khi cung cấp thông tin y khoa, quá trình giao tiếp trực tuyến và hành vi đánh giá chất lượng dịch vụ hai chiều sau khi hoàn thành ca trực.

    - Đối tượng sinh viên Y khoa:
        + Đặc trưng: Sinh viên đang theo học hệ chính quy tại các trường Đại học Y Dược thuộc nhiều chuyên ngành khác nhau như Y khoa, Điều dưỡng, Y học dự phòng, Kỹ thuật y học.
        + Hành vi nghiên cứu: Khảo sát nhu cầu thực hành lâm sàng thực tế ngoài giờ học để tích lũy kinh nghiệm chuyên môn, nhu cầu gia tăng thu nhập trang trải sinh hoạt phí, quy trình thực hiện xác thực thông tin thẻ sinh viên thông qua quản trị viên, và hiệu quả tương tác hỗ trợ bệnh nhân qua các kênh trực tuyến.

1.3.2. Đối tượng công nghệ và giải pháp kết nối chuyên biệt

    Nghiên cứu tập trung vào các giải pháp kỹ thuật nhằm hiện thực hóa mô hình kết nối an toàn, bao gồm:
    - Cơ chế xác thực độ tin cậy: Quy trình tải lên và kiểm duyệt ảnh thẻ sinh viên tự động kết hợp thủ công để ngăn chặn giả mạo danh tính.
    - Công cụ tương tác thời gian thực: Giải pháp giao tiếp đa kênh trực quan gồm nhắn tin nội bộ và cuộc gọi Video WebRTC Peer-to-Peer trực tiếp trên trình duyệt.
    - Trợ lý ảo y tế thông minh (AI Assistant): Ứng dụng mô hình ngôn ngữ lớn qua API để hỗ trợ tư vấn các kiến thức y khoa cơ bản và hướng dẫn quy trình vận hành hệ thống 24/7.

1.3.3. Đối tượng khảo sát so sánh (Các nền tảng hiện hữu)

    Nghiên cứu tiến hành khảo sát và phân tích so sánh các giải pháp kết nối y tế tự phát hiện nay:
    - Các hội nhóm cộng đồng tự phát trên mạng xã hội như Facebook, Zalo: Kết nối nhanh chóng nhưng thiếu kiểm chứng thẻ sinh viên, tiềm ẩn nguy cơ mất an toàn thông tin và lừa đảo.
    - Các trang web tìm việc làm phổ thông hoặc ứng dụng chăm sóc sức khỏe hiện hành: Không chuyên biệt cho ngành y tế, thiếu cơ chế quản lý lịch hẹn chi tiết và phản hồi hai chiều.
    - Mục tiêu nghiên cứu: Làm rõ các ưu điểm và hạn chế lớn để làm cơ sở đề xuất thiết kế kiến trúc hệ thống mới tối ưu hơn, đảm bảo tính ứng dụng cao và giao diện thân thiện với người dùng.

--------------------------------------------------------------------------------

SƠ ĐỒ MÔ HÌNH HÓA ĐỐI TƯỢNG NGHIÊN CỨU

(Chú thích hình ảnh: Sơ đồ phân tích đối tượng nghiên cứu và luồng tương tác của đề tài - File ảnh tương ứng: doi_tuong_nghien_cuu.svg)

[KHOẢNG TRỐNG DÀNH CHO HÌNH ẢNH]

================================================================================

1.4. PHẠM VI NGHIÊN CỨU

    Phạm vi nghiên cứu của đề tài được giới hạn cụ thể nhằm đảm bảo tính khả thi trong việc phân tích, thiết kế và xây dựng sản phẩm phần mềm, tập trung tối ưu hóa các chức năng cốt lõi và hạ tầng thử nghiệm trong khuôn khổ của một đồ án chuyên ngành.

1.4.1. Phạm vi về chức năng hệ thống

    Đề tài tập trung nghiên cứu, xây dựng và hoàn thiện các chức năng kết nối cơ bản bao gồm:
    - Nhóm chức năng tài khoản: Đăng ký, đăng nhập, phân quyền người dùng (bệnh nhân, sinh viên, quản trị viên) và quản lý hồ sơ cá nhân.
    - Nhóm chức năng kết nối: Đăng tin tuyển dụng tìm người chăm sóc (đối với bệnh nhân), đăng tin ứng tuyển nhận việc (đối với sinh viên y khoa).
    - Nhóm chức năng tương tác: Hỗ trợ tìm kiếm, lọc tin đăng theo các tiêu chí (chuyên khoa, khu vực địa lý, loại bài đăng), bình luận trao đổi công khai dưới bài viết, nhắn tin trao đổi thông tin nội bộ.
    - Nhóm chức năng quản lý và xác thực: Đặt lịch hẹn tự động (Appointments), đánh giá chất lượng hai chiều (Rating và Review) sau ca trực, và quy trình xác thực danh tính thẻ sinh viên thông qua bảng quản trị (Admin Dashboard).

1.4.2. Phạm vi về kỹ thuật và hạ tầng triển khai

    - Công nghệ phát triển: Ứng dụng ngôn ngữ backend PHP thuần kết nối hệ quản trị cơ sở dữ liệu MySQL thông qua PDO; thiết kế giao diện đáp ứng (Responsive Web Design) sử dụng HTML, CSS và framework Bootstrap 5.
    - Môi trường vận hành: Hệ thống được cấu hình, chạy thử nghiệm và kiểm thử cục bộ (Localhost) trên máy tính cá nhân thông qua gói XAMPP và ảo hóa container Docker.
    - Giới hạn hạ tầng: Đề tài chưa tối ưu hóa cho số lượng lớn người dùng đồng thời, chưa tích hợp các giải pháp hạ tầng phân tán nâng cao như cân bằng tải (Load Balancing), bộ nhớ đệm (Caching) hay mạng phân phối nội dung (CDN).

1.4.3. Các nội dung nằm ngoài phạm vi nghiên cứu (Out of Scope)

    Để đảm bảo tính tập trung và hoàn thành đúng tiến độ, đề tài không thực hiện nghiên cứu và tích hợp các nội dung sau:
    - Tích hợp các cổng thanh toán trực tuyến tự động (như MoMo, VNPay, ZaloPay) để giao dịch tài chính trực tiếp trên web.
    - Liên thông dữ liệu bệnh án điện tử với các hệ thống quản lý bệnh viện chuyên nghiệp (HIS, EMR).
    - Các chức năng tư vấn y khoa chuyên sâu hoặc cuộc gọi video y tế có tính pháp lý cao.
    - Triển khai chính thức hệ thống lên môi trường Internet toàn cầu hoặc thuê máy chủ đám mây thương mại (Cloud Server).
    - Cơ chế bảo đảm pháp lý tự động cho các thỏa thuận lao động giữa bệnh nhân và sinh viên.
    - Toàn bộ dữ liệu kiểm thử hệ thống là dữ liệu giả lập hoặc do người dùng tự nhập thủ công, không sử dụng dữ liệu bệnh án thực tế của bất kỳ cơ sở y tế nào.

--------------------------------------------------------------------------------

SƠ ĐỒ PHÂN TÍCH PHẠM VI NGHIÊN CỨU ĐỀ TÀI

(Chú thích hình ảnh: Sơ đồ phân giới các chức năng và giới hạn kỹ thuật trong và ngoài phạm vi nghiên cứu - File ảnh tương ứng: pham_vi_nghien_cuu.svg)

[KHOẢNG TRỐNG DÀNH CHO HÌNH ẢNH]

================================================================================

CHƯƠNG 2: CƠ SỞ LÝ THUYẾT VÀ CÔNG NGHỆ SỬ DỤNG

2.1. Mô hình Client-Server và kiến trúc 3 tầng (3-Tier Architecture)

    Mô hình Client-Server là một kiến trúc mạng phân tán, trong đó các nhiệm vụ hoặc khối lượng công việc được phân chia giữa nhà cung cấp tài nguyên hoặc dịch vụ (Server) và người yêu cầu dịch vụ (Client). Trong đề tài này, trình duyệt web đóng vai trò là Client, chịu trách nhiệm nhận các thao tác của người dùng, đóng gói thành các yêu cầu HTTP (HTTP Requests) gửi qua Internet. Máy chủ web Apache chạy PHP đóng vai trò là Server, thực thi logic nghiệp vụ và tương tác với MySQL để sinh kết quả phản hồi HTTP (HTTP Responses) chứa dữ liệu định dạng HTML/CSS hoặc JSON về cho client hiển thị.

    Hệ thống được phát triển theo mô hình kiến trúc Client-Server 3 tầng (3-Tier Architecture) nhằm tăng khả năng bảo trì, độ tin cậy và tính độc lập giữa các lớp xử lý:
    - Tầng Presentation Layer (Tầng hiển thị): Gồm toàn bộ mã nguồn HTML5, CSS3, JavaScript tương tác kết hợp thư viện Bootstrap 5. Tầng này vận hành trực tiếp trên trình duyệt của người dùng (Bệnh nhân, Sinh viên Y khoa, Admin) để hiển thị giao diện bảng điều khiển, danh sách tin tuyển dụng, ô chat nội bộ và widget trợ lý ảo AI. Tầng này đảm nhận việc thu nhận sự kiện từ người dùng (như nhấn nút ứng tuyển, đo huyết áp, mở video call), thực hiện kiểm tra dữ liệu đầu vào cơ bản (form validation) và gửi yêu cầu không đồng bộ AJAX lên máy chủ mà không cần tải lại trang.
    - Tầng Business Logic Layer (Tầng xử lý nghiệp vụ): Vận hành trên máy chủ web Apache chạy PHP 8.1. Đây là trung tâm đầu não của ứng dụng, chịu trách nhiệm thực thi các quy tắc nghiệp vụ (Business Rules) của hệ thống bao gồm: phân biệt vai trò tài khoản dựa trên đuôi email đăng ký (edu.vn), băm mật khẩu bảo mật bằng thuật toán bcrypt, kiểm soát quyền truy cập theo Session, quản lý quy trình xét duyệt hồ sơ sinh viên của Admin, gửi email thông báo qua SMTP, điều phối luồng Signaling của cuộc gọi video và giao tiếp API với các máy chủ đám mây của Google Gemini và Hugging Face.
    - Tầng Data Access Layer (Tầng truy cập dữ liệu): Bao gồm hệ quản trị cơ sở dữ liệu quan hệ MySQL 8.0. Tầng này chịu trách nhiệm trực tiếp lưu trữ, lập chỉ mục và đảm bảo tính nhất quán của toàn bộ dữ liệu hệ thống (người dùng, bài đăng, tin nhắn, lịch hẹn, đánh giá, log hoạt động). Logic nghiệp vụ PHP tương tác với tầng này thông qua thư viện kết nối PDO (PHP Data Objects) sử dụng cơ chế an toàn Prepared Statements để ngăn chặn các cuộc tấn công khai thác cơ sở dữ liệu.

--------------------------------------------------------------------------------

SƠ ĐỒ TƯƠNG TÁC CLIENT - SERVER 3 TẦNG

(Chú thích hình ảnh: Sơ đồ phân tích luồng tương tác giữa các tầng Presentation Layer, Business Logic Layer và Data Access Layer - File ảnh tương ứng: client_server_3_tier.svg)

[KHOẢNG TRỐNG DÀNH CHO HÌNH ẢNH]

--------------------------------------------------------------------------------

2.2. Ngôn ngữ lập trình PHP và kết nối cơ sở dữ liệu PDO

    PHP (Hypertext Preprocessor) là ngôn ngữ lập trình kịch bản mã nguồn mở chạy phía máy chủ, được thiết kế tối ưu hóa cho mục đích phát triển ứng dụng web động. Hoạt động theo cơ chế Server-Side Rendering (kết xuất phía máy chủ), PHP tiếp nhận HTTP Request từ Client, biên dịch mã nguồn để thực thi các tác vụ logic nghiệp vụ như xử lý biến Session, kiểm duyệt upload ảnh thẻ sinh viên, tạo thông báo hệ thống, sau đó kết xuất mã HTML tĩnh sạch gửi trả về trình duyệt để kết xuất giao diện. PHP 8.1 cung cấp hiệu suất cao, cấu trúc lập trình hướng đối tượng vững chắc và tích hợp các cơ chế bảo mật phiên mạnh mẽ.

    Để giao tiếp với cơ sở dữ liệu MySQL, hệ thống sử dụng thư viện PDO (PHP Data Objects) - một lớp trừu tượng hóa cơ sở dữ liệu cung cấp giao thức truy vấn chung thống nhất. Điểm mấu chốt của PDO trong thiết kế hệ thống y tế là việc ép buộc áp dụng Prepared Statements (câu lệnh chuẩn bị trước) kết hợp Parameter Binding (ràng buộc tham số) để giải quyết triệt để lỗ hổng bảo mật nghiêm trọng SQL Injection:
    - SQL Injection là kỹ thuật tấn công chèn các chuỗi truy vấn dữ liệu độc hại qua các trường đầu vào (như email đăng nhập, từ khóa tìm kiếm) để đánh lừa máy chủ CSDL thực thi các truy vấn trái phép nhằm rò rỉ dữ liệu hoặc phá hủy bảng.
    - Với Prepared Statements, khung câu lệnh SQL được gửi lên MySQL biên dịch trước mà không chứa dữ liệu. Khi dữ liệu của người dùng được bind vào các placeholder (dấu `?` hoặc khóa đặt tên), hệ thống MySQL sẽ xử lý chúng dưới dạng các tham số giá trị văn bản thuần túy (literals), hoàn toàn không thể biên dịch thành lệnh SQL thực thi, bảo vệ an toàn cho cơ sở dữ liệu.

2.3. Hệ quản trị cơ sở dữ liệu MySQL

    MySQL là hệ quản trị cơ sở dữ liệu quan hệ (RDBMS) mã nguồn mở sử dụng ngôn ngữ truy vấn có cấu trúc SQL để quản lý dữ liệu. Nó tổ chức thông tin dưới dạng các thực thể bảng có cấu trúc cố định, thiết lập mối liên kết chặt chẽ thông qua các ràng buộc khóa chính (Primary Key) xác định tính duy nhất của hàng dữ liệu và khóa ngoại (Foreign Key) để đảm bảo tính toàn vẹn tham chiếu chéo giữa các bảng (ví dụ liên kết trường `user_id` của bảng `posts` tới cột `id` trong bảng `users`). MySQL 8.0 hỗ trợ xử lý đa luồng đồng thời, cơ chế lưu trữ giao dịch (Transactions) để đảm bảo tính nhất quán dữ liệu ACID khi thiết lập lịch hẹn y tế và khả năng thực thi các truy vấn tìm kiếm/lọc khu vực địa lý nhanh chóng.

2.4. Thư viện thiết kế giao diện Bootstrap

    Bootstrap là framework front-end mã nguồn mở hỗ trợ phát triển nhanh các trang giao diện đáp ứng (Responsive) và tương thích cao đa trình duyệt. Bootstrap 5 hoạt động dựa trên triết lý Mobile-First (ưu tiên thiết bị di động trước), sử dụng hệ thống lưới Flexbox (Grid System) chia chiều rộng giao diện thành 12 cột ảo kết hợp các điểm ngắt Breakpoints xác định trước (`sm`, `md`, `lg`, `xl`, `xxl`). 

    Nhờ hệ lưới linh hoạt này, lập trình viên có thể cấu hình giao diện tự động thích ứng:
    - Trên màn hình máy tính (màn hình lớn), bảng điều khiển hiển thị theo cấu trúc nhiều cột song song để tối ưu không gian hiển thị thông tin chuyên môn.
    - Trên màn hình điện thoại di động, các khối thông tin tự động co giãn và xếp chồng dọc lên nhau (`col-12`) giúp các nút bấm y tế, ô nhập tin nhắn và widget trợ lý AI hiển thị to rõ ràng, phù hợp cho người cao tuổi hoặc người nhà bệnh nhân thao tác tại nhà dễ dàng bằng một tay.
    - Bootstrap cung cấp sẵn hệ thống component UI chuẩn hóa (Cards lưu trữ bài đăng, Modals hiển thị hộp thoại, Alerts hiện thông báo thành công/lỗi) giúp đảm bảo sự đồng nhất và chuyên nghiệp về mặt thẩm mỹ của sản phẩm.

2.5. Kiến trúc giao tiếp RESTful API và công cụ kiểm thử Postman

    RESTful API là kiến trúc thiết kế giao thức kết nối hệ thống dựa trên tiêu chuẩn của giao thức HTTP, định hướng việc quản lý các tài nguyên (Resources) như bài đăng, tin nhắn, dữ liệu sinh viên thông qua các phương thức HTTP chuẩn hóa tương đương quy trình CRUD:
    - `GET` (Đọc dữ liệu): Lấy danh sách tin tuyển dụng hoặc chi tiết lịch hẹn.
    - `POST` (Tạo mới dữ liệu): Đăng bài viết mới, gửi yêu cầu xác thực thẻ sinh viên.
    - `PUT` / `PATCH` (Cập nhật dữ liệu): Xác nhận hoàn thành lịch hẹn, đổi trạng thái duyệt.
    - `DELETE` (Xóa dữ liệu): Ẩn bài đăng vi phạm hoặc xóa phiên chat AI.
    Dữ liệu được trao đổi bất đồng bộ giữa Client và Server thông qua định dạng JSON (JavaScript Object Notation) gọn nhẹ, giúp hệ thống hoạt động trơn tru, giảm thiểu băng thông mạng truyền tải.

    Để phát triển và kiểm định chất lượng các API này trước khi tích hợp vào giao diện, công cụ Postman được sử dụng để giả lập các HTTP Requests, gửi các tham số đầu vào và phân tích cấu trúc dữ liệu JSON phản hồi. Postman hỗ trợ kiểm thử tự động, quản lý biến môi trường, đảm bảo các endpoint API của hệ thống (như tìm kiếm sinh viên, gửi tin nhắn) luôn hoạt động ổn định và chính xác.

--------------------------------------------------------------------------------

SƠ ĐỒ QUY TRÌNH GIAO TIẾP RESTFUL API & KIỂM THỬ POSTMAN

(Chú thích hình ảnh: Sơ đồ luồng gửi yêu cầu HTTP CRUD và nhận dữ liệu phản hồi JSON được giả lập kiểm thử bằng Postman - File ảnh tương ứng: restful_api_flow.svg)

[KHOẢNG TRỐNG DÀNH CHO HÌNH ẢNH]

--------------------------------------------------------------------------------

2.6. Cơ chế xác thực phiên làm việc Session Authentication

    Session Authentication là phương thức quản lý trạng thái đăng nhập của người dùng dựa trên bộ nhớ phía máy chủ. Do giao thức HTTP là giao thức không lưu trạng thái (Stateless), mọi yêu cầu gửi lên server độc lập với nhau. Cơ chế Session Authentication giải quyết vấn đề này bằng cách tạo ra một phiên làm việc duy nhất trên máy chủ khi người dùng đăng nhập thành công và cấp một mã khóa định danh ngẫu nhiên gọi là Session ID gửi về trình duyệt lưu trữ dưới dạng Cookie bảo mật.

    Trong các yêu cầu tiếp theo, trình duyệt tự động đính kèm Session ID Cookie này lên server. Backend PHP sẽ đối chiếu mã Session ID này với vùng nhớ Session trên máy chủ để nhận diện danh tính và vai trò người dùng mà không yêu cầu nhập lại thông tin. Cơ chế này giúp bảo vệ tuyệt đối các thông tin nhạy cảm của người dùng (như mật khẩu, quyền admin) tránh bị lộ ra phía client. Hệ thống phân quyền rõ ràng 3 vai trò: bệnh nhân (`patient`), sinh viên y khoa (`student`) và quản trị viên (`admin`) để điều phối hiển thị chức năng phù hợp.

--------------------------------------------------------------------------------

SƠ ĐỒ QUY TRÌNH XÁC THỰC PHIÊN LÀM VIỆC (SESSION AUTHENTICATION)

(Chú thích hình ảnh: Sơ đồ tuần tự các bước thiết lập Session ID và đối chiếu quyền truy cập giữa Client và Server - File ảnh tương ứng: session_auth_flow.svg)

[KHOẢNG TRỐNG DÀNH CHO HÌNH ẢNH]

--------------------------------------------------------------------------------

2.7. Nền tảng ảo hóa container Docker

    Docker là nền tảng ảo hóa ở cấp độ hệ điều hành (Containerization) cho phép đóng gói toàn bộ mã nguồn ứng dụng, tệp tin cấu hình máy chủ Apache/PHP và hệ quản trị MySQL thành các container tiêu chuẩn hóa, độc lập. Không giống như Máy ảo truyền thống (Virtual Machine) vốn đòi hỏi cài đặt một hệ điều hành khách (Guest OS) đầy đủ chạy trên Hypervisor gây tiêu tốn tài nguyên và khởi động lâu, các Docker Container chia sẻ chung nhân của hệ điều hành máy chủ vật lý bên dưới. Việc này giúp các container khởi động tức thì chỉ trong vài giây, chiếm dụng dung lượng lưu trữ cực kỳ thấp. 
    
    Quy trình thiết lập môi trường được đặc tả chi tiết trong tệp `Dockerfile` và phối hợp các container (Web Server và Database Server) thông qua `docker-compose.yml`. Docker đảm bảo tính đồng nhất môi trường phát triển tuyệt đối từ máy tính lập trình cá nhân đến máy chủ trình chiếu trước hội đồng đồ án tốt nghiệp, loại bỏ hoàn toàn các lỗi lệch thư viện hoặc sai lệch phiên bản PHP.

2.8. Công nghệ giao tiếp thời gian thực WebRTC

    WebRTC (Web Real-Time Communication) là tập hợp các tiêu chuẩn và giao thức mạng cho phép thiết lập kết nối truyền tải luồng video, âm thanh và dữ liệu thời gian thực trực tiếp ngang hàng (Peer-to-Peer - P2P) giữa hai trình duyệt mà không cần cài đặt ứng dụng phụ trợ hay qua máy chủ trung chuyển luồng stream. WebRTC cung cấp băng thông truyền dữ liệu độ trễ cực thấp và bảo mật tối đa nhờ mã hóa đầu cuối (End-to-End Encryption). Ba API JavaScript chính cấu thành WebRTC gồm: `getUserMedia` (thu hình ảnh/âm thanh từ webcam và microphone), `RTCPeerConnection` (quản lý kết nối mạng P2P, thiết lập mã hóa, kiểm soát băng thông), và `RTCDataChannel` (truyền nhận dữ liệu thô thời gian thực).

    Trong đồ án này, do triển khai trên môi trường PHP Apache thuần, cơ chế Signaling (trao đổi gói tin SDP mô tả Offer/Answer và các tọa độ mạng ICE Candidates ban đầu) được giải quyết qua giải pháp **Database-based Signaling** kết hợp kỹ thuật **AJAX Polling**:
    - Khi có cuộc gọi, client của người gọi lưu gói tin Offer và ICE Candidates vào bảng CSDL `call_signals` qua AJAX POST gửi lên `call_signaling.php`.
    - Client người nhận liên tục gửi yêu cầu HTTP GET thăm dò (sau mỗi 1.5 - 2 giây) để phát hiện gói tín hiệu mới, trích xuất dữ liệu và khởi động tiến trình bắt tay WebRTC ngang hàng để truyền phát video cuộc gọi trực tuyến.

--------------------------------------------------------------------------------

SƠ ĐỒ QUY TRÌNH KẾT NỐI CUỘC GỌI VIDEO CALL WEBRTC

(Chú thích hình ảnh: Sơ đồ báo hiệu qua Signaling Server và thiết lập luồng truyền dữ liệu trực tiếp Peer-to-Peer - File ảnh tương ứng: webrtc_flow.svg)

[KHOẢNG TRỐNG DÀNH CHO HÌNH ẢNH]

--------------------------------------------------------------------------------

2.9. Tích hợp Trí tuệ nhân tạo (Gemini AI và Hugging Face APIs)

    Tích hợp trí tuệ nhân tạo tạo sinh (Generative AI) và các mô hình ngôn ngữ lớn (LLM) thông qua API đám mây giúp website cung cấp tính năng tư vấn y học cơ bản 24/7 mà không đòi hỏi tài nguyên phần cứng lớn chạy cục bộ. Hệ thống kết nối đồng thời với Google Gemini API (`gemini-2.5-flash`) và Hugging Face Inference API (`Meta BlenderBot-400M-distill`) để xây dựng widget AI Assistant:
    - Khi người dùng trò chuyện, backend PHP gửi tin nhắn kèm lịch sử hội thoại lên Google Gemini API để nhận về kết quả tư vấn y tế hoặc hướng dẫn vận hành hệ thống.
    - Nhằm nâng cao tính chịu lỗi (Fault Tolerance) của hệ thống, backend được thiết lập cơ chế chịu lỗi tự động (Fallback mechanism): nếu API của Gemini bị lỗi kết nối hoặc vượt hạn định mức gọi miễn phí, hệ thống tự động catch lỗi và chuyển hướng yêu cầu tới mô hình phụ BlenderBot trên Hugging Face API hoặc gọi hàm trả lời từ khóa cục bộ, đảm bảo trợ lý ảo luôn túc trực hỗ trợ người dùng trong mọi hoàn cảnh.

2.10. Lý thuyết về Chăm sóc y tế tại nhà và Giới hạn chuyên môn của sinh viên Y khoa

    Dịch vụ chăm sóc y tế tại nhà (Home Healthcare) đóng vai trò then chốt trong hệ thống y tế hiện đại, giúp giảm tải đáng kể cho các bệnh viện công lập và tiết kiệm chi phí cho bệnh nhân cần phục hồi thể trạng sau phẫu thuật, người cao tuổi hoặc người mắc bệnh mãn tính. 

    Tuy nhiên, do sinh viên Y khoa chưa tốt nghiệp và chưa được cấp chứng chỉ hành nghề y khoa độc lập, đồ án nghiên cứu xác định rõ giới hạn chuyên môn lâm sàng nghiêm ngặt nhằm tuân thủ pháp luật và đạo đức nghề y:
    - Các dịch vụ sinh viên được thực hiện tại nhà: Các hoạt động điều dưỡng cơ bản (đo huyết áp, đo đường huyết, theo dõi mạch nhiệt độ), hỗ trợ vận động vật lý trị liệu cơ bản, và các thủ thuật xâm lấn nhẹ (tiêm thuốc, truyền dịch, thay băng rửa vết thương) **chỉ được phép thực hiện khi và chỉ khi có y lệnh viết tay hoặc đơn chỉ định y tế chính thức** từ bác sĩ điều trị của người bệnh.
    - Giới hạn cấm: Sinh viên tuyệt đối không được tự ý đưa ra kết luận chẩn đoán lâm sàng, tự kê đơn thuốc điều trị mới cho bệnh nhân hoặc thực hiện các phẫu thuật phức tạp vượt thẩm quyền pháp lý.

2.11. Lý thuyết về Y tế từ xa (Telehealth) và Vai trò của Giao tiếp trực quan

    Y tế từ xa (Telehealth) là việc cung cấp các dịch vụ chăm sóc sức khỏe, theo dõi lâm sàng và tư vấn giáo dục y học thông qua các công nghệ truyền thông từ xa. Trong đó, giao tiếp trực quan thông qua truyền hình trực tiếp (Video Call) và hình ảnh y tế đóng vai trò cốt lõi. Trong nền tảng kết nối y tế này, tính năng giao tiếp trực quan giải quyết hai thách thức lớn:
    - Về mặt an toàn: Cho phép bệnh nhân và người nhà kiểm chứng trực quan khuôn mặt của sinh viên y khoa so với ảnh thẻ sinh viên đã được Admin kiểm duyệt xác minh trên web trước khi cho phép vào nhà chăm sóc sức khỏe trực tiếp.
    - Về mặt nghiệp vụ: Cho phép sinh viên xem trước tình trạng vết thương thực tế của người bệnh hoặc đơn thuốc, y lệnh đính kèm để chuẩn bị đúng loại bông băng, dung dịch sát khuẩn, ống tiêm và trang thiết bị bảo hộ y khoa phù hợp nhất trước khi di chuyển đến nhà bệnh nhân.

2.12. Nguyên tắc an toàn và Giới hạn pháp lý của Trí tuệ nhân tạo (AI) trong Y tế

    Ứng dụng AI dưới dạng các chatbot đàm thoại hỗ trợ giáo dục sức khỏe ngày càng phổ biến. Tuy nhiên, do liên quan trực tiếp đến tính mạng con người, hệ thống AI trong đồ án tuân thủ nghiêm ngặt kỹ thuật Prompt Engineering chuyên biệt để xây dựng các hàng rào phòng vệ (Guardrails):
    - AI đóng vai trò cung cấp thông tin y khoa mang tính chất tham khảo, tư vấn phòng bệnh và sơ cứu ban đầu theo các tài liệu chuẩn hóa, không được phép đưa ra chẩn đoán lâm sàng thay thế bác sĩ hay kê đơn thuốc có liều lượng chi tiết.
    - AI thiết lập **Giao thức Red Flags (Cảnh báo nguy kịch)**: tự động nhận diện các triệu chứng đe dọa tính mạng (đau ngực dữ dội, khó thở cấp, liệt méo miệng đột ngột) để ép buộc hiển thị thông tin cảnh báo người bệnh dừng tư vấn trực tuyến và liên hệ ngay cấp cứu 115. AI luôn đính kèm tuyên bố từ chối trách nhiệm y tế pháp lý ở cuối các cuộc hội thoại.

--------------------------------------------------------------------------------

SƠ ĐỒ KIẾN TRÚC HỆ THỐNG VÀ CÔNG NGHỆ SỬ DỤNG

(Chú thích hình ảnh: Sơ đồ kiến trúc Client-Server 3 tầng và các công nghệ cốt lõi tích hợp trong đề tài - File ảnh tương ứng: cong_nghe_su_dung.svg)

[KHOẢNG TRỐNG DÀNH CHO HÌNH ẢNH]
