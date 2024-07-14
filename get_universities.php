<?php
include_once 'db_connection.php';

if (isset($_POST['district_id'])) {
    $district_id = $_POST['district_id'];
    $query = "SELECT * FROM universities WHERE district_id = $district_id";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        echo '<option value="">Select University</option>';
        while ($row = $result->fetch_assoc()) {
            echo '<option value="' . $row['university_id'] . '">' . $row['university_name'] . '</option>';
        }
    } else {
        echo '<option value="">No Universities Available</option>';
    }
} else {
    echo '<option value="">Invalid Request</option>';
}
?>
