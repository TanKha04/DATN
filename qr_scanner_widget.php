<?php
/**
 * QR Scanner Widget Component
 * Compact QR scanner utilizing file upload only
 */
?>

<!-- Load html5-qrcode library from CDN -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<!-- QR Scanner Widget Container -->
<div class="qr-scanner-widget-container">
    <!-- Scanner Modal Backdrop -->
    <div class="qr-scanner-modal-backdrop" id="qrScannerModal" style="display: none;">
        <div class="qr-scanner-modal-card">
            <!-- Header -->
            <div class="qr-scanner-modal-header">
                <h5 class="qr-scanner-modal-title">
                    <i class="bi bi-qr-code-scan text-primary animate-pulse"></i> Trình quét mã QR
                </h5>
                <button class="qr-scanner-modal-close" onclick="closeQRScanner()">&times;</button>
            </div>
            
            <!-- Body -->
            <div class="qr-scanner-modal-body">
                <!-- Div for HTML5-QRCode to mount (required internally by the library for file scanning) -->
                <div id="qr-reader-element" style="display: none;"></div>

                <!-- File Upload area -->
                <div id="qrUploadContainer" class="qr-view-section">
                    <div class="qr-upload-drag-zone" onclick="document.getElementById('qrFileInput').click()">
                        <i class="bi bi-cloud-arrow-up-fill upload-icon"></i>
                        <h6>Chọn hoặc kéo thả ảnh QR vào đây</h6>
                        <p>Hỗ trợ định dạng PNG, JPG, JPEG</p>
                        <input type="file" id="qrFileInput" accept="image/*" style="display: none;" onchange="handleQRFileSelect(event)">
                    </div>
                    <div id="qrFileInfo" class="mt-3" style="display: none;">
                        <span class="badge bg-secondary p-2"><i class="bi bi-file-earmark-image"></i> <span id="qrFileName">Filename.png</span></span>
                    </div>
                </div>
                
                <!-- Results View (Hidden by default, shown upon successful scan) -->
                <div id="qrResultContainer" class="qr-result-section" style="display: none;">
                    <div class="qr-success-animation mb-3">
                        <div class="success-icon-ring">
                            <i class="bi bi-check-lg"></i>
                        </div>
                    </div>
                    <h5>Quét thành công!</h5>
                    <div class="qr-scanned-content-box p-3 my-3">
                        <strong class="d-block mb-1 text-muted text-start" style="font-size: 0.75rem;">NỘI DUNG QUÉT ĐƯỢC:</strong>
                        <p id="qrScannedValue" class="text-break text-start mb-0"></p>
                    </div>
                    <div id="qrActionAlert" class="alert alert-info py-2" role="alert" style="display: none;">
                        <i class="bi bi-arrow-repeat spin"></i> Đang tải thông tin hồ sơ...
                    </div>
                    <div class="qr-result-actions">
                        <button class="btn btn-outline-secondary" onclick="resetQRScannerState()"><i class="bi bi-arrow-left"></i> Quét lại</button>
                        <a id="qrActionLink" href="#" class="btn btn-primary" style="display: none;"><i class="bi bi-box-arrow-up-right"></i> Mở liên kết</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* QR Scanner Styles */
.qr-scanner-toggle-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.2rem;
    background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
    border: none;
    border-radius: 12px;
    color: #ffffff;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    transition: all 0.2s ease;
    cursor: pointer;
}

.qr-scanner-toggle-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
}

.qr-scanner-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(8px);
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.qr-scanner-modal-backdrop.show {
    opacity: 1;
}

