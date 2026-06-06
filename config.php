<?php
/**
 * บันทึกประวัติ: ระบบเชื่อมต่อฐานข้อมูลและอัพเดตตารางอัตโนมัติ (Database Auto-Migration Engine)
 * สำหรับโรงเรียนบ้านหนองหว้า (อัตลักษณ์ ชมพู-ขาว)
 * พัฒนาด้วยมาตรฐานสูงสุด ป้องกัน SQL Injection และรองรับ PHP 8+ / MySQL
 */

// 1. ตรวจสอบสถานะการทำงาน Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. กำหนดค่าการเชื่อมต่อฐานข้อมูล MySQL (ปรับแก้เป็นค่าจริงบน Server โรงเรียนได้ที่นี่)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bannongwa_db');

try {
    // 3. เชื่อมต่อ MySQL Server ก่อนเพื่อเริ่มระบบติดตั้งเป็นครั้งแรก (Auto-Installation)
    $temp_dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $temp_pdo = new PDO($temp_dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // สร้างฐานข้อมูลหากยังไม่มีอยู่ในระบบโดยอัตโนมัติ
    $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $temp_pdo = null; // ปิดการเชื่อมต่อชั่วคราวเพื่อเข้าสู่ฐานข้อมูลหลัก

    // 4. เชื่อมต่อเข้าฐานข้อมูลอย่างเป็นทางการ ด้วยมาตรฐาน PDO ที่ปลอดภัยและเสถียรที่สุด
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // โยนข้อยกเว้นเมื่อพบข้อผิดพลาด
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // นำข้อมูลออกในสไตล์ Array Key-Value
        PDO::ATTR_EMULATE_PREPARES   => false,                  // ใช้การส่งคำสั่งของดีจริง ป้องกัน SQL Injection
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // 5. โครงสร้าง SQL สร้างตารางอัตโนมัติ (First-time installation tables schema)
    $tables = [
        'settings' => "
            CREATE TABLE IF NOT EXISTS `settings` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `school_name` varchar(255) NOT NULL,
              `short_name` varchar(50) NOT NULL,
              `address` text NOT NULL,
              `phone` varchar(20) NOT NULL,
              `email` varchar(100) NOT NULL,
              `jurisdiction` varchar(255) NOT NULL,
              `levels` varchar(255) NOT NULL,
              `director_name` varchar(150) NOT NULL,
              `director_title` varchar(100) NOT NULL,
              `director_image` varchar(255) DEFAULT NULL,
              `youtube_intro_url` varchar(255) DEFAULT NULL,
              `visitor_count` int(11) DEFAULT '0',
              `school_theme_color` varchar(50) DEFAULT 'pink-white',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'users' => "
            CREATE TABLE IF NOT EXISTS `users` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `username` varchar(50) NOT NULL UNIQUE,
              `password` varchar(255) NOT NULL,
              `name` varchar(100) NOT NULL,
              `role` varchar(30) DEFAULT 'Editor',
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'news' => "
            CREATE TABLE IF NOT EXISTS `news` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `title` varchar(255) NOT NULL,
              `category` varchar(100) NOT NULL,
              `content` text NOT NULL,
              `summary` text DEFAULT NULL,
              `image_url` varchar(255) DEFAULT NULL,
              `views` int(11) DEFAULT '0',
              `date` date NOT NULL,
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'teachers' => "
            CREATE TABLE IF NOT EXISTS `teachers` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(150) NOT NULL,
              `position` varchar(150) NOT NULL,
              `level` varchar(100) NOT NULL,
              `subject_group` varchar(100) DEFAULT NULL,
              `image_url` varchar(255) DEFAULT NULL,
              `sort_order` int(11) DEFAULT '99',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'students' => "
            CREATE TABLE IF NOT EXISTS `students` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(150) NOT NULL,
              `grade` varchar(50) NOT NULL,
              `classroom` varchar(10) NOT NULL,
              `gender` varchar(10) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'downloads' => "
            CREATE TABLE IF NOT EXISTS `downloads` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `title` varchar(255) NOT NULL,
              `category` varchar(100) NOT NULL,
              `file_type` varchar(10) NOT NULL,
              `file_size` varchar(20) NOT NULL,
              `download_count` int(11) DEFAULT '0',
              `uploaded_date` date NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'banners' => "
            CREATE TABLE IF NOT EXISTS `banners` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `title` varchar(255) NOT NULL,
              `subtitle` varchar(255) NOT NULL,
              `image_url` varchar(255) NOT NULL,
              `active` tinyint(4) DEFAULT '1',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        "
    ];

    // รันลูปสร้างตารางที่ยังไม่มีในฐานข้อมูล
    foreach ($tables as $name => $query) {
        $pdo->exec($query);
    }

    // 6. ระบบอัพเดทตารางอัตโนมัติ (Differentiate Check & Self-Healing Database Column Logic)
    // ตรวจสอบโครงสร้างตาราง หากมีคอลัมน์ใหม่ๆ เพิ่มเข้ามาในภายหลังจะรันคำสั่งแก้ไขตารางอัตโนมัติ (ALTER TABLE)
    $column_migrations = [
        'settings' => [
            'school_motto' => "ALTER TABLE `settings` ADD COLUMN `school_motto` varchar(255) DEFAULT 'ชมพู-ขาว ก้าวไกลวิชาการ' AFTER `school_theme_color`"
        ],
        'news' => [
            'sticky_flag' => "ALTER TABLE `news` ADD COLUMN `sticky_flag` tinyint(1) DEFAULT '0' AFTER `views`"
        ],
        'downloads' => [
            'file_url' => "ALTER TABLE `downloads` ADD COLUMN `file_url` varchar(255) DEFAULT NULL AFTER `file_size`"
        ]
    ];

    foreach ($column_migrations as $tableName => $cols) {
        foreach ($cols as $colName => $alterSql) {
            $checkQuery = $pdo->query("SHOW COLUMNS FROM `$tableName` LIKE '$colName'");
            if ($checkQuery->rowCount() == 0) {
                // เพิ่มคอลัมน์ใหม่ในตารางอัตโนมัติหากเว็บมีการเปลี่ยนแปลงปรับแก้!
                $pdo->exec($alterSql);
            }
        }
    }

    // 7. เติมข้อมูลตั้งต้น (Seeding) หากเป็นการรันโปรแกรมติดตั้งครั้งแรกสุด
    // เมล็ดข้อมูลโรงเรียนบ้านหนองหว้า
    $countSettings = $pdo->query("SELECT id FROM `settings` LIMIT 1")->fetch();
    if (!$countSettings) {
        $pdo->exec("INSERT INTO `settings` 
            (`id`, `school_name`, `short_name`, `address`, `phone`, `email`, `jurisdiction`, `levels`, `director_name`, `director_title`, `director_image`, `visitor_count`, `school_theme_color`, `school_motto`, `youtube_intro_url`) 
            VALUES 
            (1, 'โรงเรียนบ้านหนองหว้า', 'ร.ร.บ้านหนองหว้า', 'หมู่ที่ 2 บ้านหนองหว้า ตำบลหนองกี่ อำเภอหนองกี่ จังหวัดบุรีรัมย์ 31210', '044-641123', 'bannongwaschool@gmail.com', 'สำนักงานเขตพื้นที่การศึกษาประถมศึกษาบุรีรัมย์ เขต 3', 'ระดับปฐมวัย (อนุบาล 2-3) ถึงระดับชั้นประถมศึกษาปีที่ 6', 'นายอำนวย ยอดครูใหญ่', 'ผู้อำนวยการโรงเรียนบ้านหนองหว้า', 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&q=80&w=300', 15422, 'pink-white', 'ชมพู-ขาว ก้าวไกลวิชาการ', 'https://www.youtube.com/embed/gCOk8X63Rpk');");
    }

    $countUsers = $pdo->query("SELECT id FROM `users` LIMIT 1")->fetch();
    if (!$countUsers) {
        // แอดมินหลัก รหัสใช้งานเริ่มต้น: username คือ admin, password คือ admin123 (เข้าสหัสด้วยเทคนิค Bcrypt)
        $hashed_pwd = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->exec("INSERT INTO `users` (`id`, `username`, `password`, `name`, `role`) VALUES 
            (1, 'admin', '{$hashed_pwd}', 'ผู้ดูแลระบบ โรงเรียนบ้านหนองหว้า', 'Administrator');");
    }

    $countBanners = $pdo->query("SELECT id FROM `banners` LIMIT 1")->fetch();
    if (!$countBanners) {
        $pdo->exec("INSERT INTO `banners` (`id`, `title`, `subtitle`, `image_url`, `active`) VALUES 
            (1, 'ยินดีต้อนรับสู่ โรงเรียนบ้านหนองหว้า', 'แหล่งวิทยาการ กีฬาเด่น เน้นคุณธรรม สัมพันธ์ชุมชน', 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&q=80&w=1200', 1),
            (2, 'เปิดรับสมัครเรียน ปีการศึกษา 2569', 'ตั้งแต่ชั้น อนุบาล 1 ถึง ชั้นประถมศึกษาปีที่ 6', 'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?auto=format&fit=crop&q=80&w=1200', 1);");
    }

    $countNews = $pdo->query("SELECT id FROM `news` LIMIT 1")->fetch();
    if (!$countNews) {
        $pdo->exec("INSERT INTO `news` (`id`, `title`, `category`, `content`, `summary`, `image_url`, `views`, `date`, `sticky_flag`) VALUES 
            (1, 'ประกาศเปิดเรียนภาคเรียนที่ 1 ปีการศึกษา 2569 อย่างเป็นทางการ', 'ประชาสัมพันธ์ทั่วไป', 'โรงเรียนบ้านหนองหว้า ขอประกาศกำหนดการเปิดภาคเรียนที่ 1 ปีการศึกษา 2569 ในวันที่ 16 พฤษภาคม 2569 ขอความกรุณาผู้ปกครองเตรียมความพร้อมของนักเรียนในเรื่องของเครื่องแบบ อุปกรณ์การเรียน และสุขอนามัย ทางโรงเรียนได้ทำความสะอาดฉีดพ่นฆ่าเชื้อและเตรียมอาคารสถานที่เรียบร้อยแล้ว', 'ประกาศอย่างเป็นทางการเปิดภาคเรียนที่ 1 ปีการศึกษา 2569 ในวันที่ 16 พฤษภาคม 2569 พร้อมทั้งเตรียมความสะอาดของอาคารสถานที่และการดูแลความปลอดภัยในทุกด้าน', 'https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&q=80&w=600', 312, '2026-05-10', 1),
            (2, 'กิจกรรมวันไหว้ครู ประจำปีการศึกษา 2569 \"น้อมจิตวันทา บูชาพระคุณครู\"', 'ข่าวกิจกรรม', 'โรงเรียนบ้านหนองหว้า นำโดยคณะผู้บริหาร คณะครู และสภานักเรียน ได้ร่วมใจจัดกิจกรรมวันไหว้ครู ประจำปีการศึกษา 2569 ณ หอประชุมโรงเรียน เพื่อร่วมส่งเสริมวัฒนธรรมอันดีงามและความกตัญญูกตเวทิตาต่อครูผู้ประสิทธิ์ประสาทวิชา โดยมีการประกวดพานไหว้ครูประเภทสวยงามและประเภทความคิดสร้างสรรค์ บรรยากาศเป็นไปด้วยความอบอุ่นและเป็นระเบียบเรียบร้อย', 'โรงเรียนบ้านหนองหว้า จัดกิจกรรมวันไหว้ครู ประจำปีการศึกษา 2569 ณ หอประชุมโรงเรียน เพื่อแสดงความกตัญญูกตเวทิตา พร้อมทั้งประกวดพานไหว้ครูอันสวยงามเชิงสร้างสรรค์', 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&q=80&w=600', 185, '2026-06-02', 0),
            (3, 'การประชุมผู้ปกครองภาคทฤษฎีและแนวทางการเรียนร่วม ภาคเรียนที่ 1/2569', 'ประชุมและวิชาการ', 'เมื่อวันเสาร์ที่ผ่านมา ทางโรงเรียนจัดประชุมผู้ปกครองภาคเรียนที่ 1 ปีการศึกษา 2569 เพื่อชี้แจงนโยบายสิทธิประโยชน์เรียนฟรี 15 ปี เผยแพร่มาตรการความปลอดภัย และการร่วมมือกันพัฒนาทักษะอ่านออกเขียนได้ของนักเรียน โดยการประชุมประสบความสำเร็จและได้รับความร่วมมืออย่างดียิ่งจากผู้ปกครองทุกระดับชั้น', 'จัดประชุมผู้ปกครองภาคเรียนที่ 1/2569 เพื่อสร้างความเข้าใจต่อนโยบายโรงเรียน สิทธิประโยชน์เรียนฟรี และแนวทางประสานงานเพื่อช่วยเหลือดูแลพฤติกรรมการเรียนของนักเรียน', 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&q=80&w=600', 247, '2026-05-18', 0);");
    }

    $countTeachers = $pdo->query("SELECT id FROM `teachers` LIMIT 1")->fetch();
    if (!$countTeachers) {
        $pdo->exec("INSERT INTO `teachers` (`id`, `name`, `position`, `level`, `subject_group`, `image_url`, `sort_order`) VALUES 
            (1, 'นายอำนวย ยอดครูใหญ่', 'ผู้อำนวยการโรงเรียนบ้านหนองหว้า', 'ผู้อำนวยการโรงเรียน (คศ.3)', 'ผู้บริหาร', 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&q=80&w=300', 1),
            (2, 'นางสมศรี ปัญญาไว', 'ครูวิชาการระดับประถม / ครูประจำชั้นประถมศึกษาปีที่ 6', 'ครูชำนาญการพิเศษ (คศ.3)', 'วิชาการคณิตศาสตร์', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=300', 2),
            (3, 'นายวิชาญ อักษรศิลป์', 'ครูพลศึกษาและไอที / ครูประจำชั้นประถมศึกษาปีที่ 5', 'ครูชำนาญการ (คศ.2)', 'สุขศึกษาและพลศึกษา', 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=300', 3),
            (4, 'นางสาวดวงตา บุพผา', 'ครูภาษาไทยระดับต้น / ครูประจำชั้นประถมศึกษาปีที่ 1', 'ครูผู้ช่วย', 'ภาษาไทย', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&q=80&w=300', 4),
            (5, 'นางกานดา ใจซื่อ', 'ครูปฐมวัย / ครูประจำชั้นอนุบาล 3', 'ครูชำนาญการพิเศษ (คศ.3)', 'ระดับปฐมวัย', 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=300', 5);");
    }

    $countStudents = $pdo->query("SELECT id FROM `students` LIMIT 1")->fetch();
    if (!$countStudents) {
        $pdo->exec("INSERT INTO `students` (`id`, `name`, `grade`, `classroom`, `gender`) VALUES 
            (1, 'เด็กชายจิรายุ สมพงษ์', 'ประถมศึกษาปีที่ 6', '6/1', 'ชาย'),
            (2, 'เด็กหญิงรัตนาภรณ์ แสนดี', 'ประถมศึกษาปีที่ 6', '6/1', 'หญิง'),
            (3, 'เด็กชายวีรยุทธ สุขใจ', 'ประถมศึกษาปีที่ 5', '5/1', 'ชาย'),
            (4, 'เด็กหญิงกนกวรรณ เพียรธรรม', 'ประถมศึกษาปีที่ 1', '1/1', 'หญิง'),
            (5, 'เด็กชายอานนท์ บุรีรัมย์', 'อนุบาล 3', 'อ.3/1', 'ชาย');");
    }

    $countDownloads = $pdo->query("SELECT id FROM `downloads` LIMIT 1")->fetch();
    if (!$countDownloads) {
        $pdo->exec("INSERT INTO `downloads` (`id`, `title`, `category`, `file_type`, `file_size`, `download_count`, `uploaded_date`, `file_url`) VALUES 
            (1, 'ใบสมัครเข้าศึกษาต่อ ระดับชั้นอนุบาลและประถมศึกษา โรงเรียนบ้านหนองหว้า', 'เอกสารทั่วไป', 'PDF', '1.2 MB', 145, '2026-03-01', '#'),
            (2, 'แผนพัฒนาการศึกษา 5 ปี (พ.ศ. 2568 - 2572) โรงเรียนบ้านหนองหว้า', 'แผนงานและนโยบาย', 'PDF', '4.5 MB', 56, '2026-02-15', '#'),
            (3, 'รายงานการประเมินตนเองของสถานศึกษา SAR ปีการศึกษา 2568', 'ประกันคุณภาพ', 'PDF', '8.1 MB', 92, '2026-04-10', '#'),
            (4, 'ข้อตกลงในการพัฒนางาน PA สำหรับครูสายการสอน (ตัวอย่างไฟล์แก้ไขได้)', 'เอกสารครู', 'WORD', '520 KB', 231, '2026-05-02', '#');");
    }

} catch (PDOException $e) {
    // บันทึกรายงานข้อผิดพลาดและปิดการแสดงผลโค้ดระบบเพื่อความปลอดภัย
    error_log($e->getMessage());
    die("ขออภัย! ระบบขัดข้องทางเทคนิคด้านการเชื่อมต่อฐานข้อมูล กรุณาตรวจสอบ DB_NAME/DB_USER หรือสร้างสิทธิการเข้าถึงฐานข้อมูลใน Server อีกครั้ง หรือรีเฟรชหน้าเว็บ: " . htmlspecialchars($e->getMessage()));
}


// 8. ฟังค์ชันล้างข้อมูลขาเข้าเพื่อความปลอดภัย (XSS Prevention Filter)
function cleanInput($data) {
    if ($data === null) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// 9. ฟังก์ชันจัดรูปแบบเวลาภาษาไทยแบบย่อ
function thaiDate($dateStr) {
    if (!$dateStr) return '';
    $months = [
        '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.', '04' => 'เม.ย.',
        '05' => 'พ.ค.', '06' => 'มิ.ย.', '07' => 'ก.ค.', '08' => 'ส.ค.',
        '09' => 'ก.ย.', '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.'
    ];
    $parts = explode('-', $dateStr);
    if (count($parts) < 3) return $dateStr;
    $y = intval($parts[0]) + 543;
    $m = $parts[1];
    $d = intval($parts[2]);
    return "{$d} " . ($months[$m] ?? '') . " " . substr($y, 2);
}
?>
