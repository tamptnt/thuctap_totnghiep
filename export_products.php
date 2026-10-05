<?php
// Xuất bảng sản phẩm ra Excel. Chỉ admin đã đăng nhập mới dùng được.
// XÓA FILE NÀY SAU KHI XUẤT XONG.
require_once 'config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    exit('Chỉ admin được dùng. Hãy đăng nhập tài khoản admin trên site trước.');
}

$res = $conn->query("SELECT p.*, c.name AS category_name, b.name AS brand_name
                     FROM products p
                     LEFT JOIN categories c ON p.category_id = c.id
                     LEFT JOIN brands b ON p.brand_id = b.id
                     ORDER BY p.id");
if (!$res) exit('Lỗi truy vấn: ' . htmlspecialchars($conn->error));

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="products_' . date('Ymd_His') . '.xls"');
echo "\xEF\xBB\xBF";
echo '<html><head><meta charset="utf-8"></head><body><table border="1">';

$first = true;
while ($row = $res->fetch_assoc()) {
    if ($first) {
        echo '<tr>';
        foreach (array_keys($row) as $col) echo '<th>' . htmlspecialchars($col) . '</th>';
        echo '</tr>';
        $first = false;
    }
    echo '<tr>';
    foreach ($row as $col => $val) {
        $val = (string)$val;
        if ($col === 'description') $val = trim(strip_tags($val));
        echo '<td>' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '</td>';
    }
    echo '</tr>';
}
echo '</table></body></html>';
