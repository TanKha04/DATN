<?php
/**
 * Translation System for Medical Student Platform
 * Supports Vietnamese, English, and other languages
 */

class Translator {
    private static $instance = null;
    private $currentLanguage = 'vi';
    private $translations = [];
    private $availableLanguages = [
        'vi' => ['name' => 'Tiếng Việt', 'flag' => '🇻🇳'],
        'en' => ['name' => 'English', 'flag' => '🇺🇸'],
        'zh' => ['name' => '中文', 'flag' => '🇨🇳'],
        'ko' => ['name' => '한국어', 'flag' => '🇰🇷'],
        'ja' => ['name' => '日本語', 'flag' => '🇯🇵']
    ];

    private function __construct() {
        $this->loadTranslations();
        $this->setLanguageFromSession();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function setLanguageFromSession() {
        if (isset($_SESSION['language']) && array_key_exists($_SESSION['language'], $this->availableLanguages)) {
            $this->currentLanguage = $_SESSION['language'];
        } elseif (isset($_GET['lang']) && array_key_exists($_GET['lang'], $this->availableLanguages)) {
            $this->currentLanguage = $_GET['lang'];
            $_SESSION['language'] = $this->currentLanguage;
        }
    }

    public function setLanguage($lang) {
        if (array_key_exists($lang, $this->availableLanguages)) {
            $this->currentLanguage = $lang;
            $_SESSION['language'] = $lang;
            return true;
        }
        return false;
    }

    public function getCurrentLanguage() {
        return $this->currentLanguage;
    }

    public function getAvailableLanguages() {
        return $this->availableLanguages;
    }
    private function loadTranslations() {
        $this->translations = [
            'vi' => [
                // Navigation & Common
                'home' => 'Trang chủ',
                'dashboard' => 'Bảng điều khiển',
                'profile' => 'Hồ sơ',
                'messages' => 'Tin nhắn',
                'notifications' => 'Thông báo',
                'logout' => 'Đăng xuất',
                'login' => 'Đăng nhập',
                'register' => 'Đăng ký',
                'search' => 'Tìm kiếm',
                'filter' => 'Lọc',
                'save' => 'Lưu',
                'cancel' => 'Hủy',
                'delete' => 'Xóa',
                'edit' => 'Chỉnh sửa',
                'view' => 'Xem',
                'back' => 'Quay lại',
                'next' => 'Tiếp theo',
                'previous' => 'Trước',
                'submit' => 'Gửi',
                'close' => 'Đóng',
                'open' => 'Mở',
                'loading' => 'Đang tải...',
                'success' => 'Thành công!',
                'error' => 'Lỗi',
                'warning' => 'Cảnh báo',
                'info' => 'Thông tin',
                
                // Medical Terms
                'student' => 'Sinh viên',
                'patient' => 'Bệnh nhân',
                'doctor' => 'Bác sĩ',
                'nurse' => 'Y tá',
                'medical_student' => 'Sinh viên Y khoa',
                'hospital' => 'Bệnh viện',
                'clinic' => 'Phòng khám',
                'treatment' => 'Điều trị',
                'diagnosis' => 'Chẩn đoán',
                'medicine' => 'Thuốc',
                'prescription' => 'Đơn thuốc',
                'appointment' => 'Cuộc hẹn',
                'consultation' => 'Tư vấn',
                'emergency' => 'Cấp cứu',
                'surgery' => 'Phẫu thuật',
                
                // Posts & Applications
                'create_post' => 'Tạo tin đăng',
                'create_application' => 'Tạo tin ứng tuyển',
                'create_recruitment' => 'Tạo tin tuyển dụng',
                'post_title' => 'Tiêu đề bài đăng',
                'post_content' => 'Nội dung',
                'post_category' => 'Danh mục',
                'post_area' => 'Khu vực',
                'post_skills' => 'Kỹ năng',
                'contact_info' => 'Thông tin liên hệ',
                'evidence_image' => 'Ảnh minh chứng',
                'student_card' => 'Thẻ sinh viên',
                'application_success' => 'Tin ứng tuyển đã được đăng thành công!',
                'recruitment_success' => 'Tin tuyển dụng đã được đăng thành công!',
                
                // QR Code
                'qr_code' => 'QR Code',
                'share_page' => 'Chia sẻ trang này',
                'generating_qr' => 'Đang tạo QR Code...',
                'qr_size' => 'Kích thước',
                'download' => 'Tải xuống',
                'copy_url' => 'Sao chép URL',
                'url_copied' => 'Đã sao chép!',
                'qr_error' => 'Không thể tạo QR Code. Vui lòng thử lại.',
                
                // Dashboard
                'welcome_back' => 'Chào mừng trở lại',
                'my_posts' => 'Tin đăng của tôi',
                'favorites' => 'Yêu thích',
                'recent_activity' => 'Hoạt động gần đây',
                'statistics' => 'Thống kê',
                'quick_actions' => 'Thao tác nhanh',
                
                // Forms
                'full_name' => 'Họ và tên',
                'email' => 'Email',
                'phone' => 'Số điện thoại',
                'address' => 'Địa chỉ',
                'password' => 'Mật khẩu',
                'confirm_password' => 'Xác nhận mật khẩu',
                'required_field' => 'Trường bắt buộc',
                'optional' => 'Tùy chọn',
                
                // Time & Date
                'today' => 'Hôm nay',
                'yesterday' => 'Hôm qua',
                'this_week' => 'Tuần này',
                'this_month' => 'Tháng này',
                'last_month' => 'Tháng trước',
                'date' => 'Ngày',
                'time' => 'Thời gian',
                
                // Status
                'active' => 'Hoạt động',
                'inactive' => 'Không hoạt động',
                'pending' => 'Đang chờ',
                'approved' => 'Đã duyệt',
                'rejected' => 'Bị từ chối',
                'completed' => 'Hoàn thành',
                'in_progress' => 'Đang tiến hành',
                'online' => 'Trực tuyến',
                'offline' => 'Ngoại tuyến',
                
                // Language
                'language' => 'Ngôn ngữ',
                'change_language' => 'Đổi ngôn ngữ',
                'select_language' => 'Chọn ngôn ngữ'
            ],
            
            'en' => [
                // Navigation & Common
                'home' => 'Home',
                'dashboard' => 'Dashboard',
                'profile' => 'Profile',
                'messages' => 'Messages',
                'notifications' => 'Notifications',
                'logout' => 'Logout',
                'login' => 'Login',
                'register' => 'Register',
                'search' => 'Search',
                'filter' => 'Filter',
                'save' => 'Save',
                'cancel' => 'Cancel',
                'delete' => 'Delete',
                'edit' => 'Edit',
                'view' => 'View',
                'back' => 'Back',
                'next' => 'Next',
                'previous' => 'Previous',
                'submit' => 'Submit',
                'close' => 'Close',
                'open' => 'Open',
                'loading' => 'Loading...',
                'success' => 'Success!',
                'error' => 'Error',
                'warning' => 'Warning',
                'info' => 'Information',
                
                // Medical Terms
                'student' => 'Student',
                'patient' => 'Patient',
                'doctor' => 'Doctor',
                'nurse' => 'Nurse',
                'medical_student' => 'Medical Student',
                'hospital' => 'Hospital',
                'clinic' => 'Clinic',
                'treatment' => 'Treatment',
                'diagnosis' => 'Diagnosis',
                'medicine' => 'Medicine',
                'prescription' => 'Prescription',
                'appointment' => 'Appointment',
                'consultation' => 'Consultation',
                'emergency' => 'Emergency',
                'surgery' => 'Surgery',
                
                // Posts & Applications
                'create_post' => 'Create Post',
                'create_application' => 'Create Application',
                'create_recruitment' => 'Create Recruitment',
                'post_title' => 'Post Title',
                'post_content' => 'Content',
                'post_category' => 'Category',
                'post_area' => 'Area',
                'post_skills' => 'Skills',
                'contact_info' => 'Contact Information',
                'evidence_image' => 'Evidence Image',
                'student_card' => 'Student Card',
                'application_success' => 'Application posted successfully!',
                'recruitment_success' => 'Recruitment posted successfully!',
                
                // QR Code
                'qr_code' => 'QR Code',
                'share_page' => 'Share this page',
                'generating_qr' => 'Generating QR Code...',
                'qr_size' => 'Size',
                'download' => 'Download',
                'copy_url' => 'Copy URL',
                'url_copied' => 'Copied!',
                'qr_error' => 'Cannot generate QR Code. Please try again.',
                
                // Dashboard
                'welcome_back' => 'Welcome back',
                'my_posts' => 'My Posts',
                'favorites' => 'Favorites',
                'recent_activity' => 'Recent Activity',
                'statistics' => 'Statistics',
                'quick_actions' => 'Quick Actions',
                
                // Forms
                'full_name' => 'Full Name',
                'email' => 'Email',
                'phone' => 'Phone',
                'address' => 'Address',
                'password' => 'Password',
                'confirm_password' => 'Confirm Password',
                'required_field' => 'Required field',
                'optional' => 'Optional',
                
                // Time & Date
                'today' => 'Today',
                'yesterday' => 'Yesterday',
                'this_week' => 'This Week',
                'this_month' => 'This Month',
                'last_month' => 'Last Month',
                'date' => 'Date',
                'time' => 'Time',
                
                // Status
                'active' => 'Active',
                'inactive' => 'Inactive',
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'completed' => 'Completed',
                'in_progress' => 'In Progress',
                'online' => 'Online',
                'offline' => 'Offline',
                
                // Language
                'language' => 'Language',
                'change_language' => 'Change Language',
                'select_language' => 'Select Language'
            ]
        ];
    }

    public function translate($key, $default = null) {
        if (isset($this->translations[$this->currentLanguage][$key])) {
            return $this->translations[$this->currentLanguage][$key];
        }
        
        // Fallback to Vietnamese if key not found in current language
        if ($this->currentLanguage !== 'vi' && isset($this->translations['vi'][$key])) {
            return $this->translations['vi'][$key];
        }
        
        return $default ?: $key;
    }

    public function t($key, $default = null) {
        return $this->translate($key, $default);
    }
}

// Global translation function
function __($key, $default = null) {
    return Translator::getInstance()->translate($key, $default);
}

function t($key, $default = null) {
    return Translator::getInstance()->translate($key, $default);
}

// Initialize translator
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$translator = Translator::getInstance();
?>
