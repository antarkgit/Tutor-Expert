<?php
session_start();
include_once 'db_connection.php';

// Check if tutor is logged in, if not, redirect to login page
if (!isset($_SESSION['tutor_id'])) {
    header("Location: login.php");
    exit();
}

$tutor_id = $_SESSION['tutor_id'];

// Fetch tutor information from the database
$tutor_query = $conn->prepare("SELECT * FROM tutors WHERE tutor_id = ?");
$tutor_query->bind_param('i', $tutor_id);
$tutor_query->execute();
$result = $tutor_query->get_result();

if ($result === false) {
    die("Database query failed: " . $conn->error);
}

$tutor_data = $result->fetch_assoc();

if (!$tutor_data) {
    die("Tutor not found with ID: " . $tutor_id);
}

// Initialize variables for form data
$name = $tutor_data['name'];
$username = $tutor_data['username'];
$phone = $tutor_data['phone'];
$expected_salary_6_days = $tutor_data['expected_salary_6_days'];
$expected_salary_3_days = $tutor_data['expected_salary_3_days'];
$class_3_5_subjects = [];
$class_6_8_subjects = [];
$class_9_10_subjects = [];
$class_11_12_subjects = [];
$home_district = $tutor_data['home_district'];
$photo = $tutor_data['photo'];

// Fetch tutor subjects from the database
$fetch_subjects_query = $conn->prepare("SELECT subject_name, class_range FROM subjects JOIN tutor_subjects ON subjects.subject_id = tutor_subjects.subject_id WHERE tutor_id = ?");
$fetch_subjects_query->bind_param('i', $tutor_id);
$fetch_subjects_query->execute();
$subjects_result = $fetch_subjects_query->get_result();

if ($subjects_result) {
    while ($subject_row = $subjects_result->fetch_assoc()) {
        $subject_name = $subject_row['subject_name'];
        $class_range = $subject_row['class_range'];

        switch ($class_range) {
            case '3-5':
                $class_3_5_subjects[] = $subject_name;
                break;
            case '6-8':
                $class_6_8_subjects[] = $subject_name;
                break;
            case '9-10':
                $class_9_10_subjects[] = $subject_name;
                break;
            case '11-12':
                $class_11_12_subjects[] = $subject_name;
                break;
            default:
                // Handle unexpected class range if needed
                break;
        }
    }
} else {
    echo "Failed to fetch tutor subjects: " . $conn->error;
}

