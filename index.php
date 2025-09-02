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
            color: white;
            line-height: 1.6;
            background-image: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                              url('https://images.unsplash.com/photo-1580273916550-e323be2ae537?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2000&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 2rem;
        }

        .logo {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: floating 3s ease-in-out infinite;
        }

        h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            max-width: 600px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        .button-container {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 500;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            min-width: 250px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }

        .btn-primary {
            background: var(--primary);
            border: 1px solid var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .features {
            display: flex;
            gap: 2rem;
            margin-top: 3rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .feature {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            max-width: 300px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: var(--transition);
        }

        .feature:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
        }

        .feature i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .feature h3 {
            margin-bottom: 0.5rem;
        }

        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
            }
            
            p {
                font-size: 1rem;
            }
            
            .btn {
                padding: 12px 20px;
                min-width: 200px;
            }
            
            body {
                background-attachment: scroll;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="logo">🔧</div>
    <h1>Car Workshop Appointment System</h1>
    <p>Book your preferred mechanic online or manage appointments with our easy-to-use system</p>
    
    <div class="button-container">
        <a href="admin_login.php" class="btn ">
            <i class="fas fa-lock"></i> Admin Login
        </a>
        <a href="appointment.php" class="btn ">
            <i class="fas fa-calendar-check"></i> Book Appointment
        </a>
    </div>
    
    <div class="features">
        <div class="feature">
            <i class="fas fa-clock"></i>
            <h3>24/7 Booking</h3>
            <p>Schedule your car service anytime, anywhere</p>
        </div>
        <div class="feature">
            <i class="fas fa-user-cog"></i>
            <h3>Expert Mechanics</h3>
            <p>Choose from our certified professionals</p>
        </div>
        <div class="feature">
            <i class="fas fa-bell"></i>
            <h3>Instant Notifications</h3>
            <p>Get reminders about your appointments</p>
        </div>
    </div>
    
    <script>
        // Dynamic background image loader
        document.addEventListener('DOMContentLoaded', function() {
            const images = [
                'https://images.unsplash.com/photo-1580273916550-e323be2ae537?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2000&q=80',
                'https://images.unsplash.com/photo-1608500218807-1db0c56faf4d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2000&q=80',
                'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2000&q=80'
            ];
            
            // Change background every 10 seconds
            let currentImage = 0;
            setInterval(function() {
                currentImage = (currentImage + 1) % images.length;
                document.body.style.backgroundImage = `linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('${images[currentImage]}')`;
            }, 3000);
            
            // Add hover effects to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>