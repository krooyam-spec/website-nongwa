<?php
/**
 * 💻 ศูนย์กลางควบคุมระบบหลังบ้านผู้ดูแลระบบ (Admin Central Dashboard Controller)
 * ควบคุมข้อมูลโรงเรียน, สตรีมอัพเดตตารางฐานข้อมูลอัตโนมัติ และแยกส่วนเทมเพลตเพื่อความง่ายต่อการอภิเดต
 * ออกแบบด้วยอัตลักษณ์ "ชมพู-ขาว" สวยงาม เป็นสัดส่วนและใช้ง่ายที่สุด
 */

require_once 'db_connect.php';

// 1. ตรวจสอบสิทธิ์ผู้รักษาการระบบ Admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// 2. ระบบออกจากระบบ (Log Out)
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}

// 3. ตัวแปรสำหรับเก็บรายการแจ้งเตือนสัญกรณ์สำเร็จ / ล้มเหลว
$success_alert = '';
$err_alert = '';

/**
 * ฟังก์ชันสำหรับช่วยเหลืออัปโหลดไฟล์ระดับสากลแยกโฟลเดอร์อัตโนมัติ
 * แยกไฟล์รูปภาพเข้า uploads/images | ไฟล์ PDF เข้า uploads/pdfs | ไฟล์เอกสารอื่นๆ เข้า uploads/documents
 */
function uploadFileToServer($file, $allowed_types = 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = explode(',', $allowed_types);

    if (!in_array($ext, $allowed)) {
        return false;
    }

    // จัดแยกประเภทโฟลเดอร์ตามความประสงค์ของผู้ใช้เพื่อความเป็นระเบียบระนาบ
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
        $target_dir = 'uploads/images/';
    } elseif ($ext === 'pdf') {
        $target_dir = 'uploads/pdfs/';
    } else {
        $target_dir = 'uploads/documents/';
    }

    // ตรวจสอบเช็คสร้างไดเรกทอรีถ้าไม่มี
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $new_filename = 'file_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $target_filepath = $target_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_filepath)) {
        return $target_filepath;
    }

    return false;
}

// 4. ตัวรับและประมวลผล POST Requests ฝั่งบันทึกข้อมูลหลัก

