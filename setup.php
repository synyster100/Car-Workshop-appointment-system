<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "car_workshop";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for full Unicode support
$conn->set_charset("utf8mb4");


// Create tables with enhanced structure
$sql = [
    "CREATE TABLE IF NOT EXISTS mechanics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        max_cars_per_day INT DEFAULT 4,
        specialization VARCHAR(100),
        experience_years INT
    )",
    
    "CREATE TABLE IF NOT EXISTS clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        address TEXT NOT NULL,
        phone VARCHAR(20) NOT NULL,
        car_license VARCHAR(50) NOT NULL UNIQUE,
        car_engine VARCHAR(50) NOT NULL UNIQUE
    )",
    
    "CREATE TABLE IF NOT EXISTS appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        mechanic_id INT NOT NULL,
        appointment_date DATE NOT NULL,
        status ENUM('pending', 'completed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id),
        FOREIGN KEY (mechanic_id) REFERENCES mechanics(id),
        UNIQUE KEY unique_client_date (client_id, appointment_date)
    )",
    
    "CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100)
    )"
];

foreach ($sql as $query) {
    if (!$conn->query($query)) {
        die("Error creating table: " . $conn->error);
    }
}

// Insert Bangladeshi mechanics
$mechanics = [
    ['name' => 'Md. Rahman Ali', 'specialization' => 'Engine Specialist', 'experience_years' => 12],
    ['name' => 'Abdul Karim', 'specialization' => 'Transmission Expert', 'experience_years' => 8],
    ['name' => 'tawsif islam', 'specialization' => 'Electrical Systems', 'experience_years' => 6],
    ['name' => 'Shahriar Ahmed', 'specialization' => 'Hybrid Vehicles', 'experience_years' => 5],
    ['name' => 'kopila khanom', 'specialization' => 'AC & Cooling', 'experience_years' => 7]
];

foreach ($mechanics as $mechanic) {
    $stmt = $conn->prepare("INSERT IGNORE INTO mechanics (name, specialization, experience_years) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $mechanic['name'], $mechanic['specialization'], $mechanic['experience_years']);
    $stmt->execute();
}

// Create admin user
$admin_username = "admin";
$admin_password = password_hash("1234", PASSWORD_DEFAULT);
$admin_fullname = "Abdullah Al Mamun";

$stmt = $conn->prepare("INSERT IGNORE INTO admin_users (username, password, full_name) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $admin_username, $admin_password, $admin_fullname);
$stmt->execute();

// Add booking trigger
// Remove the DELIMITER commands and create the trigger directly
$trigger_sql = "
    CREATE TRIGGER IF NOT EXISTS prevent_overbooking
    BEFORE INSERT ON appointments
    FOR EACH ROW
    BEGIN
        DECLARE booked INT;
        DECLARE max_allowed INT;
        
        SELECT COUNT(*), m.max_cars_per_day INTO booked, max_allowed
        FROM appointments a
        JOIN mechanics m ON a.mechanic_id = m.id
        WHERE a.mechanic_id = NEW.mechanic_id 
        AND a.appointment_date = NEW.appointment_date
        AND a.status = 'pending';
        
        IF booked >= max_allowed THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Mechanic has no available slots for this date';
        END IF;
    END";

// Execute the trigger creation
if (!$conn->query($trigger_sql)) {
    echo "Error creating trigger: " . $conn->error;
}
?>