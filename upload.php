<?php
// upload.php：安全的分片上传处理，每次自动清理 1 小时前的旧文件 记录
require 'db.php';
$db = DB::get();

// 获取空间 ID
$spaceId = isset($_GET['id']) ? $_GET['id'] : 'home';  // 如果没有传递 ID，则默认为 home

// —— ① 自动清理：删除 1 小时前的旧文件记录 —— 
$threshold = time() - 1 * 3600;
$stmtClean = $db->prepare('SELECT id, path, uploaded_at, space_id FROM files WHERE space_id = :space_id');
$stmtClean->bindValue(':space_id', $spaceId, SQLITE3_TEXT);
$res = $stmtClean->execute();
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    if (strtotime($row['uploaded_at']) < $threshold) {
        $f = __DIR__ . '/uploads/' . $row['space_id'] . '/' . basename($row['path']);
        if (file_exists($f)) unlink($f);
        $del = $db->prepare('DELETE FROM files WHERE id = :id');
        $del->bindValue(':id', $row['id'], SQLITE3_INTEGER);
        $del->execute();
    }
}

// —— ② 分片参数 —— 
$uploadDir   = __DIR__ . '/uploads/' . $spaceId;  // 上传到指定的空间目录
$identifier  = preg_replace('/[^A-Za-z0-9_-]/', '', ($_REQUEST['resumableIdentifier']   ?? ''));
$filenameRaw = basename($_REQUEST['resumableFilename']     ?? 'unnamed');
$chunkNumber = (int)($_REQUEST['resumableChunkNumber'] ?? 0);
$totalChunks = (int)($_REQUEST['resumableTotalChunks'] ?? 0);
$tmpDir      = "$uploadDir/$identifier";

// 确保目录
if (!is_dir($tmpDir)) mkdir($tmpDir, 0700, true);

// —— ③ GET：分片检测 —— 
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $chunkFile = "$tmpDir/$chunkNumber";
    http_response_code(file_exists($chunkFile) ? 200 : 204);
    exit;
}

// —— ④ POST：接收分片 —— 
if (empty($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    http_response_code(400);
    exit;
}
$tmpName = $_FILES['file']['tmp_name'];
move_uploaded_file($tmpName, "$tmpDir/$chunkNumber");

// —— ⑤ 合并分片 —— 
$chunks = glob("$tmpDir/*");
if (count($chunks) === $totalChunks) {
    // 生成最终安全文件名：空间ID + MD5（原文件名）
    $safeName = $spaceId . '-' . md5($filenameRaw) . '-' . preg_replace('/[^\w.\-]/', '_', $filenameRaw);
    $finalPath = "$uploadDir/$safeName";

    $out = fopen($finalPath, 'wb');
    for ($i = 1; $i <= $totalChunks; $i++) {
        $in = fopen("$tmpDir/$i", 'rb');
        stream_copy_to_stream($in, $out);
        fclose($in);
    }
    fclose($out);

    // 删除分片目录
    array_map('unlink', $chunks);
    @rmdir($tmpDir);

    // —— ⑥ 检测 MIME 类型 —— 
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $finalPath);
        finfo_close($finfo);
    } else {
        $mime = 'application/octet-stream';
    }

    // —— ⑦ 插入数据库（预处理语句） —— 
    $size = filesize($finalPath);
    $stmt = $db->prepare('INSERT INTO files (name, path, size, mime, space_id) VALUES (:name, :path, :size, :mime, :space_id)');
    $stmt->bindValue(':name', $filenameRaw, SQLITE3_TEXT);
    $stmt->bindValue(':path', $safeName,   SQLITE3_TEXT);
    $stmt->bindValue(':size', $size,       SQLITE3_INTEGER);
    $stmt->bindValue(':mime', $mime,       SQLITE3_TEXT);
    $stmt->bindValue(':space_id', $spaceId, SQLITE3_TEXT);  // 插入空间 ID

    if (!$stmt->execute()) {
        // 获取数据库执行的错误信息
        error_log('DB INSERT ERROR: ' . $db->lastErrorMsg());
        http_response_code(500);  // 返回 500 错误代码
        exit;  // 停止执行后续代码
    }
}

http_response_code(200);
