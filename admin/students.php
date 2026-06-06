<?php
/**
 * 📊 ส่วนการจัดการข้อมูลและสถิตินักเรียนแยกปีการศึกษา (Yearly Student Stats Admin Panel)
 * พัฒนาเพื่อโรงเรียนบ้านหนองหว้า ในธีมชมพู-ขาว ช่วยในการสลับปีการศึกษา, แก้ไขจำนวนนักเรียน,
 * ตั้งเป็นปีปัจจุบัน, เพิ่ม/ลบปีการศึกษา และสรุปวิเคราะห์ข้อมูล
 */

if (!defined('DB_HOST')) {
    exit('No direct script access allowed');
}

// โหลดข้อมูลการตั้งค่าโรงเรียน เพื่อดูว่าปีการศึกษาใดเป็นปัจจุบัน
$settingsStmt = $pdo->query("SELECT current_academic_year FROM `settings` WHERE `id` = 1");
$current_settings = $settingsStmt->fetch();
$active_current_year = $current_settings['current_academic_year'] ?? '2569';

$success_yearly_alert = '';
$err_yearly_alert = '';

// =======================================
// 1. จัดการเหตุการณ์การร้องขอเซิร์ฟเวอร์ (POST Requests)
// =======================================

// ก. การตั้งปีการศึกษาปัจจุบัน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_current_year_btn'])) {
    $target_year = cleanInput($_POST['current_academic_year'] ?? '');
    if (!empty($target_year)) {
        try {
            $upd_stmt = $pdo->prepare("UPDATE `settings` SET `current_academic_year` = :year WHERE `id` = 1");
            $upd_stmt->execute(['year' => $target_year]);
            $active_current_year = $target_year;
            $success_yearly_alert = '⚡ ตั้งค่าปีการศึกษา ' . htmlspecialchars($target_year) . ' เป็นปีการศึกษาปัจจุบันเรียบร้อยแล้ว!';
        } catch (Exception $e) {
            $err_yearly_alert = 'ไม่สามารถกำหนดปีปัจจุบันได้: ' . $e->getMessage();
        }
    }
}

// ข. เพิ่มปีการศึกษาใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_new_year_btn'])) {
    $new_year = cleanInput($_POST['new_academic_year'] ?? '');
    if (empty($new_year) || !is_numeric($new_year)) {
        $err_yearly_alert = '❌ กรุณาระบุปีการศึกษาให้ถูกต้อง (เช่น 2570)';
    } else {
        try {
            // ตรวจสอบว่ามีปีนี้อยู่แล้วหรือไม่
            $chk = $pdo->prepare("SELECT id FROM `student_yearly_stats` WHERE `academic_year` = :year LIMIT 1");
            $chk->execute(['year' => $new_year]);
            if ($chk->fetch()) {
                $err_yearly_alert = '❌ ปีการศึกษา ' . $new_year . ' มีอยู่ในระบบแล้ว';
            } else {
                // เพิ่มรายการระดับชั้นดั้งเดิม 8 ระดับชั้น ตั้งค่าเริ่มต้นด้วย 0 คน
                $standard_grades = [
                    'อนุบาล 2', 'อนุบาล 3', 
                    'ประถมศึกษาปีที่ 1', 'ประถมศึกษาปีที่ 2', 'ประถมศึกษาปีที่ 3', 
                    'ประถมศึกษาปีที่ 4', 'ประถมศึกษาปีที่ 5', 'ประถมศึกษาปีที่ 6'
                ];
                $ins_stmt = $pdo->prepare("INSERT INTO `student_yearly_stats` (`academic_year`, `grade_name`, `student_count`) VALUES (:year, :grade, 0)");
                foreach ($standard_grades as $grade) {
                    $ins_stmt->execute([
                        'year' => $new_year,
                        'grade' => $grade
                    ]);
                }
                $success_yearly_alert = '🎉 เพิ่มปีการศึกษาใหม่ ' . $new_year . ' และจัดสร้างโครงสร้างชั้นเรียนเริ่มต้นสำเร็จ!';
                $_GET['year'] = $new_year; // สลับหน้าไปปีที่เพิ่งเพิ่มทันที
            }
        } catch (Exception $e) {
            $err_yearly_alert = 'เกิดข้อผิดพลาดในการเพิ่มปีการศึกษาใหม่: ' . $e->getMessage();
        }
    }
}

