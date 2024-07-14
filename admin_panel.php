<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

include_once 'db_connection.php';

// Approve tutor
if (isset($_POST['approve'])) {
    $tutor_id = $_POST['tutor_id'];
    $query = "UPDATE tutors SET approved = 1 WHERE tutor_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $tutor_id);
    if ($stmt->execute()) {
        echo '<script>alert("Tutor approved successfully!");</script>';
    } else {
        echo "Error: " . $conn->error;
    }
}

// Delete tutor
if (isset($_POST['delete'])) {
    $tutor_id = $_POST['tutor_id'];

    // Delete from tutor_subjects first
    $delete_subjects_query = "DELETE FROM tutor_subjects WHERE tutor_id = ?";
    $stmt = $conn->prepare($delete_subjects_query);
    $stmt->bind_param("i", $tutor_id);
    if ($stmt->execute()) {
        // Then delete from tutor_departments
        $delete_departments_query = "DELETE FROM tutor_departments WHERE tutor_id = ?";
        $stmt = $conn->prepare($delete_departments_query);
        $stmt->bind_param("i", $tutor_id);
        if ($stmt->execute()) {
            // Then delete from tutors table
            $delete_query = "DELETE FROM tutors WHERE tutor_id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $tutor_id);
            if ($stmt->execute()) {
                echo '<script>alert("Tutor deleted successfully!");</script>';
            } else {
                echo "Error deleting tutor: " . $conn->error;
            }
        } else {
            echo "Error deleting tutor departments: " . $conn->error;
        }
    } else {
        echo "Error deleting tutor subjects: " . $conn->error;
    }
}

// Fetch unapproved tutors
$unapproved_tutors_query = "
    SELECT 
        tutors.tutor_id, tutors.name, tutors.phone, tutors.year, 
        tutors.expected_salary_6_days, tutors.expected_salary_3_days, 
        universities.university_name, departments.department_name
    FROM 
        tutors
    JOIN 
        universities ON tutors.university_id = universities.university_id
    JOIN 
        departments ON tutors.department_id = departments.department_id
    WHERE 
        tutors.approved = 0
";
$unapproved_tutors_result = $conn->query($unapproved_tutors_query);
if (!$unapproved_tutors_result) {
    die("Error fetching unapproved tutors: " . $conn->error);
}

// Fetch registered tutors with subjects
$registered_tutors_query = "
    SELECT 
        tutors.tutor_id, tutors.name, tutors.phone, tutors.home_district,
        tutors.expected_salary_6_days, tutors.expected_salary_3_days,
        universities.university_name, departments.department_name
    FROM 
        tutors
    JOIN 
        universities ON tutors.university_id = universities.university_id
    JOIN 
        departments ON tutors.department_id = departments.department_id
    WHERE 
        tutors.approved = 1
";
$registered_tutors_result = $conn->query($registered_tutors_query);
if (!$registered_tutors_result) {
    die("Error fetching registered tutors: " . $conn->error);
}