// Update profile form submission handling
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = $_POST['name'];
        $username = $_POST['username'];
        $phone = $_POST['phone'];
        $expected_salary_6_days = $_POST['expected_salary_6_days'];
        $expected_salary_3_days = $_POST['expected_salary_3_days'];
        $class_3_5_subjects = isset($_POST['class_3_5_subjects']) ? $_POST['class_3_5_subjects'] : [];
        $class_6_8_subjects = isset($_POST['class_6_8_subjects']) ? $_POST['class_6_8_subjects'] : [];
        $class_9_10_subjects = isset($_POST['class_9_10_subjects']) ? $_POST['class_9_10_subjects'] : [];
        $class_11_12_subjects = isset($_POST['class_11_12_subjects']) ? $_POST['class_11_12_subjects'] : [];
        $home_district = $_POST['home_district'];
        $photo = $tutor_data['photo']; // Keep the existing photo if not updated

        // Handle photo upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            if ($_FILES['photo']['size'] < 100 * 1024) { // 100 KB
                $target_dir = "uploads/";
                $target_file = $target_dir . basename($_FILES["photo"]["name"]);
                if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                    $photo = $target_file;
                } else {
                    $error_message = "Error uploading photo.";
                }
            } else {
                $error_message = "Photo must be less than 100KB.";
            }
        }

        // Update tutor information
        $update_query = $conn->prepare("UPDATE tutors SET name = ?, username = ?, phone = ?, expected_salary_6_days = ?, expected_salary_3_days = ?, home_district = ?, photo = ? WHERE tutor_id = ?");
        $update_query->bind_param('sssiissi', $name, $username, $phone, $expected_salary_6_days, $expected_salary_3_days, $home_district, $photo, $tutor_id);

        if ($update_query->execute()) {
            // Remove existing tutor subjects
            $delete_subjects_query = $conn->prepare("DELETE FROM tutor_subjects WHERE tutor_id = ?");
            $delete_subjects_query->bind_param('i', $tutor_id);
            $delete_subjects_query->execute();

            // Insert updated tutor subjects
            $subjects = [
                '3-5' => $class_3_5_subjects,
                '6-8' => $class_6_8_subjects,
                '9-10' => $class_9_10_subjects,
                '11-12' => $class_11_12_subjects
            ];

            foreach ($subjects as $class_range => $subject_list) {
                if (is_array($subject_list)) {
                    foreach ($subject_list as $subject_name) {
                        $subject_name = trim($subject_name);
                        $subject_name = $conn->real_escape_string($subject_name);
                        $subject_query = "SELECT subject_id FROM subjects WHERE subject_name = '$subject_name'";
                        $subject_result = $conn->query($subject_query);
                        if ($subject_result->num_rows > 0) {
                            $subject_row = $subject_result->fetch_assoc();
                            $subject_id = $subject_row['subject_id'];
                            $sql = "INSERT INTO tutor_subjects (tutor_id, subject_id, class_range) VALUES ('$tutor_id', '$subject_id', '$class_range')";
                            if (!$conn->query($sql)) {
                                $error_message = "Error updating tutor subjects.";
                            }
                        }
                    }
                }
            }

            $success_message = "Profile updated successfully!";
            // Refresh the tutor data after update
            $tutor_query->execute();
            $tutor_data = $tutor_query->get_result()->fetch_assoc();
        } else {
            $error_message = "Failed to update profile: " . $conn->error;
        }
    }

    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (password_verify($current_password, $tutor_data['password'])) {
            if ($new_password == $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_password_query = $conn->prepare("UPDATE tutors SET password = ? WHERE tutor_id = ?");
                $update_password_query->bind_param('si', $hashed_password, $tutor_id);
                if ($update_password_query->execute()) {
                    $success_message = "Password updated successfully!";
                } else {
                    $error_message = "Failed to update password: " . $conn->error;
                }
            } else {
                $error_message = "New passwords do not match.";
            }
        } else {
            $error_message = "Current password is incorrect.";
        }
    }

    if (isset($_POST['delete_account'])) {
        $confirm_delete = $_POST['confirm_delete'];
    
        if ($confirm_delete === 'DELETE') {
            // Delete related tutor subjects first
            $delete_subjects_query = $conn->prepare("DELETE FROM tutor_subjects WHERE tutor_id = ?");
            $delete_subjects_query->bind_param('i', $tutor_id);
    
            if ($delete_subjects_query->execute()) {
                // Now delete the tutor's account
                $delete_tutor_query = $conn->prepare("DELETE FROM tutors WHERE tutor_id = ?");
                $delete_tutor_query->bind_param('i', $tutor_id);
    
                if ($delete_tutor_query->execute()) {
                    session_destroy();
                    header("Location: goodbye.php"); // Redirect to a goodbye page or home page
                    exit();
                } else {
                    $error_message = "Failed to delete account: " . $conn->error;
                }
            } else {
                $error_message = "Failed to delete tutor subjects: " . $conn->error;
            }
        } else {
            $error_message = "Please type 'DELETE' to confirm.";
        }
    }
    
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="logo.png" type="image/x-icon">
    <title>Tutor Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
    
