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
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE users (
  id VARCHAR(20) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  role ENUM('student','teacher','admin') NOT NULL,
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

CREATE TABLE settings (
  k VARCHAR(50) PRIMARY KEY,
  v VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

INSERT INTO settings (k, v) VALUES
  ('early_checkin_minutes', '60'),
  ('late_grace_minutes', '30'),
  ('institution_name', 'สำนักงานคณะกรรมการการอาชีวศึกษา (OVEC)');

-- Seed data
INSERT INTO workplaces (id, name, address, lat, lng, radius, start_time, end_time, teacher_id, allow_ot, active) VALUES
  ('WP001', 'บริษัท ไทยอิเล็กทรอนิกส์ จำกัด', '123 ถ.สุขุมวิท กรุงเทพฯ', 13.7563000, 100.5018000, 200, '08:00:00', '17:00:00', NULL, 1, 1),
  ('WP002', 'บริษัท เทคโนโลยีก้าวหน้า จำกัด', '456 ถ.พหลโยธิน กรุงเทพฯ', 13.8000000, 100.5500000, 150, '08:30:00', '17:30:00', NULL, 0, 1);

INSERT INTO users (id, name, role, password, grade, dept, workplace_id) VALUES
  ('STD001', 'นายกฤษณะ มีสุข', 'student', '1234', 'ปวส.2', 'ช่างไฟฟ้า', 'WP001'),
  ('STD002', 'นางสาวสุภาพร ใจดี', 'student', '1234', 'ปวส.2', 'การบัญชี', 'WP001'),
  ('STD003', 'นายวิชัย ฉลาดเลิศ', 'student', '1234', 'ปวส.1', 'คอมพิวเตอร์', 'WP002'),
  ('STD004', 'นางสาวมาลี สวยงาม', 'student', '1234', 'ปวส.1', 'การตลาด', 'WP001'),
  ('STD005', 'นายพิชิต เก่งมาก', 'student', '1234', 'ปวส.2', 'ช่างยนต์', 'WP002'),
  ('TEACHER01', 'อ.สมศักดิ์ วิทยาการ', 'teacher', '1234', NULL, 'ฝ่ายแนะแนว', NULL),
  ('ADMIN', 'ผู้ดูแลระบบ', 'admin', '1234', NULL, NULL, NULL);

UPDATE workplaces SET teacher_id = 'TEACHER01' WHERE id IN ('WP001','WP002');

INSERT INTO leaves (id, student_id, date, type, reason, status, approved_by) VALUES
  ('LV001', 'STD001', DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'sick', 'ไม่สบาย มีไข้', 'approved', 'TEACHER01'),
  ('LV002', 'STD002', DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'personal', 'ธุระด่วน', 'pending', NULL),
  ('LV003', 'STD003', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'sick', 'ปวดหัว', 'pending', NULL);
