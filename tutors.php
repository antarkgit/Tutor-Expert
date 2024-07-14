<?php
include_once 'db_connection.php';

if (!isset($_GET['university_id']) || !isset($_GET['department_id'])) {
    die("University ID and Department ID are required");
}

$university_id = $_GET['university_id'];
$department_id = $_GET['department_id'];
$gender = isset($_GET['gender']) ? $_GET['gender'] : 'all';

// Base SQL query
$sql = "
    SELECT 
        tutors.*, 
        universities.university_name,
        departments.department_name,
        districts.district_name AS home_district
    FROM tutors
    JOIN universities ON tutors.university_id = universities.university_id
    JOIN departments ON tutors.department_id = departments.department_id
    JOIN districts ON tutors.district_id = districts.district_id
    WHERE tutors.university_id = ? AND tutors.department_id = ? AND tutors.approved = 1
";

// Modify query based on gender
if ($gender == 'male') {
    $sql .= " AND tutors.gender = 'male'";
} elseif ($gender == 'female') {
    $sql .= " AND tutors.gender = 'female'";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $university_id, $department_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="logo.png" type="image/x-icon">
    <title>Tutor Expert - Tutors</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .tutor-card {
            display: flex;
            align-items: center;
            margin: 15px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .tutor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .tutor-card img {
            max-width: 150px;
            height: auto;
            border-radius: 8px;
            margin-right: 20px;
        }
        .tutor-info {
            flex: 1;
        }
        .tutor-info h3 {
            margin-top: 0;
        }
        .header-title {
            margin-top: 20px;
            margin-bottom: 20px;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .tutor-card {
                flex-direction: column;
                text-align: center;
            }
            .tutor-card img {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include_once 'header.php'; ?>

    <div class="container mt-5">
        <h5 class="text-center"><strong>আপনার পছন্দের শিক্ষক চূড়ান্ত করতে আমাদের সাথে যোগাযোগ করুন 01750477864 (হোয়াটসঅ্যাপে কল করুন বা বার্তা পাঠান)।</strong></h5><br>
        <!--<h3 class="text-center"><strong>Contact with Us 01750477864 (Call or message on WhatsApp) to finalize your desired tutor.</strong></h3><br>-->
        <h2 class="text-center header-title">Available Tutors In Your Location</h2>
        <div class="mt-4">
            <?php
            while ($row = $result->fetch_assoc()):
            ?>
                <div class="tutor-card">
                    <?php if ($row['photo']): ?>
                        <img src="<?php echo $row['photo']; ?>" alt="<?php echo $row['name']; ?>">
                    <?php else: ?>
                        <img src="default-photo.jpg" alt="Default Photo">
                    <?php endif; ?>
                    <div class="tutor-info">
                        <h3><?php echo $row['name']; ?></h3>
                        <strong>ID:</strong> <?php echo $row['tutor_id']; ?><br>
                        <strong>Gender:</strong> <?php echo $row['gender']; ?><br>
                        <strong>Institution:</strong> <?php echo $row['university_name']; ?><br>
                        <strong>Department:</strong> <?php echo $row['department_name']; ?><br>
                        <strong>Year:</strong> <?php echo $row['year']; ?><br>
                       
                        <strong>Home District:</strong> <?php echo $row['home_district']; ?><br>
                        <strong>Expected Salary (6 days a week):</strong> <?php echo $row['expected_salary_6_days']; ?> BDT<br>
                        <strong>Expected Salary (3 days a week):</strong> <?php echo $row['expected_salary_3_days']; ?> BDT<br>
                        <strong>Experience as a Tutor:</strong> <?php echo $row['experience']; ?><br>
                       
                        <strong>Interested Subjects:</strong><br>
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
                         <!--<strong>Contact with Us 01750477864 (Call or message on WhatsApp) to finalize your desired tutor.</strong><br>-->
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <?php include_once 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
