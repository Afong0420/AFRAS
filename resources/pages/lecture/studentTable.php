<?php
/*
 * studentTable.php — included by manageFolder.php via ob_start()
 * FIX: tbody id changed to "attendanceTableBody" (not "studentTableContainer")
 *      to avoid conflict with the outer wrapper div in home.php.
 * FIX: added space between firstName and lastName.
 */
?>

<div class="table">
    <table>
        <thead>
            <tr>
                <th>Registration No</th>
                <th>Name</th>
                <th>Course</th>
                <th>Unit</th>
                <th>Venue</th>
                <th>Attendance</th>
            </tr>
        </thead>
        <tbody id="attendanceTableBody">
            <?php
            if (isset($_POST['courseID']) && isset($_POST['unitID']) && isset($_POST['venueID'])) {
                $courseID = $_POST['courseID'];
                $unitID   = $_POST['unitID'];
                $venueID  = $_POST['venueID'];

                $sql    = "SELECT * FROM students WHERE courseCode = '$courseID'";
                $result = fetch($sql);

                if ($result) {
                    foreach ($result as $row) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row["registrationNumber"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["firstName"]) . " " . htmlspecialchars($row["lastName"]) . "</td>";
                        echo "<td>" . htmlspecialchars($courseID) . "</td>";
                        echo "<td>" . htmlspecialchars($unitID) . "</td>";
                        echo "<td>" . htmlspecialchars($venueID) . "</td>";
                        echo "<td>Absent</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No records found</td></tr>";
                }
            }
            ?>
        </tbody>
    </table>
</div>
