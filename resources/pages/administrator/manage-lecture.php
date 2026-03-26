<?php

if (isset($_POST["addLecture"])) {
    // Securely handle input
    $firstName = htmlspecialchars(trim($_POST["firstName"]));
    $lastName = htmlspecialchars(trim($_POST["lastName"]));
    $email = filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL);
    $phoneNumber = htmlspecialchars(trim($_POST["phoneNumber"]));
    $faculty = htmlspecialchars(trim($_POST["faculty"]));
    $dateRegistered = date("Y-m-d");
    $password = $_POST['password'];

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT); // Secure password hashing

    if ($email && $firstName && $lastName && $phoneNumber && $faculty) {
        try {
            // Check if lecture already exists
            $query = $pdo->prepare("SELECT * FROM lecture WHERE emailAddress = :email");
            $query->bindParam(':email', $email);
            $query->execute();

            if ($query->rowCount() > 0) {
                $_SESSION['message'] = "Lecture Already Exists";
            } else {
                // Insert new lecture
                $query = $pdo->prepare("INSERT INTO lecture 
                    (firstName, lastName, emailAddress, password, phoneNo, facultyCode, dateCreated) 
                    VALUES (:firstName, :lastName, :email, :password, :phoneNumber, :faculty, :dateCreated)");
                $query->bindParam(':firstName', $firstName);
                $query->bindParam(':lastName', $lastName);
                $query->bindParam(':email', $email);
                $query->bindParam(':password', $hashedPassword);
                $query->bindParam(':phoneNumber', $phoneNumber);
                $query->bindParam(':faculty', $faculty);
                $query->bindParam(':dateCreated', $dateRegistered);

                $query->execute();

                $_SESSION['message'] = "Lecture Added Successfully";
            }
        } catch (PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
        }
    } else {
        $_SESSION['message'] = "Invalid input. Please check your data.";
    }
}

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="resources/images/logo/attnlg.png" rel="icon">

    <title>AMS - Dashboard</title>
    <link rel="stylesheet" href="resources/assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css" rel="stylesheet">

</head>

