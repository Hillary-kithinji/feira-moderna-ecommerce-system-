<?php
session_start();
require 'db.php'; // Your DB connection

$token = $_GET['token'] ?? '';
if (!$token) {
    die("Invalid token. Please request a new password reset.");
}

// Check if token exists and is not expired
$stmt = $conn->prepare("SELECT email, expiry FROM reset_tokens WHERE token = ? LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $expiry = strtotime($row['expiry']);
    if (time() > $expiry) {
        die("Token expired. Please request a new password reset.");
    }
    $email = $row['email'];
} else {
    die("Invalid token. Please request a new password reset.");
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password 🧺😌</title>
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Yatra+One&display=swap" rel="stylesheet">
  
  <style>
    body {
      height: 100vh;
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
      overflow: hidden;
    }

    /* Floating green utensils */
    body::before {
      content: "🔪🍴🥄🥢🍳🧂🫙";
      position: absolute;
      top: -50px;
      left: -50px;
      font-size: 40px;
      color: green;
      animation: float 20s linear infinite;
      opacity: 0.3;
    }

    @keyframes float {
      0% { transform: translate(0,0) rotate(0deg);}
      50% { transform: translate(100vw,50vh) rotate(360deg);}
      100% { transform: translate(0,100vh) rotate(720deg);}
    }

    .card {
      width: 400px;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
      background-color: #ffffff;
      position: relative;
      z-index: 1;
    }

    h3 {
      text-align: center;
      margin-bottom: 20px;
      font-family: 'Yatra One', cursive;
      font-size: 28px;
    }

    h3::after {
      content: " 🧺😌";
    }

    .form-control {
      padding-left: 40px;
      padding-right: 40px;
      border-radius: 8px;
    }

    .form-group {
      position: relative;
      margin-bottom: 20px;
    }

    /* Left padlock icon */
    .form-group i.lock-icon {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: green;
    }

    /* Right toggle eye icon */
    .form-group i.toggle-eye {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: green;
    }

    .btn-primary {
      background-color: #4CAF50;
      border-color: #4CAF50;
    }

    .btn-primary:hover {
      background-color: #45a049;
      border-color: #45a049;
    }

    #alertMessage {
      margin-top: 15px;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="card">
    <h3>Reset Password</h3>
    <p class="text-center" style="color:green;">Don't forget your password! 🧺</p>
    <form id="resetForm">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      
      <div class="form-group">
        <i class="fas fa-lock lock-icon"></i>
        <input type="password" name="new_password" class="form-control" placeholder="New Password" required>
        <i class="fas fa-eye toggle-eye"></i>
      </div>
      
      <div class="form-group">
        <i class="fas fa-lock lock-icon"></i>
        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
        <i class="fas fa-eye toggle-eye"></i>
      </div>
      
      <input type="submit" class="btn btn-primary w-100" value="Reset Password">
      <div id="alertMessage" class="alert d-none"></div>
    </form>
  </div>

  <script>
    // Toggle password visibility
    document.querySelectorAll('.toggle-eye').forEach((eye) => {
      eye.addEventListener('click', () => {
        const input = eye.previousElementSibling;
        if (input.type === 'password') {
          input.type = 'text';
          eye.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
          input.type = 'password';
          eye.classList.replace('fa-eye-slash', 'fa-eye');
        }
      });
    });

    // Submit form via fetch
    document.getElementById('resetForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);

      fetch('reset_password_action.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        const alertDiv = document.getElementById('alertMessage');
        alertDiv.classList.remove('d-none', 'alert-success', 'alert-danger');

        if (data.success) {
          alertDiv.classList.add('alert-success');
          alertDiv.textContent = data.message;

          setTimeout(() => {
            window.location.href = '../index.php'; // login page
          }, 3000);

        } else {
          alertDiv.classList.add('alert-danger');
          alertDiv.textContent = data.message;
        }
      })
      .catch(err => {
        console.error(err);
        const alertDiv = document.getElementById('alertMessage');
        alertDiv.classList.remove('d-none', 'alert-success');
        alertDiv.classList.add('alert-danger');
        alertDiv.textContent = 'Something went wrong. Please try again.';
      });
    });
  </script>
</body>
</html>
