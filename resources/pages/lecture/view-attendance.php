<?php
// Get all courses with their units
$allCourses = fetch("SELECT c.Id, c.name, c.courseCode FROM course c ORDER BY c.name");

// Build units map per course: courseId => [units]
$unitsMap = [];
$allUnits = fetch("SELECT u.Id, u.name, u.unitCode, u.courseID FROM unit u ORDER BY u.name");
foreach ($allUnits as $u) {
    $unitsMap[$u['courseID']][] = $u;
}

// Selected filters from GET
$courseCode  = isset($_GET['course']) ? $_GET['course'] : '';
$unitCode    = isset($_GET['unit'])   ? $_GET['unit']   : '';
$yearFilter  = isset($_GET['year'])   ? $_GET['year']   : '';
$dateFilter  = isset($_GET['date'])   ? $_GET['date']   : '';

// Resolve names
$coursename = ''; $unitname = ''; $courseId = '';
if (!empty($courseCode)) {
    $r = fetch("SELECT Id, name FROM course WHERE courseCode = '$courseCode'");
    if ($r) { $coursename = $r[0]['name']; $courseId = $r[0]['Id']; }
}
if (!empty($unitCode)) {
    $r = fetch("SELECT name FROM unit WHERE unitCode = '$unitCode'");
    if ($r) $unitname = $r[0]['name'];
}

