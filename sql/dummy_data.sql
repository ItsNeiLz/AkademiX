-- ============================================
-- AkademiX Dummy Data
-- Database: akademix_db
-- ============================================
-- Passwords are all 'password123' hashed with password_hash()
-- Pre-computed hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

USE akademix_db;

-- ============================================
-- USERS (10 users)
-- ============================================
INSERT INTO users (name, nim, email, password, role, profile_photo) VALUES
('Admin AkademiX', '000000001', 'admin@akademix.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', NULL),
('Budi Santoso', '210101001', 'budi@student.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ketua Kelompok', NULL),
('Siti Nurhaliza', '210101002', 'siti@student.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ketua Kelompok', NULL),
('Ahmad Rizky', '210101003', 'ahmad@student.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ketua Kelompok', NULL),
('Dewi Lestari', '210101004', 'dewi@student.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anggota', NULL),
('Eko Prasetyo', '210101005', 'eko@student.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anggota', NULL),
('Fitri Handayani', '210101006', 'fitri@student.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anggota', NULL),
('Gilang Ramadhan', '210101007', 'gilang@student.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anggota', NULL),
('Hana Safitri', '210101008', 'hana@student.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anggota', NULL),
('Irfan Maulana', '210101009', 'irfan@student.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anggota', NULL);

-- ============================================
-- COURSES (5 courses)
-- ============================================
INSERT INTO courses (course_name, lecturer_name, semester) VALUES
('Pemrograman Web', 'Dr. Andi Wijaya, M.Kom', 4),
('Basis Data', 'Prof. Ratna Sari, M.T.', 3),
('Algoritma & Struktur Data', 'Dr. Hendra Kusuma, M.Cs', 2),
('Rekayasa Perangkat Lunak', 'Dr. Maya Putri, M.Kom', 5),
('Jaringan Komputer', 'Dr. Fajar Nugroho, M.T.', 4);

-- ============================================
-- GROUPS (4 groups)
-- ============================================
INSERT INTO `groups` (group_name, description, course_id, leader_id) VALUES
('Kelompok Alpha', 'Kelompok pengembangan website e-commerce untuk tugas akhir Pemrograman Web', 1, 2),
('Kelompok Beta', 'Kelompok proyek database perpustakaan digital', 2, 3),
('Kelompok Gamma', 'Kelompok implementasi algoritma sorting dan searching', 3, 4),
('Kelompok Delta', 'Kelompok pengembangan aplikasi manajemen proyek RPL', 4, 2);

-- ============================================
-- GROUP MEMBERS
-- ============================================
-- Kelompok Alpha (Budi as leader + Dewi, Eko, Fitri)
INSERT INTO group_members (group_id, user_id) VALUES
(1, 2), (1, 5), (1, 6), (1, 7),
-- Kelompok Beta (Siti as leader + Gilang, Hana)
(2, 3), (2, 8), (2, 9),
-- Kelompok Gamma (Ahmad as leader + Irfan, Dewi)
(3, 4), (3, 10), (3, 5),
-- Kelompok Delta (Budi as leader + Eko, Gilang, Hana)
(4, 2), (4, 6), (4, 8), (4, 9);

-- ============================================
-- TASKS (16 tasks with varied statuses, priorities, deadlines)
-- ============================================
INSERT INTO tasks (title, description, course_id, group_id, created_by, deadline, priority, status) VALUES
-- Kelompok Alpha - Pemrograman Web
('Desain UI Website', 'Membuat desain mockup untuk halaman utama, login, dan dashboard menggunakan Figma', 1, 1, 2, DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'High', 'In Progress'),
('Implementasi Backend', 'Mengembangkan API backend menggunakan PHP Native dengan PDO', 1, 1, 2, DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'High', 'Not Started'),
('Database Design', 'Merancang ERD dan membuat skema database MySQL', 1, 1, 2, DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'Medium', 'Completed'),
('Testing & Deployment', 'Melakukan testing unit dan deployment ke server', 1, 1, 2, DATE_ADD(CURDATE(), INTERVAL 21 DAY), 'Low', 'Not Started'),