<body>
    <?php include "Includes/topbar.php"; ?>

    <section class=main>

        <?php include "Includes/sidebar.php"; ?>

        <div class="main--content">
            <div id="overlay"></div>
            <?php showMessage() ?>
            <div class="table-container">
                <div class="title" id="showButton">
                    <h2 class="section--title">Lectures</h2>
                    <button class="add"><i class="ri-add-line"></i>Add lecture</button>
                </div>
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
                            <tr>
                                <?php
                                $sql = "SELECT * FROM lecture";
                                $result = fetch($sql);
                                if ($result) {
                                    foreach ($result as $row) {
                                        echo "<tr id='rowlecture{$row["Id"]}'>";
                                        echo "<td>" . $row["firstName"] . "</td>";
                                        echo "<td>" . $row["emailAddress"] . "</td>";
                                        echo "<td>" . $row["phoneNo"] . "</td>";
                                        echo "<td>" . $row["facultyCode"] . "</td>";
                                        echo "<td>" . $row["dateCreated"] . "</td>";
                                        echo "<td>
                                            <span><i class='ri-edit-line edit-lecture' 
                                                data-id='{$row["Id"]}'
                                                data-firstname='{$row["firstName"]}'
                                                data-lastname='{$row["lastName"]}'
                                                data-email='{$row["emailAddress"]}'
                                                data-phone='{$row["phoneNo"]}'
                                                data-faculty='{$row["facultyCode"]}'
                                                style='cursor:pointer; margin-right:8px;'></i></span>
                                            <span><i class='ri-delete-bin-line delete' data-id='{$row["Id"]}' data-name='lecture'></i></span>
                                        </td>";
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
            <div class="formDiv--" id="form" style="display:none; ">
                <form method="POST" action="" name="addLecture" enctype="multipart/form-data">
                    <div style="display:flex; justify-content:space-around;">
                        <div class="form-title">
                            <p>Add Lecture</p>
                        </div>
                        <div>
                            <span class="close">&times;</span>
                        </div>
                    </div>
                    <input type="text" name="firstName" placeholder="First Name" required>
                    <input type="text" name="lastName" placeholder="Last Name" required>
                    <input type="email" name="email" placeholder="Email Address" required>
                    <input type="text" name="phoneNumber" placeholder="Phone Number" required>
                    <input type="password" name="password" placeholder="**********" required>

                    <select required name="faculty">
                        <option value="" selected>Select Faculty</option>
                        <?php
                        $facultyNames = getFacultyNames();
                        foreach ($facultyNames as $faculty) {
                            echo '<option value="' . $faculty["facultyCode"] . '">' . $faculty["facultyName"] . '</option>';
                        }
                        ?>
                    </select>
                    <input type="submit" class="submit" value="Save Lecture" name="addLecture">
                </form>
            </div>



        <!-- ── Edit Lecture Modal ─────────────────────── -->
        <div class="formDiv--" id="editLectureForm" style="display:none;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <div class="form-title"><p>Edit Lecturer</p></div>
                <span class="close" id="closeEditLecture">&times;</span>
            </div>
            <input type="hidden" id="editLectureId">
            <input type="text" id="editLectureFirst" placeholder="First Name" required>
            <input type="text" id="editLectureLast" placeholder="Last Name" required>
            <input type="email" id="editLectureEmail" placeholder="Email Address" required>
            <input type="text" id="editLecturePhone" placeholder="Phone Number" required>
            <select id="editLectureFaculty">
                <option value="">Select Faculty</option>
                <?php
                $facultyNames = getFacultyNames();
                foreach ($facultyNames as $faculty) {
                    echo '<option value="' . $faculty["facultyCode"] . '">' . $faculty["facultyName"] . '</option>';
                }
                ?>
            </select>
            <button class="submit" id="saveLectureEdit" style="width:100%; margin-top:8px;">Save Changes</button>
        </div>

    </section>

    <script>
    // ── Edit Lecture ─────────────────────────────────────
    const overlay = document.getElementById('overlay');

    document.querySelectorAll('.edit-lecture').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('editLectureId').value    = this.dataset.id;
            document.getElementById('editLectureFirst').value = this.dataset.firstname;
            document.getElementById('editLectureLast').value  = this.dataset.lastname;
            document.getElementById('editLectureEmail').value = this.dataset.email;
            document.getElementById('editLecturePhone').value = this.dataset.phone;
            document.getElementById('editLectureFaculty').value = this.dataset.faculty;

            document.getElementById('editLectureForm').style.display = 'block';
            overlay.style.display = 'block';
        });
    });

    document.getElementById('closeEditLecture').addEventListener('click', () => {
        document.getElementById('editLectureForm').style.display = 'none';
        overlay.style.display = 'none';
    });
    overlay.addEventListener('click', () => {
        document.getElementById('editLectureForm').style.display = 'none';
        overlay.style.display = 'none';
    });

    document.getElementById('saveLectureEdit').addEventListener('click', () => {
        const id        = document.getElementById('editLectureId').value;
        const firstName = document.getElementById('editLectureFirst').value.trim();
        const lastName  = document.getElementById('editLectureLast').value.trim();
        const email     = document.getElementById('editLectureEmail').value.trim();
        const phoneNo   = document.getElementById('editLecturePhone').value.trim();
        const faculty   = document.getElementById('editLectureFaculty').value;

        if (!firstName || !lastName || !email || !phoneNo || !faculty) {
            alert('Please fill in all fields.'); return;
        }

        fetch('handle_edit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: 'lecture', id, firstName, lastName, email, phoneNo, faculty })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Update the row in-place
                const row = document.getElementById('rowlecture' + id);
                row.cells[0].textContent = firstName;
                row.cells[1].textContent = email;
                row.cells[2].textContent = phoneNo;
                row.cells[3].textContent = faculty;
                // Update data attributes on the edit icon
                const icon = row.querySelector('.edit-lecture');
                icon.dataset.firstname = firstName;
                icon.dataset.lastname  = lastName;
                icon.dataset.email     = email;
                icon.dataset.phone     = phoneNo;
                icon.dataset.faculty   = faculty;

                document.getElementById('editLectureForm').style.display = 'none';
                overlay.style.display = 'none';
            } else {
                alert('Error: ' + (data.error || 'Could not save changes.'));
            }
        });
    });
    </script>

    <?php js_asset(["admin_functions", "active_link", "delete_request", "script"]) ?>


</body>

</html>