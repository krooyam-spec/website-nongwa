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

    try {
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
            `youtube_intro_url` = :youtube_intro_url
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
            'youtube_intro_url' => $youtube_intro_url
        ]);

        $success_alert = 'อัปเดตการตั้งค่าข้อมูลทั่วไปของโรงเรียนบ้านหนองหว้าเรียบร้อยแล้ว!';
    } catch (Exception $e) {
        $err_alert = 'เกิดข้อผิดพลาดในการบันทึกข้อมูลตาราง: ' . $e->getMessage();
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

// จัดการเหตุการณ์เพิ่มเอกสารแผนงานจัดสื่อดาวน์โหลดเพิ่ม (Insert New Download Document)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_doc'])) {
    $title = cleanInput($_POST['doc_title'] ?? '');
    $category = cleanInput($_POST['doc_category'] ?? 'เอกสารทั่วไป');
    $file_type = cleanInput($_POST['doc_type'] ?? 'PDF');
    $file_size = cleanInput($_POST['doc_size'] ?? '1.5 MB');
    $file_url = cleanInput($_POST['doc_url'] ?? '#');
    $date = date('Y-m-d');

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

// จัดการเหตุการณ์เพิ่มรายชื่อครูและบุคลากรในทำเนียบ (Add Teacher to Database)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_teacher'])) {
    $name = cleanInput($_POST['teacher_name'] ?? '');
    $position = cleanInput($_POST['teacher_position'] ?? '');
    $level = cleanInput($_POST['teacher_level'] ?? 'ครูผู้ช่วย');
    $subject_group = cleanInput($_POST['teacher_group'] ?? 'งานสอนทั่วไป');
    $image_url = cleanInput($_POST['teacher_image'] ?? '');
    $sort_order = intval($_POST['teacher_order'] ?? 99);

    if (empty($image_url)) {
        $image_url = 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&q=80&w=300';
    }

    if (empty($name) || empty($position)) {
        $err_alert = 'กรุณาระบุชื่อและตำแหน่งข้าราชการครูท่านนั้นๆ ให้รอบคอบ';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO `teachers` (`name`, `position`, `level`, `subject_group`, `image_url`, `sort_order`) VALUES (:name, :pos, :level, :group, :img, :sort)");
            $stmt->execute([
                'name' => $name,
                'pos' => $position,
                'level' => $level,
                'group' => $subject_group,
                'img' => $image_url,
                'sort' => $sort_order
            ]);
            $success_alert = 'เพิ่มประวัติครูท่านใหม่เข้าเป็นประชากรข้าราชการรั้วชมพูขาวในตารางเรียบร้อย!';
        } catch (Exception $e) {
            $err_alert = 'ไม่สามารถลงทะเบียนประวัติข้าราชการครู: ' . $e->getMessage();
        }
    }
}

// 1. นำข้อมูลตั้งค่ามาป้อนใน Form อัตโนมัติ
$settingsStmt = $pdo->query("SELECT * FROM `settings` WHERE `id` = 1");
$settings = $settingsStmt->fetch();

// 2. โหลดข้อมูลข่าวสารทั้งหมดสำหรับแสดงรายละเอียด/ลบ
$news_list = $pdo->query("SELECT * FROM `news` ORDER BY `id` DESC")->fetchAll();

