
<?php
include 'db.php';

// The "Master List" Array
/*
$climbs = [
    // --- SOUTH WEST ---
    ['name' => 'Cheddar Gorge', 'location' => 'Somerset', 'strava_id' => '611339'],
    ['name' => 'Weston Hill', 'location' => 'Somerset', 'strava_id' => '669542'],
    ['name' => 'Dunkery Beacon', 'location' => 'Somerset', 'strava_id' => '669539'],
    ['name' => 'Porlock Hill', 'location' => 'Somerset', 'strava_id' => '669531'],
    ['name' => 'Dartmeet', 'location' => 'Devon', 'strava_id' => '669547'],
    ['name' => 'Haytor Vale', 'location' => 'Devon', 'strava_id' => '669549'],
    ['name' => 'Widecombe Hill', 'location' => 'Devon', 'strava_id' => '669550'],
    ['name' => 'Challacombe', 'location' => 'Devon', 'strava_id' => '669551'],

    // --- SOUTH EAST & LONDON ---
    ['name' => 'Box Hill', 'location' => 'Surrey', 'strava_id' => '635880'],
    ['name' => 'Leith Hill', 'location' => 'Surrey', 'strava_id' => '669151'],
    ['name' => 'Barhatch Lane', 'location' => 'Surrey', 'strava_id' => '669154'],
    ['name' => 'Whiteleaf', 'location' => 'Buckinghamshire', 'strava_id' => '669147'],
    ['name' => 'Kop Hill', 'location' => 'Buckinghamshire', 'strava_id' => '669149'],
    ['name' => 'Toys Hill', 'location' => 'Kent', 'strava_id' => '669158'],
    ['name' => 'York\'s Hill', 'location' => 'Kent', 'strava_id' => '669161'],

    // --- MIDLANDS ---
    ['name' => 'Winnats Pass', 'location' => 'Derbyshire', 'strava_id' => '669091'],
    ['name' => 'Monsal Head', 'location' => 'Derbyshire', 'strava_id' => '669096'],
    ['name' => 'Mam Tor', 'location' => 'Derbyshire', 'strava_id' => '669093'],
    ['name' => 'Mow Cop', 'location' => 'Staffordshire', 'strava_id' => '669109'],
    ['name' => 'Saintbury Hill', 'location' => 'Gloucestershire', 'strava_id' => '669136'],
    ['name' => 'Snowshill', 'location' => 'Gloucestershire', 'strava_id' => '669137'],

    // --- YORKSHIRE & NORTH ---
    ['name' => 'Buttertubs Pass', 'location' => 'Yorkshire', 'strava_id' => '669062'],
    ['name' => 'Fleet Moss', 'location' => 'Yorkshire', 'strava_id' => '669061'],
    ['name' => 'Park Rash', 'location' => 'Yorkshire', 'strava_id' => '669065'],
    ['name' => 'Shibden Wall', 'location' => 'Yorkshire', 'strava_id' => '669074'],
    ['name' => 'Rosedale Chimney', 'location' => 'Yorkshire', 'strava_id' => '669085'],

    // --- THE LAKE DISTRICT (CUMBRIA) ---
    ['name' => 'Hardknott Pass', 'location' => 'Cumbria', 'strava_id' => '669046'],
    ['name' => 'Wrynose Pass', 'location' => 'Cumbria', 'strava_id' => '669048'],
    ['name' => 'The Struggle', 'location' => 'Cumbria', 'strava_id' => '669055'],
    ['name' => 'Kirkstone Pass', 'location' => 'Cumbria', 'strava_id' => '669054'],
    ['name' => 'Honister Pass', 'location' => 'Cumbria', 'strava_id' => '669050'],

    // --- WALES ---
    ['name' => 'The Tumble', 'location' => 'Monmouthshire', 'strava_id' => '669116'],
    ['name' => 'Bwlch-y-Groes', 'location' => 'Gwynedd', 'strava_id' => '669103'],
    ['name' => 'Constitution Hill', 'location' => 'Swansea', 'strava_id' => '669123'],

    // --- SCOTLAND ---
    ['name' => 'Bealach na Ba', 'location' => 'Highlands', 'strava_id' => '669032'],
    ['name' => 'The Mennock Pass', 'location' => 'Dumfries', 'strava_id' => '669041'],
    ['name' => 'The Lecht', 'location' => 'Aberdeenshire', 'strava_id' => '669037']
];

echo "<h1>Updating Your Climb Database...</h1>";

foreach ($climbs as $climb) {
    // We check the Strava ID so we don't add the same hill twice
    $check = $pdo->prepare("SELECT id FROM climbs WHERE strava_id = ?");
    $check->execute([$climb['strava_id']]);
    
    if ($check->rowCount() == 0) {
        $sql = "INSERT INTO climbs (name, location, strava_id) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$climb['name'], $climb['location'], $climb['strava_id']]);
        echo "<p style='color: green;'>Added: <strong>" . $climb['name'] . "</strong> (" . $climb['location'] . ")</p>";
    } else {
        echo "<p style='color: #888;'>Skipped: " . $climb['name'] . " (Already in database)</p>";
    }
}

echo "<h2>Done! <a href='index.php'>Go to your Tracker</a></h2>";
?> */