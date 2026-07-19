<?php
// Auto-migration: runs once per session to add missing columns/tables
if (!empty($_SESSION['_migrated'])) return;
$_SESSION['_migrated'] = true;

try {
    // workplaces.work_days
    $cols = $pdo->query("SHOW COLUMNS FROM workplaces LIKE 'work_days'")->fetchAll();
    if (!$cols) {
        $pdo->exec("ALTER TABLE workplaces ADD COLUMN work_days VARCHAR(7) NOT NULL DEFAULT '1111100'");
    }
    // holidays table
    $tables = $pdo->query("SHOW TABLES LIKE 'holidays'")->fetchAll();
    if (!$tables) {
        $pdo->exec("CREATE TABLE holidays (
            id INT AUTO_INCREMENT PRIMARY KEY,
            date DATE NOT NULL UNIQUE,
            name VARCHAR(200) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
    }
} catch (Throwable $e) {
    // Silently skip if migration fails — non-critical path
}
