<?php

$courseCode = isset($_GET['course']) ? $_GET['course'] : '';
$unitCode   = isset($_GET['unit'])   ? $_GET['unit']   : '';

// Course name
$coursename = '';
if (!empty($courseCode)) {
    $r = fetch("SELECT name FROM course WHERE courseCode = '$courseCode'");
    if ($r) $coursename = $r[0]['name'];
}

// Unit name
$unitname = '';
if (!empty($unitCode)) {
    $r = fetch("SELECT name FROM unit WHERE unitCode = '$unitCode'");
    if ($r) $unitname = $r[0]['name'];
}

// Distinct dates for columns
$distinctDates = [];
if (!empty($courseCode) && !empty($unitCode)) {
    $stmt = $pdo->prepare("SELECT DISTINCT dateMarked FROM attendance WHERE course = :c AND unit = :u ORDER BY dateMarked ASC");
    $stmt->execute([':c' => $courseCode, ':u' => $unitCode]);
    $distinctDates = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Students joined from students for full info
$students = [];
if (!empty($courseCode) && !empty($unitCode)) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT a.studentRegistrationNumber,
               s.firstName, s.lastName, s.yearLevel, s.section, s.courseCode
        FROM attendance a
        LEFT JOIN students s ON a.studentRegistrationNumber = s.registrationNumber
        WHERE a.course = :c AND a.unit = :u
        ORDER BY s.lastName, s.firstName
    ");
    $stmt->execute([':c' => $courseCode, ':u' => $unitCode]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="resources/images/logo/attnlg.png" rel="icon">
    <title>Download Attendance — AFRAs</title>
    <link rel="stylesheet" href="resources/assets/css/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css" rel="stylesheet">
    <style>
        .info-bar {
            display: flex; flex-wrap: wrap; gap: 12px;
            background: var(--bg-white); border: 1px solid var(--border);
            border-radius: 12px; padding: 14px 20px; margin-bottom: 18px; font-size: 13.5px;
        }
        .info-bar span { color: var(--text-muted); }
        .info-bar strong { color: var(--text); margin-left: 4px; }
        .info-divider { color: var(--border); }
        .export-btn {
            display: inline-flex; align-items: center; gap: 8px;
            background: linear-gradient(135deg, #10B981, #059669);
            color: #fff; border: none; border-radius: 10px;
            padding: 10px 20px; font-size: 14px; font-weight: 600;
            cursor: pointer; margin-bottom: 18px; transition: opacity 0.2s, transform 0.15s;
        }
        .export-btn:hover { opacity: 0.88; transform: translateY(-1px); }
        .export-btn:disabled { opacity: 0.45; cursor: not-allowed; transform: none; }
        .attendance-badge {
            display: inline-block; padding: 3px 10px;
            border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .badge-present { background: rgba(16,185,129,0.12); color: #059669; }
        .badge-absent  { background: rgba(239,68,68,0.1);   color: #DC2626; }
        .badge-late    { background: rgba(245,158,11,0.12); color: #D97706; }
        #attendanceTable th, #attendanceTable td { white-space: nowrap; }
        .no-data { text-align:center; padding:40px; color:var(--text-muted); font-size:14px; }
        .no-data i { font-size:36px; display:block; margin-bottom:10px; }
    </style>
</head>
<body>
    <?php include 'includes/topbar.php'; ?>
    <section class="main">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main--content">

            <form class="lecture-options" id="selectForm">
                <select required name="course" id="courseSelect" onchange="updateTable()">
                    <option value="">Select Course</option>
                    <?php foreach (getCourseNames() as $course): ?>
                    <option value="<?= $course['courseCode'] ?>" <?= $courseCode===$course['courseCode']?'selected':'' ?>>
                        <?= htmlspecialchars($course['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select required name="unit" id="unitSelect" onchange="updateTable()">
                    <option value="">Select Subject</option>
                    <?php foreach (getUnitNames() as $unit): ?>
                    <option value="<?= $unit['unitCode'] ?>" <?= $unitCode===$unit['unitCode']?'selected':'' ?>>
                        <?= htmlspecialchars($unit['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <?php if (!empty($courseCode) && !empty($unitCode)): ?>
            <div class="info-bar">
                <span>Course: <strong><?= htmlspecialchars($coursename ?: $courseCode) ?></strong></span>
                <span class="info-divider">|</span>
                <span>Subject: <strong><?= htmlspecialchars($unitname ?: $unitCode) ?></strong></span>
                <span class="info-divider">|</span>
                <span>Total Students: <strong><?= count($students) ?></strong></span>
                <span class="info-divider">|</span>
                <span>Sessions Recorded: <strong><?= count($distinctDates) ?></strong></span>
            </div>
            <button class="export-btn" id="exportBtn" onclick="exportToExcel()" <?= empty($students)?'disabled':'' ?>>
                <i class="ri-file-excel-2-line"></i> Export Attendance as Excel
            </button>
            <?php endif; ?>

            <div class="table-container">
                <div class="title"><h2 class="section--title">Attendance Preview</h2></div>
                <div class="table" style="overflow-x:auto;">

                <?php if (empty($courseCode) || empty($unitCode)): ?>
                    <div class="no-data"><i class="ri-file-search-line"></i>Select a course and subject above to preview attendance.</div>
                <?php elseif (empty($students)): ?>
                    <div class="no-data"><i class="ri-emotion-sad-line"></i>No attendance records found.</div>
                <?php else: ?>
                    <table id="attendanceTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ID Number</th>
                                <th>Full Name</th>
                                <th>Course</th>
                                <th>Year</th>
                                <th>Section</th>
                                <th>Subject</th>
                                <?php foreach ($distinctDates as $date): ?>
                                <th><?= date('M d, Y', strtotime($date)) ?></th>
                                <?php endforeach; ?>
                                <th>Present</th>
                                <th>Absent</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($students as $i => $student):
                            $presentCount = 0; $absentCount = 0;
                            $regNo = $student['studentRegistrationNumber'];
                            $fullName = trim(($student['firstName']??'').' '.($student['lastName']??''));
                            if (!$fullName) $fullName = $regNo;
                        ?>
                            <tr>
                                <td><?= $i+1 ?></td>
                                <td><?= htmlspecialchars($regNo) ?></td>
                                <td><?= htmlspecialchars($fullName) ?></td>
                                <td><?= htmlspecialchars($student['courseCode'] ?? $courseCode) ?></td>
                                <td><?= htmlspecialchars($student['yearLevel'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($student['section'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($unitname ?: $unitCode) ?></td>
                                <?php foreach ($distinctDates as $date):
                                    $stmt2 = $pdo->prepare("SELECT attendanceStatus FROM attendance WHERE studentRegistrationNumber=:reg AND dateMarked=:d AND course=:c AND unit=:u LIMIT 1");
                                    $stmt2->execute([':reg'=>$regNo,':d'=>$date,':c'=>$courseCode,':u'=>$unitCode]);
                                    $att = $stmt2->fetch(PDO::FETCH_ASSOC);
                                    $status = $att ? $att['attendanceStatus'] : 'Absent';
                                    if (strtolower($status)==='present') $presentCount++;
                                    else $absentCount++;
                                    $badge = strtolower($status)==='present'?'badge-present':(strtolower($status)==='late'?'badge-late':'badge-absent');
                                ?>
                                <td><span class="attendance-badge <?= $badge ?>"><?= $status ?></span></td>
                                <?php endforeach; ?>
                                <td><strong style="color:#059669"><?= $presentCount ?></strong></td>
                                <td><strong style="color:#DC2626"><?= $absentCount ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                </div>
            </div>

        </div>
    </section>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <?php js_asset(["active_link"]) ?>
    <script>
    function updateTable() {
        var course = document.getElementById('courseSelect').value;
        var unit   = document.getElementById('unitSelect').value;
        if (course && unit) {
            window.location.href = 'download-record?course=' + encodeURIComponent(course) + '&unit=' + encodeURIComponent(unit);
        }
    }

    function exportToExcel() {
        const table = document.getElementById('attendanceTable');
        if (!table) { alert('No data to export.'); return; }

        const courseName = <?= json_encode($coursename ?: $courseCode) ?>;
        const unitName   = <?= json_encode($unitname   ?: $unitCode) ?>;
        const today      = new Date().toLocaleDateString();

        // Clone table so we don't modify the visible one
        const clone = table.cloneNode(true);

        // Strip badge HTML — keep only text in cloned cells
        clone.querySelectorAll('.attendance-badge').forEach(el => {
            const txt = document.createTextNode(el.textContent);
            el.replaceWith(txt);
        });

        const wb = XLSX.utils.book_new();
        const ws_data = [];

        // Header rows
        ws_data.push(['ATTENDANCE RECORD']);
        ws_data.push(['Course: ' + courseName + '   |   Subject: ' + unitName]);
        ws_data.push(['Exported on: ' + today]);
        ws_data.push([]); // blank row

        // Table rows
        const rows = clone.querySelectorAll('tr');
        rows.forEach(tr => {
            const row = [];
            tr.querySelectorAll('th, td').forEach(cell => row.push(cell.innerText || cell.textContent));
            ws_data.push(row);
        });

        const ws = XLSX.utils.aoa_to_sheet(ws_data);

        // Column widths
        ws['!cols'] = [{wch:4},{wch:15},{wch:25},{wch:10},{wch:6},{wch:9},{wch:22}];

        XLSX.utils.book_append_sheet(wb, ws, 'Attendance');

        const filename = (unitName || 'attendance').replace(/\s+/g,'_') + '_' + new Date().toISOString().slice(0,10) + '.xlsx';
        XLSX.writeFile(wb, filename);
    }
    </script>
</body>
</html>