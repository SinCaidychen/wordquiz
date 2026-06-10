<?php
// ============================================================
//  result.php – Hiển thị kết quả sau khi làm quiz
// ============================================================
session_start();
require_once 'includes/auth.php';
requireLogin();

$score   = $_SESSION['last_score']   ?? 0;
$total_q = $_SESSION['last_total_q'] ?? 10;

// Tính xếp loại
if ($score == 0) {
    $grade = '😢 다시 도전해 보세요!';
} elseif ($score <= $total_q) {
    $grade = '👍 잘 하셨어요!';
} elseif ($score <= $total_q * 2) {
    $grade = '🎉 훌륭해요!';
} else {
    $grade = '🏆 완벽해요!';
}

// Xoá sau khi đọc
unset($_SESSION['last_score'], $_SESSION['last_total_q']);
?>
<!DOCTYPE html>
<html lang="ko"><head>
    <meta charset="UTF-8">
    <title>WordQuiz – 결과</title>
    <link rel="stylesheet" href="css/style.css">
</head><body>
<div class="container result-box">
    <h2>퀴즈 결과</h2>
    <div class="score-display">
        <p class="score-num"><?= $score ?>점</p>
        <p class="grade"><?= $grade ?></p>
        <p><?= $total_q ?>문제 완료</p>
    </div>
    <div class="result-buttons">
        <a href="quiz.php" class="btn btn-primary">다시 풀기</a>
        <a href="leaderboard.php" class="btn btn-secondary">🏆 리더보드 보기</a>
        <a href="index.php" class="btn btn-outline">홈으로</a>
    </div>
</div>
</body></html>
