<?php
/**
 * 📊 ส่วนการจัดการข้อมูลและสถิตินักเรียน (Student Stats Panel)
 * แสดงรายการนักเรียนแยกรายชั้นเรียน ปรับปรุงยอดคำนวณสถิติจริง และหน้าสรุปบอร์ดสถานะ
 */
if (!defined('DB_HOST')) {
    exit('No direct script access allowed');
}

// ประมวลผลสำหรับการอัปเดตจำนวนสถิตินักเรียน
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
            $success_alert = 'อัปเดตสถิติจนวนนักเรียนรายชั้นเรียนเรียบร้อยแล้ว!';
        } catch (Exception $e) {
            $err_alert = 'เกิดข้อผิดพลาดในการบันทึกยอดสถิตินักเรียน: ' . $e->getMessage();
        }
    }
}

// ดึงรายชื่อข้อมูลสถิติชั้นเรียนมาคำนวณและป้อนฟอร์มล่าสุด
$loaded_stats = $pdo->query("SELECT * FROM `student_stats` ORDER BY `id` ASC")->fetchAll();

$total_students = 0;
foreach ($loaded_stats as $st) {
    $total_students += intval($st['student_count']);
}
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start w-full">
    
    <!-- ด้านซ้าย: การปรับปรุงรายชั้น (7/12) -->
    <div class="lg:col-span-8 space-y-6">
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-pink-50 shadow-sm space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-lg font-heading font-black text-slate-800 flex items-center gap-2 leading-none">
                    <span class="p-2 bg-pink-100 text-school-pink rounded-xl">📊</span>
                    สถิติจำนวนนักเรียน รายดับชั้นเรียน
                </h3>
                <p class="text-[10px] text-slate-400 font-semibold uppercase mt-2">แก้ไขตัวเลขสถิติของแต่ละระดับชั้น ระบบจะนำไปบวกรวมและแสดงผลบนแผนภูมิหน้าหลัก</p>
            </div>

            <!-- แสดงการแจ้งเตือนภายในโมดูล (ถ้ามีการส่งข้อความแจ้งมาจาก controller) -->
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

            <form action="admin.php?tab=students" method="POST" class="space-y-6">
                <input type="hidden" name="update_students" value="1">
                
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <?php foreach ($loaded_stats as $st): ?>
                        <div class="bg-slate-50 p-3 sm:p-4 rounded-2xl border border-slate-100 hover:border-pink-200 transition space-y-2">
                            <label class="block text-xs font-bold text-slate-700 leading-tight">
                                <?php echo htmlspecialchars($st['grade_name']); ?>
                            </label>
                            <div class="relative flex items-center">
                                <input type="number" name="student_counts[<?php echo $st['id']; ?>]" value="<?php echo intval($st['student_count']); ?>" class="w-full text-center rounded-xl bg-white border border-slate-200 p-2.5 text-xs font-black focus:ring-1 focus:ring-school-pink outline-none text-slate-800">
                                <span class="absolute right-3 text-[10px] text-slate-400 font-bold">คน</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="border-t border-slate-100 pt-4 flex gap-3">
                    <button type="submit" class="w-full bg-slate-900 hover:bg-slate-950 text-white font-black py-3 rounded-2xl transition shadow text-xs">
                        ⚡ อัปเดตสถิติจนวนนักเรียนรวมทุกระดับชั้น
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ด้านขวา: สรุปและวิเคราะห์ภาพรวม (4/12) -->
    <div class="lg:col-span-4 space-y-6">
        <div class="bg-indigo-950 text-white rounded-3xl p-6 border border-indigo-900/40 shadow-md space-y-6">
            <div>
                <h4 class="font-heading font-black text-sm text-pink-300 flex items-center gap-1.5 leading-none">
                    📈 สรุปภาพรวมยอดสถิติสูงสุด
                </h4>
                <p class="text-[10px] text-indigo-300 mt-2">ยอดรายงานจำนวนผู้เข้ารับการศึกษาประมวลแบบเรียลไทม์</p>
            </div>

            <!-- กล่องสรุปจำนวนรวม -->
            <div class="bg-indigo-900/60 rounded-2xl p-5 text-center border border-indigo-800/40 space-y-1">
                <span class="text-[11px] text-indigo-300 font-bold uppercase tracking-wider">จำนวนนักเรียนรวมทั้งหมด</span>
                <div class="text-3.5xl font-heading font-black text-white"><?php echo number_format($total_students); ?> <span class="text-sm font-black text-pink-300">คน</span></div>
                <p class="text-[9px] text-indigo-200 font-light mt-1">อ้างอิงตามค่าสถิติที่ป้อนเข้าสู่อินสแตนซ์ปัจจุบัน</p>
            </div>

            <!-- ผลคำนวณค่าเฉลี่ยและสถิติเสริมแบบทำงานจริง -->
            <div class="space-y-3">
                <span class="block text-[10px] text-indigo-200 font-extrabold uppercase tracking-wide">สัดส่วนคำนวณและวิเคราะห์ตามระดับชั้น:</span>
                
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
