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
    // Add trainer role to users.role ENUM
    $colInfo = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
    if ($colInfo && strpos($colInfo['Type'], 'trainer') === false) {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('student','teacher','admin','trainer') NOT NULL");
    }
    // tasks table
    if (!$pdo->query("SHOW TABLES LIKE 'tasks'")->fetch()) {
        $pdo->exec("CREATE TABLE tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(500) NOT NULL,
            description TEXT,
            score INT NOT NULL DEFAULT 10,
            trainer_id VARCHAR(20) NOT NULL,
            student_id VARCHAR(20) NOT NULL,
            status ENUM('active','completed','terminated') NOT NULL DEFAULT 'active',
            close_note TEXT NULL,
            viewed_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            closed_at DATETIME NULL,
            INDEX idx_trainer(trainer_id),
            INDEX idx_student(student_id)
        ) ENGINE=InnoDB");
    }
    // task_attachments
    if (!$pdo->query("SHOW TABLES LIKE 'task_attachments'")->fetch()) {
        $pdo->exec("CREATE TABLE task_attachments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL,
            att_type ENUM('file','link') NOT NULL DEFAULT 'file',
            original_name VARCHAR(500) NULL,
            stored_name VARCHAR(500) NULL,
            link_url VARCHAR(1000) NULL,
            mime_type VARCHAR(100) NULL,
            file_size INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");
    }
    // task_threads
    if (!$pdo->query("SHOW TABLES LIKE 'task_threads'")->fetch()) {
        $pdo->exec("CREATE TABLE task_threads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL,
            author_id VARCHAR(20) NOT NULL,
            entry_type ENUM('submission','comment') NOT NULL,
            content TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_task(task_id)
        ) ENGINE=InnoDB");
    }
    // task_thread_attachments
    if (!$pdo->query("SHOW TABLES LIKE 'task_thread_attachments'")->fetch()) {
        $pdo->exec("CREATE TABLE task_thread_attachments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            thread_id INT NOT NULL,
            att_type ENUM('file','link') NOT NULL DEFAULT 'file',
            original_name VARCHAR(500) NULL,
            stored_name VARCHAR(500) NULL,
            link_url VARCHAR(1000) NULL,
            mime_type VARCHAR(100) NULL,
            file_size INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (thread_id) REFERENCES task_threads(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");
    }
} catch (Throwable $e) {
    // Silently skip if migration fails — non-critical path
}
