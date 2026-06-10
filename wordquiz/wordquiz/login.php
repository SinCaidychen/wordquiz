<?php
// ============================================================
//  login.php – Đăng nhập
// ============================================================
session_start();
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password =      $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = '아이디와 비밀번호를 입력해 주세요.';
    } else {
        $conn = getConnection();
        $sql  = "SELECT USER_ID, USERNAME, PASSWORD FROM USERS WHERE USERNAME = :uname";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':uname', $username);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);

        if ($row && password_verify($password, $row['PASSWORD'])) {
            $_SESSION['user_id']  = $row['USER_ID'];
            $_SESSION['username'] = $row['USERNAME'];
            header('Location: index.php');
            exit;
        } else {
            $error = '아이디 또는 비밀번호가 올바르지 않습니다.';
        }

        oci_free_statement($stmt);
        oci_close($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>WordQuiz – 로그인</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h2>로그인</h2>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <form method="POST" action="login.php">
        <label>아이디</label>
        <input type="text" name="username" required>

        <label>비밀번호</label>
        <input type="password" name="password" required>

        <button type="submit">로그인</button>
    </form>
    <p>계정이 없으신가요? <a href="register.php">회원가입</a></p>
</div>
</body>
</html>