// ก. การบันทึกข้อมูลทั่วไปของสถาบัน (Settings)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $school_name = cleanInput($_POST['school_name'] ?? '');
    $short_name = cleanInput($_POST['short_name'] ?? '');
    $school_motto = cleanInput($_POST['school_motto'] ?? '');
    $address = cleanInput($_POST['address'] ?? '');
    $phone = cleanInput($_POST['phone'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $jurisdiction = cleanInput($_POST['jurisdiction'] ?? '');
    $levels = cleanInput($_POST['levels'] ?? '');
    $director_name = cleanInput($_POST['director_name'] ?? '');
    $director_title = cleanInput($_POST['director_title'] ?? '');
    $director_image = cleanInput($_POST['director_image'] ?? '');
    $youtube_intro_url = cleanInput($_POST['youtube_intro_url'] ?? '');
    $banner_title = cleanInput($_POST['banner_title'] ?? '');
    $banner_subtitle = cleanInput($_POST['banner_subtitle'] ?? '');
    $director_message_title = cleanInput($_POST['director_message_title'] ?? '');
    $director_message = cleanInput($_POST['director_message'] ?? '');

    try {
        $existing_stmt = $pdo->query("SELECT school_logo, banner_bg_image, banner_right_image FROM `settings` WHERE `id` = 1");
        $existing_sets = $existing_stmt->fetch();
        
        $school_logo = $existing_sets['school_logo'] ?? '';
        $banner_bg_image = $existing_sets['banner_bg_image'] ?? '';
        $banner_right_image = $existing_sets['banner_right_image'] ?? '';
        
        // อัปโหลดโลโก้โรงเรียน
        if (isset($_FILES['school_logo_file']) && $_FILES['school_logo_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded_logo = uploadFileToServer($_FILES['school_logo_file'], 'jpg,jpeg,png,gif');
            if ($uploaded_logo) $school_logo = $uploaded_logo;
        } else if (!empty($_POST['school_logo_url'])) {
            $school_logo = cleanInput($_POST['school_logo_url']);
        }
        
        // อัปโหลดภาพแบนเนอร์พื้นหลังหลัก
        if (isset($_FILES['banner_bg_file']) && $_FILES['banner_bg_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded_bg = uploadFileToServer($_FILES['banner_bg_file'], 'jpg,jpeg,png,gif');
            if ($uploaded_bg) $banner_bg_image = $uploaded_bg;
        } else if (!empty($_POST['banner_bg_url'])) {
            $banner_bg_image = cleanInput($_POST['banner_bg_url']);
        }
        
        // อัปโหลดภาพประดับขวาแบนเนอร์หลัก
        if (isset($_FILES['banner_right_file']) && $_FILES['banner_right_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded_right = uploadFileToServer($_FILES['banner_right_file'], 'jpg,jpeg,png,gif');
            if ($uploaded_right) $banner_right_image = $uploaded_right;
        } else if (!empty($_POST['banner_right_url'])) {
            $banner_right_image = cleanInput($_POST['banner_right_url']);
        }

        $stmt = $pdo->prepare("UPDATE `settings` SET 
            `school_name` = :school_name,
            `short_name` = :short_name,
            `school_motto` = :school_motto,
            `address` = :address,
            `phone` = :phone,
            `email` = :email,
            `jurisdiction` = :jurisdiction,
            `levels` = :levels,
            `director_name` = :director_name,
            `director_title` = :director_title,
            `director_image` = :director_image,
            `youtube_intro_url` = :youtube_intro_url,
            `school_logo` = :school_logo,
            `banner_bg_image` = :banner_bg_image,
            `banner_right_image` = :banner_right_image,
            `banner_title` = :banner_title,
            `banner_subtitle` = :banner_subtitle,
            `director_message_title` = :director_message_title,
            `director_message` = :director_message
            WHERE `id` = 1");
        
        $stmt->execute([
            'school_name' => $school_name,
            'short_name' => $short_name,
            'school_motto' => $school_motto,
            'address' => $address,
            'phone' => $phone,
            'email' => $email,
            'jurisdiction' => $jurisdiction,
            'levels' => $levels,
            'director_name' => $director_name,
            'director_title' => $director_title,
            'director_image' => $director_image,
            'youtube_intro_url' => $youtube_intro_url,
            'school_logo' => $school_logo,
            'banner_bg_image' => $banner_bg_image,
            'banner_right_image' => $banner_right_image,
            'banner_title' => $banner_title,
            'banner_subtitle' => $banner_subtitle,
            'director_message_title' => $director_message_title,
            'director_message' => $director_message
        ]);

        $success_alert = 'อัปเดตข้อมูลทั่วไปของสถานศึกษาโรงเรียนบ้านหนองหว้าเรียบร้อยแล้ว!';
    } catch (Exception $e) {
        $err_alert = 'เกิดข้อผิดพลาดในการบันทึกข้อมูลทั่วไป: ' . $e->getMessage();
    }
}

// ข. บันทึก/อัปเดตสถิติจนวนนักเรียนรายชั้นเรียน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_students'])) {
    if (isset($_POST['student_counts']) && is_array($_POST['student_counts'])) {
        try {
            foreach ($_POST['student_counts'] as $grade_id => $count) {
                $update_stat_stmt = $pdo->prepare("UPDATE `student_stats` SET `student_count` = :count WHERE `id` = :id");
                $update_stat_stmt->execute([
                    'count' => intval($count),
                    'id' => intval($grade_id)
                ]);
            }
            $success_alert = 'อัปเดตข้อมูลสถิติจำนวนนักเรียนรายชั้นเรียนเรียบร้อยแล้ว!';
        } catch (Exception $e) {
            $err_alert = 'เกิดข้อผิดพลาดในการปรับสถิตินักเรียน: ' . $e->getMessage();
        }
    }
}

// ค. ดำเนินการปรับรายละเอียดแก้ไขข่าวสาร (Edit News Submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_news_submit'])) {
    $id = intval($_POST['news_id'] ?? 0);
    $title = cleanInput($_POST['news_title'] ?? '');
    $category = cleanInput($_POST['news_category'] ?? 'ประชาสัมพันธ์ทั่วไป');
    $summary = cleanInput($_POST['news_summary'] ?? '');
    $content = cleanInput($_POST['news_content'] ?? '');
    $image_url = cleanInput($_POST['news_image'] ?? '');
    $sticky_flag = isset($_POST['news_sticky']) ? 1 : 0;

    if (isset($_FILES['news_image_file']) && $_FILES['news_image_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_image = uploadFileToServer($_FILES['news_image_file'], 'jpg,jpeg,png,gif');
        if ($uploaded_image) $image_url = $uploaded_image;
    }

    if (empty($title) || empty($content)) {
        $err_alert = 'กรุณากรอกหัวข้อ และเนื้อหาอย่างครบถ้วนเพื่อแก้ไข';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE `news` SET `title` = :title, `category` = :category, `summary` = :summary, `content` = :content, `image_url` = :image, `sticky_flag` = :sticky WHERE `id` = :id");
            $stmt->execute([
                'title' => $title,
                'category' => $category,
                'summary' => $summary,
                'content' => $content,
                'image' => $image_url,
                'sticky' => $sticky_flag,
                'id' => $id
            ]);
            $success_alert = 'แก้ไขบันทึกและสารประชาสัมพันธ์เรียบร้อยแล้ว!';
        } catch (Exception $e) {
            $err_alert = 'เกิดข้อผิดพลาดในการแก้ไขข่าว: ' . $e->getMessage();
        }
    }
}

// ง. ดำเนินการสร้างข่าวโพสต์ประชาสัมพันธ์ชิ้นใหม่ (Add News)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])) {
    $title = cleanInput($_POST['news_title'] ?? '');
    $category = cleanInput($_POST['news_category'] ?? 'ประชาสัมพันธ์ทั่วไป');
    $summary = cleanInput($_POST['news_summary'] ?? '');
    $content = cleanInput($_POST['news_content'] ?? '');
    $image_url = cleanInput($_POST['news_image'] ?? '');
    $sticky_flag = isset($_POST['news_sticky']) ? 1 : 0;
    $date = date('Y-m-d');

    if (isset($_FILES['news_image_file']) && $_FILES['news_image_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_image = uploadFileToServer($_FILES['news_image_file'], 'jpg,jpeg,png,gif');
        if ($uploaded_image) $image_url = $uploaded_image;
    }

    if (empty($image_url)) {
        $image_url = 'https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&q=80&w=600';
    }

    if (empty($title) || empty($content)) {
        $err_alert = 'กรุณาระบุหัวเรื่องประกาศข่าวประชาสัมพันธ์และเนื้อหาข่าวให้ระดมสมบรูณ์';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO `news` (`title`, `category`, `summary`, `content`, `image_url`, `sticky_flag`, `date`) VALUES (:title, :category, :summary, :content, :image, :sticky, :date)");
            $stmt->execute([
                'title' => $title,
                'category' => $category,
                'summary' => $summary,
                'content' => $content,
                'image' => $image_url,
                'sticky' => $sticky_flag,
                'date' => $date
            ]);
            $success_alert = 'เพิ่มหัวข้อเขียนข่าวประชาสัมพันธ์ชิ้นใหม่เข้าสู่ตารางสำเร็จ!';
        } catch (Exception $e) {
            $err_alert = 'เกิดข้อผิดพลาดในการเพิ่มประกาศข่าวสาร: ' . $e->getMessage();
        }
    }
}

// จ. แก้ไขสเปคปรับปรุงเอกสารดาวน์โหลด (Edit Download Doc Submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_doc_submit'])) {
    $id = intval($_POST['doc_id'] ?? 0);
    $title = cleanInput($_POST['doc_title'] ?? '');
    $category = cleanInput($_POST['doc_category'] ?? 'เอกสารทั่วไป');
    $file_type = cleanInput($_POST['doc_type'] ?? 'PDF');
    $file_size = cleanInput($_POST['doc_size'] ?? '1.5 MB');
    $file_url = cleanInput($_POST['doc_url'] ?? '#');

    if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_doc_path = uploadFileToServer($_FILES['doc_file'], 'pdf,doc,docx,xls,xlsx,zip,jpg,png,jpeg');
        if ($uploaded_doc_path) {
            $file_url = $uploaded_doc_path;
            $ext = strtolower(pathinfo($_FILES['doc_file']['name'], PATHINFO_EXTENSION));
            
            if ($ext === 'docx' || $ext === 'doc') $file_type = 'WORD';
            elseif ($ext === 'xlsx' || $ext === 'xls') $file_type = 'EXCEL';
            else $file_type = strtoupper($ext);
            
            $bytes = $_FILES['doc_file']['size'];
            if ($bytes >= 1048576) $file_size = round($bytes / 1048576, 1) . ' MB';
            else $file_size = round($bytes / 1024, 1) . ' KB';
        }
    }

    if (empty($title)) {
        $err_alert = 'กรุณาระบุชื่อประกาศเอกสารจดทะเบียนให้ครบถ้วน';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE `downloads` SET `title` = :title, `category` = :category, `file_type` = :file_type, `file_size` = :file_size, `file_url` = :url WHERE `id` = :id");
            $stmt->execute([
                'title' => $title,
                'category' => $category,
                'file_type' => $file_type,
                'file_size' => $file_size,
                'url' => $file_url,
                'id' => $id
            ]);
            $success_alert = 'แก้ไขรายละเอียดข้อมูลเอกสารประกาศดาวน์โหลดไฟล์เรียบร้อย!';
        } catch (Exception $e) {
            $err_alert = 'ไม่สามารถแก้ไขข้อมูลจำเพาะเอกสาร: ' . $e->getMessage();
        }
    }
}

