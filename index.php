<?php
// index.php ：上传进度条和文件列表 页面
require 'db.php';  // 自动创建数据库和 uploads 目录
$spaceId = isset($_GET['id']) ? $_GET['id'] : 'home';  // 获取 URL 参数 id，如果没有，则使用 'home' 作为默认值
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>文件中转 Save 1H</title>
    <!-- Resumable.js -->
    <script src="https://cdn.jsdelivr.net/npm/resumablejs@1/resumable.min.js"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- 本地样式 -->
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- 上传进度条 -->
    <div id="progress-container">
        <div id="progress-bar"></div>
    </div>
    <!-- 上传完成提示 -->
    <div id="upload-complete">上传完成</div>
    <!-- 文件宫格列表 -->
    <div id="grid"></div>
    <!-- 本地脚本 -->
    <script src="app.js"></script>
    <script>
        const spaceId = '<?php echo $spaceId; ?>';  // 获取空间 ID，传递给 app.js
    </script>
</body>

</html>
