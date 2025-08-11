<?php
session_start();

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


if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle appointment updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_appointment'])) {
    $appointment_id = $_POST['appointment_id'];
    $new_mechanic_id = $_POST['mechanic_id'];
    $new_date = $_POST['appointment_date'];
    
    // Check if new mechanic has availability
    $mechanic_check = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM appointments 
        WHERE mechanic_id = ? 
        AND appointment_date = ? 
        AND status = 'pending'
        AND id != ?
    ");
    $mechanic_check->bind_param("isi", $new_mechanic_id, $new_date, $appointment_id);
    $mechanic_check->execute();
    $mechanic_result = $mechanic_check->get_result()->fetch_assoc();
    
    $max_check = $conn->prepare("SELECT max_cars_per_day FROM mechanics WHERE id = ?");
    $max_check->bind_param("i", $new_mechanic_id);
    $max_check->execute();
    $max_result = $max_check->get_result()->fetch_assoc();
    
    if ($mechanic_result['count'] < $max_result['max_cars_per_day']) {
        $update = $conn->prepare("UPDATE appointments SET mechanic_id = ?, appointment_date = ? WHERE id = ?");
        $update->bind_param("isi", $new_mechanic_id, $new_date, $appointment_id);
        
        if ($update->execute()) {
            $_SESSION['success'] = "Appointment updated successfully";
        } else {
            $_SESSION['error'] = "Failed to update appointment";
        }
    } else {
        $_SESSION['error'] = "Selected mechanic is fully booked for this date";
    }
    header("Location: admin_panel.php");
    exit();
}

// Get all appointments
$query = "SELECT a.id, c.name as client_name, c.phone, c.car_license, 
          a.appointment_date, m.name as mechanic_name, m.id as mechanic_id,
          (SELECT COUNT(*) FROM appointments 
           WHERE mechanic_id = m.id 
           AND appointment_date = a.appointment_date
           AND status = 'pending') as mechanic_load,
          m.max_cars_per_day
          FROM appointments a
          JOIN clients c ON a.client_id = c.id
          JOIN mechanics m ON a.mechanic_id = m.id
          ORDER BY a.appointment_date DESC";
$appointments = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Get all mechanics for dropdown
$mechanics = $conn->query("SELECT id, name FROM mechanics")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; margin: 0; padding: 20px; background: #f5f7ff; }
        .container { max-width: 1200px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #4361ee; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .success { color: #4bb543; padding: 10px; background: rgba(75,181,67,0.1); margin-bottom: 15px; }
        .error { color: #ff3333; padding: 10px; background: rgba(255,51,51,0.1); margin-bottom: 15px; }
        .logout { float: right; color: #4361ee; text-decoration: none; }
        select, input[type='date'] { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { padding: 8px 15px; background: #4361ee; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #3a56d4; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Dashboard <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a></h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <h2>Appointment List</h2>
        <table>
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th>Phone</th>
                    <th>Car License</th>
                    <th>Appointment Date</th>
                    <th>Mechanic (Load)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?= htmlspecialchars($appointment['client_name']) ?></td>
                    <td><?= htmlspecialchars($appointment['phone']) ?></td>
                    <td><?= htmlspecialchars($appointment['car_license']) ?></td>
                    <td><?= htmlspecialchars($appointment['appointment_date']) ?></td>
                    <td>
                        <?= htmlspecialchars($appointment['mechanic_name']) ?>
                        (<?= $appointment['mechanic_load'] ?>/<?= $appointment['max_cars_per_day'] ?>)
                    </td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                            <input type="date" name="appointment_date" value="<?= $appointment['appointment_date'] ?>" required>
                            <select name="mechanic_id" required>
                                <?php foreach ($mechanics as $mechanic): ?>
                                    <option value="<?= $mechanic['id'] ?>" <?= $mechanic['id'] == $appointment['mechanic_id'] ? 'selected' : '' ?>>
                                        <?= $mechanic['name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="update_appointment">Update</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>