// Fetch tutors by ID
if (isset($_POST['search'])) {
    $search_tutor_id = $_POST['search_tutor_id'];
    $search_query = "
        SELECT 
            tutors.tutor_id, tutors.name, tutors.phone, tutors.home_district,
            tutors.expected_salary_6_days, tutors.expected_salary_3_days,
            universities.university_name, departments.department_name
        FROM 
            tutors
        JOIN 
            universities ON tutors.university_id = universities.university_id
        JOIN 
            departments ON tutors.department_id = departments.department_id
        WHERE 
            tutors.tutor_id = ?
    ";
    $stmt = $conn->prepare($search_query);
    if ($stmt === false) {
        die("Error preparing search query: " . $conn->error);
    }
    $stmt->bind_param("i", $search_tutor_id);
    $stmt->execute();
    $search_result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="logo.png" type="image/x-icon">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* General styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }

        h2, h3 {
            text-align: center;
            margin-bottom: 30px;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }

        /* Table styles */
        .table {
            width: 100%;
            margin-bottom: 30px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        .table th, .table td {
            padding: 12px;
            vertical-align: middle;
        }

        .table th {
            background-color: #007bff;
            color: #fff;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f8f9fa;
        }

        /* Form styles */
        .form-group {
            margin-bottom: 20px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    
    <div class="container mt-5">
        <h2 class="mb-4" style="text-align:center;">Admin Panel</h2>
        
        <h3>Approve Tutors</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Tutor ID</th>
                    <th>Name</th>
                    <th>Phone Number</th>
                    <th>University</th>
                    <th>Department</th>
                    <th>Year</th>
                    <th>Expected Salary (6 Days)</th>
                    <th>Expected Salary (3 Days)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $unapproved_tutors_result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['tutor_id']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['phone']; ?></td>
                        <td><?php echo $row['university_name']; ?></td>
                        <td><?php echo $row['department_name']; ?></td>
                        <td><?php echo $row['year']; ?></td>
                        <td><?php echo $row['expected_salary_6_days']; ?></td>
                        <td><?php echo $row['expected_salary_3_days']; ?></td>
                        <td>
                            <form method="post" action="">
                                <input type="hidden" name="tutor_id" value="<?php echo $row['tutor_id']; ?>">
                                <button type="submit" name="approve" class="btn btn-success">Approve</button>
                                <button type="submit" name="delete" class="btn btn-danger ml-2">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        
        <h3>Search Tutor by ID</h3>
        <form method="post" action="">
            <div class="form-group">
                <label for="search_tutor_id">Tutor ID:</label>
                <input type="text" class="form-control" id="search_tutor_id" name="search_tutor_id" required>
            </div>
            <button type="submit" name="search" class="btn btn-primary">Search</button>
        </form>

        <?php if (isset($search_result) && $search_result->num_rows > 0) { ?>
            <h3 class="mt-5">Search Results</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Tutor ID</th>
                        <th>Name</th>
                        <th>Phone Number</th>
                        <th>Home District</th>
                        <th>Expected Salary (6 Days)</th>
                        <th>Expected Salary (3 Days)</th>
                        <th>Department</th>
                        <th>University</th>
                        <th>Subjects</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $search_result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $row['tutor_id']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['phone']; ?></td>
                            <td><?php echo $row['home_district']; ?></td>
                            <td><?php echo $row['expected_salary_6_days']; ?></td>
                            <td><?php echo $row['expected_salary_3_days']; ?></td>
                            <td><?php echo $row['department_name']; ?></td>
                            <td><?php echo $row['university_name']; ?></td>
                            <td>
                                <?php
                                $subjectSql = "
                                    SELECT subjects.subject_name, tutor_subjects.class_range
                                    FROM tutor_subjects 
                                    JOIN subjects ON tutor_subjects.subject_id = subjects.subject_id 
                                    WHERE tutor_subjects.tutor_id = ?
                                    ORDER BY FIELD(tutor_subjects.class_range, '3-5', '6-8', '9-10', '11-12'), subjects.subject_name
                                ";
                                $subjectStmt = $conn->prepare($subjectSql);
                                $subjectStmt->bind_param("i", $row['tutor_id']);
                                $subjectStmt->execute();
                                $subjectResult = $subjectStmt->get_result();

                                $subjectsByClassRange = [
                                    '3-5' => [],
                                    '6-8' => [],
                                    '9-10' => [],
                                    '11-12' => [],
                                ];

                                while ($subjectRow = $subjectResult->fetch_assoc()) {
                                    $classRange = $subjectRow['class_range'];
                                    $subjectName = $subjectRow['subject_name'];
                                    $subjectsByClassRange[$classRange][] = $subjectName;
                                }

                                if (!empty($subjectsByClassRange['3-5'])) {
                                    echo "<strong>Class 3-5:</strong> ";
                                    echo implode(", ", $subjectsByClassRange['3-5']) . "<br>";
                                }
                                if (!empty($subjectsByClassRange['6-8'])) {
                                    echo "<strong>Class 6-8:</strong> ";
                                    echo implode(", ", $subjectsByClassRange['6-8']) . "<br>";
                                }
                                if (!empty($subjectsByClassRange['9-10'])) {
                                    echo "<strong>Class 9-10:</strong> ";
                                    echo implode(", ", $subjectsByClassRange['9-10']) . "<br>";
                                }
                                if (!empty($subjectsByClassRange['11-12'])) {
                                    echo "<strong>Class 11-12:</strong> ";
                                    echo implode(", ", $subjectsByClassRange['11-12']) . "<br>";
                                }
                                ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } elseif (isset($search_result) && $search_result->num_rows === 0) { ?>
            <p>No tutor found with that ID.</p>
        <?php } ?>

        <h3>Registered Tutors Information</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Tutor ID</th>
                    <th>Name</th>
                    <th>Phone Number</th>
                    <th>Home District</th>
                    <th>Expected Salary (6 Days)</th>
                    <th>Expected Salary (3 Days)</th>
                    <th>Department</th>
                    <th>University</th>
                    <th>Subjects</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $registered_tutors_result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['tutor_id']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['phone']; ?></td>
                    <td><?php echo $row['home_district']; ?></td>
                    <td><?php echo $row['expected_salary_6_days']; ?></td>
                    <td><?php echo $row['expected_salary_3_days']; ?></td>
                    <td><?php echo $row['department_name']; ?></td>
                    <td><?php echo $row['university_name']; ?></td>
                    <td>
                        <?php
                        $subjectSql = "
                            SELECT subjects.subject_name, tutor_subjects.class_range
                            FROM tutor_subjects 
                            JOIN subjects ON tutor_subjects.subject_id = subjects.subject_id 
                            WHERE tutor_subjects.tutor_id = ?
                            ORDER BY FIELD(tutor_subjects.class_range, '3-5', '6-8', '9-10', '11-12'), subjects.subject_name
                        ";
                        $subjectStmt = $conn->prepare($subjectSql);
                        $subjectStmt->bind_param("i", $row['tutor_id']);
                        $subjectStmt->execute();
                        $subjectResult = $subjectStmt->get_result();

                        $subjectsByClassRange = [
                            '3-5' => [],
                            '6-8' => [],
                            '9-10' => [],
                            '11-12' => [],
                        ];

                        while ($subjectRow = $subjectResult->fetch_assoc()) {
                            $classRange = $subjectRow['class_range'];
                            $subjectName = $subjectRow['subject_name'];
                            $subjectsByClassRange[$classRange][] = $subjectName;
                        }

                        if (!empty($subjectsByClassRange['3-5'])) {
                            echo "<strong>Class 3-5:</strong> ";
                            echo implode(", ", $subjectsByClassRange['3-5']) . "<br>";
                        }
                        if (!empty($subjectsByClassRange['6-8'])) {
                            echo "<strong>Class 6-8:</strong> ";
                            echo implode(", ", $subjectsByClassRange['6-8']) . "<br>";
                        }
                        if (!empty($subjectsByClassRange['9-10'])) {
                            echo "<strong>Class 9-10:</strong> ";
                            echo implode(", ", $subjectsByClassRange['9-10']) . "<br>";
                        }
                        if (!empty($subjectsByClassRange['11-12'])) {
                            echo "<strong>Class 11-12:</strong> ";
                            echo implode(", ", $subjectsByClassRange['11-12']) . "<br>";
                        }
                        ?>
                    </td>
                    <td>
                        <form method="post" action="">
                            <input type="hidden" name="tutor_id" value="<?php echo $row['tutor_id']; ?>">
                            <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <div class="mt-5">
            <a href="admin_logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
   <!--<?php include_once 'footer.php'; ?>-->
</body>
</html>

<?php
if (isset($_POST['approve'])) {
    $tutor_id = $_POST['tutor_id'];
    $approveSql = "UPDATE tutors SET approved = 1 WHERE tutor_id = ?";
    $stmt = $conn->prepare($approveSql);
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_panel.php");
    exit();
}

if (isset($_POST['delete'])) {
    $tutor_id = $_POST['tutor_id'];
    $deleteSql = "DELETE FROM tutors WHERE tutor_id = ?";
    $stmt = $conn->prepare($deleteSql);
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_panel.php");
    exit();
}

if (isset($_POST['search'])) {
    $search_tutor_id = $_POST['search_tutor_id'];
    $searchSql = "
        SELECT t.tutor_id, t.name, t.phone, t.home_district, t.expected_salary_6_days, t.expected_salary_3_days, 
               d.department_name, u.university_name 
        FROM tutors t 
        JOIN departments d ON t.department_id = d.department_id 
        JOIN universities u ON t.university_id = u.university_id 
        WHERE t.tutor_id = ?
    ";
    $stmt = $conn->prepare($searchSql);
    $stmt->bind_param("i", $search_tutor_id);
    $stmt->execute();
    $search_result = $stmt->get_result();
    $stmt->close();
}
?>
