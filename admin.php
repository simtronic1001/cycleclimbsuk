<?php 
// This connects the page to the database
include 'db.php';
include 'header.php';

// This code runs ONLY when you click the "Add Climb" button
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['climb_name'];
    $loc = $_POST['location'];
    $sid = $_POST['strava_id'];

    // Prepared statement to prevent SQL Injection (Modern Standard)
    $sql = "INSERT INTO climbs (name, location, strava_id) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $loc, $sid]);
    
    echo "<p style='color: green;'>Success: $name has been added!</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Climb Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Add a New Climb</h1>
    <form method="POST">
        <div>
            <label>Climb Name:</label><br>
            <input type="text" name="climb_name" required>
        </div>
        <div>
            <label>Location (e.g. Cumbria):</label><br>
            <input type="text" name="location">
        </div>
        <div>
            <label>Strava Segment ID:</label><br>
            <input type="text" name="strava_id">
        </div>
        <br>
        <button type="submit">Add Climb to List</button>
    </form>
    
    <br>
    <a href="index.php">Back to Main List</a>
    <?php include 'footer.php'; ?>
</body>
</html>