// ฉ. เพิ่มข้อมูลประกวดเอกสาร / อัปลงเครื่อง (Add Doc File)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_doc'])) {
    $title = cleanInput($_POST['doc_title'] ?? '');
    $category = cleanInput($_POST['doc_category'] ?? 'เอกสารทั่วไป');
    $file_type = cleanInput($_POST['doc_type'] ?? 'PDF');
    $file_size = cleanInput($_POST['doc_size'] ?? '1.5 MB');
    $file_url = cleanInput($_POST['doc_url'] ?? '#');
    $date = date('Y-m-d');

    if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_doc_path = uploadFileToServer($_FILES['doc_file'], 'pdf,doc,docx,xls,xlsx,zip,jpg,png,jpeg');
        if ($uploaded_doc_path) {
            $file_url = $uploaded_doc_path;
            $ext = strtolower(pathinfo($_FILES['doc_file']['name'], PATHINFO_EXTENSION));
            
            if ($ext === 'docx' || $ext === 'doc') $file_type = 'WORD';
            elseif ($ext === 'xlsx' || $ext === 'xls') $file_type = 'EXCEL';
            else $file_type = strtoupper($ext);
            
            $bytes = $_FILES['doc_file']['size'];
            if ($bytes >= 1048576) $file_size = round($bytes / 1048576, 1) . ' MB';
            else $file_size = round($bytes / 1024, 1) . ' KB';
        }
    }

    if (empty($title)) {
        $err_alert = 'กรุณากรอกชื่อสสารเอกสารประกวดคู่จัดส่งคลังดาวน์โหลด';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO `downloads` (`title`, `category`, `file_type`, `file_size`, `uploaded_date`, `file_url`, `download_count`) VALUES (:title, :category, :file_type, :file_size, :date, :url, 0)");
            $stmt->execute([
                'title' => $title,
                'category' => $category,
                'file_type' => $file_type,
                'file_size' => $file_size,
                'date' => $date,
                'url' => $file_url
            ]);
            $success_alert = 'บันทึกอัปและจัดสร้างไฟล์เอกสารดาวน์โหลดคลังแผนการเรียบร้อยแล้ว!';
        } catch (Exception $e) {
            $err_alert = 'ไม่สามารถลงทะเบียนแทรกไฟล์คลัง: ' . $e->getMessage();
        }
    }
}