// ค. อัปเดตจำนวนนักเรียนในแต่ละปีการศึกษา
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_yearly_counts_btn'])) {
    $work_year = cleanInput($_POST['working_year'] ?? '');
    if (isset($_POST['grade_counts']) && is_array($_POST['grade_counts']) && !empty($work_year)) {
        try {
            foreach ($_POST['grade_counts'] as $grade_id => $count) {
                // อัปเดตรายคลาสสิกของปีนั้นๆ
                $upd_stmt = $pdo->prepare("UPDATE `student_yearly_stats` SET `student_count` = :count WHERE `id` = :id AND `academic_year` = :year");
                $upd_stmt->execute([
                    'count' => intval($count),
                    'id' => intval($grade_id),
                    'year' => $work_year
                ]);
            }

            // [Auto-Sync] หากปีการศึกษาที่แก้ไขนี้ ตรงกับ "ปีการศึกษาปัจจุบัน" ของจริง
            // ให้ทำการซิงก์ข้อมูลไปที่ตารางดั้งเดิม `student_stats` เพื่อให้ระบบหน้าแรกหรือฟังก์ชันเก่าที่อ้างอิงอยู่ทำงานไม่ผิดเพี้ยน
            if ($work_year === $active_current_year) {
                $get_sync = $pdo->prepare("SELECT * FROM `student_yearly_stats` WHERE `academic_year` = :year");
                $get_sync->execute(['year' => $work_year]);
                $yearly_rows = $get_sync->fetchAll();
                
                foreach ($yearly_rows as $y_row) {
                    // อัปเดตลงตารางเก่า student_stats ที่มีคอลัมน์ grade_name เดียวกัน
                    $sync_stmt = $pdo->prepare("UPDATE `student_stats` SET `student_count` = :count WHERE `grade_name` = :grade");
                    $sync_stmt->execute([
                        'count' => intval($y_row['student_count']),
                        'grade' => $y_row['grade_name']
                    ]);
                }
            }

            $success_yearly_alert = '💾 บันทึกยอดจำนวนนักเรียนปีการศึกษา ' . htmlspecialchars($work_year) . ' เรียบร้อยพรักพร้อม!';
        } catch (Exception $e) {
            $err_yearly_alert = 'เกิดข้อผิดพลาดในการบันทึกยอด: ' . $e->getMessage();
        }
    }
}

// ง. ลบปีการศึกษา
if (isset($_GET['delete_year']) && !empty($_GET['delete_year'])) {
    $del_year = cleanInput($_GET['delete_year']);
    if ($del_year === $active_current_year) {
        $err_yearly_alert = '❌ ไม่สามารถลบปีการศึกษาที่เป็นปีการศึกษาปัจจุบัน (' . htmlspecialchars($del_year) . ') ได้!';
    } else {
        try {
            $del_stmt = $pdo->prepare("DELETE FROM `student_yearly_stats` WHERE `academic_year` = :year");
            $del_stmt->execute(['year' => $del_year]);
            $success_yearly_alert = '🗑️ ลบข้อมูลสารสนเทศทั้งหมดของปีการศึกษา ' . htmlspecialchars($del_year) . ' เรียบร้อยแล้ว!';
        } catch (Exception $e) {
            $err_yearly_alert = 'เกิดข้อผิดพลาดในการลบข้อมูลปีการศึกษา: ' . $e->getMessage();
        }
    }
}


// =======================================
// 2. ดึงข้อมูลประมวลผลสำหรับแสดงผล UI
// =======================================

