<?php
include_once 'db_connection.php';

$division = isset($_GET['division']) ? $_GET['division'] : '';
$district = isset($_GET['district']) ? $_GET['district'] : '';
$university = isset($_GET['university']) ? $_GET['university'] : '';
$subject = isset($_GET['subject']) ? $_GET['subject'] : '';

$query = "
SELECT tutors.name, tutors.photo, universities.university_name, tutors.department, tutors.year, districts.district_name, tutors.expected_salary, tutors.phone,
GROUP_CONCAT(DISTINCT CASE
    WHEN tutor_subjects.class_range = '3-5' THEN CONCAT('Class (3-5): ', subjects.subject_name)
    WHEN tutor_subjects.class_range = '6-8' THEN CONCAT('Class (6-8): ', subjects.subject_name)
    WHEN tutor_subjects.class_range = '9-10' THEN CONCAT('Class (9-10): ', subjects.subject_name)
    WHEN tutor_subjects.class_range = '11-12' THEN CONCAT('Class (11-12): ', subjects.subject_name)
    ELSE ''
END SEPARATOR '<br>') AS class_subjects
FROM tutors
JOIN universities ON tutors.university_id = universities.university_id
JOIN tutor_subjects ON tutors.tutor_id = tutor_subjects.tutor_id
JOIN subjects ON tutor_subjects.subject_id = subjects.subject_id
JOIN districts ON tutors.district_id = districts.district_id
WHERE tutors.approved = 1
";

$conditions = [];

if (!empty($division)) {
    $conditions[] = "universities.division_id = " . intval($division);
}
if (!empty($district)) {
    $conditions[] = "districts.district_id = " . intval($district);
}
if (!empty($university)) {
    $conditions[] = "tutors.university_id = " . intval($university);
}
if (!empty($subject)) {
    $conditions[] = "subjects.subject_name LIKE '%" . $conn->real_escape_string($subject) . "%'";
}

if (count($conditions) > 0) {
    $query .= " AND " . implode(" AND ", $conditions);
}

$query .= " GROUP BY tutors.tutor_id";

$result = $conn->query($query);

if (!$result) {
    echo "Error: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link
            rel="icon"
            href="logo.png"
            type="image/x-icon"
        />
    <title>Search Tutors</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include_once 'header.php'; ?>

    <div class="container mt-5">
        <h2 class="text-center">Tutor Search Results</h2>
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered mt-4">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Information</th>
                            <th>Interested Subjects</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><img src="<?php echo $row['photo']; ?>" alt="Tutor Photo" style="width: 100px;"></td>
                                <td>
                                    <strong>Name:</strong> <?php echo $row['name']; ?><br>
                                    <strong>Institution:</strong> <?php echo $row['university_name']; ?><br>
                                    <strong>Department:</strong> <?php echo $row['department']; ?><br>
                                    <strong>Year:</strong> <?php echo $row['year']; ?><br>
                                    <strong>District:</strong> <?php echo isset($row['district_name']) ? $row['district_name'] : 'N/A'; ?><br>
                                    <strong>Expected Salary:</strong> <?php echo $row['expected_salary']; ?> BDT<br>
                                    <strong>Phone:</strong> <?php echo isset($row['phone']) ? $row['phone'] : 'N/A'; ?>
                                </td>
                                <td><?php echo nl2br($row['class_subjects']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted mt-4">No tutors found matching your search criteria.</p>
        <?php endif; ?>
    </div>

    <?php include_once 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
