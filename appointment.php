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


// Get available mechanics with their current load
$current_date = date('Y-m-d');
$query = "SELECT m.id, m.name, m.max_cars_per_day, 
          COUNT(a.id) AS current_appointments,
          (m.max_cars_per_day - COUNT(a.id)) AS available_slots
          FROM mechanics m
          LEFT JOIN appointments a ON m.id = a.mechanic_id 
              AND a.appointment_date = ?
              AND a.status = 'pending'
          GROUP BY m.id
          HAVING available_slots > 0";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $current_date);
$stmt->execute();
$result = $stmt->get_result();
$mechanics = $result->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $car_license = $_POST['car_license'];
    $car_engine = $_POST['car_engine'];
    $appointment_date = $_POST['appointment_date'];
    $mechanic_id = $_POST['mechanic_id'];
    
    // Validate input
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($phone) || !preg_match('/^[0-9]{10,15}$/', $phone)) $errors[] = "Valid phone number is required";
    if (empty($car_license)) $errors[] = "Car license is required";
    if (empty($car_engine) || !is_numeric($car_engine)) $errors[] = "Valid car engine number is required";
    if (empty($appointment_date)) $errors[] = "Appointment date is required";
    if (empty($mechanic_id)) $errors[] = "Mechanic selection is required";
    
    if (empty($errors)) {
        // Check if client already has an appointment on this date
        $client_check = $conn->prepare("SELECT id FROM clients WHERE car_license = ? OR car_engine = ?");
        $client_check->bind_param("ss", $car_license, $car_engine);
        $client_check->execute();
        $client_result = $client_check->get_result();
        
        if ($client_result->num_rows > 0) {
            $client = $client_result->fetch_assoc();
            $appointment_check = $conn->prepare("SELECT id FROM appointments WHERE client_id = ? AND appointment_date = ?");
            $appointment_check->bind_param("is", $client['id'], $appointment_date);
            $appointment_check->execute();
            
            if ($appointment_check->get_result()->num_rows > 0) {
                $errors[] = "You already have an appointment on this date";
            }
        }
        
        if (empty($errors)) {
            // Check mechanic availability with status condition
            $mechanic_check = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM appointments 
                WHERE mechanic_id = ? 
                AND appointment_date = ?
                AND status = 'pending'
            ");
            $mechanic_check->bind_param("is", $mechanic_id, $appointment_date);
            $mechanic_check->execute();
            $mechanic_result = $mechanic_check->get_result()->fetch_assoc();
            
            $max_check = $conn->prepare("SELECT max_cars_per_day FROM mechanics WHERE id = ?");
            $max_check->bind_param("i", $mechanic_id);
            $max_check->execute();
            $max_result = $max_check->get_result()->fetch_assoc();
            
            if ($mechanic_result['count'] >= $max_result['max_cars_per_day']) {
                $errors[] = "Selected mechanic is fully booked for this date";
            } else {
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Insert client if not exists
                    if ($client_result->num_rows == 0) {
                        $insert_client = $conn->prepare("INSERT INTO clients (name, address, phone, car_license, car_engine) VALUES (?, ?, ?, ?, ?)");
                        $insert_client->bind_param("sssss", $name, $address, $phone, $car_license, $car_engine);
                        $insert_client->execute();
                        $client_id = $conn->insert_id;
                    } else {
                        $client_id = $client['id'];
                    }
                    
                    // Create appointment
                    $insert_appointment = $conn->prepare("INSERT INTO appointments (client_id, mechanic_id, appointment_date, status) VALUES (?, ?, ?, 'pending')");
                    $insert_appointment->bind_param("iis", $client_id, $mechanic_id, $appointment_date);
                    
                    if ($insert_appointment->execute()) {
                        $conn->commit();
                        $_SESSION['success'] = "Appointment booked successfully!";
                        header("Location: appointment.php");
                        exit();
                    } else {
                        throw new Exception("Failed to book appointment");
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $errors[] = "Failed to book appointment. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Workshop Appointment System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #4bb543;
            --danger: #ff3333;
            --warning: #ffcc00;
            --border-radius: 8px;
            --box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            line-height: 1.6;
            background-image: linear-gradient(rgba(82, 77, 77, 0.9), rgba(122, 116, 116, 0.9)), 
                            url('image.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }

        header {
            text-align: center;
            margin-bottom: 2.5rem;
            animation: fadeInDown 0.8s ease;
            position: relative;
        }

        header h1 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-weight: 700;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.1);
        }

        header p {
            color: #555;
            font-size: 1.1rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.05);
        }

        .card {
            background: rgba(223, 216, 216, 0.95);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2.5rem;
            margin-bottom: 2rem;
            transition: var(--transition);
            animation: fadeIn 0.8s ease;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
            background-color: rgba(255, 255, 255, 0.8);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            background-color: white;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 1em;
        }

        .btn {
            display: inline-block;
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: 500;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0,0,0,0.15);
        }

        .btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .alert {
            padding: 15px;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            animation: fadeIn 0.5s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert.success {
            background-color: rgba(75, 181, 67, 0.2);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert.error {
            background-color: rgba(255, 51, 51, 0.2);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .mechanic-availability {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .mechanic-badge {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }

        .mechanic-badge i {
            font-size: 0.8rem;
        }

        .mechanic-badge.available {
            background: rgba(75, 181, 67, 0.1);
            color: var(--success);
        }

        .mechanic-badge.unavailable {
            background: rgba(255, 51, 51, 0.1);
            color: var(--danger);
        }

        .header-container {
            position: relative;
            margin-bottom: 2rem;
        }

        .admin-login-btn {
            position: absolute;
            top: 0;
            right: 0;
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .admin-login-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .admin-login-btn i {
            font-size: 0.9rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 1.5rem;
            }
            
            header h1 {
                font-size: 2rem;
            }
            
            .card {
                padding: 1.5rem;
            }
            
            .admin-login-btn {
                position: static;
                margin-top: 1rem;
                width: 100%;
                justify-content: center;
            }
            
            body {
                background-attachment: scroll;
            }
        }

        /* Tooltip for form fields */
        .form-tooltip {
            position: relative;
            display: inline-block;
        }

        .form-tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.8rem;
        }

        .form-tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-container">
            <header>
                <h1>Car Workshop <span class="floating">🔧</span> Appointment</h1>
                <p>Book your preferred mechanic with our easy online system</p>
            </header>
            <a href="admin_login.php" class="admin-login-btn">
                <i class="fas fa-lock"></i> Admin Login
            </a>
        </div>
        
        <div class="card">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php foreach ($errors as $error): ?>
                        <p><?= $error ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <h2 style="margin-bottom: 1.5rem; color: var(--primary);">Book Your Appointment</h2>
            
            <!-- Mechanic availability badges -->
            <div class="mechanic-availability">
                <?php foreach ($mechanics as $mechanic): ?>
                    <div class="mechanic-badge <?= $mechanic['available_slots'] > 0 ? 'available' : 'unavailable' ?>">
                        <i class="fas fa-<?= $mechanic['available_slots'] > 0 ? 'check' : 'times' ?>"></i>
                        <?= $mechanic['name'] ?> (<?= $mechanic['available_slots'] ?> slots)
                    </div>
                <?php endforeach; ?>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> Full Name:</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="John Doe" required>
                </div>
                
                <div class="form-group">
                    <label for="address"><i class="fas fa-map-marker-alt"></i> Address:</label>
                    <textarea id="address" name="address" class="form-control" placeholder="Your complete address" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone"></i> Phone Number:</label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="123-456-7890" required>
                </div>
                
                <div class="form-group">
                    <label for="car_license"><i class="fas fa-car"></i> Car License Number:</label>
                    <input type="text" id="car_license" name="car_license" class="form-control" placeholder="ABC-1234" required>
                </div>
                
                <div class="form-group">
                    <label for="car_engine"><i class="fas fa-cog"></i> Car Engine Number:</label>
                    <input type="text" id="car_engine" name="car_engine" class="form-control" placeholder="Engine number" required>
                </div>
                
                <div class="form-group">
                    <label for="appointment_date"><i class="fas fa-calendar-alt"></i> Appointment Date:</label>
                    <input type="date" id="appointment_date" name="appointment_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="mechanic_id"><i class="fas fa-user-cog"></i> Preferred Mechanic:</label>
                    <select id="mechanic_id" name="mechanic_id" class="form-control" required>
                        <option value="">Select a mechanic</option>
                        <?php foreach ($mechanics as $mechanic): ?>
                            <option value="<?= $mechanic['id'] ?>">
                                <?= $mechanic['name'] ?> (<?= $mechanic['available_slots'] ?> slots available)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-calendar-check"></i> Book Appointment
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // Enhanced client-side validation with better UX
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            // Add real-time validation
            const phoneInput = document.getElementById('phone');
            phoneInput.addEventListener('input', function() {
                if (!/^[0-9]{10,15}$/.test(this.value)) {
                    this.style.borderColor = 'var(--danger)';
                } else {
                    this.style.borderColor = '#ddd';
                }
            });
            
            const engineInput = document.getElementById('car_engine');
            engineInput.addEventListener('input', function() {
                if (!/^[0-9]+$/.test(this.value)) {
                    this.style.borderColor = 'var(--danger)';
                } else {
                    this.style.borderColor = '#ddd';
                }
            });
            
            // Form submission
            form.addEventListener('submit', function(e) {
                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');
                
                // Check required fields
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.style.borderColor = 'var(--danger)';
                        isValid = false;
                    }
                });
                
                // Specific validations
                if (!/^[0-9]{10,15}$/.test(phoneInput.value)) {
                    phoneInput.style.borderColor = 'var(--danger)';
                    isValid = false;
                }
                
                if (!/^[0-9]+$/.test(engineInput.value)) {
                    engineInput.style.borderColor = 'var(--danger)';
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill all required fields correctly');
                }
            });
            
            // Date picker enhancement
            const dateInput = document.getElementById('appointment_date');
            dateInput.addEventListener('focus', function() {
                this.showPicker();
            });
        });
    </script>
</body>
</html>