</head>
<body>

    <!-- Header Section -->
    <header class="header">
        <div class="container">
            <h1>Tutor Dashboard</h1>
            <nav>
                <ul class="nav">
                    
                    <li class="nav-item"><a href="#profile" class="nav-link">Profile</a></li>
                    <li class="nav-item"><a href="#change_password" class="nav-link">Change Password</a></li>
                    <li class="nav-item"><a href="#delete_account" class="nav-link">Delete Account</a></li>
                    
                    <li class="nav-item"><a href="logout.php" class="nav-link">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content Section -->
    <div class="container mt-5">
        <h2>Welcome, <?php echo htmlspecialchars($name); ?>!</h2>

        <!-- Profile Section -->
        <section id="profile">
            <h3>Your Profile</h3>
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php elseif (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" name="username" id="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="text" name="phone" id="phone" class="form-control" value="<?php echo htmlspecialchars($phone); ?>" required>
                </div>
                <div class="form-group">
                    <label for="expected_salary_6_days">Expected Salary (6 days):</label>
                    <input type="number" name="expected_salary_6_days" id="expected_salary_6_days" class="form-control" value="<?php echo htmlspecialchars($expected_salary_6_days); ?>" required>
                </div>
                <div class="form-group">
                    <label for="expected_salary_3_days">Expected Salary (3 days):</label>
                    <input type="number" name="expected_salary_3_days" id="expected_salary_3_days" class="form-control" value="<?php echo htmlspecialchars($expected_salary_3_days); ?>" required>
                </div>
                <div class="form-group">
                    <label for="class_3_5_subjects">Class (3-5) Subjects:</label>
                    <div>
                        <input type="checkbox" name="class_3_5_subjects[]" value="Math" <?php if (in_array("Math", $class_3_5_subjects)) echo "checked"; ?>> Math<br>
                        <input type="checkbox" name="class_3_5_subjects[]" value="Science" <?php if (in_array("Science", $class_3_5_subjects)) echo "checked"; ?>> Science<br>
                    <input type="checkbox" name="class_3_5_subjects[]" value="Bangla"<?php if (in_array("Bangla", $class_3_5_subjects)) echo "checked"; ?>> Bangla<br>
                    <input type="checkbox" name="class_3_5_subjects[]" value="English"<?php if (in_array("English", $class_3_5_subjects)) echo "checked"; ?>> English<br>
                    <input type="checkbox" name="class_3_5_subjects[]" value="All Subjects"<?php if (in_array("All Subjects", $class_3_5_subjects)) echo "checked"; ?>> All Subjects<br>
                    <input type="checkbox" name="class_3_5_subjects[]" value="None"<?php if (in_array("None", $class_3_5_subjects)) echo "checked"; ?>> None<br>
                    </div>
                </div>
                <div class="form-group">
                    <label for="class_6_8_subjects">Class (6-8) Subjects:</label>
                    <div>
                        <input type="checkbox" name="class_6_8_subjects[]" value="Math" <?php if (in_array("Math", $class_6_8_subjects)) echo "checked"; ?>> Math<br>
                        <input type="checkbox" name="class_6_8_subjects[]" value="Science" <?php if (in_array("Science", $class_6_8_subjects)) echo "checked"; ?>> Science<br>
                    <input type="checkbox" name="class_6_8_subjects[]" value="Bangla"<?php if (in_array("Bangla", $class_6_8_subjects)) echo "checked"; ?>> Bangla<br>
                    <input type="checkbox" name="class_6_8_subjects[]" value="English"<?php if (in_array("English", $class_6_8_subjects)) echo "checked"; ?>> English<br>
                    <input type="checkbox" name="class_6_8_subjects[]" value="All Subjects"<?php if (in_array("All Subjects", $class_6_8_subjects)) echo "checked"; ?>> All Subjects<br>
                    <input type="checkbox" name="class_6_8_subjects[]" value="None"<?php if (in_array("None", $class_6_8_subjects)) echo "checked"; ?>> None<br>
                    </div>
                </div>
                <div class="form-group">
                    <label for="class_9_10_subjects">Class (9-10) Subjects:</label>
                    <div>
                        <input type="checkbox" name="class_9_10_subjects[]" value="Math" <?php if (in_array("Math", $class_9_10_subjects)) echo "checked"; ?>> Math<br>
                        <input type="checkbox" name="class_9_10_subjects[]" value="Physics" <?php if (in_array("Physics", $class_9_10_subjects)) echo "checked"; ?>> Physics<br>
                    
                    <input type="checkbox" name="class_9_10_subjects[]" value="Biology" <?php if (in_array("Biology", $class_9_10_subjects)) echo "checked"; ?>> Biology<br>
                    <input type="checkbox" name="class_9_10_subjects[]" value="Chemistry" <?php if (in_array("Chemistry", $class_9_10_subjects)) echo "checked"; ?>> Chemistry<br>
                    <input type="checkbox" name="class_9_10_subjects[]" value="Higher Math" <?php if (in_array("Higher Math", $class_9_10_subjects)) echo "checked"; ?>> Higher Math<br>
                    <input type="checkbox" name="class_9_10_subjects[]" value="Science" <?php if (in_array("Science", $class_9_10_subjects)) echo "checked"; ?>> Science<br>
                    <input type="checkbox" name="class_9_10_subjects[]" value="Bangla" <?php if (in_array("Bangla", $class_9_10_subjects)) echo "checked"; ?>> Bangla<br>
                    <input type="checkbox" name="class_9_10_subjects[]" value="English" <?php if (in_array("English", $class_9_10_subjects)) echo "checked"; ?>> English<br>
                    <input type="checkbox" name="class_9_10_subjects[]" value="All Subjects" <?php if (in_array("All Subjects", $class_9_10_subjects)) echo "checked"; ?>> All Subjects<br>
                    <input type="checkbox" name="class_9_10_subjects[]" value="None" <?php if (in_array("None", $class_9_10_subjects)) echo "checked"; ?>> None<br>
                    </div>
                </div>
                <div class="form-group">
                    <label for="class_11_12_subjects">Class (11-12) Subjects:</label>
                    <div>
                        <input type="checkbox" name="class_11_12_subjects[]" value="Math" <?php if (in_array("Math", $class_11_12_subjects)) echo "checked"; ?>> Math<br>
                        <input type="checkbox" name="class_11_12_subjects[]" value="Physics" <?php if (in_array("Physics", $class_11_12_subjects)) echo "checked"; ?>> Physics<br>
                        <input type="checkbox" name="class_11_12_subjects[]" value="Chemistry"<?php if (in_array("Chemistry", $class_11_12_subjects)) echo "checked"; ?>> Chemistry<br> 
                    <input type="checkbox" name="class_11_12_subjects[]" value="Biology"<?php if (in_array("Biology", $class_11_12_subjects)) echo "checked"; ?>> Biology<br>
                    <input type="checkbox" name="class_11_12_subjects[]" value="Higher Math"<?php if (in_array("Higher Math", $class_11_12_subjects)) echo "checked"; ?>> Higher Math<br>
                    <input type="checkbox" name="class_11_12_subjects[]" value="ICT"<?php if (in_array("ICT", $class_11_12_subjects)) echo "checked"; ?>> ICT<br>
                    <input type="checkbox" name="class_11_12_subjects[]" value="Science"<?php if (in_array("Science", $class_11_12_subjects)) echo "checked"; ?>> Science<br>
                    <input type="checkbox" name="class_11_12_subjects[]" value="Bangla"<?php if (in_array("Bangla", $class_11_12_subjects)) echo "checked"; ?>> Bangla<br>
                    <input type="checkbox" name="class_11_12_subjects[]" value="English"<?php if (in_array("Bangla", $class_11_12_subjects)) echo "checked"; ?>> English<br>
                    <input type="checkbox" name="class_11_12_subjects[]" value="All Subjects"<?php if (in_array("All Subjects", $class_11_12_subjects)) echo "checked"; ?>> All Subjects<br>
                    <input type="checkbox" name="class_11_12_subjects[]" value="None"<?php if (in_array("None", $class_11_12_subjects)) echo "checked"; ?>> None<br>
                    </div>
                <div class="form-group">
                    <label for="home_district">Home District:</label>
                    <input type="text" name="home_district" id="home_district" class="form-control" value="<?php echo htmlspecialchars($home_district); ?>" required>
                </div>
                <div class="form-group">
                    <label for="photo">Photo (max 100KB):</label>
                    <input type="file" name="photo" id="photo" class="form-control">
                    <?php if ($photo): ?>
                        <img src="<?php echo htmlspecialchars($photo); ?>" alt="Profile Photo" class="img-thumbnail mt-2" style="max-width: 150px;">
                    <?php endif; ?>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
            </form>
        </section>

        <!-- Change Password Section -->
        <section id="change_password" class="mt-5">
            <h3>Change Password</h3>
            <?php if (isset($password_success_message)): ?>
                <div class="alert alert-success"><?php echo $password_success_message; ?></div>
            <?php elseif (isset($password_error_message)): ?>
                <div class="alert alert-danger"><?php echo $password_error_message; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password">Current Password:</label>
                    <input type="password" name="current_password" id="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
            </form>
        </section>

        <!-- Delete Account Section -->
        <section id="delete_account" class="mt-5">
            <h3>Delete Account</h3>
            <p>Warning: Deleting your account will remove all your data permanently and cannot be undone.</p>
            <?php if (isset($delete_success_message)): ?>
                <div class="alert alert-success"><?php echo $delete_success_message; ?></div>
            <?php elseif (isset($delete_error_message)): ?>
                <div class="alert alert-danger"><?php echo $delete_error_message; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="confirm_delete">Type "DELETE" to confirm:</label>
                    <input type="text" name="confirm_delete" id="confirm_delete" class="form-control" required>
                </div>
                <button type="submit" name="delete_account" class="btn btn-danger">Delete Account</button>
            </form>
        </section>

    </div>

    <!-- Footer Section -->
    <footer class="footer mt-5">
        <div class="container">
            <p>&copy; 2024 Tutor Expert. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>
