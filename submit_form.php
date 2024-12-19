<?php
// Database connection
$host = "localhost"; // Change to your database host
$user = "root";      // Your database username
$password = "";      // Your database password
$dbname = "event_scheduler"; // Your database name

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to generate a UUID
function generateUUID() {
    return bin2hex(random_bytes(16));  // Generates a 32-character unique ID
}

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extract user details from the form
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $message = $conn->real_escape_string($_POST['message']);
    
    // Generate a UUID for the user
    $uid = generateUUID();

    // Insert user details into the 'users' table
    $user_sql = "INSERT INTO users (uid, name, email, phone, message) 
                 VALUES ('$uid', '$name', '$email', '$phone', '$message')";

    if ($conn->query($user_sql) === TRUE) {
        $days = $_POST['day'];            // Array of days (dates)
        $total_people = $_POST['total_people'];  // Array of total people for each day
        $event_names = $_POST['event_name']; // Nested array of event names
        $event_types = $_POST['event_type']; // Nested array of event types
        $event_uids = $_POST['event_uid']; // Nested array of event UIDs

        // Insert the days into the 'days' table
        foreach ($days as $index => $day_date) {
            $total_people_count = $total_people[$index];

            // Generate a unique day_uid for this day
            $day_uid = 'day_'.generateUUID();

            // Insert the day with user_uid (UID of the user)
            $sql = "INSERT INTO days (day_date, total_people, user_uid, day_uid) 
                    VALUES ('$day_date', '$total_people_count', '$uid', '$day_uid')";
            
            if ($conn->query($sql) === TRUE) {
                // Insert the events for this day
                foreach ($event_names[$index] as $event_index => $event_name) {
                    $event_uid = $event_uids[$index][$event_index];
                    $event_type = $event_types[$index][$event_index];

                    // Insert each event for the day
                    $event_sql = "INSERT INTO events (event_uid, event_name, event_type, day_uid) 
                                  VALUES ('$event_uid', '$event_name', '$event_type', '$day_uid')";
                    if (!$conn->query($event_sql)) {
                        echo "Error: " . $event_sql . "<br>" . $conn->error;
                    }
                }
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
        }

        // Send a success message
        echo json_encode(['status' => 'success', 'message' => 'Data successfully submitted!']);
    } else {
        echo "Error: " . $user_sql . "<br>" . $conn->error;
    }

    // Close the connection
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
