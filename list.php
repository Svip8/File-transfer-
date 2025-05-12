<?php
// list.php：返回 JSON 格式的文件列表 数据 
require 'db.php';
$spaceId = isset($_GET['id']) ? $_GET['id'] : 'home';  // 获取 URL 参数 id，如果没有，则使用 'home' 作为默认值
$db = DB::get();
$stmt = $db->prepare('SELECT id, name, path, size, mime, uploaded_at FROM files WHERE space_id = :space_id ORDER BY uploaded_at DESC');
$stmt->bindValue(':space_id', $spaceId, SQLITE3_TEXT);
$res = $stmt->execute();

$files = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $files[] = $row;
}
header('Content-Type: application/json');
echo json_encode($files);