// ดึงปีการศึกษาที่มีอยู่ทั้งหมดในระบบเพื่อนำมาลิสต์เป็นสิทธิ์การทำงาน
$years_stmt = $pdo->query("SELECT DISTINCT `academic_year` FROM `student_yearly_stats` ORDER BY `academic_year` DESC");
$years_list = $years_stmt->fetchAll();

// สรุปปีที่แอดมินหรือแผงควบคุมตึงขึ้นมาแสดงข้อมูลอยู่ในขณะนี้
$selected_year = isset($_GET['year']) ? cleanInput($_GET['year']) : $active_current_year;

// ดึงรายการระดับชั้นเรียนในพิกัดปีที่ถูกเลือก
$grade_stats_stmt = $pdo->prepare("SELECT * FROM `student_yearly_stats` WHERE `academic_year` = :year ORDER BY `id` ASC");
$grade_stats_stmt->execute(['year' => $selected_year]);
$loaded_stats = $grade_stats_stmt->fetchAll();

// หากไม่มีข้อมูลสำหรับปีที่เลือก ให้ทำการแก้ไขหรือเปลี่ยนเส้นทางไปหาปีอื่น
if (empty($loaded_stats) && !empty($years_list)) {
    $selected_year = $years_list[0]['academic_year'];
    $grade_stats_stmt->execute(['year' => $selected_year]);
    $loaded_stats = $grade_stats_stmt->fetchAll();
}

// คำนวณจำนวนข้อมูลรวมของปีที่เลือก
$total_students = 0;
foreach ($loaded_stats as $st) {
    $total_students += intval($st['student_count']);
}

// คำนวณยอดรวมสรุปแยกตามแต่ละปีการศึกษาเพื่อผู้ใช้ทำกราฟวิเคราะห์เทียบชั้นได้สวย
$yearly_summaries = [];
try {
    $sum_stmt = $pdo->query("SELECT `academic_year`, SUM(`student_count`) as total_count FROM `student_yearly_stats` GROUP BY `academic_year` ORDER BY `academic_year` ASC");
    $yearly_summaries = $sum_stmt->fetchAll();
} catch (Exception $e) {}
?>

<!-- แจ้งเตือนของหน้าโมดูลสี่นักเรียน -->
<?php if (!empty($success_yearly_alert)): ?>
    <div class="bg-green-50 rounded-2xl p-4 text-green-700 text-xs font-bold border border-green-100 flex items-center gap-2 mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <?php echo $success_yearly_alert; ?>
    </div>
<?php endif; ?>

