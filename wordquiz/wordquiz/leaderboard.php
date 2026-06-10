<?php
// ============================================================
//  leaderboard.php – Bảng xếp hạng
// ============================================================
session_start();
require_once 'includes/auth.php';
require_once 'db.php';
requireLogin();

$conn = getConnection();

// Top 20 người dùng
$sql  = "SELECT ROWNUM AS RANK_NO, U.USERNAME, L.TOTAL_SCORE, L.UPDATED_AT
         FROM (
             SELECT USER_ID, TOTAL_SCORE, UPDATED_AT
             FROM LEADERBOARD
             ORDER BY TOTAL_SCORE DESC
         ) L
         JOIN USERS U ON U.USER_ID = L.USER_ID
         WHERE ROWNUM <= 20";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
$rows = [];
while ($r = oci_fetch_assoc($stmt)) {
    $rows[] = $r;
}
oci_free_statement($stmt);
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="ko"><head>
    <meta charset="UTF-8">
    <title>WordQuiz – 리더보드</title>
    <link rel="stylesheet" href="css/style.css">
</head><body>
<div class="container">
    <h2>🏆 리더보드</h2>
    <?php if (empty($rows)): ?>
        <p>아직 기록이 없습니다.</p>
    <?php else: ?>
    <table class="leaderboard-table">
        <thead>
            <tr>
                <th>순위</th>
                <th>아이디</th>
                <th>총점</th>
                <th>마지막 플레이</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr class="<?= ($r['USERNAME'] === currentUsername()) ? 'highlight' : '' ?>">
                <td>
                    <?php if ($r['RANK_NO'] == 1) echo '🥇';
                    elseif ($r['RANK_NO'] == 2) echo '🥈';
                    elseif ($r['RANK_NO'] == 3) echo '🥉';
                    else echo '#' . $r['RANK_NO']; ?>
                </td>
                <td><?= htmlspecialchars($r['USERNAME']) ?></td>
                <td><?= number_format($r['TOTAL_SCORE']) ?>점</td>
                <td><?= htmlspecialchars($r['UPDATED_AT']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <br>
    <a href="quiz.php"  class="btn btn-primary">퀴즈 풀기</a>
    <a href="index.php" class="btn btn-secondary">홈으로</a>
</div>
</body></html>
