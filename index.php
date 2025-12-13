<?php
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT u.id, u.email, u.password_hash, u.full_name, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $password === $user['password_hash']) {

        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['full_name'],
            'role' => $user['role_name']
        ];

        audit_log(
            $user['id'], 
            $user['email'], 
            $user['role_name'], 
            'LOGIN', 
            null, 
            null, 
            json_encode(['ip'=>$_SERVER['REMOTE_ADDR'] ?? ''])
        );

        // redirección según rol
        if (strtolower($user['role_name']) === 'student') {
            header('Location: student.php');
        } elseif (strtolower($user['role_name']) === 'professor') {
            header('Location: professor.php');
        } else {
            header('Location: admin.php');
        }
        exit;
    } else {
        $error = 'Credenciales incorrectas';
    }
}

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Lab Requests System</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background: linear-gradient(135deg, #1a7e2bff 0%, #4b9f59ff 50%, #68ba6aff 100%);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .container {
      background-color: white;
      border-radius: 20px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
      width: 100%;
      max-width: 850px;
      overflow: hidden;
      position: relative;
    }

    .header {
      background: linear-gradient(to right, #1a7e2cff, #41ab39ff);
      color: white;
      padding: 30px 25px;
      text-align: center;
      position: relative;
    }

    .header h1 {
      font-size: 26px;
      font-weight: 600;
      margin-bottom: 5px;
    }

    .header p {
      opacity: 0.9;
      font-size: 14px;
    }

    .header-icon {
      background-color: rgba(255, 255, 255, 0.1);
      width: 70px;
      height: 70px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      font-size: 32px;
    }

    .form-container {
      padding: 35px 30px;
    }

    .error {
      background-color: #ffebee;
      color: #c62828;
      padding: 12px 15px;
      border-radius: 8px;
      margin-bottom: 25px;
      font-size: 14px;
      border-left: 4px solid #c62828;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .error i {
      font-size: 18px;
    }

    .form-group {
      margin-bottom: 25px;
      position: relative;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #333;
      font-size: 14px;
    }

    .input-with-icon {
      position: relative;
    }

    .input-with-icon i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #5c6bc0;
      font-size: 18px;
    }

    .form-control {
      width: 100%;
      padding: 15px 15px 15px 50px;
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      font-size: 16px;
      transition: all 0.3s ease;
      background-color: #f8f9fa;
    }

    .form-control:focus {
      border-color: #3949ab;
      box-shadow: 0 0 0 3px rgba(57, 73, 171, 0.1);
      outline: none;
      background-color: white;
    }

    .btn-login {
      background: linear-gradient(to right, #1a7e27ff, #39ab54ff);
      color: white;
      border: none;
      padding: 16px;
      width: 100%;
      border-radius: 10px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 10px;
      letter-spacing: 0.5px;
    }

    .btn-login:hover {
      background: linear-gradient(to right, #0d1b6a, #283593);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(57, 73, 171, 0.3);
    }

    .btn-login:active {
      transform: translateY(0);
    }

    .seed-users {
      background-color: #f5f5f5;
      border-radius: 10px;
      padding: 20px;
      margin-top: 30px;
      border-left: 4px solid #5c6bc0;
    }

    .seed-users h3 {
      color: #1a237e;
      font-size: 16px;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .seed-users p {
      color: #555;
      font-size: 14px;
      line-height: 1.5;
    }

    .user-role {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      margin-right: 8px;
    }

    .admin-role {
      background-color: #ffebee;
      color: #c62828;
    }

    .professor-role {
      background-color: #e8f5e9;
      color: #2e7d32;
    }

    .student-role {
      background-color: #e3f2fd;
      color: #1565c0;
    }

    .footer {
      text-align: center;
      padding: 20px;
      color: #777;
      font-size: 13px;
      border-top: 1px solid #eee;
      background-color: #fafafa;
    }

    @media (max-width: 480px) {
      .container {
        border-radius: 15px;
      }
      
      .header {
        padding: 25px 20px;
      }
      
      .form-container {
        padding: 25px 20px;
      }
      
      .header h1 {
        font-size: 22px;
      }
    }

    .password-toggle {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #777;
      cursor: pointer;
      font-size: 18px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="header-icon">
        <i class="fas fa-flask"></i>
      </div>
      <h1>Lab Requests System</h1>
      <p>Accede a tu cuenta para gestionar solicitudes</p>
    </div>
    
    <div class="form-container">
      <?php if (!empty($error)): ?>
        <div class="error">
          <i class="fas fa-exclamation-circle"></i>
          <span><?=htmlspecialchars($error)?></span>
        </div>
      <?php endif; ?>
      
      <form method="post" action="index.php" id="loginForm">
        <div class="form-group">
          <label for="email">Correo electrónico</label>
          <div class="input-with-icon">
            <i class="fas fa-envelope"></i>
            <input class="form-control" id="email" name="email" type="email" required placeholder="usuario@ejemplo.com">
          </div>
        </div>
        
        <div class="form-group">
          <label for="password">Contraseña</label>
          <div class="input-with-icon">
            <i class="fas fa-lock"></i>
            <input class="form-control" id="password" name="password" type="password" required placeholder="••••••••">
            <button type="button" class="password-toggle" id="togglePassword">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
        
        <button type="submit" class="btn-login">
          <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
        </button>
      </form>
      
      <div class="seed-users">
        <h3><i class="fas fa-users"></i> Usuarios de demostración</h3>
        <p>
          <span class="user-role admin-role">Admin</span> admin@example.com / Admin123!<br>
          <span class="user-role professor-role">Profesor</span> prof1@example.com / Prof123!<br>
          <span class="user-role student-role">Estudiante</span> student1@example.com / Stud123!
        </p>
      </div>
    </div>
    
    <div class="footer">
      <p>Sistema de Gestión de Solicitudes de Laboratorio &copy; <?=date('Y')?></p>
    </div>
  </div>

  <script>
    // Toggle para mostrar/ocultar contraseña
    document.getElementById('togglePassword').addEventListener('click', function() {
      const passwordInput = document.getElementById('password');
      const icon = this.querySelector('i');
      
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    });

    // Efecto de focus en los campos del formulario
    const formControls = document.querySelectorAll('.form-control');
    formControls.forEach(control => {
      control.addEventListener('focus', function() {
        this.parentElement.parentElement.classList.add('focused');
      });
      
      control.addEventListener('blur', function() {
        this.parentElement.parentElement.classList.remove('focused');
      });
    });

    // Pequeña animación al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelector('.container').style.opacity = '0';
      document.querySelector('.container').style.transform = 'translateY(20px)';
      
      setTimeout(() => {
        document.querySelector('.container').style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        document.querySelector('.container').style.opacity = '1';
        document.querySelector('.container').style.transform = 'translateY(0)';
      }, 100);
    });
  </script>
</body>
</html>