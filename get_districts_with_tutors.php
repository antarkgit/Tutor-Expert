<?php
include_once 'db_connection.php';

if (isset($_GET['division_id'])) {
    $division_id = $_GET['division_id'];

    $sql = "SELECT d.district_id, d.district_name, COUNT(t.tutor_id) AS num_tutors
            FROM districts d
            LEFT JOIN tutors t ON d.district_id = t.district_id
            WHERE d.division_id = ?
            GROUP BY d.district_id, d.district_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $division_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $districts = [];
    while($row = $result->fetch_assoc()) {
        $districts[] = $row;
    }
    
    echo json_encode($districts);
}
?>
