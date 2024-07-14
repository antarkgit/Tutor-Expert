<?php
include_once 'db_connection.php';

if (isset($_POST['division_id'])) {
    $division_id = $_POST['division_id'];
    $query = "SELECT * FROM districts WHERE division_id = $division_id";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        echo '<option value="">Select District</option>';
        while ($row = $result->fetch_assoc()) {
            echo '<option value="' . $row['district_id'] . '">' . $row['district_name'] . '</option>';
        }
    } else {
        echo '<option value="">No Districts Available</option>';
    }
} else {
    echo '<option value="">Invalid Request</option>';
}
?>
