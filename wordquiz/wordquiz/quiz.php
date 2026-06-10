<?php
// ============================================================
//  quiz.php – WordQuiz
//  Ver: không dùng QUIZ_LOG
// ============================================================
session_start();
require_once 'includes/auth.php';
require_once 'db.php';
requireLogin();

$conn = getConnection();

// ── BƯỚC 1: Chọn category ─────────────────────────────────
if (!isset($_SESSION['quiz_cat']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = oci_parse($conn, "SELECT CAT_ID, CAT_NAME FROM CATEGORIES ORDER BY CAT_ID");
    oci_execute($stmt);
    $categories = [];
    while ($row = oci_fetch_assoc($stmt)) $categories[] = $row;
    oci_free_statement($stmt);
    oci_close($conn);
    ?>
    <!DOCTYPE html>
    <html lang="ko"><head>
        <meta charset="UTF-8">
        <title>WordQuiz – 카테고리 선택</title>
        <link rel="stylesheet" href="css/style.css">
    </head><body>
    <div class="container">
        <h2>카테고리를 선택하세요</h2>
        <form method="POST" action="quiz.php">
            <div class="category-grid">
                <?php foreach ($categories as $cat): ?>
                <label class="cat-card">
                    <input type="radio" name="cat_id" value="<?= $cat['CAT_ID'] ?>">
                    <?= htmlspecialchars($cat['CAT_NAME']) ?>
                </label>
                <?php endforeach; ?>
                <label class="cat-card">
                    <input type="radio" name="cat_id" value="0" checked>
                    🎲 랜덤 (전체)
                </label>
            </div>
            <br>
            <label>문제 수:
                <select name="total_q">
                    <option value="5">5문제</option>
                    <option value="10" selected>10문제</option>
                    <option value="20">20문제</option>
                </select>
            </label>
            <br><br>
            <button type="submit" name="action" value="start" class="btn btn-primary">시작하기</button>
            <a href="index.php" class="btn btn-secondary">홈으로</a>
        </form>
    </div>
    </body></html>
    <?php
    exit;
}

// ── BƯỚC 2: Bắt đầu session quiz mới ─────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'start') {
    $cat_id  = (int)($_POST['cat_id']  ?? 0);
    $total_q = (int)($_POST['total_q'] ?? 10);
    if ($total_q < 1 || $total_q > 50) $total_q = 10;
    $uid = currentUserId();

    // Bước 2a: Lấy NEXTVAL trước
    $s0 = oci_parse($conn, "SELECT SEQ_SESSION.NEXTVAL AS NV FROM DUAL");
    oci_execute($s0);
    $r0 = oci_fetch_assoc($s0);
    oci_free_statement($s0);
    $new_sid = (int)$r0['NV'];

    // Bước 2b: Insert với SESSION_ID và USER_ID tường minh
    // Dùng tên bind dài để tránh reserved words Oracle
    $s1 = oci_parse($conn, "INSERT INTO QUIZ_SESSION (SESSION_ID, USER_ID, TOTAL_SCORE) VALUES (:p_sid, :p_uid, 0)");
    oci_bind_by_name($s1, ':p_sid', $new_sid);
    oci_bind_by_name($s1, ':p_uid', $uid);
    oci_execute($s1);
    oci_free_statement($s1);

    $_SESSION['quiz_session_id'] = $new_sid;
    $_SESSION['quiz_cat']        = $cat_id;
    $_SESSION['quiz_total']      = $total_q;
    $_SESSION['quiz_current']    = 0;
    $_SESSION['quiz_score']      = 0;
    $_SESSION['quiz_word_ids']   = [];

    // Bước 2c: Lấy danh sách WORD_ID ngẫu nhiên
    if ($cat_id == 0) {
        $sql3 = "SELECT WORD_ID FROM (SELECT WORD_ID FROM WORDS ORDER BY DBMS_RANDOM.VALUE) WHERE ROWNUM <= :p_n";
    } else {
        $sql3 = "SELECT WORD_ID FROM (SELECT WORD_ID FROM WORDS WHERE CAT_ID = :p_cat ORDER BY DBMS_RANDOM.VALUE) WHERE ROWNUM <= :p_n";
    }
    $s3 = oci_parse($conn, $sql3);
    oci_bind_by_name($s3, ':p_n', $total_q);
    if ($cat_id != 0) oci_bind_by_name($s3, ':p_cat', $cat_id);
    oci_execute($s3);
    $word_ids = [];
    while ($w = oci_fetch_assoc($s3)) $word_ids[] = $w['WORD_ID'];
    oci_free_statement($s3);
    $_SESSION['quiz_word_ids'] = $word_ids;
}

// ── BƯỚC 3: Xử lý câu trả lời ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'answer') {
    $user_ans   = $_POST['user_ans']   ?? '';
    $correct_ko = $_POST['correct_ko'] ?? '';
    $difficulty = (int)($_POST['difficulty'] ?? 1);
    $session_id = (int)($_SESSION['quiz_session_id'] ?? 0);

    $score = ($user_ans === $correct_ko) ? $difficulty : 0;

    $_SESSION['quiz_score']   += $score;
    $_SESSION['quiz_current'] += 1;

    // Cập nhật TOTAL_SCORE
    $total = $_SESSION['quiz_score'];
    $s2 = oci_parse($conn, "UPDATE QUIZ_SESSION SET TOTAL_SCORE = :p_sc WHERE SESSION_ID = :p_sid");
    oci_bind_by_name($s2, ':p_sc',  $total);
    oci_bind_by_name($s2, ':p_sid', $session_id);
    oci_execute($s2);
    oci_free_statement($s2);
}