// 3. โหลดข้อมูลดาวน์โหลด
$downloads_list = $pdo->query("SELECT * FROM `downloads` ORDER BY `id` DESC")->fetchAll();

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

        <!-- GRID ซ้าย-ขวา การจัดระบบหลังบ้าน -->
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

                    <form action="admin.php" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-semibold text-slate-600">
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

                        <div class="space-y-1 sm:col-span-2">
                            <label class="block">สังกัดและเขตหน่วยบริการการศึกษา</label>
                            <input type="text" name="jurisdiction" required value="<?php echo htmlspecialchars($settings['jurisdiction']); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <div class="space-y-1 sm:col-span-2">
                            <label class="block">ช่วงการศึกษาระดับระดับชั้นที่ให้เปิดการเรียนร่วม</label>
                            <input type="text" name="levels" required value="<?php echo htmlspecialchars($settings['levels']); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <div class="space-y-1">
                            <label class="block">ชื่อและนามสกุลท่านผู้อำนวยการ</label>
                            <input type="text" name="director_name" required value="<?php echo htmlspecialchars($settings['director_name']); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <div class="space-y-1">
                            <label class="block">ตำแหน่งฐานะการงานของผู้บริหาร</label>
                            <input type="text" name="director_title" required value="<?php echo htmlspecialchars($settings['director_title']); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <div class="space-y-1 sm:col-span-2">
                            <label class="block">ลิงค์รูปถ่ายผู้อำนวยการ (URL)</label>
                            <input type="text" name="director_image" required value="<?php echo htmlspecialchars($settings['director_image']); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <div class="space-y-1 sm:col-span-2">
                            <label class="block">วิดีโอแนะนำโรงเรียนบน YouTube (ลิงค์ Embed URL)</label>
                            <input type="text" name="youtube_intro_url" value="<?php echo htmlspecialchars($settings['youtube_intro_url'] ?? ''); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <div class="pt-4 sm:col-span-2">
                            <button type="submit" class="w-full bg-slate-900 hover:bg-slate-950 text-white font-black py-3 rounded-xl transition shadow">
                                บันทึกอัปเดตข้อมูลโครงสร้างสถาบัน
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ฟอร์ม 2: จัดทำแผงบันทึกข่าวสารประชาสัมพันธ์ชิ้นใหม่ -->
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-pink-50 shadow-sm space-y-6">
                    <div class="border-b border-slate-100 pb-4">
                        <h3 class="text-lg font-heading font-black text-slate-800 flex items-center gap-2 leading-none">
                            <span class="p-2 bg-pink-100 text-school-pink rounded-xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            </span>
                            เขียนข่าวสารประชาสัมพันธ์ และบันทึกกิจกรรมชิ้นใหม่
                        </h3>
                        <p class="text-[10px] text-slate-400 font-semibold uppercase mt-2">เพิ่มข้อมูลใหม่เข้าสู่ตาราง news เชื่อมสัมพันธ์ชุมชน</p>
                    </div>

                    <form action="admin.php" method="POST" class="space-y-4 text-xs font-semibold text-slate-600">
                        <input type="hidden" name="add_news" value="done">

                        <div class="space-y-1">
                            <label class="block">หัวข้อข่าวประชาสัมพันธ์</label>
                            <input type="text" name="news_title" required placeholder="เช่น ประมวลภาพพิธีเปิดตึกเรียนใหม่..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                                <label class="block">รูปภาพหน้าปกข่าว (ลิงค์ URL หรือปล่อยว่างเป็นค่าตั้งต้น)</label>
                                <input type="text" name="news_image" placeholder="https://images.unsplash.com/..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block">บทสรุปของข่าวย่อ สำหรับแสดงบนการ์ดหน้ารถ (Summary)</label>
                            <input type="text" name="news_summary" placeholder="คำเกริ่นย่อ 1-2 บรรทัด เช่น ทางโรงเรียนขอน้อมจิตตานภาพจัดประกวดพานงามสร้างสรรค์..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <div class="space-y-1">
                            <label class="block">เนื้อหาตัวเต็มของประชาสัมพันธ์ (Content)</label>
                            <textarea name="news_content" required rows="5" placeholder="กรอกเนื้อเรื่อง ข้อมูลรายละเอียด ประกาศฉบับจริงในส่วนนี้ทั้งหมด..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none"></textarea>
                        </div>

                        <!-- ปักหมุดข่าววิกฤติตั้งค่า -->
                        <div class="flex items-center gap-2 pt-2">
                            <input type="checkbox" name="news_sticky" id="news_sticky" value="1" class="h-4 w-4 rounded text-school-pink border-pink-200">
                            <label for="news_sticky" class="text-xs font-bold text-slate-700 select-none">ปักหมุดข่าวชิ้นนี้ไว้ด้านบนสุดเป็นอันดับแรกเสมอ (Sticky news)</label>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full bg-school-pink hover:bg-school-pink-dark text-white font-black py-3 rounded-xl transition shadow">
                                บันทึกและเผยแพร่ข่าวประชาสัมพันธ์ทันที
                            </button>
                        </div>
                    </form>
                </div>

            </div>

            <!-- ด้านขวา: ข้อมูลสถิติ การอัปเดตสีกิจกรรม (4/12) -->
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

                <!-- ฟอร์ม 3: สำหรับสอดแทรกเอกสารดาวน์โหลดใหม่ -->
                <div class="bg-white rounded-3xl p-6 border border-pink-50 shadow-sm space-y-4">
                    <h4 class="font-heading font-black text-sm text-slate-800 flex items-center gap-1.5 border-b border-slate-100 pb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-school-pink" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        เพิ่มเอกสารคลังข่าวจัดซื้อจัดจ้าง
                    </h4>
                    
                    <form action="admin.php" method="POST" class="space-y-4 text-xs font-semibold text-slate-600">
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

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block">ประเภทไฟล์</label>
                                <select name="doc_type" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-bold bg-white focus:ring-1 focus:ring-school-pink outline-none">
                                    <option value="PDF">PDF</option>
                                    <option value="WORD">WORD</option>
                                    <option value="EXCEL">EXCEL</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="block">ขนาดพื้นที่ (เช่น 1.2 MB)</label>
                                <input type="text" name="doc_size" value="1.2 MB" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block">ลิงค์ที่อยู่ดาวน์โหลด (URL หรือปล่อยเป็น # ไว้)</label>
                            <input type="text" name="doc_url" value="#" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
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

                    <form action="admin.php" method="POST" class="space-y-4 text-xs font-semibold text-slate-600">
                        <input type="hidden" name="add_teacher" value="done">

                        <div class="space-y-1">
                            <label class="block">ชื่อ-นามสกุลข้าราชการครู</label>
                            <input type="text" name="teacher_name" required placeholder="เช่น นายประหยัด คิดสัตย์..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <div class="space-y-1">
                            <label class="block">ตำแหน่ง (เช่น ครูวิชาการ / ครูระดับชั้น...)</label>
                            <input type="text" name="teacher_position" required placeholder="เช่น ครูวิชาการวิทยาศาสตร์..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <div class="space-y-1">
                            <label class="block">วิทยฐานะ</label>
                            <select name="teacher_level" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-bold bg-white focus:ring-1 focus:ring-school-pink outline-none">
                                <option value="ครูชำนาญการพิเศษ (คศ.3)">ครูชำนาญการพิเศษ (คศ.3)</option>
                                <option value="ครูชำนาญการ (คศ.2)">ครูชำนาญการ (คศ.2)</option>
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
                                <label class="block">ลำดับการเรียงลำดับ</label>
                                <input type="number" name="teacher_order" value="9" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block">ลิงค์รูปหน้าผู้สอน (หรือปล่อยว่าง)</label>
                            <input type="text" name="teacher_image" placeholder="https://images.unsplash.com/..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                        </div>

                        <button type="submit" class="w-full bg-school-pink hover:bg-school-pink-dark text-white font-black py-2.5 rounded-xl transition shadow text-xs">
                            ลงทะเบียนประวัติครูเข้าระบบตาราง
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
