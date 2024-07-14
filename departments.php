<?php
include_once 'db_connection.php';

if (!isset($_GET['university_id'])) {
    die("University ID is required");
}

$university_id = $_GET['university_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="logo.png" type="image/x-icon">
    <title>Tutor Expert - Departments</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .department-button {
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

        .department-button:hover {
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
        <h2 class="text-center header-title">Find Tutor From Required Department</h2>
        <div class="row justify-content-center mt-4">
            <?php
            $sql = "SELECT d.department_id, d.department_name,
                           COUNT(t.tutor_id) AS num_tutors,
                           SUM(CASE WHEN t.gender = 'male' THEN 1 ELSE 0 END) AS male_tutors,
                           SUM(CASE WHEN t.gender = 'female' THEN 1 ELSE 0 END) AS female_tutors
                    FROM departments d
                    LEFT JOIN tutors t ON d.department_id = t.department_id AND t.university_id = ?
                    WHERE d.university_id = ?
                    GROUP BY d.department_id, d.department_name";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $university_id, $university_id);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()):
            ?>
                <div class="col-md-4 text-center mb-3">
                    <a href="gender.php?university_id=<?php echo $university_id; ?>&department_id=<?php echo $row['department_id']; ?>" class="btn btn-info department-button">
                        <?php echo $row['department_name'] . ' (<em style="font-size: smaller;">' . $row['num_tutors'] . ' tutors)<br> Male: ' . $row['male_tutors'] . ' | Female: ' . $row['female_tutors']; ?></em>
                    </a>
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
