<?php
require_once 'config.php';
require_login();
$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'] ?? 'Người dùng';
$userRole = $_SESSION['role'] ?? 'patient';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Trợ lý Y tế AI - Kết nối Y tế</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/ai_assistant.css?v=<?php echo time(); ?>">
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<style>
/* Inline student cards for AI assistant full page */
.student-cards-wrap { margin-top:.75rem; }
.student-cards-label { font-size:.75rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.5rem; }
.student-card-mini {
  display:flex; align-items:flex-start; gap:.75rem;
  background:linear-gradient(135deg,#f8fafc,#eff6ff);
  border:1px solid #bfdbfe; border-radius:14px;
  padding:.65rem 1rem; margin-bottom:.6rem;
  transition:all .2s ease; flex-wrap:wrap;
}
.student-card-mini:hover { background:linear-gradient(135deg,#eff6ff,#dbeafe); transform:translateX(3px); box-shadow:0 4px 16px rgba(37,99,235,.1); }
.student-card-main { display:flex; align-items:center; gap:.75rem; width:100%; }
.student-mini-avatar {
  width:44px; height:44px; border-radius:12px;
  background:linear-gradient(135deg,#dbeafe,#bfdbfe);
  display:flex; align-items:center; justify-content:center;
  font-size:1.3rem; flex-shrink:0; overflow:hidden; position:relative;
}
.student-mini-avatar img { width:100%; height:100%; object-fit:cover; border-radius:12px; }
.online-badge-lg { position:absolute; bottom:-1px; right:-1px; width:12px; height:12px; background:#22c55e; border-radius:50%; border:2px solid #fff; }
.student-mini-info { flex:1; min-width:0; }
.student-mini-name { font-size:.875rem; font-weight:700; color:#1e293b; }
.student-mini-school { font-size:.75rem; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.student-mini-rating { font-size:.75rem; color:#f59e0b; }
.student-mini-actions { display:flex; gap:.4rem; flex-shrink:0; flex-wrap:wrap; }
.matched-post-box {
  width:100%; margin-top:.45rem;
  background:linear-gradient(135deg,#fefce8,#fef9c3);
  border:1px solid #fde68a; border-radius:10px;
  padding:.45rem .75rem; font-size:.75rem;
}
.matched-post-label { font-weight:700; color:#92400e; margin-bottom:.15rem; display:flex; align-items:center; gap:.3rem; }
.matched-post-title { font-weight:600; color:#1e293b; margin-bottom:.15rem; }
.matched-post-snippet { color:#475569; line-height:1.45; }
.matched-post-link { display:inline-flex; align-items:center; gap:.2rem; margin-top:.3rem; color:#2563eb; font-weight:600; text-decoration:none; font-size:.72rem; }
.matched-post-link:hover { text-decoration:underline; }
.s-btn {
  padding:.35rem .7rem; border-radius:10px; border:none;
  font-size:.75rem; font-weight:600; cursor:pointer; transition:all .2s;
  text-decoration:none; display:inline-flex; align-items:center; gap:.25rem;
}
.s-btn.profile { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }
.s-btn.profile:hover { background:#2563eb; color:#fff; }
.s-btn.msg { background:#0ea5e9; color:#fff; }
.s-btn.msg:hover { background:#0284c7; }
.s-btn.post-btn { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
.s-btn.post-btn:hover { background:#f59e0b; color:#fff; }
.no-students-note { font-size:.8rem; color:#94a3b8; font-style:italic; }
</style>
</head>
<body>

<div class="ai-app">
  <!-- Sidebar -->
  <aside class="ai-sidebar" id="aiSidebar">
    <div class="sidebar-top">
      <?php
      $backUrl = 'dashboard_patient.php';
      if ($userRole === 'student') {
          $backUrl = 'dashboard_student.php';
      } elseif ($userRole === 'admin') {
          $backUrl = 'admin_dashboard.php';
      }
      ?>
      <a href="<?php echo $backUrl; ?>" class="back-btn">
        <i class="bi bi-arrow-left"></i> Quay lại
      </a>
      <button class="new-chat-btn" onclick="clearChat()">
        <i class="bi bi-plus-lg"></i> Cuộc trò chuyện mới
      </button>
    </div>

    <div class="sidebar-history">
      <h6><i class="bi bi-clock-history"></i> Lịch sử trò chuyện</h6>
      <div class="history-list" id="sidebarHistoryList"></div>
    </div>

    <div class="sidebar-categories">
      <h6><i class="bi bi-grid"></i> Chủ đề gợi ý</h6>
      <?php if ($userRole === 'admin'): ?>
        <button class="cat-btn" onclick="askQuestion('Quy trình duyệt xác thực tài khoản sinh viên')">🛡️ Duyệt tài khoản</button>
        <button class="cat-btn" onclick="askQuestion('Cách xử lý bài đăng bị báo cáo vi phạm')">🚫 Xử lý vi phạm</button>
        <button class="cat-btn" onclick="askQuestion('Gợi ý tăng tỷ lệ kết nối thành công')">📊 Đề xuất tối ưu</button>
        <button class="cat-btn" onclick="askQuestion('Mẫu thông báo cảnh cáo tài khoản spam')">✉️ Mẫu thông báo</button>
      <?php elseif ($userRole === 'student'): ?>
        <button class="cat-btn" onclick="askQuestion('Quy trình thay băng vết thương tại nhà')">🩹 Thay băng tại nhà</button>
        <button class="cat-btn" onclick="askQuestion('Cách chăm sóc bệnh nhân tai biến phục hồi chức năng')">🧠 Chăm sóc tai biến</button>
        <button class="cat-btn" onclick="askQuestion('Kỹ năng giao tiếp và tạo niềm tin với bệnh nhân')">💬 Kỹ năng giao tiếp</button>
        <button class="cat-btn" onclick="askQuestion('Cách viết hồ sơ năng lực ấn tượng')">📝 Hồ sơ ấn tượng</button>
        <button class="cat-btn" onclick="askQuestion('Lịch sử nhận việc và cách điểm danh')">📅 Lịch sử nhận việc</button>
      <?php else: ?>
        <button class="cat-btn" onclick="askQuestion('Tôi bị đau đầu, cho tôi lời khuyên')">🧠 Đau đầu</button>
        <button class="cat-btn" onclick="askQuestion('Tôi bị sốt, nên làm gì?')">🌡️ Sốt</button>
        <button class="cat-btn" onclick="askQuestion('Tôi bị ho kéo dài')">😷 Ho</button>
        <button class="cat-btn" onclick="askQuestion('Tôi bị đau bụng')">🤕 Đau bụng</button>
        <button class="cat-btn" onclick="askQuestion('Cách chăm sóc người già tại nhà')">👴 Người cao tuổi</button>
        <button class="cat-btn" onclick="askQuestion('Cách quản lý bệnh tiểu đường')">💉 Tiểu đường</button>
        <button class="cat-btn" onclick="askQuestion('Hướng dẫn đăng tin tuyển dụng')">📢 Đăng tin</button>
        <button class="cat-btn" onclick="askQuestion('Cách tìm sinh viên y khoa phù hợp')">🔍 Tìm sinh viên</button>
      <?php endif; ?>
    </div>

    <div class="sidebar-bottom">
      <div class="ai-info">
        <i class="bi bi-stars"></i>
        <span>Powered by Gemini AI</span>
      </div>
    </div>
  </aside>

  <!-- Main Chat Area -->
  <main class="ai-main">
    <header class="ai-header">
      <button class="menu-toggle" onclick="document.getElementById('aiSidebar').classList.toggle('show')">
        <i class="bi bi-list"></i>
      </button>
      <div class="header-center">
        <h1>🩺 Trợ lý Y tế AI</h1>
        <span class="ai-status" id="statusLabel"><i class="bi bi-circle-fill"></i> Sẵn sàng</span>
      </div>
      <div class="header-right">
        <button onclick="openSymptomChecker()" class="symptom-btn" title="Kiểm tra triệu chứng">
          <i class="bi bi-clipboard2-pulse"></i> Kiểm tra triệu chứng
        </button>
      </div>
    </header>

    <div class="chat-area" id="chatArea">
      <!-- Welcome Screen -->
      <div class="welcome-screen" id="welcomeScreen">
        <div class="welcome-icon">🩺</div>
        <h2>Xin chào, <?php echo htmlspecialchars($userName); ?>!</h2>
        <p>Tôi là Trợ lý Y tế AI. Tôi có thể tư vấn sức khỏe, hướng dẫn sử dụng hệ thống, và giúp bạn tìm sinh viên y khoa phù hợp.</p>
        <div class="welcome-cards">
          <?php if ($userRole === 'admin'): ?>
            <div class="w-card" onclick="askQuestion('Hướng dẫn các công việc quản trị')">
              <i class="bi bi-shield-lock-fill"></i>
              <span>Công việc quản trị</span>
            </div>
            <div class="w-card" onclick="askQuestion('Quy định phê duyệt tài khoản')">
              <i class="bi bi-patch-check-fill"></i>
              <span>Duyệt tài khoản</span>
            </div>
            <div class="w-card" onclick="askQuestion('Cách xử lý báo cáo vi phạm')">
              <i class="bi bi-exclamation-triangle-fill"></i>
              <span>Xử lý báo cáo</span>
            </div>
            <div class="w-card" onclick="askQuestion('Đề xuất phát triển hệ thống')">
              <i class="bi bi-graph-up-arrow"></i>
              <span>Đề xuất tối ưu</span>
            </div>
          <?php elseif ($userRole === 'student'): ?>
            <div class="w-card" onclick="askQuestion('Hướng dẫn quy trình nhận việc và điểm danh')">
              <i class="bi bi-info-circle"></i>
              <span>Quy trình nhận việc</span>
            </div>
            <div class="w-card" onclick="askQuestion('Tra cứu kiến thức y khoa lâm sàng')">
              <i class="bi bi-mortarboard-fill"></i>
              <span>Kiến thức Y khoa</span>
            </div>
            <div class="w-card" onclick="askQuestion('Kỹ năng chăm sóc bệnh nhân tại nhà')">
              <i class="bi bi-heart-pulse-fill"></i>
              <span>Kỹ năng chăm sóc</span>
            </div>
            <div class="w-card" onclick="askQuestion('Cách tối ưu hồ sơ ứng tuyển')">
              <i class="bi bi-person-badge-fill"></i>
              <span>Hồ sơ năng lực</span>
            </div>
          <?php else: ?>
            <div class="w-card" onclick="askQuestion('Tôi cần tư vấn sức khỏe')">
              <i class="bi bi-heart-pulse"></i>
              <span>Tư vấn sức khỏe</span>
            </div>
            <div class="w-card" onclick="openSymptomChecker()">
              <i class="bi bi-clipboard2-pulse"></i>
              <span>Kiểm tra triệu chứng</span>
            </div>
            <div class="w-card" onclick="askQuestion('Hướng dẫn sử dụng hệ thống')">
              <i class="bi bi-info-circle"></i>
              <span>Hướng dẫn hệ thống</span>
            </div>
            <div class="w-card" onclick="askQuestion('Tìm sinh viên y khoa phù hợp')">
              <i class="bi bi-search"></i>
              <span>Tìm sinh viên</span>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div id="messagesArea" class="messages-area" style="display:none;"></div>
    </div>

    <div class="input-area">
      <div class="input-wrap">
        <input type="text" id="msgInput" placeholder="Nhập câu hỏi về sức khỏe hoặc hệ thống..." maxlength="1000" autocomplete="off">
        <button id="sendBtn" onclick="sendMsg()"><i class="bi bi-send-fill"></i></button>
      </div>
      <p class="disclaimer">⚠️ AI chỉ tư vấn sơ bộ, không thay thế bác sĩ chuyên khoa. Trường hợp khẩn cấp hãy gọi 115.</p>
    </div>
  </main>
</div>

<!-- Symptom Checker Modal -->
<div class="modal-overlay" id="symptomModal" style="display:none;">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="bi bi-clipboard2-pulse"></i> Kiểm tra triệu chứng</h3>
      <button onclick="closeSymptomModal()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <label>Chọn triệu chứng:</label>
      <div class="symptom-chips" id="symptomChips">
        <button class="chip" data-s="Đau đầu" onclick="toggleChip(this)">🧠 Đau đầu</button>
        <button class="chip" data-s="Sốt" onclick="toggleChip(this)">🌡️ Sốt</button>
        <button class="chip" data-s="Ho" onclick="toggleChip(this)">😷 Ho</button>
        <button class="chip" data-s="Đau bụng" onclick="toggleChip(this)">🤕 Đau bụng</button>
        <button class="chip" data-s="Khó thở" onclick="toggleChip(this)">😮‍💨 Khó thở</button>
        <button class="chip" data-s="Mệt mỏi" onclick="toggleChip(this)">😴 Mệt mỏi</button>
        <button class="chip" data-s="Chóng mặt" onclick="toggleChip(this)">💫 Chóng mặt</button>
        <button class="chip" data-s="Buồn nôn" onclick="toggleChip(this)">🤢 Buồn nôn</button>
        <button class="chip" data-s="Đau ngực" onclick="toggleChip(this)">💔 Đau ngực</button>
        <button class="chip" data-s="Đau lưng" onclick="toggleChip(this)">🦴 Đau lưng</button>
        <button class="chip" data-s="Đau khớp" onclick="toggleChip(this)">🦵 Đau khớp</button>
        <button class="chip" data-s="Mất ngủ" onclick="toggleChip(this)">🌙 Mất ngủ</button>
      </div>
      <label>Mô tả thêm (tuỳ chọn):</label>
      <textarea id="symptomDetails" placeholder="Ví dụ: đau đầu 3 ngày, kèm chóng mặt buổi sáng..." rows="3"></textarea>
      <div class="symptom-row">
        <div>
          <label>Tuổi:</label>
          <input type="number" id="symptomAge" placeholder="VD: 30" min="1" max="120">
        </div>
        <div>
          <label>Giới tính:</label>
          <select id="symptomGender"><option value="">--</option><option value="male">Nam</option><option value="female">Nữ</option></select>
        </div>
        <div>
          <label>Thời gian:</label>
          <select id="symptomDuration"><option value="">--</option><option value="Hôm nay">Hôm nay</option><option value="2-3 ngày">2-3 ngày</option><option value="1 tuần">1 tuần</option><option value="Trên 2 tuần">Trên 2 tuần</option></select>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="cancel-btn" onclick="closeSymptomModal()">Hủy</button>
      <button class="submit-btn" onclick="submitSymptoms()" id="submitSymptomBtn">
        <i class="bi bi-search"></i> Phân tích triệu chứng
      </button>
    </div>
  </div>
</div>

<script>
const msgInput=document.getElementById('msgInput');
const messagesArea=document.getElementById('messagesArea');
const welcomeScreen=document.getElementById('welcomeScreen');
let chatStarted=false;

function startChat(){
  if(!chatStarted){welcomeScreen.style.display='none';messagesArea.style.display='flex';chatStarted=true;}
}

function addMsg(text,isUser,source,studentsData,recruitmentsData){
  startChat();
  const d=document.createElement('div');
  d.className='msg '+(isUser?'user':'bot');
  const av=document.createElement('div');av.className='msg-avatar';av.textContent=isUser?'👤':'%EF%B8%8F';
  av.innerHTML = isUser ? '👤' : '🩺';
  const c=document.createElement('div');c.className='msg-content';
  c.innerHTML=formatText(text);
  if(!isUser&&source){
    const b=document.createElement('span');
    b.className='source-badge '+(source==='gemini'?'gemini':'fallback');
    b.innerHTML=source==='gemini'?'<i class="bi bi-stars"></i> Gemini AI':'<i class="bi bi-cpu"></i> Trợ lý nội bộ';
    c.appendChild(b);
  }
  if(!isUser&&studentsData){
    c.appendChild(renderStudentCards(studentsData));
  }
  if(!isUser&&recruitmentsData){
    c.appendChild(renderRecruitmentCards(recruitmentsData));
  }
  d.appendChild(av);d.appendChild(c);messagesArea.appendChild(d);
  messagesArea.scrollTop=messagesArea.scrollHeight;
}

function renderStudentCards(sd){
  const wrap=document.createElement('div');wrap.className='student-cards-wrap';
  const spec=sd.specialty?' — Chuyên khoa <strong>'+escHtml(sd.specialty)+'</strong>':'';
  const allEmpty=(!sd.matched_students||sd.matched_students.length===0)&&(!sd.suggested_students||sd.suggested_students.length===0);

  if(allEmpty){
    const lbl=document.createElement('div');lbl.className='student-cards-label';
    lbl.innerHTML='🔍 Sinh viên phù hợp'+spec;
    wrap.appendChild(lbl);
    const n=document.createElement('div');n.className='no-students-note';
    n.innerHTML='⚠️ Hiện tại chưa có sinh viên y khoa phù hợp với bệnh tình của bạn.<br>Hãy <a href="create_recruitment.php" style="color:#2563eb;font-weight:600">đăng tin tìm kiếm</a> để được hỗ trợ sớm nhất!';
    wrap.appendChild(n);return wrap;
  }

  // ── Matched students (truly relevant) ──
  if(sd.matched_students && sd.matched_students.length>0){
    const lbl=document.createElement('div');lbl.className='student-cards-label';
    lbl.innerHTML='✅ Sinh viên có kinh nghiệm phù hợp'+spec;
    wrap.appendChild(lbl);
    sd.matched_students.forEach(function(s){ wrap.appendChild(buildStudentCard(s)); });
  }

  // ── No match message + suggested students ──
  if(!sd.matched_students || sd.matched_students.length===0){
    const lbl=document.createElement('div');lbl.className='student-cards-label';
    lbl.innerHTML='🔍 Tìm sinh viên'+spec;
    wrap.appendChild(lbl);
    const note=document.createElement('div');note.className='no-students-note';
    note.style.marginBottom='.6rem';
    note.innerHTML='⚠️ Hiện tại chưa có sinh viên có kinh nghiệm về <strong>'+(sd.specialty||'bệnh tình của bạn')+'</strong>.<br>Hãy <a href="create_recruitment.php" style="color:#2563eb;font-weight:600">đăng tin tìm kiếm</a> để được hỗ trợ sớm nhất!';
    wrap.appendChild(note);
  }

  if(sd.suggested_students && sd.suggested_students.length>0){
    const slbl=document.createElement('div');slbl.className='student-cards-label';
    slbl.style.marginTop='.6rem';
    slbl.innerHTML='👥 Sinh viên khác bạn có thể liên hệ';
    wrap.appendChild(slbl);
    sd.suggested_students.forEach(function(s){ wrap.appendChild(buildStudentCard(s)); });
  }
  return wrap;
}

function buildStudentCard(s){
  const card=document.createElement('div');card.className='student-card-mini';
  const mainRow=document.createElement('div');mainRow.className='student-card-main';
  const av=document.createElement('div');av.className='student-mini-avatar';
  if(s.avatar){const img=document.createElement('img');img.src=s.avatar;img.alt=s.name;av.appendChild(img);}
  else{av.textContent='🧑‍⚕️';}
  if(s.is_online){const dot=document.createElement('span');dot.className='online-badge-lg';av.appendChild(dot);}
  const info=document.createElement('div');info.className='student-mini-info';
  const nm=document.createElement('div');nm.className='student-mini-name';
  nm.textContent=(s.name||'Sinh viên Y khoa')+(s.verified?' ✅':'');
  const sc=document.createElement('div');sc.className='student-mini-school';
  sc.textContent=s.school||s.location||'';
  const rt=document.createElement('div');rt.className='student-mini-rating';
  rt.textContent=s.avg_rating>0?'★ '+s.avg_rating.toFixed(1)+' ('+s.rating_count+')':'★ Mới';
  info.appendChild(nm);info.appendChild(sc);info.appendChild(rt);
  const acts=document.createElement('div');acts.className='student-mini-actions';
  const pb=document.createElement('a');pb.className='s-btn profile';pb.href=s.profile_url;pb.target='_top';
  pb.innerHTML='<i class="bi bi-person-badge"></i> Hồ sơ';
  const mb=document.createElement('a');mb.className='s-btn msg';mb.href=s.message_url;mb.target='_top';
  mb.innerHTML='<i class="bi bi-chat-dots-fill"></i> Nhắn tin';
  acts.appendChild(pb);acts.appendChild(mb);
  mainRow.appendChild(av);mainRow.appendChild(info);mainRow.appendChild(acts);
  card.appendChild(mainRow);
  if(s.matched_post){
    const mp=s.matched_post;
    const box=document.createElement('div');box.className='matched-post-box';
    const mlbl=document.createElement('div');mlbl.className='matched-post-label';
    mlbl.innerHTML='<i class="bi bi-file-earmark-text"></i> Bài đăng khớp với yêu cầu của bạn';
    const mtitle=document.createElement('div');mtitle.className='matched-post-title';
    mtitle.textContent=mp.post_title||'';
    const msnip=document.createElement('div');msnip.className='matched-post-snippet';
    msnip.textContent=mp.post_snippet||'';
    const mlink=document.createElement('a');mlink.className='matched-post-link';
    mlink.href=mp.post_url;mlink.target='_top';
    mlink.innerHTML='<i class="bi bi-box-arrow-up-right"></i> Xem bài đăng đầy đủ';
    box.appendChild(mlbl);box.appendChild(mtitle);box.appendChild(msnip);box.appendChild(mlink);
    card.appendChild(box);
  }
  return card;
}

function renderRecruitmentCards(rd) {
  const wrap = document.createElement('div');
  wrap.className = 'student-cards-wrap'; // Tái sử dụng CSS có sẵn
  const allEmpty = (!rd.matched_posts || rd.matched_posts.length === 0) && (!rd.suggested_posts || rd.suggested_posts.length === 0);

  if (allEmpty) {
    const lbl = document.createElement('div');
    lbl.className = 'student-cards-label';
    lbl.innerHTML = '🔍 Tin tuyển dụng phù hợp';
    wrap.appendChild(lbl);
    const n = document.createElement('div');
    n.className = 'no-students-note';
    n.innerHTML = '⚠️ Hiện tại chưa có tin tuyển dụng phù hợp với thế mạnh của bạn.<br>Hãy đăng thêm kỹ năng chi tiết hơn nhé!';
    wrap.appendChild(n);
    return wrap;
  }

  // Các tin tuyển dụng khớp trực tiếp
  if (rd.matched_posts && rd.matched_posts.length > 0) {
    const lbl = document.createElement('div');
    lbl.className = 'student-cards-label';
    lbl.innerHTML = '✅ Việc làm khớp với thế mạnh của bạn';
    wrap.appendChild(lbl);
    rd.matched_posts.forEach(function(r) { wrap.appendChild(buildRecruitmentCard(r)); });
  }

  // Các tin tuyển dụng gợi ý khác đang mở
  if (rd.suggested_posts && rd.suggested_posts.length > 0) {
    const slbl = document.createElement('div');
    slbl.className = 'student-cards-label';
    slbl.style.marginTop = '.6rem';
    slbl.innerHTML = '👥 Việc làm khác có thể bạn quan tâm';
    wrap.appendChild(slbl);
    rd.suggested_posts.forEach(function(r) { wrap.appendChild(buildRecruitmentCard(r)); });
  }
  return wrap;
}

function buildRecruitmentCard(r) {
  const card = document.createElement('div');
  card.className = 'student-card-mini'; // Tái sử dụng CSS có sẵn
  
  const mainRow = document.createElement('div');
  mainRow.className = 'student-card-main';
  
  const av = document.createElement('div');
  av.className = 'student-mini-avatar';
  if (r.patient_avatar) {
    const img = document.createElement('img');
    img.src = r.patient_avatar;
    img.alt = r.patient_name;
    av.appendChild(img);
  } else {
    av.textContent = '👤';
  }
  
  const info = document.createElement('div');
  info.className = 'student-mini-info';
  
  const nm = document.createElement('div');
  nm.className = 'student-mini-name';
  nm.textContent = r.patient_name || 'Bệnh nhân';
  
  const title = document.createElement('div');
  title.className = 'student-mini-school';
  title.style.fontWeight = '700';
  title.style.color = '#1e293b';
  title.style.whiteSpace = 'nowrap';
  title.style.overflow = 'hidden';
  title.style.textOverflow = 'ellipsis';
  title.textContent = r.title || '';
  
  const salLoc = document.createElement('div');
  salLoc.className = 'student-mini-school';
  salLoc.innerHTML = '💵 Lương: ' + r.salary + ' <br>📍 ' + r.location;
  
  info.appendChild(nm);
  info.appendChild(title);
  info.appendChild(salLoc);
  
  const acts = document.createElement('div');
  acts.className = 'student-mini-actions';
  
  const pb = document.createElement('a');
  pb.className = 's-btn profile';
  pb.href = r.post_url;
  pb.target = '_top';
  pb.innerHTML = '<i class="bi bi-file-earmark-text"></i> Xem tin';
  
  const mb = document.createElement('a');
  mb.className = 's-btn msg';
  mb.href = r.message_url;
  mb.target = '_top';
  mb.innerHTML = '<i class="bi bi-chat-dots-fill"></i> Liên hệ';
  
  acts.appendChild(pb);
  acts.appendChild(mb);
  
  mainRow.appendChild(av);
  mainRow.appendChild(info);
  mainRow.appendChild(acts);
  card.appendChild(mainRow);
  
  if (r.snippet) {
    const box = document.createElement('div');
    box.className = 'matched-post-box';
    box.innerHTML = '<div class="matched-post-snippet">' + escHtml(r.snippet) + '</div>';
    card.appendChild(box);
  }
  
  return card;
}

function escHtml(t){return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

function formatText(t){
  if (typeof marked !== 'undefined') {
    return marked.parse(t);
  }
  return t.replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>').replace(/\n/g,'<br>').replace(/•/g,'<br>•');
}

function showTyping(){
  startChat();
  const d=document.createElement('div');d.id='typing';d.className='msg bot';
  d.innerHTML='<div class="msg-avatar">🩺</div><div class="msg-content"><div class="typing-dots"><span></span><span></span><span></span></div></div>';
  messagesArea.appendChild(d);messagesArea.scrollTop=messagesArea.scrollHeight;
  document.getElementById('statusLabel').innerHTML='<i class="bi bi-circle-fill" style="color:#f59e0b"></i> Đang suy nghĩ...';
}
function hideTyping(){
  const t=document.getElementById('typing');if(t)t.remove();
  document.getElementById('statusLabel').innerHTML='<i class="bi bi-circle-fill"></i> Sẵn sàng';
}

function sendMsg(){
  const m=msgInput.value.trim();if(!m)return;
  addMsg(m,true);msgInput.value='';
  showTyping();
  fetch('api/ai_gemini.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({message:m,action:'chat'})})
  .then(r=>r.json()).then(d=>{
    hideTyping();
    if(d.success) {
      addMsg(d.reply,false,d.source,d.students||null,d.recruitments||null);
      loadSidebarHistory();
    } else {
      addMsg('Xin lỗi, có lỗi xảy ra.',false,'fallback');
    }
  })
  .catch(()=>{hideTyping();addMsg('Không thể kết nối. Vui lòng thử lại.',false,'fallback');});
}

function askQuestion(q){msgInput.value=q;sendMsg();}

msgInput.addEventListener('keypress',function(e){if(e.key==='Enter')sendMsg();});

// Startup logic: load active session messages and history list
(function(){
  loadActiveSession();
  loadSidebarHistory();
  
  const params = new URLSearchParams(window.location.search);
  if(params.get('open') === 'symptom'){
    setTimeout(openSymptomChecker, 400);
  }
})();

function loadActiveSession(){
  fetch('api/ai_gemini.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'load',message:''})})
  .then(r=>r.json()).then(d=>{
    if(d.success && d.history && d.history.length > 0){
      messagesArea.innerHTML = '';
      d.history.forEach(msg => {
        addMsg(msg.text, msg.role === 'user', msg.source || null, null);
      });
    }
  }).catch(err => console.error("Error loading active session: ", err));
}

function loadSidebarHistory(){
  const sidebarList = document.getElementById('sidebarHistoryList');
  if(!sidebarList) return;
  sidebarList.innerHTML = '<div style="text-align:center;padding:1rem;font-size:0.75rem;color:#64748b;">Đang tải...</div>';
  fetch('api/ai_gemini.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'list',message:''})})
  .then(r=>r.json()).then(d=>{
    if(d.success){
      sidebarList.innerHTML = '';
      if(!d.conversations || d.conversations.length===0){
        sidebarList.innerHTML = '<div class="history-empty">Chưa có lịch sử</div>';
        return;
      }
      d.conversations.forEach(c=>{
        const item = document.createElement('div');
        item.className = 'history-item'+(c.id == d.active_id?' active':'');
        
        let dateStr = c.updated_at;
        try {
          const dateObj = new Date(c.updated_at);
          dateStr = dateObj.toLocaleDateString('vi-VN') + ' ' + dateObj.toLocaleTimeString('vi-VN', {hour: '2-digit', minute:'2-digit'});
        } catch(e) {}

        item.innerHTML = `
          <div class="history-title">${escHtml(c.title)}</div>
          <div class="history-time"><i class="bi bi-clock"></i> ${dateStr}</div>
        `;
        item.addEventListener('click',()=>switchConversation(c.id));
        sidebarList.appendChild(item);
      });
    } else {
      sidebarList.innerHTML = '<div class="history-empty">Lỗi tải lịch sử</div>';
    }
  }).catch(()=>{
    sidebarList.innerHTML = '<div class="history-empty">Lỗi kết nối</div>';
  });
}

function switchConversation(id){
  const sidebarList = document.getElementById('sidebarHistoryList');
  if(sidebarList) {
    sidebarList.querySelectorAll('.history-item').forEach(el => el.classList.remove('active'));
  }
  messagesArea.innerHTML = '<div style="text-align:center;padding:2rem;width:100%;color:#64748b;"><div class="spinner-border text-primary" role="status"></div> Đang tải cuộc trò chuyện...</div>';
  startChat();
  
  fetch('api/ai_gemini.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'switch_session',conversation_id:id,message:''})})
  .then(r=>r.json()).then(d=>{
    if(d.success){
      messagesArea.innerHTML = '';
      if(d.history && d.history.length > 0){
        d.history.forEach(msg => {
          addMsg(msg.text, msg.role === 'user', msg.source || null, null);
        });
      } else {
        messagesArea.innerHTML = '<div style="text-align:center;padding:2rem;width:100%;color:#64748b;">Bắt đầu cuộc trò chuyện mới! 😊</div>';
      }
      loadSidebarHistory();
    } else {
      alert('Không thể chuyển cuộc trò chuyện');
      loadSidebarHistory();
    }
  }).catch(()=>{
    alert('Lỗi kết nối');
    loadSidebarHistory();
  });
}

function clearChat(){
  if(!confirm('Bắt đầu cuộc trò chuyện mới?')) return;
  fetch('api/ai_gemini.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'new_session',message:''})})
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      messagesArea.innerHTML='';
      chatStarted=false;
      welcomeScreen.style.display='';
      messagesArea.style.display='none';
      loadSidebarHistory();
    } else {
      alert('Không thể bắt đầu cuộc trò chuyện mới');
    }
  })
  .catch(()=>{
    alert('Lỗi kết nối');
  });
}

// Symptom Checker
function openSymptomChecker(){document.getElementById('symptomModal').style.display='flex';}
function closeSymptomModal(){document.getElementById('symptomModal').style.display='none';}
function toggleChip(el){el.classList.toggle('active');}

function submitSymptoms(){
  const chips=document.querySelectorAll('.chip.active');
  const symptoms=Array.from(chips).map(c=>c.dataset.s);
  const details=document.getElementById('symptomDetails').value;
  const age=document.getElementById('symptomAge').value;
  const gender=document.getElementById('symptomGender').value;
  const duration=document.getElementById('symptomDuration').value;
  if(symptoms.length===0&&!details){alert('Vui lòng chọn ít nhất 1 triệu chứng');return;}
  closeSymptomModal();
  addMsg('Kiểm tra triệu chứng: '+symptoms.join(', ')+(details?' - '+details:''),true);
  showTyping();
  fetch('api/ai_gemini.php',{method:'POST',headers:{'Content-Type':'application/json'},
    body:JSON.stringify({action:'symptom_check',symptoms,details,age,gender,duration})})
  .then(r=>r.json()).then(d=>{
    hideTyping();
    if(d.success){
      const a=d.analysis;
      const sevColors={low:'#10b981',medium:'#f59e0b',high:'#ef4444',emergency:'#dc2626'};
      let html='<div style="border-left:4px solid '+(sevColors[a.severity]||'#64748b')+';padding:0.75rem 1rem;border-radius:8px;background:'+(a.severity==='emergency'?'#fef2f2':'#f8fafc')+'">';
      html+='<strong>'+({low:'🟢 Mức độ: Nhẹ',medium:'🟡 Mức độ: Trung bình',high:'🔴 Mức độ: Nghiêm trọng',emergency:'🚨 MỨC ĐỘ: KHẨN CẤP'}[a.severity]||'')+'</strong><br><br>';
      html+='<strong>📋 Tóm tắt:</strong> '+a.summary+'<br><br>';
      if(a.possible_causes&&a.possible_causes.length){html+='<strong>🔍 Nguyên nhân có thể:</strong><br>';a.possible_causes.forEach(c=>{html+='• '+c+'<br>';});html+='<br>';}
      if(a.immediate_actions&&a.immediate_actions.length){html+='<strong>⚡ Cần làm ngay:</strong><br>';a.immediate_actions.forEach(c=>{html+='• '+c+'<br>';});html+='<br>';}
      if(a.home_care&&a.home_care.length){html+='<strong>🏠 Chăm sóc tại nhà:</strong><br>';a.home_care.forEach(c=>{html+='• '+c+'<br>';});html+='<br>';}
      html+='<strong>👨‍⚕️ Chuyên khoa phù hợp:</strong> '+a.recommended_specialty+'<br>';
      html+='<strong>📅 Khi nào gặp bác sĩ:</strong> '+a.when_to_see_doctor+'<br><br>';
      if(a.warning_signs&&a.warning_signs.length){html+='<strong>⚠️ Dấu hiệu nguy hiểm:</strong><br>';a.warning_signs.forEach(c=>{html+='• '+c+'<br>';});}
      html+='<br><em style="color:#64748b;font-size:0.85em">'+a.disclaimer+'</em></div>';
      startChat();const div=document.createElement('div');div.className='msg bot';
      div.innerHTML='<div class="msg-avatar">🩺</div><div class="msg-content">'+html+'<span class="source-badge '+(d.source==='gemini'?'gemini':'fallback')+'">'+(d.source==='gemini'?'<i class="bi bi-stars"></i> Gemini AI':'<i class="bi bi-cpu"></i> Trợ lý nội bộ')+'</span></div>';
      messagesArea.appendChild(div);messagesArea.scrollTop=messagesArea.scrollHeight;
    } else addMsg('Không thể phân tích. Vui lòng thử lại.',false,'fallback');
  }).catch(()=>{hideTyping();addMsg('Lỗi kết nối.',false,'fallback');});
}
</script>
</body>
</html>