// ── BƯỚC 4: Kiểm tra quiz đã kết thúc chưa ───────────────
$current = $_SESSION['quiz_current'] ?? 0;
$total_q = $_SESSION['quiz_total']   ?? 10;

if ($current >= $total_q) {
    $uid         = currentUserId();
    $final_score = $_SESSION['quiz_score'];

    // Upsert LEADERBOARD
    $chk = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM LEADERBOARD WHERE USER_ID = :p_uid");
    oci_bind_by_name($chk, ':p_uid', $uid);
    oci_execute($chk);
    $r = oci_fetch_assoc($chk);
    oci_free_statement($chk);

    if ((int)$r['CNT'] > 0) {
        $upd = oci_parse($conn, "UPDATE LEADERBOARD SET TOTAL_SCORE = TOTAL_SCORE + :p_sc, UPDATED_AT = SYSDATE WHERE USER_ID = :p_uid");
    } else {
        $upd = oci_parse($conn, "INSERT INTO LEADERBOARD (USER_ID, TOTAL_SCORE) VALUES (:p_uid, :p_sc)");
    }
    oci_bind_by_name($upd, ':p_uid', $uid);
    oci_bind_by_name($upd, ':p_sc',  $final_score);
    oci_execute($upd);
    oci_free_statement($upd);
    oci_close($conn);

    $_SESSION['last_score']   = $final_score;
    $_SESSION['last_total_q'] = $total_q;
    unset($_SESSION['quiz_cat'],     $_SESSION['quiz_word_ids'],
          $_SESSION['quiz_current'], $_SESSION['quiz_total'],
          $_SESSION['quiz_score'],   $_SESSION['quiz_session_id']);
    header('Location: result.php');
    exit;
}

// ── BƯỚC 5: Hiển thị câu hỏi ─────────────────────────────
$word_ids = $_SESSION['quiz_word_ids'];
$word_id  = $word_ids[$current];

$sw = oci_parse($conn, "SELECT WORD_ID, WORD_EN, WORD_KO, DIFFICULTY FROM WORDS WHERE WORD_ID = :p_wid");
oci_bind_by_name($sw, ':p_wid', $word_id);
oci_execute($sw);
$word = oci_fetch_assoc($sw);
oci_free_statement($sw);

$sw2 = oci_parse($conn, "SELECT WORD_KO FROM (SELECT WORD_KO FROM WORDS WHERE WORD_ID != :p_wid ORDER BY DBMS_RANDOM.VALUE) WHERE ROWNUM <= 3");
oci_bind_by_name($sw2, ':p_wid', $word_id);
oci_execute($sw2);
$wrongs = [];
while ($w = oci_fetch_assoc($sw2)) $wrongs[] = $w['WORD_KO'];
oci_free_statement($sw2);
oci_close($conn);

$choices = array_merge([$word['WORD_KO']], $wrongs);
shuffle($choices);
?>
<!DOCTYPE html>
<html lang="ko"><head>
    <meta charset="UTF-8">
    <title>WordQuiz – 퀴즈</title>
    <link rel="stylesheet" href="css/style.css">
</head><body>
<div class="container">
    <div class="quiz-header">
        <span>문제 <?= $current + 1 ?> / <?= $total_q ?></span>
        <span>점수: <?= $_SESSION['quiz_score'] ?>점</span>
    </div>
    <div class="progress-bar">
        <div class="progress-fill" style="width:<?= round(($current / $total_q) * 100) ?>%"></div>
    </div>
    <div class="question-box">
        <p class="difficulty">난이도: <?= str_repeat('⭐', $word['DIFFICULTY']) ?></p>
        <h2><?= htmlspecialchars($word['WORD_EN']) ?></h2>
        <p>이 단어의 한국어 뜻은?</p>
    </div>
    <form method="POST" action="quiz.php">
        <input type="hidden" name="action"     value="answer">
        <input type="hidden" name="word_id"    value="<?= $word['WORD_ID'] ?>">
        <input type="hidden" name="correct_ko" value="<?= htmlspecialchars($word['WORD_KO']) ?>">
        <input type="hidden" name="difficulty" value="<?= $word['DIFFICULTY'] ?>">
        <div class="choices">
            <?php foreach ($choices as $choice): ?>
            <button type="submit" name="user_ans" value="<?= htmlspecialchars($choice) ?>" class="choice-btn">
                <?= htmlspecialchars($choice) ?>
            </button>
            <?php endforeach; ?>
        </div>
    </form>
</div>
<script>
document.querySelectorAll('.choice-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();  // Chặn submit ngay
        const correct = document.querySelector('input[name="correct_ko"]').value;
        document.querySelectorAll('.choice-btn').forEach(b => b.disabled = true);
        if (this.value === correct) {
            this.style.background = '#2d6a4f';
            this.style.color = '#fff';
        } else {
            this.style.background = '#9b2226';
            this.style.color = '#fff';
            document.querySelectorAll('.choice-btn').forEach(b => {
                if (b.value === correct) {
                    b.style.background = '#2d6a4f';
                    b.style.color = '#fff';
                }
            });
        }
        // Chờ 1 giây rồi mới submit
        const form = this.closest('form');
        const hiddenAns = document.createElement('input');
        hiddenAns.type  = 'hidden';
        hiddenAns.name  = 'user_ans';
        hiddenAns.value = this.value;
        form.appendChild(hiddenAns);
        setTimeout(() => form.submit(), 900);
    });
});
</script>
</body></html>
