<?php
// Include database connection
include_once 'db_connection.php';

// Check if division ID is provided via GET request
if (isset($_GET['division_id'])) {
    // Sanitize division ID to prevent SQL injection
    $division_id = $_GET['division_id'];

    // Prepare and execute query to fetch districts based on division ID
    $districts_query = "SELECT d.*, COUNT(t.tutor_id) AS num_tutors FROM districts d LEFT JOIN tutors t ON d.district_id = t.district_id WHERE d.division_id = $division_id GROUP BY d.district_id";
    $districts_result = $conn->query($districts_query);

    // Check if districts are found
    if ($districts_result->num_rows > 0) {
        // Create an array to store districts
        $districts = array();

        // Fetch districts and add them to the array
        while ($row = $districts_result->fetch_assoc()) {
            $districts[] = $row;
        }

        // Encode districts array as JSON and output
        echo json_encode($districts);
    } else {
        // No districts found for the selected division
        echo json_encode(array('message' => 'No districts found for the selected division'));
    }
} else {
    // Division ID not provided in the request
    echo json_encode(array('message' => 'Division ID is required'));
}
?>
