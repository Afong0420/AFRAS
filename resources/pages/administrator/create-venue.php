<?php


if (isset($_POST["addVenue"])) {
    // Sanitize and validate inputs
    $className = htmlspecialchars(trim($_POST['className']));
    $facultyCode = htmlspecialchars(trim($_POST['faculty']));
    $currentStatus = htmlspecialchars(trim($_POST['currentStatus']));
    $capacity = filter_var($_POST['capacity'], FILTER_VALIDATE_INT);
    $classification = htmlspecialchars(trim($_POST['classification']));

    // Check for required fields
    if (!$className || !$facultyCode || !$currentStatus || !$capacity || !$classification) {
        $_SESSION['message'] = "All fields are required and must be valid.";
    } else {
        $dateRegistered = date("Y-m-d");

        // Prepare database operations using PDO
        try {
            // Check if venue already exists
            $stmt = $pdo->prepare("SELECT * FROM venue WHERE className = :className");
            $stmt->bindParam(':className', $className);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $_SESSION['message'] = "Venue Already Exists";
            } else {
                // Insert the new venue
                $stmt = $pdo->prepare(
                    "INSERT INTO venue (className, facultyCode, currentStatus, capacity, classification, dateCreated)
                    VALUES (:className, :facultyCode, :currentStatus, :capacity, :classification, :dateCreated)"
                );
                $stmt->bindParam(':className', $className);
                $stmt->bindParam(':facultyCode', $facultyCode);
                $stmt->bindParam(':currentStatus', $currentStatus);
                $stmt->bindParam(':capacity', $capacity, PDO::PARAM_INT);
                $stmt->bindParam(':classification', $classification);
                $stmt->bindParam(':dateCreated', $dateRegistered);

                if ($stmt->execute()) {
                    $_SESSION['message'] = "Venue Inserted Successfully";
                } else {
                    $_SESSION['message'] = "Failed to Insert Venue.";
                }
            }
        } catch (PDOException $e) {
            $_SESSION['message'] = "Database Error: " . $e->getMessage();
        }
    }
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="resources/images/logo/attnlg.png" rel="icon">
    <title>Dashboard</title>
    <link rel="stylesheet" href="resources/assets/css/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css" rel="stylesheet">
</head>

