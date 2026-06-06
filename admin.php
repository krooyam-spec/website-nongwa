<?php
/**
 * ระบบจัดการหลังบ้านผู้ดูแลระบบ (Admin Dashboard Panel) - โรงเรียนบ้านหนองหว้า
 * ควบคุมข้อมูลโรงเรียน, สตรีมอัพเดตตารางฐานข้อมูลอัตโนมัติทันทีที่มีการร้องขอ
 * ออกแบบด้วยหัวใจ "ชมพู-ขาว" สวยงาม เป็นสัดส่วนและใช้ง่ายที่สุด
 */

require_once 'db_connect.php';

// บังคับสิทธิแอดมินในการตรวจสอบ Session
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// ระบบตัดจบเซสชัน Log out
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}

// ข้อมูลแจ้งเตือนผู้ใช้หลังทำธุรกรรมสำเร็จ/ล้มเหลว
$success_alert = '';
$err_alert = '';

/**
 * ฟังก์ชันสำหรับช่วยเหลืออัปโหลดไฟล์จริงขึ้นสู่เซิร์ฟเวอร์โรงเรียน
 * @param array $file $_FILES['input_name']
 * @param string $allowed_types นามสกุลที่ต้องการ เช่น "jpg,png,pdf,docx"
 * @param string $target_dir ไดเรกทอรีจัดเก็บ
 * @return string|false เส้นทางจัดเก็บไฟล์ที่อัพโหลดสำเร็จ (เช่น uploads/xxxx.pdf) หรือ false ในกรณีที่ล้มเหลว
 */
function uploadFileToServer($file, $allowed_types = 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip', $target_dir = 'uploads/') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // สร้างไดเรกทอรีถ้ายังไม่มี
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = explode(',', $allowed_types);

    if (!in_array($ext, $allowed)) {
        return false;
    }

    // ความปลอดภัย: เปลี่ยนชื่อไฟล์ด้วยเครื่องหมายเวลาและสุ่มตัวเลข เพื่อไม่ให้เกิดการทับซ้อนและเวิร์กโฟลว์ผิดพลาด
    $new_filename = 'file_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $target_filepath = $target_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_filepath)) {
        return $target_filepath;
    }

    return false;
}

// จัดการเหตุการณ์เมื่อต้องการอัปเดตข้อมูลทั่วไปของโรงเรียน (Update School Settings)
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
        // อัปโหลดไฟล์รูปภาพหรือไฟล์เอกสารโลโก้และแบนเนอร์
        $existing_stmt = $pdo->query("SELECT school_logo, banner_bg_image, banner_right_image FROM `settings` WHERE `id` = 1");
        $existing_sets = $existing_stmt->fetch();
        
        $school_logo = $existing_sets['school_logo'] ?? '';
        $banner_bg_image = $existing_sets['banner_bg_image'] ?? '';
        $banner_right_image = $existing_sets['banner_right_image'] ?? '';
        
        // อัปโหลดโลโก้โรงเรียน (รูปภาพ)
        if (isset($_FILES['school_logo_file']) && $_FILES['school_logo_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded_logo = uploadFileToServer($_FILES['school_logo_file'], 'jpg,jpeg,png,gif');
            if ($uploaded_logo) {
                $school_logo = $uploaded_logo;
            }
        } else if (!empty($_POST['school_logo_url'])) {
            $school_logo = cleanInput($_POST['school_logo_url']);
        }
        
        // อัปโหลดภาพพื้นหลังแบนเนอร์ (รูปภาพพื้นหลังเด่น)
        if (isset($_FILES['banner_bg_file']) && $_FILES['banner_bg_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded_bg = uploadFileToServer($_FILES['banner_bg_file'], 'jpg,jpeg,png,gif');
            if ($uploaded_bg) {
                $banner_bg_image = $uploaded_bg;
            }
        } else if (!empty($_POST['banner_bg_url'])) {
            $banner_bg_image = cleanInput($_POST['banner_bg_url']);
        }
        
        // อัปโหลดภาพขวาบนแบนเนอร์หลัก
        if (isset($_FILES['banner_right_file']) && $_FILES['banner_right_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded_right = uploadFileToServer($_FILES['banner_right_file'], 'jpg,jpeg,png,gif');
            if ($uploaded_right) {
                $banner_right_image = $uploaded_right;
            }
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

        // จัดการอัปเดตสถิติจนวนนักเรียนรายชั้นเรียน (จากเมนูย่อยของหน้าแก้ไข)
        if (isset($_POST['student_counts']) && is_array($_POST['student_counts'])) {
            foreach ($_POST['student_counts'] as $grade_id => $count) {
                $update_stat_stmt = $pdo->prepare("UPDATE `student_stats` SET `student_count` = :count WHERE `id` = :id");
                $update_stat_stmt->execute([
                    'count' => intval($count),
                    'id' => intval($grade_id)
                ]);
            }
        }

        $success_alert = 'อัปเดตการตั้งค่าข้อมูลทั่วไปของโรงเรียนบ้านหนองหว้า สโลแกน สารผู้บริหาร และสถิตินักเรียนเรียบร้อยแล้ว!';
    } catch (Exception $e) {
        $err_alert = 'เกิดข้อผิดพลาดในการบันทึกข้อมูลตาราง settings: ' . $e->getMessage();
    }
}

// จัดการแก้ไขข่าวประชาสัมพันธ์ (Edit News Content)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_news_submit'])) {
    $id = intval($_POST['news_id'] ?? 0);
    $title = cleanInput($_POST['news_title'] ?? '');
    $category = cleanInput($_POST['news_category'] ?? 'ประชาสัมพันธ์ทั่วไป');
    $summary = cleanInput($_POST['news_summary'] ?? '');
    $content = cleanInput($_POST['news_content'] ?? '');
    $image_url = cleanInput($_POST['news_image'] ?? '');
    $sticky_flag = isset($_POST['news_sticky']) ? 1 : 0;

    // รองรับอัปเดตไฟล์ภาพกิจกรรมจริงขึ้นเซิร์ฟเวอร์
    if (isset($_FILES['news_image_file']) && $_FILES['news_image_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_image = uploadFileToServer($_FILES['news_image_file'], 'jpg,jpeg,png,gif');
        if ($uploaded_image) {
            $image_url = $uploaded_image;
        }
    }

    if (empty($title) || empty($content)) {
        $err_alert = 'กรุณากรอกหัวข้อ และเนื้อหาอย่างครบถ้วนเพื่อทำการบันทึกแก้ไขคลังข่าว';
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
            $success_alert = 'ยินดีด้วย! บันทึกและแก้ไขข่าวประชาสัมพันธ์เรียบร้อยแล้ว';
        } catch (Exception $e) {
            $err_alert = 'เกิดข้อผิดพลาดในการแก้ไขข่าวประชาสัมพันธ์: ' . $e->getMessage();
        }
    }
}

// จัดการเหตุการณ์เมื่อเพิ่มข่าวประชาสัมพันธ์ชิ้นใหม่ (Post New News)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])) {
    $title = cleanInput($_POST['news_title'] ?? '');
    $category = cleanInput($_POST['news_category'] ?? 'ประชาสัมพันธ์ทั่วไป');
    $summary = cleanInput($_POST['news_summary'] ?? '');
    $content = cleanInput($_POST['news_content'] ?? '');
    $image_url = cleanInput($_POST['news_image'] ?? '');
    $sticky_flag = isset($_POST['news_sticky']) ? 1 : 0;
    $date = date('Y-m-d');

    // รองรับอัปเดตไฟล์ภาพกิจกรรมจริงขึ้นเซิร์ฟเวอร์
    if (isset($_FILES['news_image_file']) && $_FILES['news_image_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_image = uploadFileToServer($_FILES['news_image_file'], 'jpg,jpeg,png,gif');
        if ($uploaded_image) {
            $image_url = $uploaded_image;
        }
    }

    if (empty($image_url)) {
        $image_url = 'https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&q=80&w=600';
    }

    if (empty($title) || empty($content)) {
        $err_alert = 'กรุณากรอกหัวข้อ และเนื้อหาอย่างครบถ้วนเพื่อทำการบันทึกข้อมูล';
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
            $success_alert = 'ยินดีด้วย! บันทึกข่าวประชาสัมพันธ์ชิ้นใหม่เข้าสู่ตารางฐานข้อมูลสำเร็จ';
        } catch (Exception $e) {
            $err_alert = 'เกิดข้อผิดพลาดในการเพิ่มข่าวประชาสัมพันธ์: ' . $e->getMessage();
        }
    }
}

// จัดการแก้ไขคลังเอกสารจัดซื้อจัดจ้าง (Edit Download Document Command)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_doc_submit'])) {
    $id = intval($_POST['doc_id'] ?? 0);
    $title = cleanInput($_POST['doc_title'] ?? '');
    $category = cleanInput($_POST['doc_category'] ?? 'เอกสารทั่วไป');
    $file_type = cleanInput($_POST['doc_type'] ?? 'PDF');
    $file_size = cleanInput($_POST['doc_size'] ?? '1.5 MB');
    $file_url = cleanInput($_POST['doc_url'] ?? '#');

    // รองรับการบันทึกอัปโหลดไฟล์จริงขึ้นจัดเก็บอย่างแท้จริง
    if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_doc_path = uploadFileToServer($_FILES['doc_file'], 'pdf,doc,docx,xls,xlsx,zip,jpg,png,jpeg');
        if ($uploaded_doc_path) {
            $file_url = $uploaded_doc_path;
            
            // ตรวจจับนามสกุลเพื่อกำหนดประเภทไฟล์โดยอัตโนมัติ
            $ext = strtolower(pathinfo($_FILES['doc_file']['name'], PATHINFO_EXTENSION));
            if ($ext === 'docx' || $ext === 'doc') {
                $file_type = 'WORD';
            } elseif ($ext === 'xlsx' || $ext === 'xls') {
                $file_type = 'EXCEL';
            } else {
                $file_type = strtoupper($ext);
            }
            
            // ตรวจจับขนาดไฟล์โดยอัตโนมัติ
            $bytes = $_FILES['doc_file']['size'];
            if ($bytes >= 1048576) {
                $file_size = round($bytes / 1048576, 1) . ' MB';
            } else {
                $file_size = round($bytes / 1024, 1) . ' KB';
            }
        }
    }

    if (empty($title)) {
        $err_alert = 'กรุณากรอกชื่อสาส์นสิทธิ์เอกสารดาวน์โหลดเพื่อสร้างลิงก์เข้าสู่คลังจัดจ้าง';
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
            $success_alert = 'แก้ไขข้อมูลสิทธิเอกสารดาวน์โหลดเรียบร้อยแล้ว!';
        } catch (Exception $e) {
            $err_alert = 'ไม่สามารถแก้ไขเอกสารดาวน์โหลด: ' . $e->getMessage();
        }
    }
}

