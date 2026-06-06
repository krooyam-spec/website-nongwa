<?php
/**
 * 📡 ระบบเชื่อมต่อฐานข้อมูลหลัก (Core Database Connection & Session Config)
 * โรงเรียนบ้านหนองหว้า (อัตลักษณ์ ชมพู-ขาว)
 * -------------------------------------------------------------------------
 * ไฟล์นี้ทำหน้าที่เฉพาะการเชื่อมต่อฐานข้อมูล และระบายฟังค์ชันความปลอดภัยหลักเท่านั้น
 * โดยแยกตัวประมวลผลตารางและเนื้อหาข่าวสารตั้งต้น (Migration) ออกไปอยู่ทีไฟล์ db_migrate.php
 * เพื่อความปลอดภัยสูงสุดและไม่ให้การอัพโหลดโค้ดข้ามฝั่งไปทับโครงสร้างเดิมที่มีอยู่ก่อนหน้า
 */

// 1. ตรวจสอบสถานะการทำงาน Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. กำหนดค่าการเชื่อมต่อฐานข้อมูล MySQL (ปรับแก้เป็นค่าจริงบน Server โรงเรียนได้ที่นี่)
// หากผู้ใช้มีไฟล์ตั้งค่าดั้งเดิม สามารถปรับแต่งค่าเฉพาะจุด หรือเปลี่ยนการเชื่อมตามความสะดวก
define('DB_HOST', 'localhost');
define('DB_USER', 'schoolos_nongwa');
define('DB_PASS', '8$p5GfJqgwlv3!Or');
define('DB_NAME', 'schoolos_nongwa');

try {
    // 3. เชื่อมต่อฐานข้อมูลอย่างเป็นทางการ ด้วยมาตรฐาน PDO ที่ปลอดภัยและเสถียรที่สุด
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // โยนข้อยกเว้นเมื่อพบข้อผิดพลาด
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // นำข้อมูลออกในรูปแบบ Array Key-Value
        PDO::ATTR_EMULATE_PREPARES   => false,                  // ใช้การส่งคำสั่งของดีจริง ป้องกัน SQL Injection
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // บันทึกรายงานข้อผิดพลาดและแสดงแจ้งเตือนความคืบหน้าอย่างละเอียด
    error_log($e->getMessage());
    die("❌ ขออภัย! ระบบขัดข้องทางเทคนิคด้านการเชื่อมต่อฐานข้อมูล<br>" .
        "กรุณาตรวจสอบว่ามีฐานข้อมูลชื่อ <strong>" . DB_NAME . "</strong> อยู่บนเซิร์ฟเวอร์ และสิทธิ์ผู้ใช้ถูกต้อง<br>" .
        "หรือทำการรันสคริปต์สร้างฐานข้อมูลก่อนที่: <a href='db_migrate.php'>db_migrate.php</a><br>" .
        "ข้อความระบบ: " . htmlspecialchars($e->getMessage()));
}