<?php if (!empty($err_yearly_alert)): ?>
    <div class="bg-red-50 rounded-2xl p-4 text-red-600 text-xs font-bold border border-red-100 flex items-center gap-2 mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
        <?php echo $err_yearly_alert; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start w-full">
    
    <!-- แผงซ้าย: การแก้ไข รายชั้นเรียนปีที่เลือก (8/12) -->
    <div class="lg:col-span-8 space-y-6">
        
        <!-- แท็บสลับและกรองปีการศึกษา -->
        <div class="bg-white rounded-3xl p-6 border border-pink-50 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-slate-100 pb-3 gap-3">
                <div>
                    <h3 class="text-base font-heading font-black text-slate-800">
                        เลือกปีการศึกษาที่ต้องการดำเนินการ
                    </h3>
                    <p class="text-[10px] text-slate-400 font-medium">สลับเปลี่ยนเพื่อแก้ไขประวัติข้อมูลและอัตราสถิติประจำตัวนักเรียน</p>
                </div>
                
                <!-- ปุ่มสร้างประวัติปีใหม่ -->
                <button onclick="document.getElementById('addYearModal').classList.remove('hidden')" class="bg-pink-100/80 hover:bg-pink-100 border border-pink-200 text-school-pink text-xs px-3 py-1.5 rounded-xl font-bold transition flex items-center gap-1">
                    ➕ เพิ่มปีการศึกษาใหม่
                </button>
            </div>
            
            <!-- ปีทั้งหมดที่มีอยู่ในระบบ -->
            <div class="flex flex-wrap gap-2 pt-1">
                <?php foreach ($years_list as $yr): ?>
                    <a href="admin.php?tab=students&year=<?php echo urlencode($yr['academic_year']); ?>" 
                       class="px-4.5 py-2.5 rounded-2xl text-xs font-black transition-all flex items-center gap-2 <?php echo $selected_year === $yr['academic_year'] ? 'bg-school-pink text-white shadow-md shadow-pink-500/20' : 'bg-slate-50 hover:bg-slate-100 text-slate-650 border border-slate-200/60'; ?>">
                        ปีการศึกษา <?php echo htmlspecialchars($yr['academic_year']); ?>
                        <?php if ($yr['academic_year'] === $active_current_year): ?>
                            <span class="px-1.5 py-0.5 bg-white text-school-pink font-extrabold rounded-md text-[8px] tracking-wider uppercase">CURRENT</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ฟอร์มแก้ไขยอดนักเรียนของปีที่เจาะจง -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-pink-50 shadow-sm space-y-6">
            <div class="border-b border-slate-100 pb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-heading font-black text-slate-800 flex items-center gap-2 leading-none">
                        <span class="p-2 bg-pink-50 text-school-pink rounded-xl">📊</span>
                        สถิตินักเรียน ปีการศึกษา <?php echo htmlspecialchars($selected_year); ?>
                    </h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-2">แก้ไขข้อมูลจำนวนประชากรเยาวชนของปีการศึกษานี้ ระบบจะนำไปประยุกต์ทำตารางและจัดกราฟเปรียบเทียบในหน้าหลัก</p>
                </div>

                <?php if ($selected_year !== $active_current_year): ?>
                    <a href="admin.php?tab=students&delete_year=<?php echo urlencode($selected_year); ?>" 
                       onclick="return confirm('⚠️ คุณสมบัติความมั่นคงข้อมูล: แน่ใจหรือไม่ว่าต้องการลบข้อมูลสถิตินักเรียนทั้งหมดของปีการศึกษา <?php echo $selected_year; ?>?')"
                       class="text-xs font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-100 py-1.5 px-3 rounded-xl transition">
                        🗑️ ลบปีนี้ออกจากระบบ
                    </a>
                <?php endif; ?>
            </div>

            <form action="admin.php?tab=students&year=<?php echo urlencode($selected_year); ?>" method="POST" class="space-y-6">
                <input type="hidden" name="save_yearly_counts_btn" value="1">
                <input type="hidden" name="working_year" value="<?php echo htmlspecialchars($selected_year); ?>">
                
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <?php foreach ($loaded_stats as $st): ?>
                        <div class="bg-slate-50/60 p-3.5 sm:p-4 rounded-2xl border border-slate-100 hover:border-pink-200 transition space-y-2">
                            <label class="block text-xs font-extrabold text-slate-700 leading-tight">
                                <?php echo htmlspecialchars($st['grade_name']); ?>
                            </label>
                            <div class="relative flex items-center">
                                <input type="number" name="grade_counts[<?php echo $st['id']; ?>]" value="<?php echo intval($st['student_count']); ?>" class="w-full text-center rounded-xl bg-white border border-slate-200 p-2.5 text-xs font-black focus:ring-1 focus:ring-school-pink outline-none text-slate-800">
                                <span class="absolute right-3 text-[10px] text-slate-400 font-bold">คน</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="border-t border-slate-100 pt-4">
                    <button type="submit" class="w-full bg-slate-900 hover:bg-slate-950 text-white font-black py-3 rounded-2xl transition shadow-lg text-xs tracking-wider">
                        ⚡ บันทึกข้อมูลและปรับปรุงสถิตินักเรียนปีการศึกษา <?php echo htmlspecialchars($selected_year); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- แผนภูมิ/กราฟเปรียบเทียบในมุมมองเฉพาะแอดมิน (วิเคราะห์ความเคลื่อนไหว) -->
        <div class="bg-indigo-950 text-white rounded-3xl p-6 sm:p-8 border border-slate-850/30 shadow-md space-y-6">
            <div>
                <h4 class="font-heading font-black text-sm text-pink-300 flex items-center gap-1.5 leading-none">
                    📈 กราฟเปรียบเทียบประชากรนักเรียนรายปีการศึกษา
                </h4>
                <p class="text-[10px] text-indigo-200 mt-2">อัตราเปรียบเทียบแสดงพัฒนาการจดจัดสัดส่วนนักเรียนรวมแต่ละปีการศึกษา</p>
            </div>

            <!-- การพล็อตกราฟเปรียบเทียบ -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-2 items-end min-h-[160px]">
                <?php 
                $max_val = 1;
                foreach ($yearly_summaries as $summary) {
                    if (intval($summary['total_count']) > $max_val) {
                        $max_val = intval($summary['total_count']);
                    }
                }
                foreach ($yearly_summaries as $summary): 
                    $ht_pct = round((intval($summary['total_count']) / $max_val) * 100);
                ?>
                    <div class="flex flex-col items-center gap-2">
                        <div class="text-[11px] font-black text-pink-300"><?php echo number_format($summary['total_count']); ?> คน</div>
                        <div class="w-12 bg-indigo-900/80 rounded-t-xl overflow-hidden relative border border-indigo-800/10 min-h-[20px]" style="height: <?php echo max(20, $ht_pct * 0.9); ?>px">
                            <div class="absolute bottom-0 left-0 w-full bg-gradient-to-t from-school-pink to-pink-400 rounded-t-xl" style="height: 100%"></div>
                        </div>
                        <div class="text-[10px] font-black text-indigo-300">ปีการศึกษา <?php echo htmlspecialchars($summary['academic_year']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- แผงขวา: การตั้งปีปัจจุบันและการวิเคราะห์รายสัดส่วน (4/12) -->
    <div class="lg:col-span-4 space-y-6">
        
        <!-- การกำหนดปีการศึกษาปัจจุบัน -->
        <div class="bg-white rounded-3xl p-6 border border-pink-50 shadow-sm space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <h4 class="font-heading font-black text-sm text-slate-800 flex items-center gap-2">
                    <span>👑</span> กำหนดปีการศึกษาปัจจุบัน
                </h4>
                <p class="text-[10px] text-slate-400 mt-1">ใช้ตัวเลือกนี้ในการบอกระบบว่า ปีนี้คือนักเรียนภาคปัจจุบันที่จะไปแสดงบนหน้าแรกทันที</p>
            </div>

            <form action="admin.php?tab=students&year=<?php echo urlencode($selected_year); ?>" method="POST" class="space-y-4">
                <input type="hidden" name="set_current_year_btn" value="1">
                
                <div class="space-y-1.5">
                    <label class="block text-[10px] text-slate-400 font-extrabold uppercase tracking-wider">เลือกปีการศึกษาปัจจุบัน:</label>
                    <select name="current_academic_year" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-black outline-none focus:border-school-pink">
                        <?php foreach ($years_list as $yr): ?>
                            <option value="<?php echo htmlspecialchars($yr['academic_year']); ?>" <?php echo $yr['academic_year'] === $active_current_year ? 'selected' : ''; ?>>
                                ปีการศึกษา <?php echo htmlspecialchars($yr['academic_year']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="w-full bg-slate-900 hover:bg-slate-950 text-white font-black py-2.5 rounded-xl transition text-xs">
                    🌟 บันทึกปีการศึกษาปัจจุบันเป็นปีนี้
                </button>
            </form>
        </div>

        <!-- วิเคราะห์ภาพรวมสัดส่วนของปีที่ดึงขึ้นมาอยู่ -->
        <div class="bg-indigo-950 text-white rounded-3xl p-6 border border-indigo-900/40 shadow-md space-y-6">
            <div>
                <h4 class="font-heading font-black text-sm text-pink-300 flex items-center gap-1.5 leading-none">
                    📈 สัดส่วนนักเรียนรวมปี <?php echo htmlspecialchars($selected_year); ?>
                </h4>
                <p class="text-[10px] text-indigo-300 mt-2">คำนวณผลประเมินรายคนแยกชั้นอย่างละเอียด</p>
            </div>

            <!-- กล่องสรุปจำนวนรวม -->
            <div class="bg-indigo-900/60 rounded-2xl p-5 text-center border border-indigo-800/40 space-y-1">
                <span class="text-[11px] text-indigo-300 font-bold uppercase tracking-wider">จำนวนนักเรียนรวม</span>
                <div class="text-3.5xl font-heading font-black text-white"><?php echo number_format($total_students); ?> <span class="text-sm font-black text-pink-300">คน</span></div>
                <p class="text-[9px] text-indigo-200 font-light mt-1">อ้างอิงของปีการศึกษา <?php echo htmlspecialchars($selected_year); ?></p>
            </div>

            <!-- ผลคำนวณค่าเฉลี่ยและสถิติเสริมแบบทำงานจริง -->
            <div class="space-y-3">
                <span class="block text-[10px] text-indigo-200 font-extrabold uppercase tracking-wide">สัดส่วนรายละเอียดประชากร:</span>
                
                <div class="space-y-3.5 text-xs font-bold text-slate-200">
                    <?php 
                    foreach ($loaded_stats as $st): 
                        $pct = $total_students > 0 ? round((intval($st['student_count']) / $total_students) * 100, 1) : 0;
                    ?>
                        <div class="space-y-1">
                            <div class="flex justify-between text-[11px]">
                                <span class="text-indigo-100"><?php echo htmlspecialchars($st['grade_name']); ?></span>
                                <span class="text-pink-300"><?php echo intval($st['student_count']); ?> คน (<?php echo $pct; ?>%)</span>
                            </div>
                            <div class="w-full bg-indigo-900/80 rounded-full h-2 overflow-hidden border border-indigo-900">
                                <div class="bg-gradient-to-r from-school-pink to-pink-400 h-full rounded-full transition-all duration-500" style="width: <?php echo $pct; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- MODAL: เพิ่มปีการศึกษาใหม่ -->
<div id="addYearModal" class="fixed inset-0 z-55 flex items-center justify-center hidden bg-black/60 backdrop-blur-sm p-4 animate-fade-in">
    <div class="bg-white rounded-3xl max-w-sm w-full overflow-hidden border border-pink-50 shadow-2xl relative">
        <div class="bg-gradient-to-r from-pink-500 to-rose-400 px-6 py-5 text-white">
            <h4 class="font-heading font-black text-base flex items-center gap-1.5">
                🏫 แทรกปีการศึกษาใหม่
            </h4>
            <p class="text-[10px] text-white/85 mt-1">ระบบจะเซ็ตอัประดับชั้นจำลอง อนุบาล-ประถม ให้โดยอัตโนมัติ</p>
        </div>
        
        <form action="admin.php?tab=students" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="add_new_year_btn" value="1">
            
            <div class="space-y-1.5">
                <label class="block text-xs font-extrabold text-slate-700">เขียนระบุพิกัดปีการศึกษาใหม่:</label>
                <input type="number" name="new_academic_year" required placeholder="ตัวอย่าง: 2570" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-black outline-none focus:ring-1 focus:ring-school-pink text-slate-800">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('addYearModal').classList.add('hidden')" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 py-3 rounded-xl transition font-black text-xs">
                    ยกเลิก
                </button>
                <button type="submit" class="flex-1 bg-school-pink hover:bg-school-pink-dark text-white py-3 rounded-xl transition font-black text-xs shadow-md shadow-pink-500/10">
                    🚀 ตกลงเพิ่มปี
                </button>
            </div>
        </form>
    </div>
</div>
