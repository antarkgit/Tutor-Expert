<?php
include_once 'db_connection.php';

if (!isset($_GET['university_id']) || !isset($_GET['department_id'])) {
    die("University ID and Department ID are required");
}

$university_id = $_GET['university_id'];
$department_id = $_GET['department_id'];

// Fetch tutor counts by gender
$sql = "SELECT 
            SUM(CASE WHEN gender = 'male' THEN 1 ELSE 0 END) AS male_tutors,
            SUM(CASE WHEN gender = 'female' THEN 1 ELSE 0 END) AS female_tutors,
            COUNT(tutor_id) AS total_tutors
        FROM tutors
        WHERE university_id = ? AND department_id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $university_id, $department_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$male_tutors = $row['male_tutors'];
$female_tutors = $row['female_tutors'];
$total_tutors = $row['total_tutors'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="logo.png" type="image/x-icon">
    <title>Tutor Expert - Gender</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .gender-button {
            margin: 8px;
            padding: 15px 20px;
            font-size: 18px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            transition: transform 0.3s, box-shadow 0.3s;
            width: 100%;
            max-width: 100%;
            display: inline-block;
            text-align: center;
            white-space: normal;
        }

        .gender-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .carousel-item img {
            height: 150px;
            width: 100%;
            object-fit: contain;
        }
        
        .header-title {
            margin-top: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include_once 'header.php'; ?>

    <!-- Slideshow -->
    <div id="carouselExampleIndicators" class="carousel slide mt-4" data-ride="carousel">
        <ol class="carousel-indicators">
            <?php
            $imagesDir = 'slideshow_images/';
            $images = glob($imagesDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
            $count = count($images);
            for ($i = 0; $i < $count; $i++) {
                echo '<li data-target="#carouselExampleIndicators" data-slide-to="' . $i . '" class="' . ($i == 0 ? 'active' : '') . '"></li>';
            }
            ?>
        </ol>
        <div class="carousel-inner">
            <?php
            foreach ($images as $index => $image) {
                echo '<div class="carousel-item' . ($index == 0 ? ' active' : '') . '">';
                echo '<img class="d-block w-100" src="' . $image . '" alt="Slide ' . ($index + 1) . '">';
                echo '</div>';
            }
            ?>
        </div>
        <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
        </a>
    </div>
    <!-- End of Slideshow -->

    <div class="container mt-5">
        <h2 class="text-center header-title">Find Tutor By Gender</h2>
        <div class="row justify-content-center mt-4">
            <div class="col-md-4 text-center mb-3">
                <a href="tutors.php?university_id=<?php echo $university_id; ?>&department_id=<?php echo $department_id; ?>&gender=male" class="btn btn-info gender-button">
                    Male Tutors (<em style="font-size: smaller;"><?php echo $male_tutors; ?> tutors</em>)
                </a>
            </div>
            <div class="col-md-4 text-center mb-3">
                <a href="tutors.php?university_id=<?php echo $university_id; ?>&department_id=<?php echo $department_id; ?>&gender=female" class="btn btn-info gender-button">
                    Female Tutors (<em style="font-size: smaller;"><?php echo $female_tutors; ?> tutors</em>)
                </a>
            </div>
            <div class="col-md-4 text-center mb-3">
                <a href="tutors.php?university_id=<?php echo $university_id; ?>&department_id=<?php echo $department_id; ?>&gender=all" class="btn btn-info gender-button">
                    All Tutors (<em style="font-size: smaller;"><?php echo $total_tutors; ?> tutors</em>)
                </a>
            </div>
        </div>
    </div>

    <?php include_once 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