// 4. ฟังค์ชันล้างข้อมูลขาเข้าเพื่อความปลอดภัย (XSS Prevention Filter)
function cleanInput($data) {
    if ($data === null) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// 5. ฟังก์ชันจัดรูปแบบเวลาภาษาไทยแบบย่อ
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

// 6. อัตโนมัติอัพเดตฟิลด์เพื่อรองรับการปรับแต่งแบนเนอร์และสารผู้บริหาร (Auto-Schema Alignment)
try {
    $healing_cols = [
        'banner_title' => "ALTER TABLE `settings` ADD COLUMN `banner_title` varchar(255) DEFAULT 'ยินดีต้อนรับสู่รั้วชมพู-ขาว แหล่งการศึกษาระดับเยาวชนต้นแบบ' AFTER `banner_right_image`",
        'banner_subtitle' => "ALTER TABLE `settings` ADD COLUMN `banner_subtitle` text DEFAULT NULL AFTER `banner_title`",
        'director_message_title' => "ALTER TABLE `settings` ADD COLUMN `director_message_title` varchar(255) DEFAULT 'มุ่งมั่นเสริมนวัตกรรมการเรียนการสอน เชิดชูคุณธรรมความดี' AFTER `banner_subtitle`",
        'director_message` WHERE 1=0;' => "", // placeholder for safety
        'director_message' => "ALTER TABLE `settings` ADD COLUMN `director_message` text DEFAULT NULL AFTER `director_message_title`",
        'current_academic_year' => "ALTER TABLE `settings` ADD COLUMN `current_academic_year` varchar(10) DEFAULT '2569' AFTER `school_theme_color`"
    ];
    foreach ($healing_cols as $col => $sql) {
        if (empty($sql)) continue;
        $check = $pdo->query("SHOW COLUMNS FROM `settings` LIKE '$col'")->fetchAll();
        if (empty($check)) {
            $pdo->exec($sql);
            if ($col === 'banner_title') {
                $pdo->exec("UPDATE `settings` SET `banner_title` = 'ยินดีต้อนรับสู่รั้วชมพู-ขาว แหล่งการศึกษาระดับเยาวชนต้นแบบ' WHERE `id` = 1");
            } else if ($col === 'banner_subtitle') {
                $pdo->exec("UPDATE `settings` SET `banner_subtitle` = 'เน้นทักษะชีวิต ความดีงาม คุณธรรมสูงส่ง ส่งผ่านความใส่ใจในระดับชั้น:' WHERE `id` = 1");
            } else if ($col === 'director_message_title') {
                $pdo->exec("UPDATE `settings` SET `director_message_title` = 'มุ่งมั่นเสริมนวัตกรรมการเรียนการสอน เชิดชูคุณธรรมความดี' WHERE `id` = 1");
            } else if ($col === 'director_message') {
                $pdo->exec("UPDATE `settings` SET `director_message` = '\"โรงเรียนบ้านหนองหว้า ขอตลับใจเป็นพันธมิตรร่วมกับชุมชน ผู้ปกครอง เพื่อขับเคลื่อนและสร้างสรรค์โอกาสทางวิชาการและวิชาชีพแก่นักเรียน สู่ความพร้อมในการปฏิสัมพันธ์และดำรงชีพในศตวรรษที่ 21 เรามุ่งเสกสร้างสภาพแวดล้อมที่สะอาด ปลอดภัย เพื่อเสริมองค์ความรู้อย่างบูรณาการสูงสุด\"' WHERE `id` = 1");
            } else if ($col === 'current_academic_year') {
                $pdo->exec("UPDATE `settings` SET `current_academic_year` = '2569' WHERE `id` = 1");
            }
        }
    }

    // 7. จัดตั้งและเพิ่มตารางจัดเก็บรายชื่อนักเรียนและข้อมูลสถิติที่จำเป็น (Auto-healing for student tables)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `students` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(150) NOT NULL,
      `grade` varchar(50) NOT NULL,
      `classroom` varchar(10) NOT NULL,
      `gender` varchar(10) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `student_stats` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `grade_name` varchar(50) NOT NULL UNIQUE,
      `student_count` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $check_students_empty = $pdo->query("SELECT id FROM `students` LIMIT 1")->fetch();
    if (!$check_students_empty) {
        $pdo->exec("INSERT INTO `students` (`id`, `name`, `grade`, `classroom`, `gender`) VALUES 
            (1, 'เด็กชายจิรายุ สมพงษ์', 'ประถมศึกษาปีที่ 6', '6/1', 'ชาย'),
            (2, 'เด็กหญิงรัตนาภรณ์ แสนดี', 'ประถมศึกษาปีที่ 6', '6/1', 'หญิง'),
            (3, 'เด็กหญิงนภัสสร แก้วมณี', 'ประถมศึกษาปีที่ 5', '5/1', 'หญิง'),
            (4, 'เด็กชายชินดนัย มีสุข', 'ประถมศึกษาปีที่ 4', '4/1', 'ชาย'),
            (5, 'เด็กหญิงพิชชาภา เกิดดี', 'ประถมศึกษาปีที่ 3', '3/1', 'หญิง'),
            (6, 'เด็กหญิงกานต์พิชชา ผลเจริญ', 'ประถมศึกษาปีที่ 2', '2/1', 'หญิง'),
            (7, 'เด็กชายอนุรักษ์ รักเรียน', 'ประถมศึกษาปีที่ 1', '1/1', 'ชาย'),
            (8, 'เด็กหญิงมัทนา งามศิลป์', 'อนุบาล 3', 'อ.3/1', 'หญิง');");
    }

    // จัดตั้งและเพิ่มตารางจัดเก็บสถิตินักเรียนแยกปีการศึกษา (Student Yearly Stats Table Alignment)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `student_yearly_stats` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `academic_year` varchar(10) NOT NULL,
      `grade_name` varchar(50) NOT NULL,
      `student_count` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`),
      UNIQUE KEY `year_grade_unique` (`academic_year`, `grade_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $check_yearly_empty = $pdo->query("SELECT id FROM `student_yearly_stats` LIMIT 1")->fetch();
    if (!$check_yearly_empty) {
        $initial_yearly_stats = [
            // 2566
            ['year' => '2566', 'grade' => 'อนุบาล 2', 'count' => 40],
            ['year' => '2566', 'grade' => 'อนุบาล 3', 'count' => 42],
            ['year' => '2566', 'grade' => 'ประถมศึกษาปีที่ 1', 'count' => 50],
            ['year' => '2566', 'grade' => 'ประถมศึกษาปีที่ 2', 'count' => 48],
            ['year' => '2566', 'grade' => 'ประถมศึกษาปีที่ 3', 'count' => 50],
            ['year' => '2566', 'grade' => 'ประถมศึกษาปีที่ 4', 'count' => 52],
            ['year' => '2566', 'grade' => 'ประถมศึกษาปีที่ 5', 'count' => 51],
            ['year' => '2566', 'grade' => 'ประถมศึกษาปีที่ 6', 'count' => 53],
            
            // 2567
            ['year' => '2567', 'grade' => 'อนุบาล 2', 'count' => 42],
            ['year' => '2567', 'grade' => 'อนุบาล 3', 'count' => 44],
            ['year' => '2567', 'grade' => 'ประถมศึกษาปีที่ 1', 'count' => 52],
            ['year' => '2567', 'grade' => 'ประถมศึกษาปีที่ 2', 'count' => 50],
            ['year' => '2567', 'grade' => 'ประถมศึกษาปีที่ 3', 'count' => 52],
            ['year' => '2567', 'grade' => 'ประถมศึกษาปีที่ 4', 'count' => 55],
            ['year' => '2567', 'grade' => 'ประถมศึกษาปีที่ 5', 'count' => 54],
            ['year' => '2567', 'grade' => 'ประถมศึกษาปีที่ 6', 'count' => 56],

            // 2568
            ['year' => '2568', 'grade' => 'อนุบาล 2', 'count' => 44],
            ['year' => '2568', 'grade' => 'อนุบาล 3', 'count' => 46],
            ['year' => '2568', 'grade' => 'ประถมศึกษาปีที่ 1', 'count' => 54],
            ['year' => '2568', 'grade' => 'ประถมศึกษาปีที่ 2', 'count' => 52],
            ['year' => '2568', 'grade' => 'ประถมศึกษาปีที่ 3', 'count' => 54],
            ['year' => '2568', 'grade' => 'ประถมศึกษาปีที่ 4', 'count' => 57],
            ['year' => '2568', 'grade' => 'ประถมศึกษาปีที่ 5', 'count' => 56],
            ['year' => '2568', 'grade' => 'ประถมศึกษาปีที่ 6', 'count' => 58],

            // 2569
            ['year' => '2569', 'grade' => 'อนุบาล 2', 'count' => 45],
            ['year' => '2569', 'grade' => 'อนุบาล 3', 'count' => 48],
            ['year' => '2569', 'grade' => 'ประถมศึกษาปีที่ 1', 'count' => 56],
            ['year' => '2569', 'grade' => 'ประถมศึกษาปีที่ 2', 'count' => 52],
            ['year' => '2569', 'grade' => 'ประถมศึกษาปีที่ 3', 'count' => 54],
            ['year' => '2569', 'grade' => 'ประถมศึกษาปีที่ 4', 'count' => 59],
            ['year' => '2569', 'grade' => 'ประถมศึกษาปีที่ 5', 'count' => 58],
            ['year' => '2569', 'grade' => 'ประถมศึกษาปีที่ 6', 'count' => 60],
        ];
        $stmt_ins = $pdo->prepare("INSERT INTO `student_yearly_stats` (`academic_year`, `grade_name`, `student_count`) VALUES (:year, :grade, :count)");
        foreach ($initial_yearly_stats as $stat) {
            $stmt_ins->execute([
                'year' => $stat['year'],
                'grade' => $stat['grade'],
                'count' => $stat['count']
            ]);
        }
    }
} catch (Exception $e) {
    // ล้มเหลวแบบเงียบ
}
?>
