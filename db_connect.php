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
?>