<body>
    <?php include 'includes/topbar.php' ?>
    <section class="main">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main--content">

            <div id="overlay"></div>

            <?php
            // Fetch subjects (units) grouped by venueID for room cards
            $roomSubjects = [];
            $subjectSql = "SELECT u.Id, u.name AS unit_name, u.unitCode, u.startTime, u.endTime, u.scheduleDays, u.venueID, c.name AS course_name, c.courseCode FROM unit u LEFT JOIN course c ON u.courseID = c.Id WHERE u.venueID IS NOT NULL";
            $subjectStmt = $pdo->query($subjectSql);
            if ($subjectStmt) {
                foreach ($subjectStmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
                    $roomSubjects[$s['venueID']][] = $s;
                }
            }

            // Fetch venue records matching the 5 fixed rooms by className
            $fixedRooms = ['Classroom', 'Basic Lab A', 'Basic Lab B', 'IT Lab', 'Room 113'];
            $roomIcons  = ['ri-building-2-line', 'ri-flask-line', 'ri-flask-2-line', 'ri-computer-line', 'ri-door-open-line'];
            $roomColors = ['#3B5BDB', '#10B981', '#0EA5E9', '#8B5CF6', '#F59E0B'];

            // Map venue className → venue row
            $venueMap = [];
            $venueAllStmt = $pdo->query("SELECT * FROM venue");
            if ($venueAllStmt) {
                foreach ($venueAllStmt->fetchAll(PDO::FETCH_ASSOC) as $v) {
                    $venueMap[$v['className']] = $v;
                }
            }
            ?>

            <div class="rooms">
                <div class="title">
                    <h2 class="section--title">Rooms</h2>
                    <div class="rooms--right--btns">
                        <button id="addClass1" class="add show-form"><i class="ri-add-line"></i>Add Lecture Room</button>
                    </div>
                </div>
                <div class="rooms--cards">
                    <?php foreach ($fixedRooms as $i => $roomName):
                        $venueRow = $venueMap[$roomName] ?? null;
                        $venueId  = $venueRow ? $venueRow['Id'] : null;
                        $subjects = ($venueId && isset($roomSubjects[$venueId])) ? $roomSubjects[$venueId] : [];
                        $subjectCount = count($subjects);
                        $color = $roomColors[$i];
                        $icon  = $roomIcons[$i];
                        $subjectsJson = htmlspecialchars(json_encode($subjects), ENT_QUOTES);
                    ?>
                    <div class="room--card" style="cursor:pointer; position:relative;"
                         onclick="openRoomModal(<?= $venueId ?? 'null' ?>, '<?= htmlspecialchars($roomName) ?>', this)"
                         data-subjects='<?= $subjectsJson ?>'>
                        <div class="img--box--cover" style="border-color: <?= $color ?>;">
                            <div class="img--box" style="background: <?= $color ?>18; display:flex; align-items:center; justify-content:center;">
                                <i class="<?= $icon ?>" style="font-size:32px; color:<?= $color ?>; padding:0;"></i>
                            </div>
                        </div>
                        <p style="font-weight:600; font-size:13px; color:#1E293B; margin-top:4px;"><?= htmlspecialchars($roomName) ?></p>
                        <span style="font-size:11px; color:<?= $color ?>; font-weight:600;">
                            <?= $subjectCount ?> subject<?= $subjectCount !== 1 ? 's' : '' ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Room Subjects Modal -->
            <div id="roomModal" style="display:none; position:fixed; inset:0; z-index:1100;">
                <div id="roomModalOverlay" onclick="closeRoomModal()" style="position:absolute; inset:0; background:rgba(10,15,30,0.55); backdrop-filter:blur(4px);"></div>
                <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; border-radius:20px; padding:32px 36px; min-width:460px; max-width:600px; width:90%; max-height:80vh; overflow-y:auto; box-shadow:0 24px 64px rgba(0,0,0,0.18); z-index:1101;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                        <div>
                            <h2 id="roomModalTitle" style="font-family:'Syne',sans-serif; font-size:18px; font-weight:700; color:#1E293B;"></h2>
                            <p id="roomModalSub" style="font-size:12px; color:#64748B; margin-top:2px;"></p>
                        </div>
                        <span onclick="closeRoomModal()" style="cursor:pointer; font-size:22px; color:#64748B; line-height:1;">&times;</span>
                    </div>
                    <div id="roomModalBody"></div>
                </div>
            </div>
            <?php showMessage() ?>
            <div class="table-container">
                <div class="title" id="addClass2">
                    <h2 class="section--title">Lecture Rooms</h2>
                </div>

                <div class="table">
                    <table>
                        <thead>
                            <tr>
                                <th>Room Name</th>
                                <th>Faculty</th>
                                <th>Current Status</th>
                                <th>Capacity</th>
                                <th>Classification</th>
                                <th>Settings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM venue";
                            $stmt = $pdo->query($sql);
                            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            if ($result) {
                                foreach ($result as $row)
                                    echo "<tr id='rowvenue{$row["Id"]}'>";
                                echo "<td>" . $row["className"] . "</td>";
                                echo "<td>" . $row["facultyCode"] . "</td>";
                                echo "<td>" . $row["currentStatus"] . "</td>";
                                echo "<td>" . $row["capacity"] . "</td>";
                                echo "<td>" . $row["classification"] . "</td>";
                                echo "<td><span><i class='ri-delete-bin-line delete' data-id='{$row["Id"]}' data-name='venue'></i></span></td>";
                                echo "</tr>";
                            } else {
                                echo "<tr><td colspan='6'>No records found</td></tr>";
                            }

                            ?>
                        </tbody>
                    </table>
                </div>

            </div>

            <div class="formDiv-venue" id="addClassForm" style="display:none ">
                <form method="POST" action="" name="addVenue" enctype="multipart/form-data">
                    <div style="display:flex; justify-content:space-around;">
                        <div class="form-title">
                            <p>Add Venue</p>
                        </div>
                        <div>
                            <span class="close">&times;</span>
                        </div>
                    </div>
                    <input type="text" name="className" placeholder="Class Name" required>
                    <select name="currentStatus" id="">
                        <option value="">--Current Status--</option>
                        <option value="availlable">Available</option>
                        <option value="scheduled">Scheduled</option>
                    </select>
                    <input type="text" name="capacity" placeholder="Capacity" required>
                    <select required name="classification">
                        <option value="" selected> --Select Class Type--</option>
                        <option value="laboratory">Laboratory</option>
                        <option value="computerLab">Computer Lab</option>
                        <option value="lectureHall">Lecture Hall</option>
                        <option value="class">Class</option>
                        <option value="office">Office</option>
                    </select>
                    <select required name="faculty">
                        <option value="" selected>Select Faculty</option>
                        <?php
                        $facultyNames = getFacultyNames();
                        foreach ($facultyNames as $faculty) {
                            echo '<option value="' . $faculty["facultyCode"] . '">' . $faculty["facultyName"] . '</option>';
                        }
                        ?>
                    </select>
                    <input type="submit" class="submit" value="Save Venue" name="addVenue">
                </form>
            </div>
        </div>
    </section>
    <?php js_asset(["active_link", "delete_request"]) ?>


    <script>
        const show_form = document.querySelectorAll(".show-form")
        const addClassForm = document.getElementById('addClassForm');
        const overlay = document.getElementById('overlay');
        const closeButtons = document.querySelectorAll('#addClassForm .close');
        show_form.forEach((showForm) => {
            showForm.addEventListener('click', function() {
                addClassForm.style.display = 'block';
                overlay.style.display = 'block';
                document.body.style.overflow = 'hidden';
            });
        })
        closeButtons.forEach(function(closeButton) {
            closeButton.addEventListener('click', function() {
                addClassForm.style.display = 'none';
                overlay.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
        });

        // ── Room modal ───────────────────────────────────────
        function fmt12(t) {
            if (!t) return '';
            const p = t.split(':'); let h = parseInt(p[0]), m = p[1] || '00';
            const ap = h < 12 ? 'AM' : 'PM';
            if (h === 0) h = 12; else if (h > 12) h -= 12;
            return h + ':' + m + ' ' + ap;
        }

        function openRoomModal(venueId, roomName, card) {
            const subjects = JSON.parse(card.getAttribute('data-subjects') || '[]');
            document.getElementById('roomModalTitle').textContent = roomName;
            document.getElementById('roomModalSub').textContent = subjects.length + ' subject' + (subjects.length !== 1 ? 's' : '') + ' assigned to this room';

            const body = document.getElementById('roomModalBody');
            if (subjects.length === 0) {
                body.innerHTML = '<div style="text-align:center; padding:32px 0; color:#94A3B8;">' +
                    '<i class="ri-inbox-line" style="font-size:40px; display:block; margin-bottom:10px;"></i>' +
                    '<p style="font-size:14px;">No subjects assigned to this room yet.</p>' +
                    '<p style="font-size:12px; margin-top:6px;">Assign a venue when adding a subject.</p></div>';
            } else {
                let html = '';
                subjects.forEach(s => {
                    const days = s.scheduleDays ? s.scheduleDays.split(',') : [];
                    const allDays = ['Mon','Tue','Wed','Thu','Fri'];
                    let dayBadges = allDays.map(d => {
                        const active = days.includes(d);
                        return '<span style="display:inline-block;padding:2px 7px;border-radius:5px;font-size:10px;font-weight:600;margin-right:3px;' +
                            (active ? 'background:#3B5BDB;color:#fff;' : 'background:#F1F5F9;color:#94A3B8;') + '">' + d + '</span>';
                    }).join('');
                    const schedule = (s.startTime && s.endTime) ? fmt12(s.startTime) + ' – ' + fmt12(s.endTime) : 'No schedule';
                    html += '<div style="background:#F8FAFF; border:1px solid #E2E8F0; border-radius:12px; padding:16px 18px; margin-bottom:12px;">' +
                        '<div style="display:flex; justify-content:space-between; align-items:flex-start;">' +
                            '<div>' +
                                '<span style="font-size:11px; font-weight:700; color:#3B5BDB; letter-spacing:0.5px; text-transform:uppercase;">' + s.unitCode + '</span>' +
                                '<p style="font-family:Syne,sans-serif; font-size:15px; font-weight:700; color:#1E293B; margin:4px 0 2px;">' + s.unit_name + '</p>' +
                                '<p style="font-size:12px; color:#64748B;">' + (s.course_name || '') + ' · ' + (s.courseCode || '') + '</p>' +
                            '</div>' +
                            '<span style="font-size:12px; font-weight:600; color:#10B981; background:#ECFDF5; padding:4px 10px; border-radius:20px; white-space:nowrap;">' + schedule + '</span>' +
                        '</div>' +
                        '<div style="margin-top:10px;">' + dayBadges + '</div>' +
                    '</div>';
                });
                body.innerHTML = html;
            }
            document.getElementById('roomModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeRoomModal() {
            document.getElementById('roomModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    </script>
</body>

</html>