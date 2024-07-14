<?php
include_once 'db_connection.php';

// Fetch all divisions
$divisions_query = "SELECT * FROM divisions";
$divisions = $conn->query($divisions_query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capture form data
    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $gender = $_POST['gender'];
    $division_id = $_POST['division'];
    $district_id = $_POST['district'];
    $university_id = $_POST['university'];
    $department_id = $_POST['department'];
    $year = $_POST['year'];
    $expected_salary_6_days = $_POST['expected_salary_6_days'];
    $expected_salary_3_days = $_POST['expected_salary_3_days'];
    $class_3_5_subjects = isset($_POST['class_3_5_subjects']) ? $_POST['class_3_5_subjects'] : [];
    $class_6_8_subjects = isset($_POST['class_6_8_subjects']) ? $_POST['class_6_8_subjects'] : [];
    $class_9_10_subjects = isset($_POST['class_9_10_subjects']) ? $_POST['class_9_10_subjects'] : [];
    $class_11_12_subjects = isset($_POST['class_11_12_subjects']) ? $_POST['class_11_12_subjects'] : [];
    $phone = $_POST['phone'];
    $home_district = $_POST['home_district'];
    $photo = '';
    $experience = $_POST['experience']; // New field for experience as a tutor

    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        if ($_FILES['photo']['size'] < 300 * 1024) { // 100 KB
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES["photo"]["name"]);
            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                $photo = $target_file;
            } else {
                echo "Error uploading photo.";
                exit();
            }
        } else {
            echo "Photo must be less than 300KB.";
            exit();
        }
    }

    // Check if the checkbox is checked
    if (!isset($_POST['agree'])) {
        echo '<script>alert("Please agree to the terms before registering.");</script>';
        exit();
    }

    // Insert tutor details into the tutors table
    $sql = "INSERT INTO tutors (name, username, password, gender, university_id, district_id, department_id, year, expected_salary_6_days, expected_salary_3_days, approved, photo, phone, home_district, experience) 
            VALUES ('$name', '$username', '$password', '$gender', '$university_id', '$district_id', '$department_id', '$year', '$expected_salary_6_days', '$expected_salary_3_days', 0, '$photo', '$phone', '$home_district', '$experience')";
    if ($conn->query($sql) === TRUE) {
        $tutor_id = $conn->insert_id;

        // Insert tutor subjects
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
                            echo "Error: " . $sql . "<br>" . $conn->error;
                        }
                    }
                }
            }
        }

        echo '<script>alert("Tutor registered successfully and your account will be approved by the Admin within 24 hours. Please wait and login to your account after 24 hours.");</script>';
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="logo.png" type="image/x-icon">
    <title>Tutor Expert</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .btn-large {
            padding: 10px 40px;
            font-size: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include_once 'header.php'; ?>

    <div class="container mt-5">
        <h2 class="text-center">Register yourself as a Tutor</h2>
        <form method="POST" action="register.php" enctype="multipart/form-data" class="mt-4">
            <div class="form-group">
            <strong><label for="name">Name:</label></strong>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="form-group">
            <strong><label for="username">Username:</label></strong>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
            <strong><label for="password">Password:</label></strong>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="form-group">
                <strong><label for="gender">Gender:</label></strong>
                <select class="form-control" id="gender" name="gender" required>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <div class="form-group">
            <strong><label for="photo">Photo:</label></strong>
                <input type="file" class="form-control-file" id="photo" name="photo">
                <p>Photo must be less than 300KB.</p>
            </div>
            <div class="form-group">
            <strong> <label for="phone">Phone Number:</label></strong>
                <input type="tel" class="form-control" id="phone" name="phone" required>
            </div>
            <div class="form-group">
            <strong><label for="home_district">Home District:</label></strong>
                <input type="text" class="form-control" id="home_district" name="home_district" required>
            </div>
            <div class="form-group">
                <strong><p>Select the location of your University</p></strong>
                <label for="division">Location of your University (Division):</label>
                <select class="form-control" id="division" name="division" required>
                    <option value="">Select the Division of your University</option>
                    <?php while($row = $divisions->fetch_assoc()): ?>
                        <option value="<?php echo $row['division_id']; ?>"><?php echo $row['division_name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="district">Location of your University (District):</label>
                <select class="form-control" id="district" name="district" required>
                    <option value="">Select the District of your University</option>
                </select>
            </div>
            <div class="form-group">
                <label for="university">Institution:</label>
                <select class="form-control" id="university" name="university" required>
                    <option value="">Select University</option>
                </select>
            </div>
            <div class="form-group">
                <label for="department">Department:</label>
                <select class="form-control" id="department" name="department" required>
                    <option value="">Select Department</option>
                </select>
            </div>
            <div class="form-group">
                <strong><label for="year">Year:</label></strong>
                <div>
                    <input type="radio" name="year" value="1st Year"> 1st Year<br>
                    <input type="radio" name="year" value="2nd Year"> 2nd Year<br>
                    <input type="radio" name="year" value="3rd Year"> 3rd Year<br>
                    <input type="radio" name="year" value="4th Year"> 4th Year<br>
                    <input type="radio" name="year" value="B.Sc./Honours Complete"> B.Sc./Honours Complete<br>
                </div>
            </div>
            <div class="form-group">
            <strong><label for="expected_salary">Expected Salary (6 days a week):</label></strong>
            <p>Lower salary range will increase the probability of getting tuition.</p>
                <input type="number" class="form-control" id="expected_salary_6_days" name="expected_salary_6_days" required>
               
            </div>
            <div class="form-group">
            <strong><label for="expected_salary_3_days">Expected Salary (3 days a week):</label></strong>
            <p>Lower salary range will increase the probability of getting tuition.</p>
                <input type="number" class="form-control" id="expected_salary_3_days" name="expected_salary_3_days" required>
            </div>
            <div class="form-group">
            <strong><label for="class_3_5_subjects">Interested Subject of Class 3-5</strong> (If no interested, select 'None')</label>
                <div class="subjects-selection-group">
                    <input type="checkbox" name="class_3_5_subjects[]" value="Math"> Math<br>
                    <input type="checkbox" name="class_3_5_subjects[]" value="Science"> Science<br>
                    <input type="checkbox" name="class_3_5_subjects[]" value="Bangla"> Bangla<br>
                    <input type="checkbox" name="class_3_5_subjects[]" value="English"> English<br>
                    <input type="checkbox" name="class_3_5_subjects[]" value="All Subjects"> All Subjects<br>
                    <input type="checkbox" name="class_3_5_subjects[]" value="None"> None<br>
                </div>
            </div>
            <div class="form-group">
            <strong><label for="class_6_8_subjects">Interested Subject of Class 6-8 </strong> (If no interested, select 'None')
            </label>
                <div class="subjects-selection-group">
                    <input type="checkbox" name="class_6_8_subjects[]" value="Math"> Math<br>
                    <input type="checkbox" name="class_6_8_subjects[]" value="Science"> Science<br>
                    <input type="checkbox" name="class_6_8_subjects[]" value="Bangla"> Bangla<br>
                    <input type="checkbox" name="class_6_8_subjects[]" value="English"> English<br>
                    <input type="checkbox" name="class_6_8_subjects[]" value="All Subjects"> All Subjects<br>
                    <input type="checkbox" name="class_6_8_subjects[]" value="None"> None<br>
                </div>
            </div>
            <div class="form-group">
            <strong><label for="class_9_10_subjects">Interested Subject of Class 9-10</strong> (If no interested, select 'None')</label>
                <div class="subjects-selection-group">
                    <input type="checkbox" name="class_9_10_subjects[]" value="Math"> Math<br>
                    <input type="checkbox" name="class_9_10_subjects[]" value="Physics"> Physics<br>
                    <input type="checkbox" name="class_9_10_subjects[]" value="Chemistry"> Chemistry<br>
                    <input type="checkbox" name="class_9_10_subjects[]" value="Biology"> Biology<br>
                    <input type="checkbox" name="class_9_10_subjects[]" value="Higher Math"> Higher Math<br>
                    <input type="checkbox" name="class_9_10_subjects[]" value="Science"> Science<br>
                    <input type="checkbox" name="class_9_10_subjects[]" value="Bangla"> Bangla<br>
                    <input type="checkbox" name="class_9_10_subjects[]" value="English"> English<br>
                    <input type="checkbox" name="class_9_10_subjects[]" value="All Subjects"> All Subjects<br>
                    <input type="checkbox" name="class_9_10_subjects[]" value="None"> None<br>
                </div>
            </div>
            <div class="form-group">
            <strong><label for="class_11_12_subjects">Interested Subject of Class 11-12</strong> (If no interested, select 'None')</label>
                <div class="subjects-selection-group">
                    <input type="checkbox" name="class_11_12_subjects[]" value="Math"> Math<br>
                    <input type="checkbox" name="class_11_12_subjects[]" value="Physics"> Physics<br>
                    <input type="checkbox" name="class_11_12_subjects[]" value="Chemistry"> Chemistry<br>
                    <input type="checkbox" name="class_11_12_subjects[]" value="Biology"> Biology<br>
                    <input type="checkbox" name="class_11_12_subjects[]" value="Higher Math"> Higher Math<br>
                    <input type="checkbox" name="class_11_12_subjects[]" value="ICT"> ICT<br>
                    <input type="checkbox" name="class_11_12_subjects[]" value="Science"> Science<br>
                    <input type="checkbox" name="class_11_12_subjects[]" value="Bangla"> Bangla<br>
                    <input type="checkbox" name="class_11_12_subjects[]" value="English"> English<br>
                    <input type="checkbox" name="class_11_12_subjects[]" value="All Subjects"> All Subjects<br>
                    <input type="checkbox" name="class_11_12_subjects[]" value="None"> None<br>
                </div>
            </div>
            <div class="form-group">
            <strong> <label for="experience">Experience as a Tutor:</label><br></strong>
                <input type="radio" name="experience" value="Less than 1 year"> Less than 1 year<br>
                <input type="radio" name="experience" value="1-2 years"> 1-2 years<br>
                <input type="radio" name="experience" value="2-3 years"> 2-5 years<br>
                <input type="radio" name="experience" value="More than 3 years"> More than 5 years<br>
            </div>
            <div class="form-group form-check">
                <input type="checkbox" class="form-check-input" id="agree" name="agree" required>
                <label class="form-check-label" for="agree">I agree to the terms and conditions (This is valid only for Honours & Masters student & Service charge 1000 BDT will be applied per tuition only for one time)</label>
            </div>
            <button type="submit" class="btn btn-primary btn-large" id="registerBtn" disabled>Submit</button>
        </form>
    </div>

    <?php include_once 'footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const groups = document.querySelectorAll('.subjects-selection-group');

            groups.forEach(group => {
                const checkboxes = group.querySelectorAll('input[type="checkbox"]');
                
                checkboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', () => {
                        handleCheckboxChange(checkbox, group);
                    });
                });
            });

            function handleCheckboxChange(changedCheckbox, group) {
                const checkboxes = group.querySelectorAll('input[type="checkbox"]');
                const allSubjectsCheckbox = group.querySelector('input[value="All Subjects"]');
                const noneCheckbox = group.querySelector('input[value="None"]');

                if (changedCheckbox.value === 'All Subjects' || changedCheckbox.value === 'None') {
                    if (changedCheckbox.checked) {
                        checkboxes.forEach(checkbox => {
                            if (checkbox !== changedCheckbox) {
                                checkbox.checked = false;
                                checkbox.disabled = true;
                            }
                        });
                    } else {
                        checkboxes.forEach(checkbox => {
                            checkbox.disabled = false;
                        });
                    }
                } else {
                    allSubjectsCheckbox.checked = false;
                    noneCheckbox.checked = false;
                    allSubjectsCheckbox.disabled = false;
                    noneCheckbox.disabled = false;
                }
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            // Enable register button only when checkbox is checked
            $('#agree').change(function() {
                if (this.checked) {
                    $('#registerBtn').prop('disabled', false);
                } else {
                    $('#registerBtn').prop('disabled', true);
                }
            });

            // Populate districts based on division selection
            $('#division').change(function() {
                var division_id = $(this).val();
                if (division_id) {
                    $.ajax({
                        type: 'POST',
                        url: 'get_districts.php',
                        data: { division_id: division_id },
                        success: function(response) {
                            $('#district').html(response);
                        }
                    });
                } else {
                    $('#district').html('<option value="">Select the District of your University</option>');
                }
            });

            // Populate universities based on district selection
            $('#district').change(function() {
                var district_id = $(this).val();
                if (district_id) {
                    $.ajax({
                        type: 'POST',
                        url: 'get_universities.php',
                        data: { district_id: district_id },
                        success: function(response) {
                            $('#university').html(response);
                        }
                    });
                } else {
                    $('#university').html('<option value="">Select University</option>');
                }
            });

            // Populate departments based on university selection
            $('#university').change(function() {
                var university_id = $(this).val();
                if (university_id) {
                    $.ajax({
                        type: 'POST',
                        url: 'get_departments.php',
                        data: { university_id: university_id },
                        success: function(response) {
                            $('#department').html(response);
                        }
                    });
                } else {
                    $('#department').html('<option value="">Select Department</option>');
                }
            });
        });
    </script>
</body>
</html>
