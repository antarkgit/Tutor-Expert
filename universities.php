<?php
include_once 'db_connection.php';

if (!isset($_GET['district_id'])) {
    die("District ID is required");
}

$district_id = $_GET['district_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="logo.png" type="image/x-icon">
    <title>Tutor Expert - Universities</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .university-button {
            margin: 10px;
            padding: 15px 25px;
            font-size: 18px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .university-button:hover {
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
        <h2 class="text-center header-title">Find Tutor From Your Favourite Institution </h2>
        <div class="text-center mt-4">
            <?php
            $sql = "SELECT u.university_id, u.university_name, COUNT(t.tutor_id) AS num_tutors
                    FROM universities u
                    LEFT JOIN tutors t ON u.university_id = t.university_id
                    WHERE u.district_id = ?
                    GROUP BY u.university_id, u.university_name";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $district_id);
            $stmt->execute();
            $result = $stmt->get_result();

            while($row = $result->fetch_assoc()):
            ?>
                <a href="departments.php?university_id=<?php echo $row['university_id']; ?>" class="btn btn-info university-button">
    <?php echo $row['university_name'] . ' (<em style="font-size: smaller;">' . $row['num_tutors'] . ' tutors</em>)'; ?>
</a>

            <?php endwhile; ?>
        </div>
    </div>

    <?php include_once 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
