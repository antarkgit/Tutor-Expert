<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="logo.png" type="image/x-icon">
    <title>Tutor Expert</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .carousel-item img {
            height: 150px;
            width: 100%;
            object-fit: contain;
        }
        .division-button {
            margin: 10px;
            padding: 15px 25px;
            font-size: 18px;
            border-radius: 25px;
            background-color: blue;
            color: white;
            transition: all 0.3s ease-in-out;
        }
        .division-button:hover {
            background-color: #007bff;
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .statistics-card {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .statistics-card h3 {
            font-size: 2rem;
            margin-bottom: 15px;
        }
        .statistics-card p {
            font-size: 1.2rem;
        }
        .container.text-center {
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <?php include_once 'header.php'; ?>
    
    <!-- Slideshow -->
    <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
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
        <h2 class="text-center">Find Tutor From Your Division</h2>
        <div class="text-center mt-4">
            <?php
            include_once 'db_connection.php';

            // Fetch divisions and tutors
            $sql = "
                SELECT d.division_id, d.division_name, COUNT(t.tutor_id) AS num_tutors
                FROM divisions d
                LEFT JOIN districts di ON d.division_id = di.division_id
                LEFT JOIN tutors t ON di.district_id = t.district_id
                GROUP BY d.division_id, d.division_name
                ORDER BY FIELD(d.division_id, 3) DESC, d.division_name
            ";
            $result = $conn->query($sql);

            while ($row = $result->fetch_assoc()) {
                if ($row['division_id'] == 3) {
                    echo '<a href="districts.php?division_id=' . $row['division_id'] . '" class="btn btn-light division-button">';
                    echo $row['division_name'] . ' (<em style="font-size: smaller;">' . $row['num_tutors'] . ' tutors</em>)';
                    echo '</a>';
                } else {
                    echo '<button class="btn btn-light division-button" disabled>';
                    echo $row['division_name'] . ' (<em style="font-size: smaller;">In progress</em>)';
                    echo '</button>';
                }
            }
            ?>
        </div>
    </div>

    <?php
    // Define the number of delivered tutors
    $delivered_tutors = 0; // Example value, you can set it dynamically as needed

    // Fetch the total number of registered tutors from the database
    $sql = "SELECT COUNT(tutor_id) AS total_tutors FROM tutors";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $total_tutors = $row['total_tutors'];
    ?>

    <div class="container text-center">
        <div class="statistics-card">
            <!--<h3>Statistics</h3>-->
            <p><i class="fas fa-user-graduate"></i> Total Registered Tutors: <?php echo $total_tutors; ?></p>
            <strong<p>আপনার পছন্দের শিক্ষক বাছাই করতে আমাদের সাথে যোগাযোগ করুন 01750477864 (হোয়াটসঅ্যাপে কল করুন বা ম্যাসেজ পাঠান)।</p></strong>
            <!--<p><i class="fas fa-chalkboard-teacher"></i> successfully Delivered Tutors: <?php echo $delivered_tutors; ?></p>-->
        </div>
    </div>

    <?php include_once 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    // Activate carousel and set interval for automatic sliding
    $('.carousel').carousel({
        interval: 3000 // 3 seconds
    });
    </script>
</body>
</html>
