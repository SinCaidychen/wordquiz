<?php
// ============================================================
//  db.php – Kết nối Oracle (dùng chung toàn project)
//  Chỉnh DB_HOST, DB_PORT, DB_SID theo server của trường
// ============================================================

define('DB_HOST', 'earth.gwangju.ac.kr');   // ← đổi thành IP/host server trường
define('DB_PORT', '1521');
define('DB_SID',  'orcl');          // ← đổi thành SID hoặc Service Name trường
define('DB_USER', 'dbuser241593'); // ← tài khoản Oracle trường
define('DB_PASS', 'ce1234'); // ← mật khẩu Oracle trường

function getConnection() {
    $dsn  = DB_HOST . ':' . DB_PORT . '/' . DB_SID;
    $conn = oci_connect(DB_USER, DB_PASS, $dsn, 'AL32UTF8');
    if (!$conn) {
        $e = oci_error();
        die('Không thể kết nối database: ' . htmlspecialchars($e['message']));
    }
    return $conn;
}
?>