// Get available year levels for filter (from enrolled students in this course)
$yearLevels = [];
if (!empty($courseCode)) {
    $stmt = $pdo->prepare("SELECT DISTINCT yearLevel FROM students WHERE courseCode = :c AND yearLevel != '' ORDER BY yearLevel");
    $stmt->execute([':c' => $courseCode]);
    $yearLevels = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Fetch attendance records
$distinctDates = [];
$students = [];
$sessionTimes = []; // times recorded on the selected date
if (!empty($courseCode) && !empty($unitCode)) {
    // All distinct dates for dropdown
    $stmt = $pdo->prepare("SELECT DISTINCT dateMarked FROM attendance WHERE course=:c AND unit=:u ORDER BY dateMarked DESC");
    $stmt->execute([':c'=>$courseCode,':u'=>$unitCode]);
    $distinctDates = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Default to latest date if none selected
    if (empty($dateFilter) && !empty($distinctDates)) {
        $dateFilter = $distinctDates[0];
    }

    if (!empty($dateFilter)) {
        // Times recorded on this specific date
        $stmtT = $pdo->prepare("SELECT DISTINCT timeMarked FROM attendance WHERE course=:c AND unit=:u AND dateMarked=:d AND timeMarked IS NOT NULL ORDER BY timeMarked");
        $stmtT->execute([':c'=>$courseCode,':u'=>$unitCode,':d'=>$dateFilter]);
        $sessionTimes = $stmtT->fetchAll(PDO::FETCH_COLUMN);

        // Students for this course+unit+date, optionally filtered by year
        $yearWhere = !empty($yearFilter) ? "AND s.yearLevel = :year" : "";
        $sql = "SELECT DISTINCT a.studentRegistrationNumber,
                   s.firstName, s.lastName, s.yearLevel, s.section, s.courseCode
                FROM attendance a
                LEFT JOIN students s ON a.studentRegistrationNumber = s.registrationNumber
                WHERE a.course = :c AND a.unit = :u AND a.dateMarked = :d $yearWhere
                ORDER BY s.yearLevel, s.lastName, s.firstName";
        $stmt = $pdo->prepare($sql);
        $params = [':c'=>$courseCode, ':u'=>$unitCode, ':d'=>$dateFilter];
        if (!empty($yearFilter)) $params[':year'] = $yearFilter;
        $stmt->execute($params);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="resources/images/logo/attnlg.png" rel="icon">
    <title>View Attendance — AFRAs</title>
    <link rel="stylesheet" href="resources/assets/css/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css" rel="stylesheet">
    <style>
        /* ── Course Cards ───────────────────────── */
        .course-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }
        .course-card-btn {
            background: var(--bg-white);
            border: 2px solid var(--border);
            border-radius: 14px;
            padding: 16px 18px;
            cursor: pointer;
            transition: all 0.18s;
            text-align: left;
            position: relative;
            overflow: hidden;
        }
        .course-card-btn::before {
            content:''; position:absolute; top:0; left:0; right:0; height:3px;
            background: var(--card-color); border-radius:14px 14px 0 0;
        }
        .course-card-btn:hover { transform:translateY(-3px); box-shadow: var(--shadow-md); }
        .course-card-btn.active {
            border-color: var(--card-color);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--card-color) 18%, transparent);
        }
        .course-card-btn .cc-code {
            font-size:11px; font-weight:700; letter-spacing:1px;
            text-transform:uppercase; color: var(--card-color); margin-bottom:4px;
        }
        .course-card-btn .cc-name {
            font-size:13px; font-weight:600; color:var(--text); line-height:1.3;
        }
        .course-card-btn .cc-icon {
            font-size:22px; color:var(--card-color); margin-bottom:8px; display:block;
        }

        /* ── Subject + Year dropdowns row ──────── */
        .filter-row {
            display: flex; gap: 12px; align-items: center;
            flex-wrap: wrap; margin-bottom: 18px;
        }
        .filter-row select {
            flex: 1; min-width: 180px; max-width: 280px;
            background: var(--bg-white); border: 1px solid var(--border);
            border-radius: 10px; padding: 10px 14px;
            font-size: 14px; color: var(--text);
            cursor: pointer;
        }
        .filter-row select:focus { outline: none; border-color: var(--accent); }

        /* ── Info bar ───────────────────────────── */
        .info-bar {
            display:flex; flex-wrap:wrap; gap:12px;
            background:var(--bg-white); border:1px solid var(--border);
            border-radius:12px; padding:14px 20px; margin-bottom:18px; font-size:13.5px;
        }
        .info-bar span { color:var(--text-muted); }
        .info-bar strong { color:var(--text); margin-left:4px; }
        .info-divider { color:var(--border); }

        /* ── Export button ──────────────────────── */
        .export-btn {
            display:inline-flex; align-items:center; gap:8px;
            background:linear-gradient(135deg,#10B981,#059669);
            color:#fff; border:none; border-radius:10px;
            padding:10px 20px; font-size:14px; font-weight:600;
            cursor:pointer; margin-bottom:18px; transition:opacity 0.2s, transform 0.15s;
        }
        .export-btn:hover { opacity:0.88; transform:translateY(-1px); }
        .export-btn:disabled { opacity:0.45; cursor:not-allowed; transform:none; }

        /* ── Table header with year filter ─────── */
        .table-header-row {
            display:flex; align-items:center; justify-content:space-between;
            flex-wrap:wrap; gap:10px; margin-bottom:10px;
        }
        .year-filter-wrap {
            display:flex; align-items:center; gap:8px; font-size:13px; color:var(--text-muted);
        }
        .year-filter-wrap select {
            background:var(--bg-white); border:1px solid var(--border);
            border-radius:8px; padding:7px 12px; font-size:13px; color:var(--text); cursor:pointer;
        }

        /* ── Badges ─────────────────────────────── */
        .attendance-badge {
            display:inline-block; padding:3px 10px;
            border-radius:20px; font-size:12px; font-weight:600;
        }
        .badge-present { background:rgba(16,185,129,0.12); color:#059669; }
        .badge-absent  { background:rgba(239,68,68,0.1);   color:#DC2626; }
        .badge-late    { background:rgba(245,158,11,0.12); color:#D97706; }

        #attendanceTable th, #attendanceTable td { white-space:nowrap; }
        .no-data { text-align:center; padding:40px; color:var(--text-muted); font-size:14px; }
        .no-data i { font-size:36px; display:block; margin-bottom:10px; }

        .date-time-header { font-size:11px; line-height:1.4; }
        .date-time-header .dth-date { font-weight:700; }
        .date-time-header .dth-time { color:var(--text-muted); font-weight:400; }
    </style>
</head>
<body>
<?php include 'includes/topbar.php'; ?>
<section class="main">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main--content">

        <!-- ── Section title ──────────────────────────── -->
        <div class="title" style="margin-bottom:16px;">
            <h2 class="section--title">View Attendance</h2>
        </div>

        <!-- ── Course Cards ───────────────────────────── -->
        <div class="course-cards-grid">
            <?php
            $cardColors = ['#3B5BDB','#0EA5E9','#10B981','#F59E0B','#8B5CF6','#EF4444','#06B6D4','#EC4899','#F97316'];
            $cardIcons  = ['ri-book-2-line','ri-computer-line','ri-flask-line','ri-settings-3-line','ri-flashlight-line','ri-shield-line','ri-anchor-line','ri-hotel-line','ri-microscope-line'];
            foreach ($allCourses as $idx => $c):
                $color = $cardColors[$idx % count($cardColors)];
                $icon  = $cardIcons[$idx  % count($cardIcons)];
                $isActive = ($c['courseCode'] === $courseCode);
            ?>
            <button class="course-card-btn <?= $isActive ? 'active' : '' ?>"
                    style="--card-color:<?= $color ?>"
                    onclick="selectCourse('<?= $c['courseCode'] ?>', '<?= $c['Id'] ?>')">
                <i class="<?= $icon ?> cc-icon"></i>
                <div class="cc-code"><?= htmlspecialchars($c['courseCode']) ?></div>
                <div class="cc-name"><?= htmlspecialchars($c['name']) ?></div>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- ── Subject + Year + Date filter row ─────── -->
        <div class="filter-row" id="filterRow" style="<?= empty($courseCode) ? 'display:none' : '' ?>">
            <select id="subjectSelect" onchange="selectSubject(this.value)">
                <option value="">— Select Subject —</option>
                <?php if (!empty($courseId) && isset($unitsMap[$courseId])): ?>
                    <?php foreach ($unitsMap[$courseId] as $u): ?>
                    <option value="<?= $u['unitCode'] ?>" <?= $unitCode===$u['unitCode']?'selected':'' ?>>
                        <?= htmlspecialchars($u['name']) ?> (<?= $u['unitCode'] ?>)
                    </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>

            <?php if (!empty($unitCode)): ?>

            <?php if (!empty($yearLevels)): ?>
            <select id="yearSelect" onchange="selectYear(this.value)">
                <option value="">All Year Levels</option>
                <?php foreach ($yearLevels as $yr): ?>
                <option value="<?= $yr ?>" <?= $yearFilter===$yr?'selected':'' ?>>
                    Year <?= htmlspecialchars($yr) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <?php if (!empty($distinctDates)): ?>
            <select id="dateSelect" onchange="selectDate(this.value)">
                <option value="">— Select Date —</option>
                <?php foreach ($distinctDates as $d): ?>
                <option value="<?= $d ?>" <?= $dateFilter===$d?'selected':'' ?>>
                    <?= date('F d, Y', strtotime($d)) ?> (<?= date('D', strtotime($d)) ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <?php endif; ?>
        </div>

        <?php if (!empty($courseCode) && !empty($unitCode)): ?>

        <!-- ── Info bar ───────────────────────────────── -->
        <div class="info-bar">
            <span>Course: <strong><?= htmlspecialchars($coursename ?: $courseCode) ?></strong></span>
            <span class="info-divider">|</span>
            <span>Subject: <strong><?= htmlspecialchars($unitname ?: $unitCode) ?></strong></span>
            <span class="info-divider">|</span>
            <span>Date: <strong><?= $dateFilter ? date('F d, Y', strtotime($dateFilter)) : '—' ?></strong></span>
            <span class="info-divider">|</span>
            <span>Students: <strong><?= count($students) ?></strong></span>
            <?php if (!empty($yearFilter)): ?>
            <span class="info-divider">|</span>
            <span>Year: <strong>Year <?= htmlspecialchars($yearFilter) ?></strong></span>
            <?php endif; ?>
        </div>

        <!-- ── Export button ──────────────────────────── -->
        <button class="export-btn" onclick="exportToExcel()" <?= empty($students)?'disabled':'' ?>>
            <i class="ri-file-excel-2-line"></i> Export Attendance as Excel
        </button>

        <?php endif; ?>

        <!-- ── Attendance Table ───────────────────────── -->
        <div class="table-container">
            <div class="table-header-row">
                <h2 class="section--title">Attendance Preview</h2>
            </div>
            <div class="table" style="overflow-x:auto;">

            <?php if (empty($courseCode)): ?>
                <div class="no-data">
                    <i class="ri-mouse-line"></i>
                    Click a course card above to get started.
                </div>

            <?php elseif (empty($unitCode)): ?>
                <div class="no-data">
                    <i class="ri-book-open-line"></i>
                    Now select a subject from the dropdown.
                </div>

            <?php elseif (empty($dateFilter) || empty($distinctDates)): ?>
                <div class="no-data">
                    <i class="ri-calendar-line"></i>
                    No attendance sessions recorded yet for this subject.
                </div>

            <?php elseif (empty($students)): ?>
                <div class="no-data">
                    <i class="ri-emotion-sad-line"></i>
                    No attendance records found for this date.
                </div>

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
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($students as $i => $student):
                        $regNo    = $student['studentRegistrationNumber'];
                        $fullName = trim(($student['firstName']??'').' '.($student['lastName']??''));
                        if (!$fullName) $fullName = $regNo;

                        // Fetch attendance record for this student on this date
                        $stmt2 = $pdo->prepare("SELECT attendanceStatus, timeMarked FROM attendance
                            WHERE studentRegistrationNumber=:reg AND dateMarked=:d AND course=:c AND unit=:u
                            ORDER BY timeMarked ASC LIMIT 1");
                        $stmt2->execute([':reg'=>$regNo,':d'=>$dateFilter,':c'=>$courseCode,':u'=>$unitCode]);
                        $att    = $stmt2->fetch(PDO::FETCH_ASSOC);
                        $status = $att ? $att['attendanceStatus'] : 'Absent';
                        $time   = ($att && $att['timeMarked']) ? date('h:i A', strtotime($att['timeMarked'])) : '—';
                        $badge  = strtolower($status)==='present' ? 'badge-present'
                                : (strtolower($status)==='late'   ? 'badge-late' : 'badge-absent');
                    ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><?= htmlspecialchars($regNo) ?></td>
                            <td><?= htmlspecialchars($fullName) ?></td>
                            <td><?= htmlspecialchars($student['courseCode'] ?? $courseCode) ?></td>
                            <td><?= htmlspecialchars($student['yearLevel'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($student['section'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($unitname ?: $unitCode) ?></td>
                            <td><?= date('M d, Y', strtotime($dateFilter)) ?></td>
                            <td><?= $time ?></td>
                            <td><span class="attendance-badge <?= $badge ?>"><?= $status ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            </div>
        </div>

    </div>
</section>

<!-- Units data for JS subject dropdown population -->
<script>
const unitsMap = <?= json_encode($unitsMap) ?>;
const allUnits = <?= json_encode($allUnits) ?>;

function selectCourse(courseCode, courseId) {
    window.location.href = 'view-attendance?course=' + encodeURIComponent(courseCode);
}

function selectSubject(unitCode) {
    const course = new URLSearchParams(window.location.search).get('course') || '';
    if (unitCode) {
        window.location.href = 'view-attendance?course=' + encodeURIComponent(course) + '&unit=' + encodeURIComponent(unitCode);
    }
}

function selectYear(year) {
    const params = new URLSearchParams(window.location.search);
    if (year) params.set('year', year);
    else params.delete('year');
    window.location.href = 'view-attendance?' + params.toString();
}

function selectDate(date) {
    const params = new URLSearchParams(window.location.search);
    if (date) params.set('date', date);
    else params.delete('date');
    window.location.href = 'view-attendance?' + params.toString();
}
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<?php js_asset(["active_link"]) ?>
<script>
function exportToExcel() {
    const table = document.getElementById('attendanceTable');
    if (!table) { alert('No data to export.'); return; }

    const courseName = <?= json_encode($coursename ?: $courseCode) ?>;
    const unitName   = <?= json_encode($unitname   ?: $unitCode) ?>;
    const dateLabel  = <?= json_encode($dateFilter ? date('F d, Y', strtotime($dateFilter)) : '') ?>;
    const yearLabel  = <?= json_encode($yearFilter ? 'Year '.$yearFilter : 'All Years') ?>;
    const today      = new Date().toLocaleDateString();

    const clone = table.cloneNode(true);
    clone.querySelectorAll('.attendance-badge').forEach(el => {
        el.replaceWith(document.createTextNode(el.textContent.trim()));
    });

    const wb   = XLSX.utils.book_new();
    const rows = [];
    rows.push(['ATTENDANCE RECORD']);
    rows.push(['Course: ' + courseName + '   |   Subject: ' + unitName]);
    rows.push(['Date: ' + dateLabel + '   |   ' + yearLabel]);
    rows.push(['Exported on: ' + today]);
    rows.push([]);

    clone.querySelectorAll('tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('th, td').forEach(cell => row.push((cell.innerText || cell.textContent).trim()));
        rows.push(row);
    });

    const ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [{wch:4},{wch:15},{wch:25},{wch:10},{wch:6},{wch:9},{wch:22},{wch:14},{wch:10},{wch:10}];
    XLSX.utils.book_append_sheet(wb, ws, 'Attendance');

    const filename = (unitName||'attendance').replace(/\s+/g,'_') + '_' + (<?= json_encode($dateFilter) ?> || new Date().toISOString().slice(0,10)) + '.xlsx';
    XLSX.writeFile(wb, filename);
}
</script>
</body>
</html>