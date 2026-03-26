<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="resources/images/logo/attnlg.png" rel="icon">
    <title>AFRAS</title>
    <link rel="stylesheet" href="resources/assets/css/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css" rel="stylesheet">
</head>

<body>
<?php include 'includes/topbar.php'; ?>

<section class="main">
<?php include 'includes/sidebar.php'; ?>

<div class="main--content">

    <!-- TOGGLE BUTTONS -->
    <div class="content-buttons">
        <button onclick="showSection('lectures', this)" class="active-tab">Lectures</button>
        <button onclick="showSection('students', this)">Students</button>
        <button onclick="showSection('rooms', this)">Lecture Rooms</button>
        <button onclick="showSection('courses', this)">Courses</button>
    </div>

    <!-- ================= LECTURES ================= -->
    <div class="table-container section-content" id="lectures">

        <a href="manage-lecture" style="text-decoration:none;">
            <div class="title">
                <h2 class="section--title">Lectures</h2>
                <button class="add"><i class="ri-add-line"></i>Add lecture</button>
            </div>
        </a>

        <div class="table">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email Address</th>
                        <th>Phone No</th>
                        <th>Faculty</th>
                        <th>Date Registered</th>
                        <th>Settings</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT l.*, f.facultyName
                            FROM lecture l
                            LEFT JOIN faculty f ON l.facultyCode = f.facultyCode";

                    $stmt = $pdo->query($sql);
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($result) {
                        foreach ($result as $row) {
                            echo "<tr id='rowlecture{$row["Id"]}'>";
                            echo "<td>" . $row["firstName"] . "</td>";
                            echo "<td>" . $row["emailAddress"] . "</td>";
                            echo "<td>" . $row["phoneNo"] . "</td>";
                            echo "<td>" . $row["facultyName"] . "</td>";
                            echo "<td>" . $row["dateCreated"] . "</td>";
                            echo "<td><span><i class='ri-delete-bin-line delete' data-id='{$row["Id"]}' data-name='lecture'></i></span></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No records found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ================= STUDENTS ================= -->
    <div class="table-container section-content" id="students" style="display:none;">

        <a href="manage-students" style="text-decoration:none;">
            <div class="title">
                <h2 class="section--title">Students</h2>
                <button class="add"><i class="ri-add-line"></i>Add Student</button>
            </div>
        </a>

        <div class="table">
            <table>
                <thead>
                    <tr>
                        <th>Registration No</th>
                        <th>Name</th>
                        <th>Faculty</th>
                        <th>Course</th>
                        <th>Email</th>
                        <th>Settings</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM students";
                    $stmt = $pdo->query($sql);
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($result) {
                        foreach ($result as $row) {
                            echo "<tr id='rowstudents{$row["Id"]}'>";
                            echo "<td>" . $row["registrationNumber"] . "</td>";
                            echo "<td>" . $row["firstName"] . "</td>";
                            echo "<td>" . $row["faculty"] . "</td>";
                            echo "<td>" . $row["courseCode"] . "</td>";
                            echo "<td>" . $row["email"] . "</td>";
                            echo "<td><span><i class='ri-delete-bin-line delete' data-id='{$row["Id"]}' data-name='students'></i></span></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No records found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ================= ROOMS ================= -->
    <div class="table-container section-content" id="rooms" style="display:none;">

        <a href="create-venue" style="text-decoration:none;">
            <div class="title">
                <h2 class="section--title">Lecture Rooms</h2>
                <button class="add"><i class="ri-add-line"></i>Add room</button>
            </div>
        </a>

        <div class="table">
            <table>
                <thead>
                    <tr>
                        <th>Class Name</th>
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
                        foreach ($result as $row) {
                            echo "<tr id='rowvenue{$row["Id"]}'>";
                            echo "<td>" . $row["className"] . "</td>";
                            echo "<td>" . $row["facultyCode"] . "</td>";
                            echo "<td>" . $row["currentStatus"] . "</td>";
                            echo "<td>" . $row["capacity"] . "</td>";
                            echo "<td>" . $row["classification"] . "</td>";
                            echo "<td><span><i class='ri-delete-bin-line delete' data-id='{$row["Id"]}' data-name='venue'></i></span></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No records found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ================= COURSES ================= -->
    <div class="table-container section-content" id="courses" style="display:none;">

        <a href="manage-course" style="text-decoration:none;">
            <div class="title">
                <h2 class="section--title">Courses</h2>
                <button class="add"><i class="ri-add-line"></i>Add Course</button>
            </div>
        </a>

        <div class="table">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Faculty</th>
                        <th>Total Units</th>
                        <th>Total Students</th>
                        <th>Date Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT 
                        c.name AS course_name,c.Id AS Id,
                        f.facultyName AS faculty_name,
                        COUNT(u.ID) AS total_units,
                        COUNT(DISTINCT s.Id) AS total_students,
                        c.dateCreated AS date_created
                        FROM course c
                        LEFT JOIN unit u ON c.ID = u.courseID
                        LEFT JOIN students s ON c.courseCode = s.courseCode
                        LEFT JOIN faculty f on c.facultyID=f.Id
                        GROUP BY c.ID";

                    $stmt = $pdo->query($sql);
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($result) {
                        foreach ($result as $row) {
                            echo "<tr id='rowcourse{$row["Id"]}'>";
                            echo "<td>" . $row["course_name"] . "</td>";
                            echo "<td>" . $row["faculty_name"] . "</td>";
                            echo "<td>" . $row["total_units"] . "</td>";
                            echo "<td>" . $row["total_students"] . "</td>";
                            echo "<td>" . $row["date_created"] . "</td>";
                            echo "<td><span><i class='ri-delete-bin-line delete' data-id='{$row["Id"]}' data-name='course'></i></span></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No records found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</section>

<script>
function showSection(sectionId, btn) {
    const sections = document.querySelectorAll('.section-content');
    sections.forEach(section => section.style.display = 'none');
    document.getElementById(sectionId).style.display = 'block';
    document.querySelectorAll('.content-buttons button').forEach(b => b.classList.remove('active-tab'));
    if (btn) btn.classList.add('active-tab');
}
</script>

<?php js_asset(["active_link", "delete_request"]) ?>

</body>
</html>