// จัดการเหตุการณ์เพิ่มเอกสารแผนงานจัดสื่อดาวน์โหลดเพิ่ม (Insert New Download Document)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_doc'])) {
    $title = cleanInput($_POST['doc_title'] ?? '');
    $category = cleanInput($_POST['doc_category'] ?? 'เอกสารทั่วไป');
    $file_type = cleanInput($_POST['doc_type'] ?? 'PDF');
    $file_size = cleanInput($_POST['doc_size'] ?? '1.5 MB');
    $file_url = cleanInput($_POST['doc_url'] ?? '#');
    $date = date('Y-m-d');

    // รองรับการบันทึกอัปโหลดไฟล์จริงขึ้นจัดเก็บอย่างแท้จริง
    if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_doc_path = uploadFileToServer($_FILES['doc_file'], 'pdf,doc,docx,xls,xlsx,zip,jpg,png,jpeg');
        if ($uploaded_doc_path) {
            $file_url = $uploaded_doc_path;
            
            // ตรวจจับนามสกุลเพื่อกำหนดประเภทไฟล์โดยอัตโนมัติ
            $ext = strtolower(pathinfo($_FILES['doc_file']['name'], PATHINFO_EXTENSION));
            if ($ext === 'docx' || $ext === 'doc') {
                $file_type = 'WORD';
            } elseif ($ext === 'xlsx' || $ext === 'xls') {
                $file_type = 'EXCEL';
            } else {
                $file_type = strtoupper($ext);
            }
            
            // ตรวจจับขนาดไฟล์โดยอัตโนมัติ
            $bytes = $_FILES['doc_file']['size'];
            if ($bytes >= 1048576) {
                $file_size = round($bytes / 1048576, 1) . ' MB';
            } else {
                $file_size = round($bytes / 1024, 1) . ' KB';
            }
        }
    }

    if (empty($title)) {
        $err_alert = 'กรุณากรอกชื่อสาส์นสิทธิ์เอกสารดาวน์โหลดเพื่อสร้างลิงก์เข้าสู่คลังจัดจ้าง';
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
            $success_alert = 'เพิ่มหัวข้อเอกสารดาวน์โหลดเข้าสู่คลังจัดซื้อจัดจ้างสำเร็จ!';
        } catch (Exception $e) {
            $err_alert = 'ไม่สามารถเพิ่มเอกสารดาวน์โหลด: ' . $e->getMessage();
        }
    }
}

// จัดการเหตุการณ์แก้ไขครูในทำเนียบ (Edit Teacher Database)
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

    // อัปโหลดไฟล์รูปประจำตนเอง (Teacher Profile Photo)
    if (isset($_FILES['teacher_image_file']) && $_FILES['teacher_image_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_img = uploadFileToServer($_FILES['teacher_image_file'], 'jpg,jpeg,png,gif');
        if ($uploaded_img) {
            $image_url = $uploaded_img;
        }
    }

    // อัปโหลดรายงานผลการปฏิบัติงาน (PA PDF/Doc file)
    if (isset($_FILES['teacher_pa_file']) && $_FILES['teacher_pa_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_pa = uploadFileToServer($_FILES['teacher_pa_file'], 'pdf,doc,docx,zip');
        if ($uploaded_pa) {
            $pa_link_url = $uploaded_pa;
        }
    }

    // อัปโหลดบันทึกพอร์ตหรือรายงานสมบัติ (Portfolio file)
    if (isset($_FILES['teacher_portfolio_file']) && $_FILES['teacher_portfolio_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_port = uploadFileToServer($_FILES['teacher_portfolio_file'], 'pdf,doc,docx,zip,jpg,png,jpeg');
        if ($uploaded_port) {
            $portfolio_url = $uploaded_port;
        }
    }

    if (empty($name) || empty($position)) {
        $err_alert = 'กรุณาระบุชื่อและตำแหน่งข้าราชการครู';
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
            $success_alert = 'อัปเดตประวัติครูและผลงานเรียบร้อยแล้ว!';
        } catch (Exception $e) {
            $err_alert = 'ไม่สามารถบันทึกประวัติข้าราชการครู: ' . $e->getMessage();
        }
    }
}

// จัดการเหตุการณ์เพิ่มรายชื่อครูและบุคลากรในทำเนียบ (Add Teacher to Database)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_teacher'])) {
    $name = cleanInput($_POST['teacher_name'] ?? '');
    $position = cleanInput($_POST['teacher_position'] ?? '');
    $level = cleanInput($_POST['teacher_level'] ?? 'ครูผู้ช่วย');
    $subject_group = cleanInput($_POST['teacher_group'] ?? 'งานสอนทั่วไป');
    $image_url = cleanInput($_POST['teacher_image'] ?? '');
    $sort_order = intval($_POST['teacher_order'] ?? 99);
    $pa_link_url = cleanInput($_POST['teacher_pa_url'] ?? '');
    $portfolio_url = cleanInput($_POST['teacher_portfolio_url'] ?? '');

    // อัปโหลดไฟล์รูปประจำตนเอง (Teacher Profile Photo)
    if (isset($_FILES['teacher_image_file']) && $_FILES['teacher_image_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_img = uploadFileToServer($_FILES['teacher_image_file'], 'jpg,jpeg,png,gif');
        if ($uploaded_img) {
            $image_url = $uploaded_img;
        }
    }

    // อัปโหลดรายงานผลการปฏิบัติงาน (PA PDF/Doc file)
    if (isset($_FILES['teacher_pa_file']) && $_FILES['teacher_pa_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_pa = uploadFileToServer($_FILES['teacher_pa_file'], 'pdf,doc,docx,zip');
        if ($uploaded_pa) {
            $pa_link_url = $uploaded_pa;
        }
    }

    // อัปโหลดบันทึกพอร์ตหรือรายงานสมบัติ (Portfolio file)
    if (isset($_FILES['teacher_portfolio_file']) && $_FILES['teacher_portfolio_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_port = uploadFileToServer($_FILES['teacher_portfolio_file'], 'pdf,doc,docx,zip,jpg,png,jpeg');
        if ($uploaded_port) {
            $portfolio_url = $uploaded_port;
        }
    }

    if (empty($image_url)) {
        $image_url = 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&q=80&w=300';
    }

    if (empty($name) || empty($position)) {
        $err_alert = 'กรุณาระบุชื่อและตำแหน่งข้าราชการครูท่านนั้นๆ ให้รอบคอบ';
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
            $success_alert = 'เพิ่มประวัติครูท่านใหม่และบันทึกข้อตกลง PA และลิงก์ทางวิชาการเรียบร้อย!';
        } catch (Exception $e) {
            $err_alert = 'ไม่สามารถลงทะเบียนประวัติข้าราชการครู: ' . $e->getMessage();
        }
    }
}

// จัดการลบข้อมูล (Delete actions)
if (isset($_GET['action'])) {
    $action_to_do = $_GET['action'];
    $item_id = intval($_GET['id'] ?? 0);

    if ($action_to_do === 'delete_news' && $item_id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `news` WHERE `id` = :id");
            $stmt->execute(['id' => $item_id]);
            $success_alert = 'ลบข่าวประชาสัมพันธ์เรียบร้อยแล้ว!';
        } catch (Exception $e) {
            $err_alert = 'เกิดข้อผิดพลาดในการลบข่าวประชาสัมพันธ์: ' . $e->getMessage();
        }
    } elseif ($action_to_do === 'delete_doc' && $item_id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `downloads` WHERE `id` = :id");
            $stmt->execute(['id' => $item_id]);
            $success_alert = 'ลบเอกสารดาวน์โหลดเรียบร้อยแล้ว!';
        } catch (Exception $e) {
            $err_alert = 'เกิดข้อผิดพลาดในการลบเอกสารดาวน์โหลด: ' . $e->getMessage();
        }
    } elseif ($action_to_do === 'delete_teacher' && $item_id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `teachers` WHERE `id` = :id");
            $stmt->execute(['id' => $item_id]);
            $success_alert = 'ลบประวัติและทำเนียบครูเรียบร้อยแล้ว!';
        } catch (Exception $e) {
            $err_alert = 'เกิดข้อผิดพลาดในการลบข้อมูลครู: ' . $e->getMessage();
        }
    }
}

// 1. นำข้อมูลตั้งค่ามาป้อนใน Form อัตโนมัติ
$settingsStmt = $pdo->query("SELECT * FROM `settings` WHERE `id` = 1");
$settings = $settingsStmt->fetch();

// 2. โหลดข้อมูลข่าวสารทั้งหมดสำหรับแสดงรายละเอียด/ลบ - ข่าวประชาสัมพันธ์ล่าสุดอันดับแรก
$news_list = $pdo->query("SELECT * FROM `news` ORDER BY `sticky_flag` DESC, `date` DESC, `id` DESC")->fetchAll();

// 3. โหลดข้อมูลดาวน์โหลด
$downloads_list = $pdo->query("SELECT * FROM `downloads` ORDER BY `id` DESC")->fetchAll();