// ช. แก้ไขประวัติคุณครู / เอกสารข้อตกลง PA (Edit Teacher)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_teacher_submit'])) {
    $id = intval($_POST['teacher_id'] ?? 0);
    $name = cleanInput($_POST['teacher_name'] ?? '');
    $position = cleanInput($_POST['teacher_position'] ?? '');
    $level = cleanInput($_POST['teacher_level'] ?? 'ครูผู้ช่วย');
    $subject_group = cleanInput($_POST['teacher_group'] ?? 'งานสอนทั่วไป');
    $image_url = cleanInput($_POST['teacher_image'] ?? '');
    $sort_order = intval($_POST['teacher_order'] ?? 99);
    $pa_link_url = cleanInput($_POST['teacher_pa_url'] ?? '');
    $portfolio_url = cleanInput($_POST['teacher_portfolio_url'] ?? '');

    // อัปโหลดไฟล์รูปประจำกายครู
    if (isset($_FILES['teacher_image_file']) && $_FILES['teacher_image_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_img = uploadFileToServer($_FILES['teacher_image_file'], 'jpg,jpeg,png,gif');
        if ($uploaded_img) $image_url = $uploaded_img;
    }

    // อัปโหลดรายงานผลการปฏิบัติงาน PA
    if (isset($_FILES['teacher_pa_file']) && $_FILES['teacher_pa_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_pa = uploadFileToServer($_FILES['teacher_pa_file'], 'pdf,doc,docx,zip');
        if ($uploaded_pa) $pa_link_url = $uploaded_pa;
    }

    // อัปโหลดพอร์ตลิทเทอร์แลนด์
    if (isset($_FILES['teacher_portfolio_file']) && $_FILES['teacher_portfolio_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_port = uploadFileToServer($_FILES['teacher_portfolio_file'], 'pdf,doc,docx,zip,jpg,png,jpeg');
        if ($uploaded_port) $portfolio_url = $uploaded_port;
    }

    if (empty($name) || empty($position)) {
        $err_alert = 'กรุณากรอกชื่อ-สกุล และตำแหน่งวิชาชีพข้าราชการครูท่านนั้นๆ';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE `teachers` SET `name` = :name, `position` = :pos, `level` = :level, `subject_group` = :group, `image_url` = :img, `pa_link_url` = :pa, `portfolio_url` = :portfolio, `sort_order` = :sort WHERE `id` = :id");
            $stmt->execute([
                'name' => $name,
                'pos' => $position,
                'level' => $level,
                'group' => $subject_group,
                'img' => $image_url,
                'pa' => $pa_link_url,
                'portfolio' => $portfolio_url,
                'sort' => $sort_order,
                'id' => $id
            ]);
            $success_alert = 'แก้ไขแฟ้มบุคลากรและลิงค์รายงานแผน PA สำเร็จ!';
        } catch (Exception $e) {
            $err_alert = 'เกิดข้อผิดพลาดในการปรับข้อมูลประวัติครู: ' . $e->getMessage();
        }
    }
}

// ซ. การลงทะเบียนประวัติคุณครูท่านใหม่ (Add Teacher to Database)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_teacher'])) {
    $name = cleanInput($_POST['teacher_name'] ?? '');
    $position = cleanInput($_POST['teacher_position'] ?? '');
    $level = cleanInput($_POST['teacher_level'] ?? 'ครูผู้ช่วย');
    $subject_group = cleanInput($_POST['teacher_group'] ?? 'งานสอนทั่วไป');
    $image_url = cleanInput($_POST['teacher_image'] ?? '');
    $sort_order = intval($_POST['teacher_order'] ?? 99);
    $pa_link_url = cleanInput($_POST['teacher_pa_url'] ?? '');
    $portfolio_url = cleanInput($_POST['teacher_portfolio_url'] ?? '');

    if (isset($_FILES['teacher_image_file']) && $_FILES['teacher_image_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_img = uploadFileToServer($_FILES['teacher_image_file'], 'jpg,jpeg,png,gif');
        if ($uploaded_img) $image_url = $uploaded_img;
    }

    if (isset($_FILES['teacher_pa_file']) && $_FILES['teacher_pa_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_pa = uploadFileToServer($_FILES['teacher_pa_file'], 'pdf,doc,docx,zip');
        if ($uploaded_pa) $pa_link_url = $uploaded_pa;
    }

    if (isset($_FILES['teacher_portfolio_file']) && $_FILES['teacher_portfolio_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_port = uploadFileToServer($_FILES['teacher_portfolio_file'], 'pdf,doc,docx,zip,jpg,png,jpeg');
        if ($uploaded_port) $portfolio_url = $uploaded_port;
    }

    if (empty($image_url)) {
        $image_url = 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&q=80&w=300';
    }

    if (empty($name) || empty($position)) {
        $err_alert = 'กรุณาระบุชื่อจริงและตำแหน่งสายข้าราชการครูที่ชัดเจน';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO `teachers` (`name`, `position`, `level`, `subject_group`, `image_url`, `pa_link_url`, `portfolio_url`, `sort_order`) VALUES (:name, :pos, :level, :group, :img, :pa, :portfolio, :sort)");
            $stmt->execute([
                'name' => $name,
                'pos' => $position,
                'level' => $level,
                'group' => $subject_group,
                'img' => $image_url,
                'pa' => $pa_link_url,
                'portfolio' => $portfolio_url,
                'sort' => $sort_order
            ]);
            $success_alert = 'เพิ่มรายชื่อประวัติครูท่านใหม่และบันทึกลิงค์ทางวิชาการและสารสนเทศเรียบร้อย!';
        } catch (Exception $e) {
            $err_alert = 'ไม่สามารถลงทะเบียนประวัติข้าราชการเพิ่ม: ' . $e->getMessage();
        }
    }
}

// 5. จัดการเหตุการณ์ลบข้อมูล (Delete actions)
if (isset($_GET['action'])) {
    $action_to_do = $_GET['action'];
    $item_id = intval($_GET['id'] ?? 0);

    if ($action_to_do === 'delete_news' && $item_id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `news` WHERE `id` = :id");
            $stmt->execute(['id' => $item_id]);
            $success_alert = 'ลบข่าวประชาสัมพันธ์ออกจากประคบคลังเรียบร้อยแล้ว!';
        } catch (Exception $e) {
            $err_alert = 'เกิดข้อผิดพลาดในการลบข่าว: ' . $e->getMessage();
        }
    } elseif ($action_to_do === 'delete_doc' && $item_id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `downloads` WHERE `id` = :id");
            $stmt->execute(['id' => $item_id]);
            $success_alert = 'ลบเอกสารประกาศดาวน์โหลดไฟล์ออกจากระบบแล้ว!';
        } catch (Exception $e) {
            $err_alert = 'เกิดข้อผิดพลาดในการลบเอกสารดาวน์โหลด: ' . $e->getMessage();
        }
    } elseif ($action_to_do === 'delete_teacher' && $item_id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `teachers` WHERE `id` = :id");
            $stmt->execute(['id' => $item_id]);
            $success_alert = 'ลบแฟ้มข้อมูลคุณครูข้าราชการท่านนั้นทิ้งเสร็จสมบูรณ์!';
        } catch (Exception $e) {
            $err_alert = 'เกิดข้อผิดพลาดในการลบข้อมูลครู: ' . $e->getMessage();
        }
    }
}

