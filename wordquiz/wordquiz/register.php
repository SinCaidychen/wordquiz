<?php
// ============================================================
//  register.php – Đăng ký tài khoản
// ============================================================
session_start();
require_once 'db.php';

// Nếu đã đăng nhập → về trang chủ
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';
    $confirm  =      $_POST['confirm']  ?? '';

    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = '모든 필드를 입력해 주세요.'; // Vui lòng điền đầy đủ
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '이메일 형식이 올바르지 않습니다.';
    } elseif (strlen($password) < 6) {
        $error = '비밀번호는 6자 이상이어야 합니다.';
    } elseif ($password !== $confirm) {
        $error = '비밀번호가 일치하지 않습니다.';
    } else {
        $conn = getConnection();

        // Kiểm tra username/email đã tồn tại chưa
        $sql  = "SELECT COUNT(*) AS CNT FROM USERS WHERE USERNAME = :uname OR EMAIL = :email";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':uname', $username);
        oci_bind_by_name($stmt, ':email', $email);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);

        if ((int)$row['CNT'] > 0) {
            $error = '이미 사용 중인 아이디 또는 이메일입니다.';
        } else {
            // Insert user mới
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql2   = "INSERT INTO USERS (USERNAME, EMAIL, PASSWORD) VALUES (:uname, :email, :pass)";
            $stmt2  = oci_parse($conn, $sql2);
            oci_bind_by_name($stmt2, ':uname', $username);
            oci_bind_by_name($stmt2, ':email', $email);
            oci_bind_by_name($stmt2, ':pass',  $hashed);

            if (oci_execute($stmt2)) {
                $success = '회원가입이 완료되었습니다. 로그인해 주세요.';
            } else {
                $e     = oci_error($stmt2);
                $error = '오류가 발생했습니다: ' . htmlspecialchars($e['message']);
            }
            oci_free_statement($stmt2);
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
    <title>WordQuiz – 회원가입</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h2>회원가입</h2>
    <?php if ($error):   ?><p class="error"><?= htmlspecialchars($error)   ?></p><?php endif; ?>
    <?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>

    <form method="POST" action="register.php">
        <label>아이디</label>
        <input type="text" name="username" required maxlength="50">

        <label>이메일</label>
        <input type="email" name="email" required maxlength="100">

        <label>비밀번호</label>
        <input type="password" name="password" required minlength="6">

        <label>비밀번호 확인</label>
        <input type="password" name="confirm" required>

        <button type="submit">가입하기</button>
    </form>
    <p>이미 계정이 있으신가요? <a href="login.php">로그인</a></p>
</div>
</body>
</html>