.qr-scanner-modal-card {
    background: #ffffff;
    border-radius: 24px;
    width: 90%;
    max-width: 480px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    transform: translateY(20px);
    transition: transform 0.3s ease;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.qr-scanner-modal-backdrop.show .qr-scanner-modal-card {
    transform: translateY(0);
}

.qr-scanner-modal-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(135deg, #0D1B36 0%, #1a3a5c 100%);
    color: #ffffff;
}

.qr-scanner-modal-title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.qr-scanner-modal-close {
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.75rem;
    line-height: 1;
    cursor: pointer;
    transition: color 0.2s;
    padding: 0;
}

.qr-scanner-modal-close:hover {
    color: #ffffff;
}

.qr-scanner-modal-body {
    padding: 2rem 1.5rem;
    min-height: 250px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.qr-view-section {
    width: 100%;
}

/* File Upload drag zone */
.qr-upload-drag-zone {
    border: 2px dashed #cbd5e1;
    border-radius: 16px;
    padding: 3.5rem 1.5rem;
    text-align: center;
    background: #f8fafc;
    cursor: pointer;
    transition: all 0.2s ease;
}

.qr-upload-drag-zone:hover {
    border-color: #3b82f6;
    background: #eff6ff;
}

.upload-icon {
    font-size: 3rem;
    color: #94a3b8;
    margin-bottom: 1rem;
    display: block;
}

.qr-upload-drag-zone h6 {
    color: #334155;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.qr-upload-drag-zone p {
    font-size: 0.8rem;
    color: #64748b;
    margin-bottom: 0;
}

/* Result Section */
.qr-result-section {
    text-align: center;
    animation: scaleUp 0.3s ease forwards;
}

@keyframes scaleUp {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.qr-success-animation {
    display: inline-flex;
    justify-content: center;
    align-items: center;
}

.success-icon-ring {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #dcfce7;
    color: #15803d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    box-shadow: 0 0 0 8px #f0fdf4;
}

.qr-scanned-content-box {
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.qr-result-actions {
    display: flex;
    justify-content: center;
    gap: 0.75rem;
}

/* Spinner helper */
.spin {
    animation: rotate 1s linear infinite;
    display: inline-block;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.animate-pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>

<script>
function openQRScanner() {
    const modal = document.getElementById('qrScannerModal');
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show'), 10);
    resetQRScannerState();
}

function closeQRScanner() {
    const modal = document.getElementById('qrScannerModal');
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
        resetQRScannerState();
    }, 300);
}

function handleQRFileSelect(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    document.getElementById('qrFileName').textContent = file.name;
    document.getElementById('qrFileInfo').style.display = 'block';
    
    // Check if Html5Qrcode is loaded
    if (typeof Html5Qrcode === 'undefined') {
        alert("Lỗi: Không thể tải thư viện giải mã QR. Vui lòng kiểm tra kết nối internet.");
        document.getElementById('qrFileInfo').style.display = 'none';
        return;
    }
    
    // Initialize scanner for file decoding
    const fileScanner = new Html5Qrcode("qr-reader-element");
    
    fileScanner.scanFile(file, true)
        .then(decodedText => {
            showQRResults(decodedText);
        })
        .catch(err => {
            console.error("Error scanning file", err);
            alert("Không thể giải mã được mã QR từ hình ảnh này. Vui lòng chọn ảnh rõ nét hơn.");
            document.getElementById('qrFileInfo').style.display = 'none';
        });
}

function showQRResults(content) {
    document.getElementById('qrUploadContainer').style.display = 'none';
    document.getElementById('qrResultContainer').style.display = 'block';
    document.getElementById('qrScannedValue').textContent = content;
    
    const actionAlert = document.getElementById('qrActionAlert');
    const actionLink = document.getElementById('qrActionLink');
    actionAlert.style.display = 'block';
    actionLink.style.display = 'none';
    
    // Save the scan result to database via API
    const formData = new FormData();
    formData.append('action', 'save_scan');
    formData.append('content', content);
    formData.append('method', 'upload');
    
    fetch('api/qr_scanner.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        // Analyze content
        const analyzeForm = new FormData();
        analyzeForm.append('action', 'analyze_qr');
        analyzeForm.append('content', content);
        
        return fetch('api/qr_scanner.php', {
            method: 'POST',
            body: analyzeForm
        });
    })
    .then(res => res.json())
    .then(data => {
        actionAlert.style.display = 'none';
        
        if (data.success && data.analysis) {
            const analysis = data.analysis;
            
            // If it's a URL in our application (like profile link)
            if (analysis.is_url && (content.includes('view_profile.php') || content.includes('profile.php'))) {
                actionAlert.className = 'alert alert-success py-2';
                actionAlert.innerHTML = '<i class="bi bi-check-circle-fill"></i> Đã phát hiện liên kết Hồ sơ! Đang mở...';
                actionAlert.style.display = 'block';
                
                setTimeout(() => {
                    if (typeof window.openUserProfileInDashboard === 'function') {
                        window.openUserProfileInDashboard(content);
                        closeQRScanner();
                    } else if (window.parent && typeof window.parent.openUserProfileInDashboard === 'function') {
                        window.parent.openUserProfileInDashboard(content);
                        closeQRScanner();
                    } else {
                        window.location.href = content;
                    }
                }, 1800);
            } else if (analysis.is_url) {
                // If standard web URL
                actionLink.href = content;
                actionLink.target = '_blank';
                actionLink.style.display = 'inline-block';
                actionLink.innerHTML = '<i class="bi bi-box-arrow-up-right"></i> Mở trang web';
            }
        }
    })
    .catch(err => {
        console.error("API error", err);
        actionAlert.style.display = 'none';
    });
}

function resetQRScannerState() {
    document.getElementById('qrResultContainer').style.display = 'none';
    document.getElementById('qrFileInfo').style.display = 'none';
    document.getElementById('qrFileInput').value = '';
    document.getElementById('qrUploadContainer').style.display = 'block';
}

// Close on backdrop click
window.addEventListener('click', function(event) {
    const modal = document.getElementById('qrScannerModal');
    if (event.target === modal) {
        closeQRScanner();
    }
});
</script>