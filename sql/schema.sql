-- ============================================
-- AkademiX Database Schema
-- Database: akademix_db
-- Engine: InnoDB (FK support)
-- Charset: utf8mb4
-- ============================================

CREATE DATABASE IF NOT EXISTS akademix_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE akademix_db;

-- ============================================
-- 1. USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    nim VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Ketua Kelompok', 'Anggota') NOT NULL DEFAULT 'Anggota',
    profile_photo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_users_role (role),
    INDEX idx_users_email (email),
    INDEX idx_users_nim (nim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. COURSES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(150) NOT NULL,
    lecturer_name VARCHAR(100) NOT NULL,
    semester TINYINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_courses_semester (semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. GROUPS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `groups` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    course_id INT NOT NULL,
    leader_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_groups_course FOREIGN KEY (course_id)
        REFERENCES courses(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_groups_leader FOREIGN KEY (leader_id)
        REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,

    INDEX idx_groups_course (course_id),
    INDEX idx_groups_leader (leader_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. GROUP MEMBERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_gm_group FOREIGN KEY (group_id)
        REFERENCES `groups`(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_gm_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE KEY uk_group_user (group_id, user_id),
    INDEX idx_gm_group (group_id),
    INDEX idx_gm_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. TASKS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    course_id INT DEFAULT NULL,
    group_id INT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    deadline DATE NOT NULL,
    priority ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Medium',
    status ENUM('Not Started', 'In Progress', 'Completed') NOT NULL DEFAULT 'Not Started',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_tasks_course FOREIGN KEY (course_id)
        REFERENCES courses(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_tasks_group FOREIGN KEY (group_id)
        REFERENCES `groups`(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_tasks_creator FOREIGN KEY (created_by)
        REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,

    INDEX idx_tasks_course (course_id),
    INDEX idx_tasks_group (group_id),
    INDEX idx_tasks_status (status),
    INDEX idx_tasks_priority (priority),
    INDEX idx_tasks_deadline (deadline)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. TASK ASSIGNMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS task_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('Not Started', 'In Progress', 'Completed') NOT NULL DEFAULT 'Not Started',
    completion_percentage TINYINT UNSIGNED NOT NULL DEFAULT 0,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,

    CONSTRAINT fk_ta_task FOREIGN KEY (task_id)
        REFERENCES tasks(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ta_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE KEY uk_task_user (task_id, user_id),
    INDEX idx_ta_task (task_id),
    INDEX idx_ta_user (user_id),
    INDEX idx_ta_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. TASK CHECKLISTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS task_checklists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_assignment_id INT NOT NULL,
    checklist_item VARCHAR(255) NOT NULL,
    is_completed TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_tc_assignment FOREIGN KEY (task_assignment_id)
        REFERENCES task_assignments(id) ON DELETE CASCADE ON UPDATE CASCADE,

    INDEX idx_tc_assignment (task_assignment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. NOTIFICATIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('Deadline', 'Assignment', 'Reminder', 'Completed') NOT NULL DEFAULT 'Reminder',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notif_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,

    INDEX idx_notif_user (user_id),
    INDEX idx_notif_read (is_read),
    INDEX idx_notif_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. ACTIVITY LOGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_log_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,

    INDEX idx_log_user (user_id),
    INDEX idx_log_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
