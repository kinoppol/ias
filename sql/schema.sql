-- ระบบบันทึกการเข้างานฝึกงาน (OVEC Internship Attendance System)
CREATE DATABASE IF NOT EXISTS ovec_attendance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ovec_attendance;

CREATE TABLE workplaces (
  id VARCHAR(20) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  address VARCHAR(255) NOT NULL,
  lat DECIMAL(10,7) NOT NULL,
  lng DECIMAL(10,7) NOT NULL,
  radius INT NOT NULL DEFAULT 200,
  start_time TIME NOT NULL DEFAULT '08:00:00',
  end_time TIME NOT NULL DEFAULT '17:00:00',
  teacher_id VARCHAR(20) NULL,
  allow_ot TINYINT(1) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  work_days VARCHAR(7) NOT NULL DEFAULT '1111100'
) ENGINE=InnoDB;

CREATE TABLE users (
  id VARCHAR(20) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  role ENUM('student','teacher','admin','trainer') NOT NULL,
  password VARCHAR(255) NOT NULL,
  grade VARCHAR(50) NULL,
  dept VARCHAR(100) NULL,
  workplace_id VARCHAR(20) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (workplace_id) REFERENCES workplaces(id) ON DELETE SET NULL
) ENGINE=InnoDB;

ALTER TABLE workplaces ADD CONSTRAINT fk_wp_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE attendance (
  id VARCHAR(64) PRIMARY KEY,
  student_id VARCHAR(20) NOT NULL,
  date DATE NOT NULL,
  check_in_time DATETIME NULL,
  check_in_lat DECIMAL(10,7) NULL,
  check_in_lng DECIMAL(10,7) NULL,
  check_in_dist INT NULL,
  check_in_in_radius TINYINT(1) NULL,
  check_out_time DATETIME NULL,
  check_out_lat DECIMAL(10,7) NULL,
  check_out_lng DECIMAL(10,7) NULL,
  status ENUM('present','late','half-day','absent') NOT NULL DEFAULT 'present',
  workplace_id VARCHAR(20) NULL,
  UNIQUE KEY uq_student_date (student_id, date),
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (workplace_id) REFERENCES workplaces(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE leaves (
  id VARCHAR(30) PRIMARY KEY,
  student_id VARCHAR(20) NOT NULL,
  date DATE NOT NULL,
  type ENUM('sick','personal','holiday') NOT NULL,
  reason VARCHAR(500) NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  approved_by VARCHAR(20) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE holidays (
  id INT AUTO_INCREMENT PRIMARY KEY,
  date DATE NOT NULL UNIQUE,
  name VARCHAR(200) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE settings (
  k VARCHAR(50) PRIMARY KEY,
  v VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

INSERT INTO settings (k, v) VALUES
  ('early_checkin_minutes', '60'),
  ('late_grace_minutes', '30'),
  ('institution_name', 'สำนักงานคณะกรรมการการอาชีวศึกษา (OVEC)');

CREATE TABLE tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(500) NOT NULL,
  description TEXT,
  score INT NOT NULL DEFAULT 10,
  due_date DATETIME NULL,
  trainer_id VARCHAR(20) NOT NULL,
  student_id VARCHAR(20) NOT NULL,
  status ENUM('active','completed','terminated') NOT NULL DEFAULT 'active',
  close_note TEXT NULL,
  viewed_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  closed_at DATETIME NULL,
  INDEX idx_trainer(trainer_id),
  INDEX idx_student(student_id)
) ENGINE=InnoDB;

CREATE TABLE task_attachments (
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
) ENGINE=InnoDB;

CREATE TABLE task_threads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  author_id VARCHAR(20) NOT NULL,
  entry_type ENUM('submission','comment') NOT NULL,
  content TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_task(task_id)
) ENGINE=InnoDB;

CREATE TABLE task_thread_attachments (
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
) ENGINE=InnoDB;

-- Initial admin account (no demo student/teacher accounts)
INSERT INTO users (id, name, role, password, grade, dept, workplace_id) VALUES
  ('ADMIN', 'ผู้ดูแลระบบ', 'admin', 'password', NULL, NULL, NULL);
