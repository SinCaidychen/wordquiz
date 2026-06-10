<?php
// ============================================================
//  index.php – Trang chủ
// ============================================================
session_start();
require_once 'includes/auth.php';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>WordQuiz – 홈</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1>🎯 WordQuiz</h1>
    <p class="subtitle">영어 단어를 한국어로 맞춰보세요!</p>

    <?php if (isLoggedIn()): ?>
        <p>안녕하세요, <strong><?= htmlspecialchars(currentUsername()) ?></strong> 님!</p>
        <a href="quiz.php" class="btn btn-primary">퀴즈 시작하기</a>
        <a href="leaderboard.php" class="btn btn-secondary">🏆 리더보드</a>
        <a href="logout.php" class="btn btn-logout">로그아웃</a>
    <?php else: ?>
        <a href="login.php"    class="btn btn-primary">로그인</a>
        <a href="register.php" class="btn btn-secondary">회원가입</a>
    <?php endif; ?>
</div>
</body>
</html>
