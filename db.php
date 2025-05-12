<?php
// db.php：初始化 SQLite 数据库和 uploads 目录 
class DB
{
    private static $instance = null;
    /** @var SQLite3 */
    private $db;

    private function __construct()
    {
        $this->db = new SQLite3(__DIR__ . '/files.db');
        $this->db->exec("CREATE TABLE IF NOT EXISTS files (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            path TEXT,
            size INTEGER,
            mime TEXT,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            space_id TEXT
        )");
        // 确保 uploads 目录存在
        $up = __DIR__ . '/uploads';
        if (!is_dir($up)) mkdir($up, 0777, true);
    }

    public static function get(): SQLite3
    {
        if (self::$instance === null) {
            self::$instance = new DB();
        }
        return self::$instance->db;
    }
}
