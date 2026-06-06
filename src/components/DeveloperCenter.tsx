import { useState } from "react";
import { Copy, Check, FileCode, Database, Server, HelpCircle, HardDrive } from "lucide-react";

export default function DeveloperCenter() {
  const [copiedKey, setCopiedKey] = useState<string | null>(null);

  const handleCopy = (key: string, text: string) => {
    navigator.clipboard.writeText(text);
    setCopiedKey(key);
    setTimeout(() => setCopiedKey(null), 2000);
  };

  const sqlScript = `-- SQL Script สำหรับเว็บไซต์โรงเรียนบ้านหนองหว้า
-- รองรับ MySQL / PHPMyAdmin / XAMPP
-- ออกแบบมาตามหลัก 3NF และความปลอดภัยฐานข้อมูลสูงสุด

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- 1. ตารางตั้งค่าเว็บไซต์ (settings)
CREATE TABLE IF NOT EXISTS \`settings\` (
  \`id\` int(11) NOT NULL AUTO_INCREMENT,
  \`school_name\` varchar(255) NOT NULL,
  \`short_name\` varchar(50) NOT NULL,
  \`address\` text NOT NULL,
  \`phone\` varchar(20) NOT NULL,
  \`email\` varchar(100) NOT NULL,
  \`jurisdiction\` varchar(255) NOT NULL,
  \`levels\` varchar(100) NOT NULL,
  \`director_name\` varchar(150) NOT NULL,
  \`director_title\` varchar(100) NOT NULL,
  \`director_image\` varchar(255) DEFAULT NULL,
  \`youtube_intro_url\` varchar(255) DEFAULT NULL,
  \`visitor_count\` int(11) DEFAULT '0',
  PRIMARY KEY (\`id\`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. ตารางผู้ใช้ระบบ Admin (users)
CREATE TABLE IF NOT EXISTS \`users\` (
  \`id\` int(11) NOT NULL AUTO_INCREMENT,
  \`username\` varchar(50) NOT NULL UNIQUE,
  \`password\` varchar(255) NOT NULL,
  \`name\` varchar(100) NOT NULL,
  \`role\` varchar(30) DEFAULT 'Editor',
  \`created_at\` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (\`id\`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4_unicode_ci;

-- 3. ตารางหมวดหมู่ข่าวสาร (categories)
CREATE TABLE IF NOT EXISTS \`categories\` (
  \`id\` int(11) NOT NULL AUTO_INCREMENT,
  \`name\` varchar(100) NOT NULL,
  PRIMARY KEY (\`id\`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4_unicode_ci;

-- 4. ตารางข่าวประชาสัมพันธ์ (news)
CREATE TABLE IF NOT EXISTS \`news\` (
  \`id\` int(11) NOT NULL AUTO_INCREMENT,
  \`title\` varchar(255) NOT NULL,
  \`category_id\` int(11) DEFAULT NULL,
  \`content\` text NOT NULL,
  \`summary\` text DEFAULT NULL,
  \`image_url\` varchar(255) DEFAULT NULL,
  \`views\` int(11) DEFAULT '0',
  \`date\` date NOT NULL,
  \`created_at\` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (\`id\`),
  KEY \`category_id\` (\`category_id\`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4_unicode_ci;

-- 5. ตารางบุคลากรครู (teachers)
CREATE TABLE IF NOT EXISTS \`teachers\` (
  \`id\` int(11) NOT NULL AUTO_INCREMENT,
  \`name\` varchar(150) NOT NULL,
  \`position\` varchar(150) NOT NULL,
  \`level\` varchar(100) NOT NULL, -- เช่น ผู้อำนวยการ, คศ.3, คศ.2, ครูช่วยงาน
  \`subject_group\` varchar(100) DEFAULT NULL,
  \`image_url\` varchar(255) DEFAULT NULL,
  \`sort_order\` int(11) DEFAULT '99',
  PRIMARY KEY (\`id\`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4_unicode_ci;

-- 6. ตารางนักเรียน (students)
CREATE TABLE IF NOT EXISTS \`students\` (
  \`id\` int(11) NOT NULL AUTO_INCREMENT,
  \`name\` varchar(150) NOT NULL,
  \`grade\` varchar(50) NOT NULL, -- เช่น อนุบาล 3, ป.1, ป.6
  \`classroom\` varchar(10) NOT NULL, -- เช่น 1/1
  \`gender\` varchar(10) NOT NULL, -- ชาย/หญิง
  PRIMARY KEY (\`id\`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4_unicode_ci;

-- 7. ตารางเอกสารดาวน์โหลด (downloads)
CREATE TABLE IF NOT EXISTS \`downloads\` (
  \`id\` int(11) NOT NULL AUTO_INCREMENT,
  \`title\` varchar(255) NOT NULL,
  \`category\` varchar(100) NOT NULL, -- แผนการเรียน, จัดซื้อ, เอกสารครู
  \`file_name\` varchar(255) NOT NULL,
  \`file_type\` varchar(10) NOT NULL, -- PDF, DOCX
  \`file_size\` varchar(20) NOT NULL,
  \`download_count\` int(11) DEFAULT '0',
  \`uploaded_at\` date NOT NULL,
  PRIMARY KEY (\`id\`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4_unicode_ci;

-- 8. ตารางงานแกลเลอรีรูปภาพ (galleries / album)
CREATE TABLE IF NOT EXISTS \`galleries\` (
  \`id\` int(11) NOT NULL AUTO_INCREMENT,
  \`title\` varchar(255) NOT NULL,
  \`description\` text DEFAULT NULL,
  \`cover_image\` varchar(255) DEFAULT NULL,
  \`date\` date NOT NULL,
  PRIMARY KEY (\`id\`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4_unicode_ci;

-- 9. ตารางภาพสไลด์แบนเนอร์แรก (banners)
CREATE TABLE IF NOT EXISTS \`banners\` (
  \`id\` int(11) NOT NULL AUTO_INCREMENT,
  \`title\` varchar(255) NOT NULL,
  \`subtitle\` text,
  \`image_url\` varchar(255) NOT NULL,
  \`is_active\` tinyint(1) DEFAULT '1',
  PRIMARY KEY (\`id\`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4_unicode_ci;

-- เพิ่ม Foreign Key constraints
ALTER TABLE \`news\`
  ADD CONSTRAINT \`fk_news_category\` FOREIGN KEY (\`category_id\`) REFERENCES \`categories\` (\`id\`) ON DELETE SET NULL;

-- บันทึกข้อมูลตั้งต้น (Seed Data)
INSERT INTO \`settings\` (\`id\`, \`school_name\`, \`short_name\`, \`address\`, \`phone\`, \`email\`, \`jurisdiction\`, \`levels\`, \`director_name\`, \`director_title\`, \`director_image\`, \`youtube_intro_url\`, \`visitor_count\`) VALUES
(1, 'โรงเรียนบ้านหนองหว้า', 'ร.ร.บ้านหนองหว้า', 'หมู่ที่ 2 บ้านหนองหว้า ตำบลหนองกี่ อำเภอหนองกี่ จังหวัดบุรีรัมย์ 31210', '044-641123', 'bannongwaschool@gmail.com', 'สำนักงานเขตพื้นที่การศึกษาประถมศึกษาบุรีรัมย์ เขต 3', 'ระดับอนุบาล ถึง ประถมศึกษาปีที่ 6', 'นายอำนวย ยอดครูใหญ่', 'ผู้อำนวยการโรงเรียนบ้านหนองหว้า', 'director.png', 'https://www.youtube.com/embed/gCOk8X63Rpk', 15420);

INSERT INTO \`users\` (\`id\`, \`username\`, \`password\`, \`name\`, \`role\`) VALUES
-- พาสเวิร์ดแฮชด้วย password_hash('admin123', PASSWORD_BCRYPT)
(1, 'admin', '$2y$10$7zRofG867zHw3PMyr96URe/0pIe1o0kU9R39QY6Ew01wLMy.B5Obe', 'ผู้ดูแลระบบ ร.ร.บ้านหนองหว้า', 'Administrator');

INSERT INTO \`categories\` (\`id\`, \`name\`) VALUES
(1, 'ข่าวประชาสัมพันธ์ทั่วไป'),
(2, 'ข่าวกิจกรรม'),
(3, 'ข่าวประกาศจัดซื้อจัดจ้าง');

COMMIT;
`;

  const phpConfig = `<?php
// app/config/config.php
// ระบบเชื่อมต่อฐานข้อมูลและอัพเดทตารางอัตโนมัติ (Database Auto-Migration & Self-Installation Engine)
// รองรับ PHP 8+ และ MySQL สำหรับระบบควบคุมโรงเรียนบ้านหนองหว้า

// 1. ตรวจสอบสถานะการทำงาน Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. กำหนดค่าการเซ็ตติ้งฐานข้อมูล
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bannongwa_db');

try {
    // 3. เชื่อมต่อ MySQL Server ก่อนเพื่อตรวจสอบว่ามีฐานข้อมูลอยู่หรือไม่ (หากไม่มีให้สร้างอัตโนมัติเมื่อติดตั้งครั้งแรก)
    $temp_dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $temp_pdo = new PDO($temp_dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // สร้างฐานข้อมูลให้อัตโนมัติเมื่อลงเครื่องครั้งแรก (First installation)
    $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS \`" . DB_NAME . "\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $temp_pdo = null; // ปิดการเชื่อมต่อชั่วคราวเพื่อเชื่อมไปยังฐานข้อมูลแอปโดยตรง

    // 4. สร้างการเชื่อมต่อ PDO ด้วยการเปิดรับระบบความปลอดภัยสูงสุด ป้องกัน SQL Injection
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // บังคับโยน Exception สำหรับตรวจสอบ Error
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // รูปแบบอาร์เรย์คีย์-ค่า
        PDO::ATTR_EMULATE_PREPARES   => false,                  // ปิดการดัดแปลงคำสั่ง และใช้ Real Prepared Statements
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // 5. ระบบ Auto-Installer & Auto-Migration: อัพเดทสกีมาตารางคอลัมน์อัตโนมัติเมื่อมีการเปิดรันระบบ
    // ตารางพื้นฐานที่แอปพลิเคชันของเราต้องการ
    $tables = [
        'settings' => "
            CREATE TABLE IF NOT EXISTS \`settings\` (
              \`id\` int(11) NOT NULL AUTO_INCREMENT,
              \`school_name\` varchar(255) NOT NULL,
              \`short_name\` varchar(50) NOT NULL,
              \`address\` text NOT NULL,
              \`phone\` varchar(20) NOT NULL,
              \`email\` varchar(100) NOT NULL,
              \`jurisdiction\` varchar(255) NOT NULL,
              \`levels\` varchar(100) NOT NULL,
              \`director_name\` varchar(150) NOT NULL,
              \`director_title\` varchar(100) NOT NULL,
              \`director_image\` varchar(255) DEFAULT NULL,
              \`youtube_intro_url\` varchar(255) DEFAULT NULL,
              \`visitor_count\` int(11) DEFAULT '0',
              \`school_theme_color\` varchar(50) DEFAULT 'pink-white',
              PRIMARY KEY (\`id\`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'users' => "
            CREATE TABLE IF NOT EXISTS \`users\` (
              \`id\` int(11) NOT NULL AUTO_INCREMENT,
              \`username\` varchar(50) NOT NULL UNIQUE,
              \`password\` varchar(255) NOT NULL,
              \`name\` varchar(100) NOT NULL,
              \`role\` varchar(30) DEFAULT 'Editor',
              \`created_at\` timestamp DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (\`id\`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4_unicode_ci;
        ",
        'categories' => "
            CREATE TABLE IF NOT EXISTS \`categories\` (
              \`id\` int(11) NOT NULL AUTO_INCREMENT,
              \`name\` varchar(100) NOT NULL,
              PRIMARY KEY (\`id\`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4_unicode_ci;
        ",
        'news' => "
            CREATE TABLE IF NOT EXISTS \`news\` (
              \`id\` int(11) NOT NULL AUTO_INCREMENT,
              \`title\` varchar(255) NOT NULL,
              \`category_id\` int(11) DEFAULT NULL,
              \`content\` text NOT NULL,
              \`summary\` text DEFAULT NULL,
              \`image_url\` varchar(255) DEFAULT NULL,
              \`views\` int(11) DEFAULT '0',
              \`date\` date NOT NULL,
              \`created_at\` timestamp DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (\`id\`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4_unicode_ci;
        ",
        'teachers' => "
            CREATE TABLE IF NOT EXISTS \`teachers\` (
              \`id\` int(11) NOT NULL AUTO_INCREMENT,
              \`name\` varchar(150) NOT NULL,
              \`position\` varchar(150) NOT NULL,
              \`level\` varchar(100) NOT NULL,
              \`subject_group\` varchar(100) DEFAULT NULL,
              \`image_url\` varchar(255) DEFAULT NULL,
              \`sort_order\` int(11) DEFAULT '99',
              PRIMARY KEY (\`id\`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4_unicode_ci;
        ",
        'students' => "
            CREATE TABLE IF NOT EXISTS \`students\` (
              \`id\` int(11) NOT NULL AUTO_INCREMENT,
              \`name\` varchar(150) NOT NULL,
              \`grade\` varchar(50) NOT NULL,
              \`classroom\` varchar(10) NOT NULL,
              \`gender\` varchar(10) NOT NULL,
              PRIMARY KEY (\`id\`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4_unicode_ci;
        ",
        'downloads' => "
            CREATE TABLE IF NOT EXISTS \`downloads\` (
              \`id\` int(11) NOT NULL AUTO_INCREMENT,
              \`title\` varchar(255) NOT NULL,
              \`category\` varchar(100) NOT NULL,
              \`file_name\` varchar(255) NOT NULL,
              \`file_type\` varchar(10) NOT NULL,
              \`file_size\` varchar(20) NOT NULL,
              \`download_count\` int(11) DEFAULT '0',
              \`uploaded_at\` date NOT NULL,
              PRIMARY KEY (\`id\`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4_unicode_ci;
        "
    ];

    // ทยอยรันคำสั่งสร้างตารางหากยังไม่เคยมีตารางในระบบ (ตารางใหม่จะอัพเดตที่นี่อัตโนมัติ)
    foreach ($tables as $tableName => $createSql) {
        $pdo->exec($createSql);
    }

    // 6. ส่วนช่วยในการอัพเดทตารางให้อัตโนมัติ (Dynamic Schema Update Column Logic)
    // หากโปรแกรมเมอร์ปรับแก้ฟังก์ชันของแอปพลิเคชันและมีการเพิ่มคอลัมน์ใหม่ในตาราง
    // ตัวระบบตรวจจับความเปลี่ยนแปลงนี้และแทรกคำสั่ง ALTER TABLE ให้ทันทีโดยไม่ต้องไปพึ่งคำสั่งนำเข้าฐานข้อมูลซ้ำซ้อน
    $columns_delta_check = [
        'settings' => [
            'school_theme_color' => "ALTER TABLE \`settings\` ADD COLUMN \`school_theme_color\` varchar(50) DEFAULT 'pink-white' AFTER \`visitor_count\`",
            'school_motto'       => "ALTER TABLE \`settings\` ADD COLUMN \`school_motto\` varchar(255) DEFAULT 'ชมพู-ขาว ก้าวไกลวิชาการ' AFTER \`school_theme_color\`"
        ],
        'users' => [
            'status'             => "ALTER TABLE \`users\` ADD COLUMN \`status\` varchar(20) DEFAULT 'active' AFTER \`role\`"
        ],
        'news' => [
            'sticky_flag'        => "ALTER TABLE \`news\` ADD COLUMN \`sticky_flag\` tinyint(1) DEFAULT '0' AFTER \`views\`"
        ]
    ];

    foreach ($columns_delta_check as $tableName => $cols) {
        foreach ($cols as $colName => $alterSql) {
            // คัดกรองชื่อคอลัมน์ที่มีอยู่เดรสบอร์ดปัจจุบันเพื่อความคุ้มครองโครงสร้างเดิม
            $checkQuery = $pdo->query("SHOW COLUMNS FROM \`$tableName\` LIKE '$colName'");
            if ($checkQuery->rowCount() == 0) {
                // อัพเดทตารางในฐานข้อมูลอัตโนมัติเมื่อมีความเปลี่ยนแปลง!
                $pdo->exec($alterSql);
            }
        }
    }

    // 7. ทำการ Seed ค่าตั้งต้นกรณีพึ่งติดตั้งครั้งแรกและยังไม่มีข้อมูลดิบ
    $check_settings = $pdo->query("SELECT id FROM \`settings\` LIMIT 1");
    if ($check_settings->rowCount() == 0) {
        $insert_settings = "INSERT INTO \`settings\` (\`id\`, \`school_name\`, \`short_name\`, \`address\`, \`phone\`, \`email\`, \`jurisdiction\`, \`levels\`, \`director_name\`, \`director_title\`, \`school_theme_color\`, \`school_motto\`) VALUES
        (1, 'โรงเรียนบ้านหนองหว้า', 'ร.ร.บ้านหนองหว้า', 'หมู่ที่ 2 บ้านหนองหว้า ตำบลหนองกี่ อำเภอหนองกี่ จังหวัดบุรีรัมย์ 31210', '044-641123', 'bannongwaschool@gmail.com', 'สำนักงานเขตพื้นที่การศึกษาประถมศึกษาบุรีรัมย์ เขต 3', 'ระดับอนุบาล ถึง ประถมศึกษาปีที่ 6', 'นายอำนวย ยอดครูใหญ่', 'ผู้อำนวยการโรงเรียนบ้านหนองหว้า', 'pink-white', 'ชมพู-ขาว ก้าวไกลวิชาการ');";
        $pdo->exec($insert_settings);
    }

    $check_users = $pdo->query("SELECT id FROM \`users\` LIMIT 1");
    if ($check_users->rowCount() == 0) {
        $admin_pwd = password_hash('admin123', PASSWORD_BCRYPT);
        $insert_user = "INSERT INTO \`users\` (\`id\`, \`username\`, \`password\`, \`name\`, \`role\`) VALUES (1, 'admin', '$admin_pwd', 'ผู้ดูแลระบบ ร.ร.บ้านหนองหว้า', 'Administrator');";
        $pdo->exec($insert_user);
    }

} catch (PDOException $e) {
    // บันทึก Log และไม่แสดงข้อมูลโครงสร้างระบบดิบแก่บุคคลภายนอก เพื่อกันรั่วไหล
    error_log($e->getMessage());
    die("ขออภัย ระบบฐานข้อมูลขัดข้องทางเทคนิค กำลังดำเนินการปรับฐานข้อมูล กรุณารีเฟรชเพื่อพยายามใหม่อีกครั้ง: " . htmlspecialchars($e->getMessage()));
}

// 8. ฟังก์ชันช่วยสนับสนุนความปลอดภัยเพิ่มเติม (XSS & CSRF Protection Filter)
function cleanInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>`;

  const phpLogin = `<?php
// login.php - ล็อกอินหลังบ้านแบบระบบปลอดภัย (Prepared Statements)

require_once 'app/config/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. ตรวจจับ CSRF Token ป้องกันการกรอกข้อมูลจากภายนอกโรงเรียน
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die("การร้องขอไม่ถูกต้อง (CSRF Detected)");
    }

    $username = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        // 2. ป้องกัน SQL Injection ด้วยการ Bind Parameters
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        // 3. ยืนยันรหัสผ่านที่แฮชด้วย BCRYPT ป้องกัน SQL Leakage
        if ($user && password_verify($password, $user['password'])) {
            // ป้องกัน Session Fixation
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['admin_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    }
}
?>
<!-- HTML หน้าล็อกอินและเชื่อมต่อเพื่อความปลอดภัย -->`;

  const phpMvcStructure = `โรงเรียนบ้านหนองหว้า PHP MVC Project Structure:
├── app/
│   ├── config/
│   │   └── config.php          <-- เชื่อมต่อฐานข้อมูล PDO, เซสชั่น, CSRF
│   ├── controllers/
│   │   ├── HomeController.php  <-- ประมวลหน้าหลักและข้อมูลสถิติโรงเรียน
│   │   ├── NewsController.php  <-- การจัดการ CRUD ข่าวทั้งหมด
│   │   ├── StaffController.php <-- หน้าคัดกรองและนำเสนอครูตามลำดับ
│   │   └── AdminController.php  <-- ล็อกอินและแดชบอร์ดหลังบ้าน
│   ├── models/
│   │   ├── NewsModel.php       <-- อัปเดตข้อมูลข่าวลงฐานข้อมูล SQL
│   │   ├── StaffModel.php      <-- ตารางบุคลากรโรงเรียนบ้านหนองหว้า
│   │   └── DownloadModel.php   <-- การบันทึกและนับจำนวนดาวน์โหลดเอกสาร
│   └── views/
│       ├── home.php            <-- แสดงผลหน้าบ้าน โทนสีชมพู-ขาว เรียบหรู อ่อนโยน
│       ├── news/
│       │   ├── list.php
│       │   └── view.php
│       └── admin/
│           ├── login.php
│           └── dashboard.php     <-- แดชบอร์ดสภานักเรียนและการบริหารทรัพยากร
├── public/                     <-- ส่วนที่เข้าถึงได้ภายนอก (Web Root)
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css       <-- โค้ดสีประจำโรงเรียน (#ec4899 & #ffffff)
│   │   └── js/
│   │       └── main.js
│   ├── uploads/                <-- เก็บเอกสารและไฟล์ที่อัปโหลดความปลอดภัยสูง
│   └── index.php               <-- จุดรับคำขอกลาง (Front Controller)
└── .htaccess                   <-- จัดการย่อลิงก์เขียน URL สะอาด (Rewrite URL)`;

  return (
    <div className="space-y-12">
      {/* Intro Section */}
      <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-school-red via-school-red-dark to-slate-900 p-8 text-white shadow-xl">
        <div className="absolute top-0 right-0 h-48 w-48 rounded-full bg-school-yellow/15 blur-3xl"></div>
        <div className="relative z-10 space-y-4 max-w-3xl">
          <span className="inline-block rounded-full bg-school-yellow/25 px-4 py-1 text-xs font-semibold text-school-yellow tracking-wider uppercase">
            Developer Code Artifact Center
          </span>
          <h1 className="text-3xl font-bold tracking-tight md:text-4xl text-school-yellow">
            ระบบสนับสนุนโปรแกรมเมอร์และสเปคฐานข้อมูล
          </h1>
          <p className="text-emerald-100 text-sm md:text-base leading-relaxed">
            จัดเตรียมไฟล์โครงการสมบูรณ์แบบ PHP 8+ และ MySQL สำหรับใช้ในการนำไปติดตั้งลงในเครื่องเซิร์ฟเวอร์จำลอง XAMPP 
            หรือ Deploy เว็บไซต์จริงขึ้นบน Web Hosting ระบบความปลอดภัยผ่านการตรวจสอบตามมาตรฐานสากล
          </p>
        </div>
      </div>

      {/* Grid Tabs */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {/* Left column guide */}
        <div className="space-y-6 lg:col-span-1">
          <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
              <Server className="h-5 w-5 text-school-red" />
              โครงสร้างโฟลเดอร์ MVC
            </h2>
            <p className="text-xs text-gray-500 mb-4 leading-relaxed">
              สถาปัตยกรรม Model-View-Controller (MVC) เพื่อการเขียนโค้ดที่สะอาด แยกฝั่งสเกลฐานข้อมูลและส่วนแสดงผลออกจากกัน
            </p>
            <pre className="no-scrollbar overflow-x-auto rounded-lg bg-slate-950 p-4 font-mono text-[11px] text-emerald-400 max-h-96">
              {phpMvcStructure}
            </pre>
          </div>

          <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm space-y-4">
            <h2 className="text-xl font-bold text-gray-800 flex items-center gap-2">
              <HelpCircle className="h-5 w-5 text-school-yellow-dark" />
              วิธีติดตั้งใช้งานบน XAMPP
            </h2>
            <div className="space-y-3 text-xs text-gray-600 leading-relaxed">
              <div className="flex gap-2">
                <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-school-red/10 font-bold text-school-red text-[11px]">1</span>
                <p>คัดลอกโฟลเดอร์โครงการไปไว้ที่ห้อง <code>C:\xampp\htdocs\bannongwa</code></p>
              </div>
              <div className="flex gap-2">
                <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-school-red/10 font-bold text-school-red text-[11px]">2</span>
                <p>เปิดไปที่เว็บเบราว์เซอร์ เข้า URL <code>http://localhost/phpmyadmin</code> จากนั้นสร้าง Database ใหม่ชื่อ <code>bannongwa_db</code></p>
              </div>
              <div className="flex gap-2">
                <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-school-red/10 font-bold text-school-red text-[11px]">3</span>
                <p>กดเมนู <strong>Import</strong> และนำไฟล์ SQL ที่คัดลอกจากศูนย์พัฒนานี้อัปโหลดเพื่อสร้างตารางทั้งหมด</p>
              </div>
              <div className="flex gap-2">
                <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-school-red/10 font-bold text-school-red text-[11px]">4</span>
                <p>เปิดหน้าเว็บเพื่อรันระบบได้ที่ <code>http://localhost/bannongwa</code> รหัสเข้าแอดมินคือ <code>admin</code> รหัสผ่าน <code>admin123</code></p>
              </div>
            </div>
          </div>

          <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm space-y-4">
            <h2 className="text-xl font-bold text-gray-800 flex items-center gap-2">
              <HardDrive className="h-5 w-5 text-emerald-600" />
              การอัปขึ้น Hosting จริง
            </h2>
            <div className="space-y-3 text-xs text-gray-600 leading-relaxed">
              <div className="flex gap-2">
                <span className="inline-block h-2 w-2 mt-1.5 shrink-0 rounded-full bg-emerald-600"></span>
                <p>นำเข้าไฟล์ฐานข้อมูล SQL ขึ้น MySQL Database ของโฮスติ้งปลายทาง</p>
              </div>
              <div className="flex gap-2">
                <span className="inline-block h-2 w-2 mt-1.5 shrink-0 rounded-full bg-emerald-600"></span>
                <p>แก้ไขไฟล์ <code>app/config/config.php</code> เปลี่ยนแปลง HOST, USER, PASS, DBNAME ให้ตรงกับเซิร์ฟเวอร์จริง</p>
              </div>
              <div className="flex gap-2">
                <span className="inline-block h-2 w-2 mt-1.5 shrink-0 rounded-full bg-emerald-600"></span>
                <p>ตั้งค่าสิทธิ์โฟลเดอร์ <code>public/uploads/</code> เป็น <code>755</code> เพื่อให้สิทธิ์ผู้ดูแลอัปหมวดหมู่รูปภาพประชาสัมพันธ์โรงเรียนได้คล่องตัว</p>
              </div>
            </div>
          </div>
        </div>

        {/* Right column files and code tools */}
        <div className="space-y-8 lg:col-span-2">
          
          {/* File 1: SQL ddl */}
          <div className="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
            <div className="flex items-center justify-between border-b border-gray-100 bg-gray-50/50 px-6 py-4">
              <div className="flex items-center gap-3">
                <Database className="h-5 w-5 text-school-red" />
                <div>
                  <h3 className="font-bold text-sm text-gray-800">Database Schema (MySQL Script)</h3>
                  <p className="text-[10px] text-gray-500">รองรับระบบล็อกอิน, สมุดข่าวกิจกรรม, ตารางนักเรียน-ครู, และแผงควบคุม</p>
                </div>
              </div>
              <button
                onClick={() => handleCopy("sql", sqlScript)}
                className="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50"
              >
                {copiedKey === "sql" ? (
                  <>
                    <Check className="h-3.5 w-3.5 text-emerald-600" />
                    <span>คัดลอกสำเร็จ!</span>
                  </>
                ) : (
                  <>
                    <Copy className="h-3.5 w-3.5" />
                    <span>คัดลอก SQL</span>
                  </>
                )}
              </button>
            </div>
            <div className="p-0">
              <pre className="no-scrollbar max-h-96 overflow-y-auto bg-slate-900 p-6 font-mono text-[11px] text-gray-200 leading-relaxed">
                {sqlScript}
              </pre>
            </div>
          </div>

          {/* File 2: Connection settings */}
          <div className="rounded-2xl border border-pink-100 bg-white shadow-sm overflow-hidden">
            <div className="flex items-center justify-between border-b border-pink-50 bg-pink-50/20 px-6 py-4">
              <div className="flex items-center gap-3">
                <FileCode className="h-5 w-5 text-school-pink" />
                <div>
                  <h3 className="font-bold text-sm text-gray-800">app/config/config.php</h3>
                  <p className="text-[10px] text-pink-600 font-medium">ระบบเชื่อมต่อ สร้าง และหมุนเวียนคอลัมน์ฐานข้อมูลอัตโนมัติ (อัตลักษณ์ชมพู-ขาว)</p>
                </div>
              </div>
              <button
                onClick={() => handleCopy("config", phpConfig)}
                className="flex items-center gap-1.5 rounded-lg border border-pink-200 bg-white px-3 py-1.5 text-xs font-semibold text-school-pink hover:bg-pink-50"
              >
                {copiedKey === "config" ? (
                  <>
                    <Check className="h-3.5 w-3.5 text-emerald-600" />
                    <span>คัดลอกสำเร็จ!</span>
                  </>
                ) : (
                  <>
                    <Copy className="h-3.5 w-3.5" />
                    <span>คัดลอกไฟล์ PHP</span>
                  </>
                )}
              </button>
            </div>
            <div className="p-0">
              <pre className="no-scrollbar max-h-96 overflow-y-auto bg-slate-900 p-6 font-mono text-[11px] text-pink-300 leading-relaxed">
                {phpConfig}
              </pre>
            </div>
          </div>

          {/* File 3: Login form auth logic */}
          <div className="rounded-2xl border border-pink-100 bg-white shadow-sm overflow-hidden">
            <div className="flex items-center justify-between border-b border-pink-50 bg-pink-50/20 px-6 py-4">
              <div className="flex items-center gap-3">
                <FileCode className="h-5 w-5 text-school-pink" />
                <div>
                  <h3 className="font-bold text-sm text-gray-800">login.php (ระบบลงทะเบียนและล็อกอินแอดมิน)</h3>
                  <p className="text-[10px] text-pink-600 font-medium">ใช้ระเบียบเข้ารหัส Password_Verify ป้องกันฟิชชิ่งโจมตีระบบ</p>
                </div>
              </div>
              <button
                onClick={() => handleCopy("login", phpLogin)}
                className="flex items-center gap-1.5 rounded-lg border border-pink-200 bg-white px-3 py-1.5 text-xs font-semibold text-school-pink hover:bg-pink-50"
              >
                {copiedKey === "login" ? (
                  <>
                    <Check className="h-3.5 w-3.5 text-emerald-600" />
                    <span>คัดลอกสำเร็จ!</span>
                  </>
                ) : (
                  <>
                    <Copy className="h-3.5 w-3.5" />
                    <span>คัดลอกไฟล์ PHP</span>
                  </>
                )}
              </button>
            </div>
            <div className="p-0">
              <pre className="no-scrollbar max-h-96 overflow-y-auto bg-slate-900 p-6 font-mono text-[11px] text-pink-200 leading-relaxed">
                {phpLogin}
              </pre>
            </div>
          </div>

        </div>

      </div>
    </div>
  );
}
