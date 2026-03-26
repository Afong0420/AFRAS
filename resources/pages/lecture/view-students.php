<?php

$courseCode  = isset($_GET['course'])  ? $_GET['course']  : '';
$yearFilter  = isset($_GET['year'])    ? $_GET['year']    : '';
$sectionFilter = isset($_GET['section']) ? $_GET['section'] : '';

// All courses
$allCourses = fetch("SELECT Id, name, courseCode FROM course ORDER BY name");

// Resolve course name
$coursename = '';
if (!empty($courseCode)) {
    $r = fetch("SELECT name FROM course WHERE courseCode = '$courseCode'");
    if ($r) $coursename = $r[0]['name'];
}

// Available year levels for this course
$yearLevels = [];
if (!empty($courseCode)) {
    $stmt = $pdo->prepare("SELECT DISTINCT yearLevel FROM students WHERE courseCode=:c AND yearLevel!='' ORDER BY yearLevel");
    $stmt->execute([':c' => $courseCode]);
    $yearLevels = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Available sections for this course + year
$sections = [];
if (!empty($courseCode)) {
    $secSql = "SELECT DISTINCT section FROM students WHERE courseCode=:c AND section!=''";
    $secParams = [':c' => $courseCode];
    if (!empty($yearFilter)) { $secSql .= " AND yearLevel=:y"; $secParams[':y'] = $yearFilter; }
    $secSql .= " ORDER BY section";
    $stmt = $pdo->prepare($secSql);
    $stmt->execute($secParams);
    $sections = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Fetch students
$students = [];
if (!empty($courseCode)) {
    $where = "courseCode = :c";
    $params = [':c' => $courseCode];
    if (!empty($yearFilter))    { $where .= " AND yearLevel = :y"; $params[':y'] = $yearFilter; }
    if (!empty($sectionFilter)) { $where .= " AND section = :s";   $params[':s'] = $sectionFilter; }

    $stmt = $pdo->prepare("SELECT * FROM students WHERE $where ORDER BY yearLevel, section, lastName, firstName");
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="resources/images/logo/attnlg.png" rel="icon">
    <title>Students — AFRAs</title>
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
            text-transform:uppercase; color:var(--card-color); margin-bottom:4px;
        }
        .course-card-btn .cc-name {
            font-size:13px; font-weight:600; color:var(--text); line-height:1.3;
        }
        .course-card-btn .cc-icon {
            font-size:22px; color:var(--card-color); margin-bottom:8px; display:block;
        }
        .course-card-btn .cc-count {
            font-size:11px; color:var(--text-muted); margin-top:6px;
        }

        /* ── Filter row ─────────────────────────── */
        .filter-row {
            display:flex; gap:12px; align-items:center;
            flex-wrap:wrap; margin-bottom:18px;
        }
        .filter-row select {
            flex:1; min-width:160px; max-width:220px;
            background:var(--bg-white); border:1px solid var(--border);
            border-radius:10px; padding:10px 14px;
            font-size:14px; color:var(--text); cursor:pointer;
        }
        .filter-row select:focus { outline:none; border-color:var(--accent); }

        /* ── Info bar ───────────────────────────── */
        .info-bar {
            display:flex; flex-wrap:wrap; gap:12px;
            background:var(--bg-white); border:1px solid var(--border);
            border-radius:12px; padding:14px 20px; margin-bottom:18px; font-size:13.5px;
        }
        .info-bar span { color:var(--text-muted); }
        .info-bar strong { color:var(--text); margin-left:4px; }
        .info-divider { color:var(--border); }

        /* ── Student avatar ─────────────────────── */
        .student-avatar {
            width:36px; height:36px; border-radius:50%;
            object-fit:cover; border:2px solid var(--border);
            background:var(--bg);
        }
        .avatar-placeholder {
            width:36px; height:36px; border-radius:50%;
            background:linear-gradient(135deg,#3B5BDB,#6C63FF);
            display:inline-flex; align-items:center; justify-content:center;
            color:#fff; font-size:13px; font-weight:700; flex-shrink:0;
        }

        /* ── Year badge ─────────────────────────── */
        .year-badge {
            display:inline-block; padding:2px 9px;
            border-radius:20px; font-size:11px; font-weight:700;
            background:rgba(59,91,219,0.1); color:#3B5BDB;
        }
        .section-badge {
            display:inline-block; padding:2px 9px;
            border-radius:20px; font-size:11px; font-weight:700;
            background:rgba(16,185,129,0.1); color:#059669;
        }

        /* ── No data ────────────────────────────── */
        .no-data { text-align:center; padding:40px; color:var(--text-muted); font-size:14px; }
        .no-data i { font-size:36px; display:block; margin-bottom:10px; }

        #studentTable th, #studentTable td { white-space:nowrap; }
        .td-name { display:flex; align-items:center; gap:10px; }

        /* ── Photo zoom on hover ────────────────── */
        .photo-wrap {
            position: relative;
            display: inline-block;
        }
        .photo-wrap .zoom-preview {
            display: none;
            position: fixed;
            z-index: 9999;
            width: 160px;
            height: 160px;
            border-radius: 12px;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 8px 32px rgba(0,0,0,0.35);
            pointer-events: none;
            transition: opacity 0.15s;
        }
        .photo-wrap:hover .zoom-preview {
            display: block;
        }
    </style>
</head>
<body>
<?php include 'includes/topbar.php'; ?>
<section class="main">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main--content">

        <!-- ── Title ────────────────────────────────── -->
        <div class="title" style="margin-bottom:16px;">
            <h2 class="section--title">Students</h2>
        </div>

        <!-- ── Course Cards ──────────────────────────── -->
        <div class="course-cards-grid">
            <?php
            $cardColors = ['#3B5BDB','#0EA5E9','#10B981','#F59E0B','#8B5CF6','#EF4444','#06B6D4','#EC4899','#F97316'];
            $cardIcons  = ['ri-book-2-line','ri-computer-line','ri-flask-line','ri-settings-3-line','ri-flashlight-line','ri-shield-line','ri-anchor-line','ri-hotel-line','ri-microscope-line'];
            foreach ($allCourses as $idx => $c):
                $color    = $cardColors[$idx % count($cardColors)];
                $icon     = $cardIcons[$idx  % count($cardIcons)];
                $isActive = ($c['courseCode'] === $courseCode);
                // student count per course
                $cnt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE courseCode=:c");
                $cnt->execute([':c'=>$c['courseCode']]);
                $studentCount = $cnt->fetchColumn();
            ?>
            <button class="course-card-btn <?= $isActive ? 'active' : '' ?>"
                    style="--card-color:<?= $color ?>"
                    onclick="window.location.href='view-students?course=<?= urlencode($c['courseCode']) ?>'">
                <i class="<?= $icon ?> cc-icon"></i>
                <div class="cc-code"><?= htmlspecialchars($c['courseCode']) ?></div>
                <div class="cc-name"><?= htmlspecialchars($c['name']) ?></div>
                <div class="cc-count"><?= $studentCount ?> student<?= $studentCount!=1?'s':'' ?></div>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- ── Year + Section filter row ────────────── -->
        <?php if (!empty($courseCode)): ?>
        <div class="filter-row">
            <select id="yearSelect" onchange="applyFilter()">
                <option value="">All Year Levels</option>
                <?php foreach ($yearLevels as $yr): ?>
                <option value="<?= $yr ?>" <?= $yearFilter===$yr?'selected':'' ?>>Year <?= htmlspecialchars($yr) ?></option>
                <?php endforeach; ?>
            </select>

            <select id="sectionSelect" onchange="applyFilter()">
                <option value="">All Sections</option>
                <?php foreach ($sections as $sec): ?>
                <option value="<?= $sec ?>" <?= $sectionFilter===$sec?'selected':'' ?>>Section <?= htmlspecialchars($sec) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <!-- ── Info bar ──────────────────────────────── -->
        <?php if (!empty($courseCode)): ?>
        <div class="info-bar">
            <span>Course: <strong><?= htmlspecialchars($coursename ?: $courseCode) ?></strong></span>
            <span class="info-divider">|</span>
            <span>Students Shown: <strong><?= count($students) ?></strong></span>
            <?php if (!empty($yearFilter)): ?>
            <span class="info-divider">|</span>
            <span>Year: <strong>Year <?= htmlspecialchars($yearFilter) ?></strong></span>
            <?php endif; ?>
            <?php if (!empty($sectionFilter)): ?>
            <span class="info-divider">|</span>
            <span>Section: <strong><?= htmlspecialchars($sectionFilter) ?></strong></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ── Students Table ────────────────────────── -->
        <div class="table-container">
            <div class="title"><h2 class="section--title">Students List</h2></div>
            <div class="table" style="overflow-x:auto;">

            <?php if (empty($courseCode)): ?>
                <div class="no-data">
                    <i class="ri-mouse-line"></i>
                    Click a course card above to view students.
                </div>

            <?php elseif (empty($students)): ?>
                <div class="no-data">
                    <i class="ri-emotion-sad-line"></i>
                    No students found for this selection.
                </div>

            <?php else: ?>
                <table id="studentTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Photo</th>
                            <th>ID Number</th>
                            <th>Full Name</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Section</th>
                            <th>Email</th>
                            <th>Date Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($students as $i => $s):
                        $fullName = trim($s['firstName'].' '.$s['lastName']);
                        $initials = strtoupper(substr($s['firstName'],0,1).substr($s['lastName'],0,1));
                        // Try to get first image
                        $imgs = json_decode($s['studentImage'] ?? '[]', true);
                        $imgPath = (!empty($imgs))
                            ? 'resources/labels/' . $s['registrationNumber'] . '/1.png'
                            : null;
                    ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td>
                                <?php if ($imgPath): ?>
                                <div class="photo-wrap">
                                    <img src="<?= htmlspecialchars($imgPath) ?>" class="student-avatar"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='none'; this.parentElement.nextElementSibling && null; this.closest('.photo-wrap').querySelector('.avatar-placeholder').style.display='inline-flex';">
                                    <img src="<?= htmlspecialchars($imgPath) ?>" class="zoom-preview" id="zp-<?= $i ?>">
                                    <span class="avatar-placeholder" style="display:none"><?= $initials ?></span>
                                </div>
                                <?php else: ?>
                                <span class="avatar-placeholder"><?= $initials ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($s['registrationNumber']) ?></td>
                            <td><?= htmlspecialchars($fullName) ?></td>
                            <td><?= htmlspecialchars($s['courseCode']) ?></td>
                            <td><span class="year-badge">Year <?= htmlspecialchars($s['yearLevel'] ?: '—') ?></span></td>
                            <td><span class="section-badge"><?= htmlspecialchars($s['section'] ?: '—') ?></span></td>
                            <td><?= htmlspecialchars($s['email']) ?></td>
                            <td><?= htmlspecialchars($s['dateRegistered']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            </div>
        </div>

    </div>
</section>

<?php js_asset(["active_link"]) ?>
<script>
function applyFilter() {
    const year    = document.getElementById('yearSelect')?.value    || '';
    const section = document.getElementById('sectionSelect')?.value || '';
    const course  = <?= json_encode($courseCode) ?>;
    let url = 'view-students?course=' + encodeURIComponent(course);
    if (year)    url += '&year='    + encodeURIComponent(year);
    if (section) url += '&section=' + encodeURIComponent(section);
    window.location.href = url;
}

// Follow cursor for zoom previews
document.querySelectorAll('.photo-wrap').forEach(wrap => {
    const preview = wrap.querySelector('.zoom-preview');
    if (!preview) return;

    wrap.addEventListener('mousemove', e => {
        const offset = 16;
        let x = e.clientX + offset;
        let y = e.clientY + offset;

        // Keep preview within viewport
        if (x + 160 > window.innerWidth)  x = e.clientX - 160 - offset;
        if (y + 160 > window.innerHeight) y = e.clientY - 160 - offset;

        preview.style.left = x + 'px';
        preview.style.top  = y + 'px';
    });
});
</script>
</body>
</html>