// 4. โหลดข้อมูลทำเนียบครู
$teachers_list = $pdo->query("SELECT * FROM `teachers` ORDER BY `sort_order` ASC, `id` ASC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบศูนย์สนับสนุนแอดมินหลังบ้าน | โรงเรียนบ้านหนองหว้า</title>
    <!-- ฟอนต์ Kanit/Sarabun -->
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

    <!-- แถบเมนูด้านบนแอดมิน -->
    <header class="bg-indigo-950 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 bg-school-pink text-white rounded-lg text-xs font-black animate-pulse">ADMIN WORKSPACE</span>
                <span class="text-white text-sm font-semibold hidden sm:inline"><?php echo htmlspecialchars($settings['school_name']); ?></span>
            </div>
            
            <div class="flex items-center gap-4 text-xs font-bold">
                <span class="text-pink-300">ยินดีต้อนรับ: <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <a href="index.php" target="_blank" class="bg-white/10 hover:bg-white/15 px-3 py-1.5 rounded-lg border border-white/10 transition flex items-center gap-1 text-[11px]">
                    เปิดหน้าแรกเว็บรร.
                </a>
                <a href="admin.php?action=logout" class="bg-rose-600 hover:bg-rose-700 px-3 py-1.5 rounded-lg transition">
                    ออกจากระบบ
                </a>
            </div>
        </div>
    </header>

    <!-- พื้นที่แสดงรายงานธุรกรรมต่างๆ -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow space-y-8 w-full">
        
        <!-- แถบอธิบายสถานะและการตรวจสอบตารางฐานข้อมูลอัตโนมัติ -->
        <div class="bg-gradient-to-r from-school-pink-dark via-school-pink to-pink-500 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
            <div class="absolute right-0 top-0 h-full w-1/3 bg-white/5 skew-x-12 translate-x-10 pointer-events-none"></div>
            <div class="relative z-10 space-y-2">
                <div class="inline-flex items-center gap-1.5 bg-white/20 text-white rounded-full px-3 py-1 text-[10px] font-black tracking-wider uppercase backdrop-blur-sm shadow-inner mb-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-ping"></span>
                    ฐานข้อมูลออนไลน์: ทำงานปกติ (Auto-Migration Active)
                </div>
                <h1 class="text-2xl sm:text-3.5xl font-heading font-black">ศูนย์สตรีมควบคุมข้อมูลและอัพเดทตารางอัตโนมัติ</h1>
                <p class="text-xs text-pink-50 max-w-2xl font-light leading-relaxed">
                    ระบบจะทำการเขียนโครงสร้างฐานข้อมูล MySQL และอัพเดทตาราง ตลอดจนเติมคอลัมน์ใหม่ๆ โดยอัตโนมัติเมื่อเว็บเซิร์ฟเวอร์เปิดรัน 
                    สอดรับกับนโยบายชมพู-ขาว โรงเรียนบ้านหนองหว้า ช่วยขจัดอุปสรรคของการรันสคริปต์ SQL บนเซิร์ฟเวอร์โฮสติ้งของจริง
                </p>
            </div>
        </div>

        <?php if (!empty($success_alert)): ?>
            <div class="bg-green-50 rounded-2xl p-4 text-green-700 text-xs font-bold border border-green-100 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <?php echo $success_alert; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($err_alert)): ?>
            <div class="bg-red-50 rounded-2xl p-4 text-red-600 text-xs font-bold border border-red-100 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <?php echo $err_alert; ?>
            </div>
        <?php endif; ?>

        <!-- แถบเมนูด้านบนแอดมินสำหรับการจัดหมวดหมู่ -->
        <?php
        $active_tab = 'general';
        if (isset($_GET['tab'])) {
            $active_tab = cleanInput($_GET['tab']);
        } elseif (isset($_GET['edit_news']) || (isset($_GET['action']) && strpos($_GET['action'], 'news') !== false) || isset($_POST['add_news']) || isset($_POST['edit_news_submit'])) {
            $active_tab = 'news';
        } elseif (isset($_GET['edit_doc']) || (isset($_GET['action']) && strpos($_GET['action'], 'doc') !== false) || isset($_POST['add_doc']) || isset($_POST['edit_doc_submit'])) {
            $active_tab = 'downloads';
        } elseif (isset($_GET['edit_teacher']) || (isset($_GET['action']) && strpos($_GET['action'], 'teacher') !== false) || isset($_POST['add_teacher']) || isset($_POST['edit_teacher_submit'])) {
            $active_tab = 'teachers';
        }
        ?>
        <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-px">
            <a href="admin.php?tab=general" class="px-5 py-3 rounded-t-2xl font-heading font-black text-xs sm:text-sm flex items-center gap-2 transition <?php echo $active_tab === 'general' ? 'bg-white text-school-pink border-t-2 border-school-pink border-x border-slate-200' : 'text-slate-500 hover:text-slate-800 bg-slate-100/60 hover:bg-slate-100'; ?>">
                ⚙️ ตั้งค่าทั่วไป
            </a>
            <a href="admin.php?tab=news" class="px-5 py-3 rounded-t-2xl font-heading font-black text-xs sm:text-sm flex items-center gap-2 transition <?php echo $active_tab === 'news' ? 'bg-white text-school-pink border-t-2 border-school-pink border-x border-slate-200' : 'text-slate-500 hover:text-slate-800 bg-slate-100/60 hover:bg-slate-100'; ?>">
                📰 จัดการข่าวประชาสัมพันธ์
            </a>
            <a href="admin.php?tab=downloads" class="px-5 py-3 rounded-t-2xl font-heading font-black text-xs sm:text-sm flex items-center gap-2 transition <?php echo $active_tab === 'downloads' ? 'bg-white text-school-pink border-t-2 border-school-pink border-x border-slate-200' : 'text-slate-500 hover:text-slate-800 bg-slate-100/60 hover:bg-slate-100'; ?>">
                📂 จัดการคลังเอกสาร
            </a>
            <a href="admin.php?tab=teachers" class="px-5 py-3 rounded-t-2xl font-heading font-black text-xs sm:text-sm flex items-center gap-2 transition <?php echo $active_tab === 'teachers' ? 'bg-white text-school-pink border-t-2 border-school-pink border-x border-slate-200' : 'text-slate-500 hover:text-slate-800 bg-slate-100/60 hover:bg-slate-100'; ?>">
                🧑‍🏫 จัดการทำเนียบครู
            </a>
        </div>

        <!-- TABBED PANELS CONTAINER -->
        <div class="mt-8">

            <!-- TAB 1: GENERAL SETTINGS -->
            <?php if ($active_tab === 'general'): ?>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- ด้านซ้าย: จัดการข้อมูลพื้นฐานทั่วไปของโรงเรียน (7/12) -->
                <div class="lg:col-span-8 space-y-8">
                
                <!-- ฟอร์ม 1: ตั้งค่าข้อมูลโรงเรียน -->
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-pink-50 shadow-sm space-y-6">
                    <div class="border-b border-slate-100 pb-4">
                        <h3 class="text-lg font-heading font-black text-slate-800 flex items-center gap-2 leading-none">
                            <span class="p-2 bg-pink-100 text-school-pink rounded-xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
                            </span>
                            ตั้งค่าแก้ไข ข้อมูลทั่วไปของสถานศึกษา
                        </h3>
                        <p class="text-[10px] text-slate-400 font-semibold uppercase mt-2">แก้ไขข้อมูลและจัดเก็บลงในตาราง settings ประมวลผลสดแบบเรียลไทม์</p>
                    </div>

                    <form action="admin.php" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-semibold text-slate-600">
                        <input type="hidden" name="update_settings" value="done">

                        <div class="space-y-1">
                            <label class="block">ชื่อโรงเรียนอย่างเป็นทางการ</label>
                            <input type="text" name="school_name" required value="<?php echo htmlspecialchars($settings['school_name']); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <div class="space-y-1">
                            <label class="block">ชื่อย่อสำหรับระบบย่อ</label>
                            <input type="text" name="short_name" required value="<?php echo htmlspecialchars($settings['short_name']); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <div class="space-y-1 sm:col-span-2">
                            <label class="block">คำขวัญประจำโรงเรียน (อัพเดทอัตโนมัติบนระบบตาราง)</label>
                            <input type="text" name="school_motto" required value="<?php echo htmlspecialchars($settings['school_motto'] ?? 'ชมพู-ขาว ก้าวไกลวิชาการ'); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <!-- 💖 โซนใหม่: หัวข้อแบนเนอร์และสโลแกนในใจ -->
                        <div class="space-y-1 sm:col-span-2">
                            <label class="block text-slate-700 font-bold">ข้อความแบนเนอร์หลัก (พาดหัวสโลแกนหลัก)</label>
                            <input type="text" name="banner_title" value="<?php echo htmlspecialchars($settings['banner_title'] ?? 'ยินดีต้อนรับสู่รั้วชมพู-ขาว แหล่งการศึกษาระดับเยาวชนต้นแบบ'); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-semibold focus:ring-1 focus:ring-school-pink outline-none">
                        </div>
                        <div class="space-y-1 sm:col-span-2">
                            <label class="block text-slate-700 font-bold">คำขยายแถบสโลแกนแบนเนอร์ (Subtitle)</label>
                            <input type="text" name="banner_subtitle" value="<?php echo htmlspecialchars($settings['banner_subtitle'] ?? 'เน้นทักษะชีวิต ความดีงาม คุณธรรมสูงส่ง ส่งผ่านความใส่ใจในระดับชั้น:'); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <!-- 💖 โซนใหม่: สารและกล่องข้อความจากผู้บริหาร -->
                        <div class="sm:col-span-2 border-t border-slate-100 pt-4 mt-2 space-y-4">
                            <label class="block text-slate-800 font-black text-xs sm:text-xs">สารจากผู้บริหารโรงเรียน</label>
                            <div class="space-y-3 p-4 bg-pink-50/20 rounded-2xl border border-pink-100/40">
                                <div class="space-y-1">
                                    <label class="block text-slate-650 font-bold">หัวข้อสาส์นผู้บริหาร (Message Title)</label>
                                    <input type="text" name="director_message_title" value="<?php echo htmlspecialchars($settings['director_message_title'] ?? 'มุ่งมั่นเสริมนวัตกรรมการเรียนการสอน เชิดชูคุณธรรมความดี'); ?>" class="w-full bg-white rounded-xl border border-pink-100 p-2.5 text-xs outline-none focus:ring-1 focus:ring-school-pink">
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-slate-650 font-bold">เนื้อความสารฉบับเต็มของผู้อำนวยการ</label>
                                    <textarea name="director_message" rows="5" class="w-full bg-white rounded-xl border border-pink-100 p-2.5 text-xs outline-none focus:ring-1 focus:ring-school-pink" placeholder="พิมพ์ข้อความสารสาส์นจากผู้บริหาร..."><?php echo htmlspecialchars($settings['director_message'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-1 sm:col-span-2">
                            <label class="block">ที่ตั้งและสถานที่ตั้งอย่างละเอียด</label>
                            <textarea name="address" required rows="2" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none"><?php echo htmlspecialchars($settings['address']); ?></textarea>
                        </div>

                        <div class="space-y-1">
                            <label class="block">เบอร์โทรศัพท์ติดต่อ</label>
                            <input type="text" name="phone" required value="<?php echo htmlspecialchars($settings['phone']); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <div class="space-y-1">
                            <label class="block">อีเมลของสถาบัน</label>
                            <input type="email" name="email" required value="<?php echo htmlspecialchars($settings['email']); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <div class="spa                </div> <!-- Closes lg:col-span-8 -->

                <!-- ด้านขวา: ระบบสถิติ และความมั่นคงฐานข้อมูล (4/12) -->
                <div class="lg:col-span-4 space-y-8">
                    
                    <!-- บล็อก: ระบบสุขภาพและการรัน ALTER TABLE อัตโนมัติ -->
                    <div class="bg-indigo-900 text-white rounded-3xl p-6 border border-pink-950/20 shadow space-y-4">
                        <h4 class="font-heading font-black text-sm text-pink-300 flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            โปรแกรมรักษาความมั่นคงฐานข้อมูล
                        </h4>
                        <p class="text-[11px] leading-relaxed font-light text-slate-300">
                            ในระบบนี้ มีการติดตั้งฟังก์ชัน <strong class="text-pink-200">Auto-Schema Alignment</strong> 
                            เมื่อเกิดการแก้ไขสคริปต์ใน PHP หรือมีการร้องขอสอยเพิ่มคอลัมน์ใหม่ในโค้ด 
                            ตัวระบบจะรันสิทธิสั่ง ALTER TABLE ทันทีที่โหลดไฟล์ โดยไม่ต้องกรอกคำสั่งนำเข้าซ้ำซาก
                        </p>
                        <div class="bg-indigo-950 rounded-2xl p-4 text-[10px] space-y-1 border border-indigo-800/50">
                            <div class="flex justify-between font-bold text-green-300">
                                <span>ตาราง settings</span>
                                <span>✓ สมบรูณ์แบบ</span>
                            </div>
                            <div class="flex justify-between font-bold text-green-300">
                                <span>ตาราง news</span>
                                <span>✓ สมบรูณ์แบบ</span>
                            </div>
                            <div class="flex justify-between font-bold text-green-300">
                                <span>ตาราง teachers</span>
                                <span>✓ สมบรูณ์แบบ</span>
                            </div>
                            <div class="flex justify-between font-bold text-green-300">
                                <span>ตาราง downloads</span>
                                <span>✓ สมบรูณ์แบบ</span>
                            </div>
                        </div>
                    </div>

                </div> <!-- Closes lg:col-span-4 -->

            </div> <!-- Closes grid of general settings -->
            <?php endif; ?> <!-- Closes General Settings Tab -->


            <!-- TAB 2: NEWS MANAGEMENT -->
            <?php if ($active_tab === 'news'): ?>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start w-full">
                
                <!-- ด้านซ้าย: เพิ่ม/แก้ไขข่าวประชาสัมพันธ์ (5/12) -->
                <div class="lg:col-span-5 space-y-8">
                    <!-- If $_GET['edit_news'] is passed, let's display an edit form! -->
                    <?php 
                    $edit_news_item = null;
                    if (isset($_GET['edit_news'])) {
                        $edit_id = intval($_GET['edit_news']);
                        $stmt_edit = $pdo->prepare("SELECT * FROM `news` WHERE `id` = :id");
                        $stmt_edit->execute(['id' => $edit_id]);
                        $edit_news_item = $stmt_edit->fetch();
                    }
                    ?>
                    
                    <?php if ($edit_news_item): ?>
                    <!-- ฟอร์ม: แก้ไขข่าวสารประชาสัมพันธ์ -->
                    <div class="bg-indigo-950 text-slate-100 rounded-3xl p-6 sm:p-8 border border-indigo-900 shadow-sm space-y-6">
                        <div class="border-b border-indigo-900 pb-4 flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-heading font-black text-pink-300 flex items-center gap-2 leading-none">
                                    <span>✏️</span>
                                    แก้ไขข่าวประชาสัมพันธ์ (#<?php echo $edit_news_item['id']; ?>)
                                </h3>
                                <p class="text-[10px] text-slate-400 font-semibold uppercase mt-2">แก้ไขข้อมูลและกดตกลงเพื่อเสร็จสิ้น</p>
                            </div>
                            <a href="admin.php?tab=news" class="bg-indigo-900 text-white hover:bg-slate-800 text-[10px] font-bold px-2.5 py-1.5 rounded-lg border border-indigo-800">ยกเลิก</a>
                        </div>

                        <form action="admin.php?tab=news" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-semibold text-slate-300">
                            <input type="hidden" name="edit_news_submit" value="1">
                            <input type="hidden" name="news_id" value="<?php echo $edit_news_item['id']; ?>">

                            <div class="space-y-1">
                                <label class="block">หัวข้อข่าวประชาสัมพันธ์</label>
                                <input type="text" name="news_title" required value="<?php echo htmlspecialchars($edit_news_item['title']); ?>" class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-2.5 text-xs text-white focus:ring-1 focus:ring-school-pink outline-none">
                            </div>

                            <div class="space-y-1">
                                <label class="block">หมวดหมู่รายงานข่าวสาร</label>
                                <select name="news_category" class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-2.5 text-xs font-bold text-white focus:ring-1 focus:ring-school-pink outline-none">
                                    <option value="ประชาสัมพันธ์ทั่วไป" <?php echo ($edit_news_item['category'] === 'ประชาสัมพันธ์ทั่วไป') ? 'selected' : ''; ?>>ประชาสัมพันธ์ทั่วไป</option>
                                    <option value="ข่าวกิจกรรม" <?php echo ($edit_news_item['category'] === 'ข่าวกิจกรรม') ? 'selected' : ''; ?>>ข่าวกิจกรรม</option>
                                    <option value="ประชุมและวิชาการ" <?php echo ($edit_news_item['category'] === 'ประชุมและวิชาการ') ? 'selected' : ''; ?>>ประชุมและวิชาการ</option>
                                    <option value="ผลงานครูและนักเรียน" <?php echo ($edit_news_item['category'] === 'ผลงานครูและนักเรียน') ? 'selected' : ''; ?>>ผลงานครูและนักเรียน</option>
                                </select>
                            </div>

                            <div class="space-y-1 pb-1">
                                <label class="block text-slate-300 font-bold">รูปหน้าปกข่าวสาร</label>
                                <input type="file" name="news_image_file" accept="image/*" class="w-full text-[10px] text-slate-455 mb-1.5">
                                <input type="text" name="news_image" value="<?php echo htmlspecialchars($edit_news_item['image_url']); ?>" placeholder="หรือระบุ URL รูปตรง..." class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-2 text-xs text-white focus:ring-1 focus:ring-school-pink outline-none">
                            </div>

                            <div class="space-y-1">
                                <label class="block">บทสรุปข่าวย่อ สำหรับแสดงบนการ์ดหน้าหลัก (Summary)</label>
                                <input type="text" name="news_summary" value="<?php echo htmlspecialchars($edit_news_item['summary']); ?>" class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-2.5 text-xs text-white focus:ring-1 focus:ring-school-pink outline-none">
                            </div>

                            <div class="space-y-1">
                                <label class="block">เนื้อหาตัวเต็มของประชาสัมพันธ์ (Content)</label>
                                <textarea name="news_content" required rows="6" class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-2.5 text-xs text-white focus:ring-1 focus:ring-school-pink outline-none"><?php echo htmlspecialchars($edit_news_item['content']); ?></textarea>
                            </div>

                            <div class="flex items-center gap-2 pt-2">
                                <input type="checkbox" name="news_sticky" id="edit_news_sticky" value="1" <?php echo ($edit_news_item['sticky_flag'] == 1) ? 'checked' : ''; ?> class="h-4 w-4 bg-indigo-900 rounded">
                                <label for="edit_news_sticky" class="text-xs font-bold text-slate-300 select-none">ปักหมุดข่าวชิ้นนี้ไว้ด้านบนสุดเป็นอันดับแรกเสมอ</label>
                            </div>

                            <div class="pt-2">
                                <button type="submit" class="w-full bg-school-pink hover:bg-school-pink-dark text-white font-black py-3 rounded-xl transition shadow text-xs">
                                    บันทึกการแก้ไขข่าวสารประชาสัมพันธ์
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                    <!-- ฟอร์มเขียนข่าวใหม่ -->
                    <div class="bg-white rounded-3xl p-6 border border-pink-50 shadow-sm space-y-6">
                        <div class="border-b border-slate-100 pb-4">
                            <h3 class="text-lg font-heading font-black text-slate-800 flex items-center gap-2 leading-none">
                                <span class="p-2 bg-pink-100 text-school-pink rounded-xl">📰</span>
                                เพิ่มข่าวประชาสัมพันธ์ใหม่
                            </h3>
                            <p class="text-[10px] text-slate-400 font-semibold uppercase mt-2">เขียนเรื่องราวประกาศข่าวแจกจ่ายเข้าคลังเนื้อหา</p>
                        </div>

                        <form action="admin.php?tab=news" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-semibold text-slate-600">
                            <input type="hidden" name="add_news" value="done">

                            <div class="space-y-1">
                                <label class="block">หัวข้อข่าวประชาสัมพันธ์</label>
                                <input type="text" name="news_title" required placeholder="เช่น ประมวลภาพพิธีวันไหว้ครูเสร็จสมเกียรติ..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                            </div>

                            <div class="space-y-1">
                                <label class="block">หมวดหมู่รายงานข่าวสาร</label>
                                <select name="news_category" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-bold bg-white focus:ring-1 focus:ring-school-pink outline-none">
                                    <option value="ประชาสัมพันธ์ทั่วไป">ประชาสัมพันธ์ทั่วไป</option>
                                    <option value="ข่าวกิจกรรม">ข่าวกิจกรรม</option>
                                    <option value="ประชุมและวิชาการ">ประชุมและวิชาการ</option>
                                    <option value="ผลงานครูและนักเรียน">ผลงานครูและนักเรียน</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-slate-800 font-bold">รูปหน้าปกข่าวสาร</label>
                                <input type="file" name="news_image_file" accept="image/*" class="w-full text-[10px] text-slate-500 mb-1.5">
                                <input type="text" name="news_image" placeholder="หรือพิมพ์ / วางที่อยู่ลิงก์รูปตรง..." class="w-full rounded-xl border border-pink-100 p-2 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                            </div>

                            <div class="space-y-1">
                                <label class="block">บทสรุปข่าวย่อ สำหรับแสดงบนการ์ดหน้ารถ (Summary)</label>
                                <input type="text" name="news_summary" placeholder="คำเกริ่นย่อ 1-2 บรรทัด..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                            </div>

                            <div class="space-y-1">
                                <label class="block">เนื้อหาตัวเต็มของประชาสัมพันธ์ (Content)</label>
                                <textarea name="news_content" required rows="5" placeholder="กรอกเรื่องราวและรายละเอียดทั้งหมด..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none"></textarea>
                            </div>

                            <div class="flex items-center gap-2 pt-2">
                                <input type="checkbox" name="news_sticky" id="add_news_sticky" value="1" class="h-4 w-4 bg-white rounded">
                                <label for="add_news_sticky" class="text-xs font-bold text-slate-700 select-none">ปักหมุดข่าวชิ้นนี้ไว้ด้านบนสุดเป็นอันดับแรก</label>
                            </div>

                            <button type="submit" class="w-full bg-school-pink hover:bg-school-pink-dark text-white font-black py-3 rounded-xl transition shadow text-xs">
                                บันทึกและเผยแพร่ข่าวประชาสัมพันธ์
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>

                </div> <!-- Closes lg:col-span-5 -->

                <!-- ด้านขวา: ประวัติตารางรายการข่าวสารที่มีอยู่ในระบบ (7/12) -->
                <div class="lg:col-span-7 space-y-8">
                    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
                        <div class="border-b border-slate-100 pb-3 flex justify-between items-center">
                            <h4 class="font-heading font-black text-sm text-slate-800">
                                📰 รายชื่อข่าวประชาสัมพันธ์ประชาคม (แสดงข่าวล่าสุดเป็นอันดับแรก)
                            </h4>
                            <span class="text-xs font-bold text-school-pink">จำนวน: <?php echo count($news_list); ?> ข่าว</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-600 font-bold border-b border-slate-100">
                                        <th class="p-3">หน้าปก</th>
                                        <th class="p-3">หัวเรื่องข่าวกิจกรรม</th>
                                        <th class="p-3">หมวดหมู่</th>
                                        <th class="p-3 text-right">ดำเนินการและควบคุม</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-medium">
                                    <?php if (empty($news_list)): ?>
                                        <tr><td colspan="4" class="p-4 text-center text-slate-400">ยังไม่มีข้อมูลข่าวประชาสัมพันธ์ในระบบ</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($news_list as $nw): ?>
                                        <tr>
                                            <td class="p-3">
                                                <img src="<?php echo htmlspecialchars($nw['image_url']); ?>" class="w-12 h-12 object-cover rounded-lg border border-slate-200">
                                            </td>
                                            <td class="p-3">
                                                <div class="font-bold text-slate-800 line-clamp-1 max-w-xs">
                                                    <?php if($nw['sticky_flag']): ?><span class="text-rose-600 font-extrabold mr-1">[📌 ปักหมุด]</span><?php endif; ?>
                                                    <?php echo htmlspecialchars($nw['title']); ?>
                                                </div>
                                                <div class="text-[10px] text-slate-400 line-clamp-1 mt-0.5"><?php echo htmlspecialchars($nw['summary']); ?></div>
                                                <div class="text-[9px] text-slate-400 mt-1"><?php echo htmlspecialchars($nw['date']); ?></div>
                                            </td>
                                            <td class="p-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] bg-pink-50 text-school-pink font-bold"><?php echo htmlspecialchars($nw['category']); ?></span>
                                            </td>
                                            <td class="p-3 text-right space-x-1 whitespace-nowrap">
                                                <a href="admin.php?tab=news&edit_news=<?php echo $nw['id']; ?>" class="inline-block bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-2.5 py-1.5 rounded-lg font-bold text-[10px]">✏️ แก้ไข</a>
                                                <a href="admin.php?tab=news&action=delete_news&id=<?php echo $nw['id']; ?>" onclick="return confirm('ยืนยันลบข่าวประชาสัมพันธ์นี้ใช่ไหม?')" class="inline-block bg-rose-50 hover:bg-rose-100 text-rose-600 px-2.5 py-1.5 rounded-lg font-bold text-[10px]">🗑️ ลบ</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div> <!-- Closes lg:col-span-7 -->

            </div> <!-- Closes grid -->
            <?php endif; ?> <!-- Closes news tab conditional -->


            <!-- TAB 3: DOWNLOADS DOCUMENTS MANAGEMENT -->
            <?php if ($active_tab === 'downloads'): ?>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start w-full">
                
                <!-- ด้านซ้าย: เพิ่ม/แก้ไขดาวน์โหลดเอกสารคลังสอบจัดจ้าง (5/12) -->
                <div class="lg:col-span-5 space-y-8">
                    <?php 
                    $edit_doc_item = null;
                    if (isset($_GET['edit_doc'])) {
                        $edit_doc_id = intval($_GET['edit_doc']);
                        $stmt_edit_doc = $pdo->prepare("SELECT * FROM `downloads` WHERE `id` = :id");
                        $stmt_edit_doc->execute(['id' => $edit_doc_id]);
                        $edit_doc_item = $stmt_edit_doc->fetch();
                    }
                    ?>
                    
                    <?php if ($edit_doc_item): ?>
                    <!-- ฟอร์มแก้ไขคู่ขนาน -->
                    <div class="bg-indigo-950 text-slate-100 rounded-3xl p-6 border border-slate-800 shadow space-y-4">
                        <div class="flex items-center justify-between border-b border-indigo-900/50 pb-3">
                            <h4 class="font-heading font-black text-sm text-pink-300">✏️ แก้ไขสเปคเอกสารดาวน์โหลด (#<?php echo $edit_doc_item['id']; ?>)</h4>
                            <a href="admin.php?tab=downloads" class="text-[10px] bg-indigo-900 border border-indigo-800 hover:bg-indigo-850 p-1.5 rounded text-white font-bold">ยกเลิก</a>
                        </div>
                        <form action="admin.php?tab=downloads" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-semibold text-slate-300">
                            <input type="hidden" name="edit_doc_submit" value="1">
                            <input type="hidden" name="doc_id" value="<?php echo $edit_doc_item['id']; ?>">

                            <div class="space-y-1">
                                <label class="block">ชื่อเรียกจัดซื้อประมูล/เอกสารจัดส่ง</label>
                                <input type="text" name="doc_title" required value="<?php echo htmlspecialchars($edit_doc_item['title']); ?>" class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-2.5 text-xs text-white focus:ring-1 focus:ring-school-pink outline-none">
                            </div>

                            <div class="space-y-1">
                                <label class="block">ประเภทหมวดหมู่เอกสาร</label>
                                <select name="doc_category" class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-2.5 text-xs font-bold text-white focus:ring-1 focus:ring-school-pink outline-none">
                                    <option value="เอกสารทั่วไป" <?php echo ($edit_doc_item['category'] == 'เอกสารทั่วไป') ? 'selected' : ''; ?>>เอกสารทั่วไป</option>
                                    <option value="แผนงานและนโยบาย" <?php echo ($edit_doc_item['category'] == 'แผนงานและนโยบาย') ? 'selected' : ''; ?>>แผนงานและนโยบาย</option>
                                    <option value="ประกันคุณภาพ" <?php echo ($edit_doc_item['category'] == 'ประกันคุณภาพ') ? 'selected' : ''; ?>>ประกันคุณภาพ</option>
                                    <option value="เอกสารครู" <?php echo ($edit_doc_item['category'] == 'เอกสารครู') ? 'selected' : ''; ?>>เอกสารครู</option>
                                </select>
                            </div>

                            <div class="space-y-2 border-t border-indigo-900 pt-2">
                                <label class="block font-bold">1. อัปโหลดไฟล์เอกสารทับไฟล์เดิมบน Server</label>
                                <input type="file" name="doc_file" class="w-full text-slate-400">
                            </div>

                            <div class="border-t border-indigo-900 pt-2 space-y-2 text-[10px]">
                                <label class="block text-slate-400">หรือวางลิงก์ระบุค่าแบบกำหนดเองด้านล่าง:</label>
                                <div class="grid grid-cols-2 gap-3 text-xs">
                                    <div class="space-y-1">
                                        <label class="block font-bold">ประเภทสกุล</label>
                                        <input type="text" name="doc_type" value="<?php echo htmlspecialchars($edit_doc_item['file_type']); ?>" class="w-full rounded bg-indigo-900 border border-indigo-850 p-1.5 text-white">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block font-bold">ขนาด (เช่น 1.2 MB)</label>
                                        <input type="text" name="doc_size" value="<?php echo htmlspecialchars($edit_doc_item['file_size']); ?>" class="w-full rounded bg-indigo-900 border border-indigo-850 p-1.5 text-white">
                                    </div>
                                </div>
                                <div class="space-y-1 text-xs">
                                    <label class="block font-bold">ลิงค์ดาวน์โหลดตรง (URL หรือใส่ # ไว้)</label>
                                    <input type="text" name="doc_url" value="<?php echo htmlspecialchars($edit_doc_item['file_url']); ?>" class="w-full rounded bg-indigo-900 border border-indigo-850 p-2 text-white">
                                </div>
                            </div>

                            <button type="submit" class="w-full bg-school-pink hover:bg-school-pink-dark text-white font-black py-2.5 rounded text-xs shadow">
                                บันทึกการปรับปรุงข้อมูลคุณสมบัติเอกสารคลัง
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <!-- ฟอร์มเพิ่มเอกสารดาวน์โหลดเอกสารคลังใหม่ -->
                    <div class="bg-white rounded-3xl p-6 border border-pink-50 shadow-sm space-y-4">
                        <h4 class="font-heading font-black text-sm text-slate-800 flex items-center gap-1.5 border-b border-slate-100 pb-3">
                            📂 เพิ่มเอกสารและประกาศจัดซื้อจัดจ้างใหม่
                        </h4>
                        
                        <form action="admin.php?tab=downloads" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-semibold text-slate-600">
                            <input type="hidden" name="add_doc" value="done">

                            <div class="space-y-1">
                                <label class="block">ชื่อเรียกจัดซื้อประมูล/สารสิทธิประโยชน์</label>
                                <input type="text" name="doc_title" required placeholder="เช่น เอกสารประกวดราคาจ้างเหมาต่อเติมอาคาร..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                            </div>

                            <div class="space-y-1">
                                <label class="block">ประเภทหมวดหมู่เอกสารคลัง</label>
                                <select name="doc_category" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-bold bg-white focus:ring-1 focus:ring-school-pink outline-none">
                                    <option value="เอกสารทั่วไป">เอกสารทั่วไป</option>
                                    <option value="แผนงานและนโยบาย">แผนงานและนโยบาย</option>
                                    <option value="ประกันคุณภาพ">ประกันคุณภาพ</option>
                                    <option value="เอกสารครู">เอกสารครู</option>
                                </select>
                            </div>

                            <div class="space-y-2 border-t border-pink-100/35 pt-2">
                                <label class="block text-slate-800 font-bold">1. อัพโหลดตัวไฟล์จริงขึ้นสถาบันโรงเรียน</label>
                                <input type="file" name="doc_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip" class="w-full text-[10px] text-slate-500">
                            </div>

                            <div class="border-t border-pink-100/35 pt-2 space-y-2 text-[10px]">
                                <label class="block text-slate-400">หรือกำหนดค่าขนาดและที่อยู่ดาวน์โหลดเอง:</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="space-y-1">
                                        <label class="block text-xs">ประเภทไฟล์แยก</label>
                                        <select name="doc_type" class="w-full rounded-xl border border-pink-100 p-1.5 text-xs font-bold bg-white focus:ring-1 focus:ring-school-pink outline-none">
                                            <option value="PDF">PDF</option>
                                            <option value="WORD">WORD</option>
                                            <option value="EXCEL">EXCEL</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-xs">ขนาดพื้นที่ (เช่น 1.2 MB)</label>
                                        <input type="text" name="doc_size" value="1.2 MB" class="w-full rounded-xl border border-pink-100 p-1.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                                    </div>
                                </div>

                                <div class="space-y-1 text-xs">
                                    <label class="block">ลิงค์ที่อยู่ดาวน์โหลด (URL หรือปล่อยเป็น # ไว้)</label>
                                    <input type="text" name="doc_url" value="#" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                                </div>
                            </div>

                            <button type="submit" class="w-full bg-slate-900 hover:bg-slate-950 text-white font-black py-2.5 rounded-xl transition shadow text-xs">
                                บันทึกเอกสารเข้าสู่ศูนย์คลังข่าวสาร
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ด้านขวา: แสดงตารางรายชื่อเอกสารทั้งหมด (7/12) -->
                <div class="lg:col-span-7 space-y-8">
                    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
                        <div class="border-b border-slate-100 pb-3 flex justify-between items-center">
                            <h4 class="font-heading font-black text-sm text-slate-800">
                                📂 รายการเอกสารดาวน์โหลดและจดทะเบียนทั้งหมดในระบบ
                            </h4>
                            <span class="text-xs font-bold text-school-pink">จำนวน: <?php echo count($downloads_list); ?> ชุด</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-600 font-bold border-b border-slate-100">
                                        <th class="p-3">ชื่อสารเอกสารประกวดราคา / ทรัพย์สิน</th>
                                        <th class="p-3">หมวดหมู่</th>
                                        <th class="p-3">ชนิดไฟล์</th>
                                        <th class="p-3 text-right">การจัดการดำเนินการ</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-medium">
                                    <?php if (empty($downloads_list)): ?>
                                        <tr><td colspan="4" class="p-4 text-center text-slate-400">ยังไม่มีข้อมูลเอกสารคลังในระบบ</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($downloads_list as $dl): ?>
                                        <tr>
                                            <td class="p-3 text-slate-900 font-black">
                                                <div class="line-clamp-2 max-w-xs"><?php echo htmlspecialchars($dl['title']); ?></div>
                                                <div class="text-[9px] text-slate-400 mt-1"><?php echo htmlspecialchars($dl['uploaded_date'] ?? ''); ?> | ขนาด: <?php echo htmlspecialchars($dl['file_size']); ?></div>
                                            </td>
                                            <td class="p-3">
                                                <span class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 text-slate-600"><?php echo htmlspecialchars($dl['category']); ?></span>
                                            </td>
                                            <td class="p-3">
                                                <span class="font-bold text-[10px] px-1.5 py-0.5 rounded bg-blue-50 text-blue-700"><?php echo htmlspecialchars($dl['file_type']); ?></span>
                                            </td>
                                            <td class="p-3 text-right space-x-1 whitespace-nowrap">
                                                <a href="admin.php?tab=downloads&edit_doc=<?php echo $dl['id']; ?>" class="inline-block bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-2.5 py-1.5 rounded-lg font-bold text-[10px]">✏️ แก้ไข</a>
                                                <a href="admin.php?tab=downloads&action=delete_doc&id=<?php echo $dl['id']; ?>" onclick="return confirm('ยืนยันลบเอกสารดาวน์โหลดนี้ใช่ไหม?')" class="inline-block bg-rose-50 hover:bg-rose-100 text-rose-600 px-2.5 py-1.5 rounded-lg font-bold text-[10px]">🗑️ ลบ</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div> <!-- Closes lg:col-span-7 -->

            </div> <!-- Closes grid -->
            <?php endif; ?> <!-- Closes downloads tab conditional -->


            <!-- TAB 4: TEACHERS DIRECTORY MANAGEMENT -->
            <?php if ($active_tab === 'teachers'): ?>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start w-full">
                
                <!-- ด้านซ้าย: เพิ่ม/แก้ไขประวัติของคณะครู (5/12) -->
                <div class="lg:col-span-5 space-y-8">
                    <?php 
                    $edit_teacher_item = null;
                    if (isset($_GET['edit_teacher'])) {
                        $edit_teacher_id = intval($_GET['edit_teacher']);
                        $stmt_edit_teacher = $pdo->prepare("SELECT * FROM `teachers` WHERE `id` = :id");
                        $stmt_edit_teacher->execute(['id' => $edit_teacher_id]);
                        $edit_teacher_item = $stmt_edit_teacher->fetch();
                    }
                    ?>
                    
                    <?php if ($edit_teacher_item): ?>
                    <!-- ฟอร์มแก้ไขประวัติและผลงานครู -->
                    <div class="bg-indigo-950 text-slate-100 rounded-3xl p-6 border border-slate-900 shadow space-y-4">
                        <div class="flex items-center justify-between border-b border-indigo-850 pb-3">
                            <h4 class="font-heading font-black text-sm text-pink-300">✏️ แก้ไขข้อมูลบุคลากร (#<?php echo $edit_teacher_item['id']; ?>)</h4>
                            <a href="admin.php?tab=teachers" class="text-[10px] bg-indigo-900 border border-indigo-800 hover:bg-slate-800 p-1.5 rounded text-white font-bold">ยกเลิก</a>
                        </div>
                        <form action="admin.php?tab=teachers" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-semibold text-slate-300">
                            <input type="hidden" name="edit_teacher_submit" value="1">
                            <input type="hidden" name="teacher_id" value="<?php echo $edit_teacher_item['id']; ?>">

                            <div class="space-y-1">
                                <label class="block">ชื่อ-นามสกุลข้าราชการครู</label>
                                <input type="text" name="teacher_name" required value="<?php echo htmlspecialchars($edit_teacher_item['name']); ?>" class="w-full bg-indigo-900 border border-indigo-850 p-2.5 rounded text-white outline-none">
                            </div>
                            
                            <div class="space-y-1">
                                <label class="block">ตำแหน่งสถานะหน้าที่</label>
                                <input type="text" name="teacher_position" required value="<?php echo htmlspecialchars($edit_teacher_item['position']); ?>" class="w-full bg-indigo-900 border border-indigo-850 p-2.5 rounded text-white outline-none">
                            </div>
                            
                            <div class="space-y-1">
                                <label class="block">ระดับวิทยฐานะ</label>
                                <select name="teacher_level" class="w-full bg-indigo-900 border border-indigo-850 text-white rounded p-2.5 font-bold outline-none">
                                    <option value="ผู้อำนวยการโรงเรียน (คศ.3)" <?php echo ($edit_teacher_item['level'] == 'ผู้อำนวยการโรงเรียน (คศ.3)') ? 'selected' : ''; ?>>ผู้อำนวยการโรงเรียน (คศ.3)</option>
                                    <option value="ครูชำนาญการพิเศษ (คศ.3)" <?php echo ($edit_teacher_item['level'] == 'ครูชำนาญการพิเศษ (คศ.3)') ? 'selected' : ''; ?>>ครูชำนาญการพิเศษ (คศ.3)</option>
                                    <option value="ครูชำนาญการ (คศ.2)" <?php echo ($edit_teacher_item['level'] == 'ครูชำนาญการ (คศ.2)') ? 'selected' : ''; ?>>ครูชำนาญการ (คศ.2)</option>
                                    <option value="ครู คศ.1" <?php echo ($edit_teacher_item['level'] == 'ครู คศ.1') ? 'selected' : ''; ?>>ครู คศ.1</option>
                                    <option value="ครูผู้ช่วย" <?php echo ($edit_teacher_item['level'] == 'ครูผู้ช่วย') ? 'selected' : ''; ?>>ครูผู้ช่วย</option>
                                    <option value="พนักงานราชการ" <?php echo ($edit_teacher_item['level'] == 'พนักงานราชการ') ? 'selected' : ''; ?>>พนักงานราชการ</option>
                                </select>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label class="block">กลุ่มสาระการเรียนรู้</label>
                                    <select name="teacher_group" class="w-full bg-indigo-900 border border-indigo-850 text-white rounded p-2 outline-none">
                                        <option value="ผู้บริหาร" <?php echo ($edit_teacher_item['subject_group'] == 'ผู้บริหาร') ? 'selected' : ''; ?>>ผู้บริหาร</option>
                                        <option value="วิชาการคณิตศาสตร์" <?php echo ($edit_teacher_item['subject_group'] == 'วิชาการคณิตศาสตร์') ? 'selected' : ''; ?>>วิชาการคณิตศาสตร์</option>
                                        <option value="วิทยาศาสตร์" <?php echo ($edit_teacher_item['subject_group'] == 'วิทยาศาสตร์') ? 'selected' : ''; ?>>วิทยาศาสตร์</option>
                                        <option value="ภาษาไทย" <?php echo ($edit_teacher_item['subject_group'] == 'ภาษาไทย') ? 'selected' : ''; ?>>ภาษาไทย</option>
                                        <option value="ระดับปฐมวัย" <?php echo ($edit_teacher_item['subject_group'] == 'ระดับปฐมวัย') ? 'selected' : ''; ?>>ระดับปฐมวัย</option>
                                        <option value="สุขศึกษาและพลศึกษา" <?php echo ($edit_teacher_item['subject_group'] == 'สุขศึกษาและพลศึกษา') ? 'selected' : ''; ?>>สุขศึกษาและพลศึกษา</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="block">จัดตำแหน่งลำดับเรียก</label>
                                    <input type="number" name="teacher_order" value="<?php echo intval($edit_teacher_item['sort_order']); ?>" class="w-full bg-indigo-900 border border-indigo-850 rounded p-1.5 text-white">
                                </div>
                            </div>
                            
                            <div class="p-2.5 bg-white/5 border border-white/10 rounded-xl space-y-1 text-[10px]">
                                <label class="block font-bold">1. รูปถ่ายข้าราชครูประจำทำเนียบ</label>
                                <input type="file" name="teacher_image_file" accept="image/*" class="w-full text-slate-400 pb-1">
                                <input type="text" name="teacher_image" value="<?php echo htmlspecialchars($edit_teacher_item['image_url']); ?>" placeholder="หรือระบุ URL รูปตรง..." class="w-full bg-indigo-900 border border-indigo-850 text-white rounded text-xs p-1.5 mt-1">
                            </div>
                            
                            <div class="p-2.5 bg-white/5 border border-white/10 rounded-xl space-y-1 text-[10px]">
                                <label class="block font-bold">2. บันทึกคำรายงานรายงานข้อตกลง PA</label>
                                <input type="file" name="teacher_pa_file" accept=".pdf,.doc,.docx,.zip" class="w-full text-slate-400 pb-1">
                                <input type="text" name="teacher_pa_url" value="<?php echo htmlspecialchars($edit_teacher_item['pa_link_url']); ?>" placeholder="หรือระบุที่อยู่ลิงค์ภายนอก..." class="w-full bg-indigo-900 border border-indigo-850 text-white rounded text-xs p-1.5 mt-1">
                            </div>
                            
                            <div class="p-2.5 bg-white/5 border border-white/10 rounded-xl space-y-1 text-[10px]">
                                <label class="block font-bold">3. คลังแฟ้มสมบัติสะสม Portfolio</label>
                                <input type="file" name="teacher_portfolio_file" accept=".pdf,.doc,.docx,.zip,image/*" class="w-full text-slate-400 pb-1">
                                <input type="text" name="teacher_portfolio_url" value="<?php echo htmlspecialchars($edit_teacher_item['portfolio_url']); ?>" placeholder="หรือระบุที่อยู่ลิงค์ภายนอก..." class="w-full bg-indigo-900 border border-indigo-850 text-white rounded text-xs p-1.5 mt-1">
                            </div>

                            <button type="submit" class="w-full bg-school-pink hover:bg-school-pink-dark text-white font-black py-2.5 rounded-xl transition text-xs shadow">
                                อัปเดตประวัติข้าราชการครูท่านนี้
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <!-- ฟอร์มเพิ่มทำเนียบครูข้าราชการใหม่ -->
                    <div class="bg-white rounded-3xl p-6 border border-pink-50 shadow-sm space-y-4">
                        <h4 class="font-heading font-black text-sm text-slate-800 flex items-center gap-1.5 border-b border-slate-100 pb-3">
                            🧑‍🏫 ลงทะเบียนจัดทำเนียบคณาจารย์และครูโรงเรียนเพิ่ม
                        </h4>

                        <form action="admin.php?tab=teachers" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-semibold text-slate-600">
                            <input type="hidden" name="add_teacher" value="done">

                            <div class="space-y-1">
                                <label class="block">ชื่อ-นามสกุลข้าราชการครู</label>
                                <input type="text" name="teacher_name" required placeholder="เช่น นางสาวสมสวย สวยจริงเรียนไว..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                            </div>

                            <div class="space-y-1">
                                <label class="block">ตำแหน่งและภาระหน้าที่สถานศึกษา</label>
                                <input type="text" name="teacher_position" required placeholder="เช่น ครูภาษาไทย ชั้นมัธยม / ครูอนุบาล..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                            </div>

                            <div class="space-y-1">
                                <label class="block">วิทยฐานะ</label>
                                <select name="teacher_level" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-bold bg-white focus:ring-1 focus:ring-school-pink outline-none">
                                    <option value="ผู้อำนวยการโรงเรียน (คศ.3)">ผู้อำนวยการโรงเรียน (คศ.3)</option>
                                    <option value="ครูชำนาญการพิเศษ (คศ.3)">ครูชำนาญการพิเศษ (คศ.3)</option>
                                    <option value="ครูชำนาญการ (คศ.2)">ครูชำนาญการ (คศ.2)</option>
                                    <option value="ครู คศ.1">ครู คศ.1</option>
                                    <option value="ครูผู้ช่วย" selected>ครูผู้ช่วย</option>
                                    <option value="พนักงานราชการ">พนักงานราชการ</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label class="block">กลุ่มสาระการเรียนรู้</label>
                                    <select name="teacher_group" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-bold bg-white focus:ring-1 focus:ring-school-pink outline-none">
                                        <option value="ผู้บริหาร">ผู้บริหาร</option>
                                        <option value="วิชาการคณิตศาสตร์">วิชาการคณิตศาสตร์</option>
                                        <option value="วิทยาศาสตร์">วิทยาศาสตร์</option>
                                        <option value="ภาษาไทย" selected>ภาษาไทย</option>
                                        <option value="ระดับปฐมวัย">ระดับปฐมวัย</option>
                                        <option value="สุขศึกษาและพลศึกษา">สุขศึกษาและพลศึกษา</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="block">ลำดับการเรียกแสดงผล</label>
                                    <input type="number" name="teacher_order" value="9" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                                </div>
                            </div>

                            <!-- อัพโหลดรูปประจำตัวครู -->
                            <div class="p-3 bg-pink-50/20 rounded-2xl border border-pink-50 space-y-1.5 text-[10px]">
                                <label class="block font-bold text-slate-800">1. รูปหน้าปกข้าราชการถ่ายตัวตน</label>
                                <input type="file" name="teacher_image_file" accept="image/*" class="w-full text-[10px] text-slate-500 pb-1">
                                <input type="text" name="teacher_image" placeholder="หรือพิมพ์ลิงก์รูปตรง..." class="w-full rounded-lg border border-pink-100 p-1.5 text-xs outline-none">
                            </div>

                            <!-- ระบบข้อตกลง PA และเว็บ Portfolio -->
                            <div class="p-3 bg-pink-50/20 rounded-2xl border border-pink-50 space-y-1.5 text-[10px]">
                                <label class="block font-bold text-slate-800">2. รายงานข้อตกลง PA (Performance Agreement)</label>
                                <input type="file" name="teacher_pa_file" accept=".pdf,.doc,.docx,.zip" class="w-full text-[10px] text-slate-500 pb-1">
                                <input type="text" name="teacher_pa_url" placeholder="หรือสอดลิงก์รายงานแบบแฝงภายนอก..." class="w-full rounded-lg border border-pink-100 p-1.5 text-xs outline-none">
                            </div>

                            <div class="p-3 bg-pink-50/20 rounded-2xl border border-pink-50 space-y-1.5 text-[10px]">
                                <label class="block font-bold text-slate-800">3. แฟ้มผลงานทางวิชาการ (Portfolio)</label>
                                <input type="file" name="teacher_portfolio_file" accept=".pdf,.doc,.docx,.zip,image/*" class="w-full text-[10px] text-slate-500 pb-1">
                                <input type="text" name="teacher_portfolio_url" placeholder="หรือสอดลิงก์แฟ้มสะสมงานภายนอก..." class="w-full rounded-lg border border-pink-100 p-1.5 text-xs outline-none">
                            </div>

                            <button type="submit" class="w-full bg-school-pink hover:bg-school-pink-dark text-white font-black py-2.5 rounded-xl transition shadow text-xs">
                                ลงทะเบียนประวัติข้าราชการครูเข้าระบบ
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ด้านขวา: แสดงรายการรายชื่อครูในทำเนียบปัจจุบัน (7/12) -->
                <div class="lg:col-span-7 space-y-8">
                    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
                        <div class="border-b border-slate-100 pb-3 flex justify-between items-center">
                            <h4 class="font-heading font-black text-sm text-slate-800">
                                🧑‍🏫 สารบบทำเนียบข้าราชการและครูทั้งหมดในสถิติโรงเรียน
                            </h4>
                            <span class="text-xs font-bold text-school-pink">จำนวนครู: <?php echo count($teachers_list); ?> ท่าน</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-600 font-bold border-b border-slate-100">
                                        <th class="p-3">รูปถ่าย</th>
                                        <th class="p-3">ชื่อ-สกุล / ข้อมูลสังกัดกลุ่มสาระ</th>
                                        <th class="p-3">จัดเรียงเรียก</th>
                                        <th class="p-3 text-right">ดำเนินการและควบคุม</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-medium">
                                    <?php if (empty($teachers_list)): ?>
                                        <tr><td colspan="4" class="p-4 text-center text-slate-400">ยังไม่มีข้อมูลทำเนียบคณะครูในระบบ</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($teachers_list as $tc): ?>
                                        <tr>
                                            <td class="p-3">
                                                <img src="<?php echo htmlspecialchars($tc['image_url']); ?>" class="w-10 h-10 object-cover rounded-full border border-slate-200">
                                            </td>
                                            <td class="p-3">
                                                <div class="font-black text-slate-900 text-xs sm:text-sm"><?php echo htmlspecialchars($tc['name']); ?></div>
                                                <div class="text-[10px] text-slate-500 font-medium mt-0.5"><?php echo htmlspecialchars($tc['position']); ?></div>
                                                <div class="text-[9px] text-slate-400 font-bold mt-1">
                                                    <span class="px-1.5 py-0.5 rounded bg-pink-50 text-school-pink font-bold mr-1"><?php echo htmlspecialchars($tc['level']); ?></span>
                                                    กลุ่มสาระ: <?php echo htmlspecialchars($tc['subject_group']); ?>
                                                </div>
                                            </td>
                                            <td class="p-3 text-slate-550 font-bold"><?php echo intval($tc['sort_order']); ?></td>
                                            <td class="p-3 text-right space-x-1 whitespace-nowrap">
                                                <a href="admin.php?tab=teachers&edit_teacher=<?php echo $tc['id']; ?>" class="inline-block bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-2.5 py-1.5 rounded-lg font-bold text-[10px]">✏️ แก้ไข</a>
                                                <a href="admin.php?tab=teachers&action=delete_teacher&id=<?php echo $tc['id']; ?>" onclick="return confirm('ยืนยันลบประวัติคุณครูท่านนี้และผลงานทิ้งใช่ไหม?')" class="inline-block bg-rose-50 hover:bg-rose-100 text-rose-600 px-2.5 py-1.5 rounded-lg font-bold text-[10px]">🗑️ ลบ</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div> <!-- Closes lg:col-span-7 -->

            </div> <!-- Closes grid -->
            <?php endif; ?> <!-- Closes teachers tab conditional -->n>
                            <span>✓ สมบรูณ์แบบ</span>
                        </div>
                        <div class="flex justify-between font-bold text-green-300">
                            <span>ตาราง news</span>
                            <span>✓ สมบรูณ์แบบ</span>
                        </div>
                        <div class="flex justify-between font-bold text-green-300">
                            <span>ตาราง teachers</span>
                            <span>✓ สมบรูณ์แบบ</span>
                        </div>
                        <div class="flex justify-between font-bold text-green-300">
                            <span>ตาราง downloads</span>
                            <span>✓ สมบรูณ์แบบ</span>
                        </div>
                    </div>
                </div>

                <!-- ฟอร์ม 3: สำหรับสอดแทรกเอกสารดาวน์โหลดใหม่ -->
                <div class="bg-white rounded-3xl p-6 border border-pink-50 shadow-sm space-y-4">
                    <h4 class="font-heading font-black text-sm text-slate-800 flex items-center gap-1.5 border-b border-slate-100 pb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-school-pink" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        เพิ่มเอกสารคลังข่าวจัดซื้อจัดจ้าง
                    </h4>
                    
                    <form action="admin.php" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-semibold text-slate-600">
                        <input type="hidden" name="add_doc" value="done">

                        <div class="space-y-1">
                            <label class="block">ชื่อเรียกจัดซื้อประมูล/สารสิทธิประโยชน์</label>
                            <input type="text" name="doc_title" required placeholder="เช่น ประกาศจัดซื้อส้วมสุขานักเรียนอนุบาล..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <div class="space-y-1">
                            <label class="block">ประเภทหมวดหมู่เอกสาร</label>
                            <select name="doc_category" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-bold bg-white focus:ring-1 focus:ring-school-pink outline-none">
                                <option value="เอกสารทั่วไป">เอกสารทั่วไป</option>
                                <option value="แผนงานและนโยบาย">แผนงานและนโยบาย</option>
                                <option value="ประกันคุณภาพ">ประกันคุณภาพ</option>
                                <option value="เอกสารครู">เอกสารครู</option>
                            </select>
                        </div>

                        <div class="space-y-2 border-t border-pink-100/35 pt-2">
                            <label class="block text-slate-800 font-bold">1. อัพโหลดเอกสารจริงเข้าเซิร์ฟเวอร์</label>
                            <p class="text-[9px] text-slate-400 mt-0.5">เลือกเพื่อจำนำไฟล์ขึ้นเซิร์ฟเวอร์โดยตรง (ระบบจะตรวจจับนามสกุลและขนาดไฟล์โดยอัตโนมัติ)</p>
                            <input type="file" name="doc_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip" class="w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:font-semibold file:bg-pink-50 file:text-school-pink hover:file:bg-pink-100">
                        </div>

                        <div class="border-t border-pink-100/35 pt-2 space-y-2">
                            <label class="block text-slate-500">หรือวางลิงก์ระบุค่าแบบกำหนดเองด้านล่าง:</label>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label class="block">ประเภทไฟล์แยก</label>
                                    <select name="doc_type" class="w-full rounded-xl border border-pink-100 p-1.5 text-xs font-bold bg-white focus:ring-1 focus:ring-school-pink outline-none">
                                        <option value="PDF">PDF</option>
                                        <option value="WORD">WORD</option>
                                        <option value="EXCEL">EXCEL</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="block">ขนาดพื้นที่ (เช่น 1.2 MB)</label>
                                    <input type="text" name="doc_size" value="1.2 MB" class="w-full rounded-xl border border-pink-100 p-1.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block">ลิงค์ที่อยู่ดาวน์โหลด (URL หรือปล่อยเป็น # ไว้)</label>
                                <input type="text" name="doc_url" value="#" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-slate-900 hover:bg-slate-950 text-white font-black py-2.5 rounded-xl transition shadow text-xs">
                            บันทึกเอกสารเข้าสู่ศูนย์ดาวน์โหลด
                        </button>
                    </form>
                </div>

                <!-- ฟอร์ม 4: เพิ่มทำเนียบครูข้าราชการ -->
                <div class="bg-white rounded-3xl p-6 border border-pink-50 shadow-sm space-y-4">
                    <h4 class="font-heading font-black text-sm text-slate-800 flex items-center gap-1.5 border-b border-slate-100 pb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-school-pink" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                        ลงทะเบียนผู้บริหารและครูเพิ่ม
                    </h4>

                    <form action="admin.php" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-semibold text-slate-600">
                        <input type="hidden" name="add_teacher" value="done">

                        <div class="space-y-1">
                            <label class="block">ชื่อ-นามสกุลข้าราชการครู</label>
                            <input type="text" name="teacher_name" required placeholder="เช่น นายประหยัด คิดสัตย์..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <div class="space-y-1">
                            <label class="block">ตำแหน่ง (เช่น ครูวิชาการ / ครูประจำชั้นชั้น...)</label>
                            <input type="text" name="teacher_position" required placeholder="เช่น ครูประจำชั้น ป.6 / ครูผู้ช่วย..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <div class="space-y-1">
                            <label class="block">วิทยฐานะ</label>
                            <select name="teacher_level" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-bold bg-white focus:ring-1 focus:ring-school-pink outline-none">
                                <option value="ผู้อำนวยการโรงเรียน (คศ.3)">ผู้อำนวยการโรงเรียน (คศ.3)</option>
                                <option value="ครูชำนาญการพิเศษ (คศ.3)">ครูชำนาญการพิเศษ (คศ.3)</option>
                                <option value="ครูชำนาญการ (คศ.2)">ครูชำนาญการ (คศ.2)</option>
                                <option value="ครู คศ.1">ครู คศ.1</option>
                                <option value="ครูผู้ช่วย">ครูผู้ช่วย</option>
                                <option value="พนักงานราชการ">พนักงานราชการ</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block">กลุ่มสาระการเรียนรู้</label>
                                <select name="teacher_group" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-bold bg-white focus:ring-1 focus:ring-school-pink outline-none">
                                    <option value="ผู้บริหาร">ผู้บริหาร</option>
                                    <option value="วิชาการคณิตศาสตร์">วิชาการคณิตศาสตร์</option>
                                    <option value="วิทยาศาสตร์">วิทยาศาสตร์</option>
                                    <option value="ภาษาไทย">ภาษาไทย</option>
                                    <option value="ระดับปฐมวัย">ระดับปฐมวัย</option>
                                    <option value="สุขศึกษาและพลศึกษา">สุขศึกษาและพลศึกษา</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="block">ลำดับการจัดเรียงลำดับ</label>
                                <input type="number" name="teacher_order" value="9" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                            </div>
                        </div>

                        <!-- อัพโหลดรูปประจำตัวครู -->
                        <div class="p-3 bg-pink-50/30 rounded-2xl border border-pink-100/50 space-y-1">
                            <label class="block font-bold text-slate-800">1. รูปถ่ายข้าราชการครู</label>
                            <input type="file" name="teacher_image_file" accept="image/*" class="w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-pink-100 file:text-school-pink">
                            <input type="text" name="teacher_image" placeholder="หรือพิมพ์ลิงก์ที่อยู่รูปตรง..." class="w-full rounded-lg border border-pink-100 p-2 text-xs focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <!-- ระบบข้อตกลง PA และเว็บ Portfolio -->
                        <div class="p-3 bg-pink-50/30 rounded-2xl border border-pink-100/50 space-y-2">
                            <label class="block font-bold text-slate-800">2. รายงานข้อตกลง PA (Performance Agreement)</label>
                            <input type="file" name="teacher_pa_file" accept=".pdf,.doc,.docx,.zip" class="w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-pink-100 file:text-school-pink">
                            <input type="text" name="teacher_pa_url" placeholder="หรือสอดลิงก์รายงานแบบแฝงภายนอก (เช่น Google Drive)..." class="w-full rounded-lg border border-pink-100 p-2 text-xs focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <div class="p-3 bg-pink-50/30 rounded-2xl border border-pink-100/50 space-y-2">
                            <label class="block font-bold text-slate-800">3. แฟ้มผลงานทางวิชาการ (Portfolio)</label>
                            <input type="file" name="teacher_portfolio_file" accept=".pdf,.doc,.docx,.zip,image/*" class="w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-pink-100 file:text-school-pink">
                            <input type="text" name="teacher_portfolio_url" placeholder="หรือสอดลิงก์แฟ้มสะสมงานภายนอก (เช่น Canva/Drive)..." class="w-full rounded-lg border border-pink-100 p-2 text-xs focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <button type="submit" class="w-full bg-school-pink hover:bg-school-pink-dark text-white font-black py-2.5 rounded-xl transition shadow text-xs">
                            ลงทะเบียนประวัติข้าราชการครูและบันทึกข้อมูลผลงาน
                        </button>
                    </form>
                </div>

            </div>

        </div>

    </main>

    <footer class="bg-slate-900 text-slate-500 text-center py-6 border-t border-slate-800 text-xs">
        <p>© 2026 <?php echo htmlspecialchars($settings['school_name']); ?> | ระบบควบคุมเทลเวฟแอดมินชมพูขาว</p>
    </footer>

</body>
</html>
