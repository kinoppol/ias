<?php
// Auto-migration — versioned so new migrations always run even on existing sessions
// Bump MIGRATE_VERSION whenever new migrations are added
define('MIGRATE_VERSION', 3);

if (!empty($_SESSION['_migrated_v']) && (int)$_SESSION['_migrated_v'] >= MIGRATE_VERSION) return;

function run_migrations($pdo) {
    $results = [];

    // M1: workplaces.work_days
    $cols = $pdo->query("SHOW COLUMNS FROM workplaces LIKE 'work_days'")->fetchAll();
    if (!$cols) {
        $pdo->exec("ALTER TABLE workplaces ADD COLUMN work_days VARCHAR(7) NOT NULL DEFAULT '1111100'");
        $results[] = ['name' => 'workplaces.work_days', 'status' => 'applied'];
    } else {
        $results[] = ['name' => 'workplaces.work_days', 'status' => 'ok'];
    }

    // M2: holidays table
    if (!$pdo->query("SHOW TABLES LIKE 'holidays'")->fetch()) {
        $pdo->exec("CREATE TABLE holidays (
            id INT AUTO_INCREMENT PRIMARY KEY,
            date DATE NOT NULL UNIQUE,
            name VARCHAR(200) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        $results[] = ['name' => 'table: holidays', 'status' => 'applied'];
    } else {
        $results[] = ['name' => 'table: holidays', 'status' => 'ok'];
    }

    // M3: trainer role in users.role ENUM
    $colInfo = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
    if ($colInfo && strpos($colInfo['Type'], 'trainer') === false) {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('student','teacher','admin','trainer') NOT NULL");
        $results[] = ['name' => "users.role ENUM += 'trainer'", 'status' => 'applied'];
    } else {
        $results[] = ['name' => "users.role ENUM += 'trainer'", 'status' => 'ok'];
    }

    // M4: tasks table
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
        $results[] = ['name' => 'table: tasks', 'status' => 'applied'];
    } else {
        $results[] = ['name' => 'table: tasks', 'status' => 'ok'];
    }

    // M5: task_attachments
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
        $results[] = ['name' => 'table: task_attachments', 'status' => 'applied'];
    } else {
        $results[] = ['name' => 'table: task_attachments', 'status' => 'ok'];
    }

    // M6: task_threads
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
        $results[] = ['name' => 'table: task_threads', 'status' => 'applied'];
    } else {
        $results[] = ['name' => 'table: task_threads', 'status' => 'ok'];
    }

    // M7: task_thread_attachments
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
        $results[] = ['name' => 'table: task_thread_attachments', 'status' => 'applied'];
    } else {
        $results[] = ['name' => 'table: task_thread_attachments', 'status' => 'ok'];
    }

    return $results;
}

try {
    run_migrations($pdo);
    $_SESSION['_migrated_v'] = MIGRATE_VERSION;
} catch (Throwable $e) {
    // Store error for admin to see, but don't crash
    $_SESSION['_migrate_error'] = $e->getMessage();
}