-- Kelompok Beta - Basis Data
('Normalisasi Database', 'Melakukan normalisasi tabel dari 1NF hingga 3NF', 2, 2, 3, DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'High', 'In Progress'),
('Query Optimization', 'Mengoptimalkan query SQL untuk performa yang lebih baik', 2, 2, 3, DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'Medium', 'Not Started'),
('Laporan Proyek DB', 'Menyusun laporan akhir proyek basis data', 2, 2, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'High', 'In Progress'),
('Presentasi Basis Data', 'Menyiapkan slide presentasi untuk demo proyek', 2, 2, 3, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Medium', 'Not Started'),

-- Kelompok Gamma - Algoritma
('Implementasi Sorting', 'Mengimplementasikan algoritma Merge Sort dan Quick Sort', 3, 3, 4, DATE_ADD(CURDATE(), INTERVAL 12 DAY), 'High', 'In Progress'),
('Analisis Kompleksitas', 'Menganalisis Big-O dari setiap algoritma yang diimplementasikan', 3, 3, 4, DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'Medium', 'Not Started'),
('Visualisasi Algoritma', 'Membuat visualisasi interaktif proses sorting', 3, 3, 4, DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'Low', 'Not Started'),
('Dokumentasi Kode', 'Menulis dokumentasi lengkap untuk semua fungsi', 3, 3, 4, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Low', 'Completed'),

-- Kelompok Delta - RPL
('Requirement Analysis', 'Mengumpulkan dan mendokumentasikan kebutuhan sistem', 4, 4, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'High', 'Completed'),
('System Architecture', 'Merancang arsitektur sistem menggunakan UML', 4, 4, 2, DATE_ADD(CURDATE(), INTERVAL 8 DAY), 'High', 'In Progress'),
('Prototype Development', 'Mengembangkan prototype interaktif aplikasi', 4, 4, 2, DATE_ADD(CURDATE(), INTERVAL 18 DAY), 'Medium', 'Not Started'),
('User Acceptance Testing', 'Melakukan UAT dengan pengguna akhir', 4, 4, 2, DATE_ADD(CURDATE(), INTERVAL 25 DAY), 'Low', 'Not Started');

-- ============================================
-- TASK ASSIGNMENTS
-- ============================================
INSERT INTO task_assignments (task_id, user_id, status, completion_percentage) VALUES
-- Task 1: Desain UI Website
(1, 5, 'In Progress', 60),
(1, 7, 'In Progress', 40),
-- Task 2: Implementasi Backend
(2, 6, 'Not Started', 0),
(2, 2, 'Not Started', 0),
-- Task 3: Database Design (completed)
(3, 2, 'Completed', 100),
(3, 5, 'Completed', 100),
-- Task 5: Normalisasi Database
(5, 8, 'In Progress', 70),
(5, 3, 'In Progress', 50),
-- Task 7: Laporan Proyek DB
(7, 9, 'In Progress', 30),
-- Task 8: Presentasi (overdue)
(8, 3, 'Not Started', 0),
(8, 8, 'Not Started', 0),
-- Task 9: Implementasi Sorting
(9, 10, 'In Progress', 45),
(9, 4, 'In Progress', 55),
-- Task 12: Dokumentasi Kode (completed)
(12, 5, 'Completed', 100),
-- Task 13: Requirement Analysis (completed)
(13, 6, 'Completed', 100),
(13, 8, 'Completed', 100),
-- Task 14: System Architecture
(14, 2, 'In Progress', 65),
(14, 9, 'In Progress', 35);

-- ============================================
-- TASK CHECKLISTS
-- ============================================
INSERT INTO task_checklists (task_assignment_id, checklist_item, is_completed) VALUES
-- Dewi on Desain UI (assignment_id=1)
(1, 'Membuat wireframe halaman utama', 1),
(1, 'Desain mockup halaman login', 1),
(1, 'Desain mockup dashboard', 0),
(1, 'Responsive design mobile', 0),
-- Fitri on Desain UI (assignment_id=2)
(2, 'Desain halaman profil user', 1),
(2, 'Desain halaman manajemen tugas', 0),
(2, 'Membuat design system & komponen', 0),
-- Budi on Database Design (assignment_id=5, completed)
(5, 'Membuat ERD', 1),
(5, 'Membuat Database', 1),
(5, 'Normalisasi tabel', 1),
(5, 'Testing relasi', 1),
-- Gilang on Normalisasi (assignment_id=7)
(7, 'Identifikasi dependensi fungsional', 1),
(7, 'Normalisasi ke 1NF', 1),
(7, 'Normalisasi ke 2NF', 1),
(7, 'Normalisasi ke 3NF', 0),
-- Hana on Laporan (assignment_id=9)
(9, 'Menulis pendahuluan', 1),
(9, 'Menulis bab metodologi', 0),
(9, 'Menulis bab hasil', 0),
(9, 'Membuat kesimpulan', 0),
-- Irfan on Sorting (assignment_id=12)
(12, 'Implementasi Merge Sort', 1),
(12, 'Implementasi Quick Sort', 0),
(12, 'Unit testing', 0),
-- Budi on System Architecture (assignment_id=17)
(17, 'Use Case Diagram', 1),
(17, 'Class Diagram', 1),
(17, 'Sequence Diagram', 0),
(17, 'Activity Diagram', 0);

-- ============================================
-- NOTIFICATIONS
-- ============================================
INSERT INTO notifications (user_id, title, message, type, is_read) VALUES
(2, 'Deadline Mendekati', 'Deadline tugas "Database Design" tinggal 3 hari lagi.', 'Deadline', 0),
(5, 'Tugas Baru', 'Anda telah ditugaskan untuk mengerjakan "Desain UI Website".', 'Assignment', 1),
(3, 'Deadline Hari Ini', 'Deadline tugas "Laporan Proyek DB" adalah hari ini!', 'Deadline', 0),
(8, 'Deadline Terlewat', 'Tugas "Presentasi Basis Data" telah melewati batas waktu.', 'Deadline', 0),
(6, 'Tugas Selesai', 'Anggota kelompok telah menyelesaikan tugas "Database Design".', 'Completed', 0),
(2, 'Pengingat', 'Jangan lupa untuk mengecek progress tugas kelompok Anda.', 'Reminder', 1),
(4, 'Deadline Mendekati', 'Deadline tugas "Implementasi Sorting" tinggal 7 hari lagi.', 'Deadline', 0),
(9, 'Tugas Baru', 'Anda telah ditugaskan untuk mengerjakan "Laporan Proyek DB".', 'Assignment', 1),
(10, 'Tugas Baru', 'Anda telah ditugaskan untuk mengerjakan "Implementasi Sorting".', 'Assignment', 0),
(2, 'Deadline Mendekati', 'Deadline tugas "Requirement Analysis" tinggal 1 hari lagi.', 'Deadline', 0);

-- ============================================
-- ACTIVITY LOGS
-- ============================================
INSERT INTO activity_logs (user_id, activity, created_at) VALUES
(1, 'Admin melakukan login ke sistem', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 'Budi membuat kelompok "Kelompok Alpha"', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(2, 'Budi menambahkan tugas "Desain UI Website"', DATE_SUB(NOW(), INTERVAL 8 DAY)),
(3, 'Siti membuat kelompok "Kelompok Beta"', DATE_SUB(NOW(), INTERVAL 9 DAY)),
(5, 'Dewi menyelesaikan checklist "Membuat wireframe halaman utama"', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 'Budi menyelesaikan tugas "Database Design"', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(8, 'Gilang memperbarui progress "Normalisasi Database" menjadi 70%', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 'Ahmad menambahkan tugas "Implementasi Sorting"', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(6, 'Eko menyelesaikan tugas "Requirement Analysis"', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(9, 'Hana mulai mengerjakan "Laporan Proyek DB"', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 'Siti melakukan login ke sistem', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(7, 'Fitri memperbarui desain halaman profil user', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(10, 'Irfan menyelesaikan implementasi Merge Sort', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'Budi memperbarui arsitektur sistem dengan Use Case Diagram', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'Admin menambahkan mata kuliah "Jaringan Komputer"', DATE_SUB(NOW(), INTERVAL 12 DAY));