// 6. ดึงข้อมูลพื้นฐานเพื่อใช้เป็น Global State ในชุด View ต่างๆ
$settingsStmt = $pdo->query("SELECT * FROM `settings` WHERE `id` = 1");
$settings = $settingsStmt->fetch();

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบศูนย์สนับสนุนหลังบ้านแอดมิน | โรงเรียนบ้านหนองหว้า</title>
    <!-- ฟอนต์ Kanit และ Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Sarabun', 'sans-serif'],
                        heading: ['Kanit', 'sans-serif'],
                    },
                    colors: {
                        school: {
                            pink: {
                                light: '#f472b6',
                                DEFAULT: '#ec4899',
                                dark: '#be185d',
                            }
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 font-sans flex flex-col justify-between">

    <!-- แถบแถบหัวเมนูด้านบนแอดมิน -->
    <header class="bg-indigo-950 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 bg-school-pink text-white rounded-lg text-xs font-black animate-pulse">ADMIN MODULE</span>
                <span class="text-white text-sm font-semibold hidden sm:inline"><?php echo htmlspecialchars($settings['school_name']); ?></span>
            </div>
            
            <div class="flex items-center gap-4 text-xs font-bold font-heading">
                <span class="text-pink-300">เข้าใช้บัญชีโดย: <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <a href="index.php" target="_blank" class="bg-white/10 hover:bg-white/15 px-3 py-2 rounded-lg border border-white/10 transition flex items-center gap-1.5 text-[11px]">
                    👁️ เปิดหน้าแรกเว็บโรงเรียน
                </a>
                <a href="admin.php?action=logout" class="bg-rose-600 hover:bg-rose-700 px-3 py-2 rounded-lg transition">
                    ออกจากระบบ
                </a>
            </div>
        </div>
    </header>

    <!-- พื้นหลักการแสดงรายงานผลการทำงาน -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow space-y-8 w-full">
        
        <!-- แบนเนอร์อธิบายตารางหลังบ้านอัตโนมัติ -->
        <div class="bg-gradient-to-r from-school-pink-dark via-school-pink to-pink-500 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
            <div class="absolute right-0 top-0 h-full w-1/3 bg-white/5 skew-x-12 translate-x-10 pointer-events-none"></div>
            <div class="relative z-10 space-y-2">
                <div class="inline-flex items-center gap-1.5 bg-white/20 text-white rounded-full px-3 py-1 text-[10px] font-black tracking-wider uppercase backdrop-blur-sm shadow-inner mb-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-ping"></span>
                    การเชื่อมต่อเซิร์ฟเวอร์เรียบร้อย: ตารางสัมพันธ์ออโต้ไลนท์
                </div>
                <h1 class="text-2xl sm:text-3xl font-heading font-black">ระบบสตรีมจัดการสารสนเทศหลังบ้านโรงเรียนบ้านหนองหว้า</h1>
                <p class="text-xs text-pink-50 max-w-2xl font-light leading-relaxed">
                    ยินดีต้อนรับเข้าสู่วิเศษวิชาการจัดการโรงเรียนบ้านหนองหว้า แยกแท็บการแก้ไขออกเป็นบล็อกระบบอิสระ สะดวก รวดเร็ว สอดรับกับแนวคิดความสวยงามความเรียบง่ายสะอ้านตา
                </p>
            </div>
        </div>

        <!-- กล่องสถานะทรานแซคชั่นระบบ -->
        <?php if (!empty($success_alert)): ?>
            <div class="bg-green-50 rounded-2xl p-4 text-green-700 text-xs font-bold border border-green-100 flex items-center gap-2 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <?php echo $success_alert; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($err_alert)): ?>
            <div class="bg-red-50 rounded-2xl p-4 text-red-600 text-xs font-bold border border-red-100 flex items-center gap-2 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <?php echo $err_alert; ?>
            </div>
        <?php endif; ?>

        <!-- เมนูจัดแท่งจัดหมวดหมู่โมดูลความสะดวกสบาย -->
        <?php
        $active_tab = 'general';
        if (isset($_GET['tab'])) {
            $active_tab = cleanInput($_GET['tab']);
        } elseif (isset($_POST['update_students'])) {
            $active_tab = 'students';
        } elseif (isset($_GET['edit_news']) || (isset($_GET['action']) && strpos($_GET['action'], 'news') !== false) || isset($_POST['add_news']) || isset($_POST['edit_news_submit'])) {
            $active_tab = 'news';
        } elseif (isset($_GET['edit_doc']) || (isset($_GET['action']) && strpos($_GET['action'], 'doc') !== false) || isset($_POST['add_doc']) || isset($_POST['edit_doc_submit'])) {
            $active_tab = 'downloads';
        } elseif (isset($_GET['edit_teacher']) || (isset($_GET['action']) && strpos($_GET['action'], 'teacher') !== false) || isset($_POST['add_teacher']) || isset($_POST['edit_teacher_submit'])) {
            $active_tab = 'teachers';
        } elseif (isset($_GET['edit_link']) || (isset($_GET['action']) && strpos($_GET['action'], 'link') !== false) || isset($_POST['add_link']) || isset($_POST['edit_link_submit'])) {
            $active_tab = 'links';
        }
        ?>
        <div class="flex flex-wrap gap-1.5 border-b border-slate-200 pb-px">
            <a href="admin.php?tab=general" class="px-4 py-3 rounded-t-2xl font-heading font-black text-xs sm:text-sm flex items-center gap-2 transition <?php echo $active_tab === 'general' ? 'bg-white text-school-pink border-t-2 border-school-pink border-x border-slate-200 shadow-sm' : 'text-slate-500 hover:text-slate-850 bg-slate-100/40 hover:bg-slate-100'; ?>">
                ⚙️ ตั้งค่าทั่วไป
            </a>
            <a href="admin.php?tab=students" class="px-4 py-3 rounded-t-2xl font-heading font-black text-xs sm:text-sm flex items-center gap-2 transition <?php echo $active_tab === 'students' ? 'bg-white text-school-pink border-t-2 border-school-pink border-x border-slate-200 shadow-sm' : 'text-slate-500 hover:text-slate-850 bg-slate-100/40 hover:bg-slate-100'; ?>">
                📊 ข้อมูลนักเรียน
            </a>
            <a href="admin.php?tab=news" class="px-4 py-3 rounded-t-2xl font-heading font-black text-xs sm:text-sm flex items-center gap-2 transition <?php echo $active_tab === 'news' ? 'bg-white text-school-pink border-t-2 border-school-pink border-x border-slate-200 shadow-sm' : 'text-slate-500 hover:text-slate-850 bg-slate-100/40 hover:bg-slate-100'; ?>">
                📰 ข่าวประชาสัมพันธ์
            </a>
            <a href="admin.php?tab=downloads" class="px-4 py-3 rounded-t-2xl font-heading font-black text-xs sm:text-sm flex items-center gap-2 transition <?php echo $active_tab === 'downloads' ? 'bg-white text-school-pink border-t-2 border-school-pink border-x border-slate-200 shadow-sm' : 'text-slate-500 hover:text-slate-850 bg-slate-100/40 hover:bg-slate-100'; ?>">
                📂 เอกสารแผนงานต่างๆ
            </a>
            <a href="admin.php?tab=teachers" class="px-4 py-3 rounded-t-2xl font-heading font-black text-xs sm:text-sm flex items-center gap-2 transition <?php echo $active_tab === 'teachers' ? 'bg-white text-school-pink border-t-2 border-school-pink border-x border-slate-200 shadow-sm' : 'text-slate-500 hover:text-slate-850 bg-slate-100/40 hover:bg-slate-100'; ?>">
                🧑‍🏫 ข้อมูลครูและทำเนียบ
            </a>
            <a href="admin.php?tab=links" class="px-4 py-3 rounded-t-2xl font-heading font-black text-xs sm:text-sm flex items-center gap-2 transition <?php echo $active_tab === 'links' ? 'bg-white text-school-pink border-t-2 border-school-pink border-x border-slate-200 shadow-sm' : 'text-slate-500 hover:text-slate-850 bg-slate-100/40 hover:bg-slate-100'; ?>">
                🔗 สื่อและระบบงานครู
            </a>
        </div>

        <!-- คลังกล่องสวิตช์โหลดไฟล์เทมเพลตที่แยกโมดูลเรียบร้อยแล้ว -->
        <div class="mt-4 transition-all duration-300">
            <?php 
            if ($active_tab === 'general') {
                require_once 'admin/general.php';
            } elseif ($active_tab === 'students') {
                require_once 'admin/students.php';
            } elseif ($active_tab === 'news') {
                require_once 'admin/news.php';
            } elseif ($active_tab === 'downloads') {
                require_once 'admin/downloads.php';
            } elseif ($active_tab === 'teachers') {
                require_once 'admin/teachers.php';
            } elseif ($active_tab === 'links') {
                require_once 'admin/external_links.php';
            } else {
                require_once 'admin/general.php';
            }
            ?>
        </div>

    </main>

    <footer class="bg-slate-900 text-slate-500 text-center py-6 border-t border-slate-800 text-[11px] font-medium leading-loose mt-8">
        <p>© 2026 โรงเรียนบ้านหนองหว้า | แผงส่งเสริมการจัดการสารสนเทศแยกส่วนอัตลักษณ์ชมพูขาว</p>
    </footer>

</body>
</html>
