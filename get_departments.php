<?php
include_once 'db_connection.php';

if (isset($_POST['university_id'])) {
    $university_id = $_POST['university_id'];
    $query = "SELECT * FROM departments WHERE university_id = $university_id";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        echo '<option value="">Select Department</option>';
        while ($row = $result->fetch_assoc()) {
            echo '<option value="' . $row['department_id'] . '">' . $row['department_name'] . '</option>';
        }
    } else {
        echo '<option value="">No Departments Available</option>';
    }
} else {
    echo '<option value="">Invalid Request</option>';
}
?>
