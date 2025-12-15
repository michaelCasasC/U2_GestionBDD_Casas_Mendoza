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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Lab Requests System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {min-height: 100vh; background: #eafde7;}
        .panel-izquierdo {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.4)), url('./Rename.jpg');
            background-size: cover; 
            background-position: center; 
            color: #eafde7; 
            border-radius: 0 40px 40px 0;
        }
        .titulo-principal {
            font-size: 2.8rem; 
            font-weight: 700; 
            line-height: 1.2;
        }
        .titulo-formulario {
            font-size: 2rem; 
            font-weight: 700; 
            color: #00312D;
        }
        .entrada-con-icono {
            position: relative;
        }
        .entrada-con-icono i {
            position: absolute; 
            left: 15px; 
            top: 50%; 
            transform: translateY(-50%); 
            color: #999;
        }
        .entrada-con-icono .form-control {
            padding-left: 45px;
        }
        .form-control {
            border: 1px solid #e0e0e0; 
            padding: 12px 15px; 
            border-radius: 8px; 
            background: white;
        }
        .boton-ingresar {
            background: #00312D; 
            border: none; 
            padding: 14px; 
            border-radius: 8px; 
            font-weight: 600; 
            color: white;
            transition: all 0.3s ease;
        }
        .boton-ingresar:hover {
            background: #001f1c;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 49, 45, 0.3);
        }
        .seccion-demostracion {
            background: #f0f0f0; 
            border-radius: 12px; 
            padding: 20px;
            margin-top: 30px;
        }
        .boton-demo {
            border: none; 
            padding: 12px 20px; 
            border-radius: 8px; 
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .boton-demo:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }
        .demo1 {background-color: #00312D; color:white;}
        .demo2 {background-color: #1a7e2c; color:white;}
        .demo3 {background-color: #2e7d32; color:white;}
        .alert-danger {
            border-radius: 8px;
            border-left: 4px solid #c62828;
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
        @media (max-width: 768px) {
            .panel-izquierdo {border-radius: 20px 20px 0 0;}
            .titulo-principal {font-size: 2.2rem;}
            .titulo-formulario {font-size: 1.8rem;}
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row min-vh-100">

            <!-- Panel Izquierdo -->
            <div class="col-md-6 panel-izquierdo d-flex flex-column justify-content-center p-5">
                <h1 class="titulo-principal mb-3">Sistema de<br>Gestión de Laboratorios</h1>
                <p class="subtitulo mb-0">Gestiona tus solicitudes<br>de manera eficiente</p>
            </div>

            <!-- Panel Derecho -->
            <div class="col-md-6 p-5 d-flex flex-column justify-content-center">
                <h2 class="titulo-formulario mb-4">Iniciar Sesión</h2>

                <form id="formularioLogin" method="POST" action="index.php">
                    <div class="mb-3">
                        <label for="correo" class="form-label fw-medium text-secondary">Email</label>
                        <div class="entrada-con-icono">
                            <i class="fas fa-envelope"></i>
                            <input id="email" type="email" name="email" class="form-control" placeholder="usuario@ejemplo.com" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="contrasena" class="form-label fw-medium text-secondary">Contraseña</label>
                        <div class="entrada-con-icono">
                            <i class="fas fa-lock"></i>
                            <input id="password" type="password" name="password" class="form-control" placeholder="••••••••" required>
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <span><?=htmlspecialchars($error)?></span>
                        </div>
                    <?php endif; ?>

                    <button type="submit" id="submit" class="btn boton-ingresar w-100 mb-4">
                        <i class="fas fa-sign-in-alt me-2"></i>Ingresar al Sistema
                    </button>

                    <div class="seccion-demostracion">
                        <p class="text-center text-secondary mb-3 fw-medium">Usuarios de demostración:</p>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn boton-demo demo1" id="btnAdmin">
                                <i class="fas fa-user-shield me-2"></i>Admin
                            </button>
                            <button type="button" class="btn boton-demo demo2" id="btnProfesor">
                                <i class="fas fa-chalkboard-teacher me-2"></i>Profesor
                            </button>
                            <button type="button" class="btn boton-demo demo3" id="btnEstudiante">
                                <i class="fas fa-user-graduate me-2"></i>Estudiante
                            </button>
                        </div>
                        <p class="text-center text-secondary mt-3 small">
                            Contraseña: misma que el usuario (Admin123!, Prof123!, Stud123!)
                        </p>
                    </div>

                    <div class="col text-center mt-4">
                        <p class="text-secondary mb-0 small">
                            Sistema de Gestión de Solicitudes de Laboratorio &copy; <?=date('Y')?>
                        </p>
                    </div>
                </form>
            </div>
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

        // Auto completar demo - Admin
        document.getElementById('btnAdmin').onclick = () => {
            document.getElementById('email').value = "admin@example.com";
            document.getElementById('password').value = "Admin123!";
            document.getElementById('submit').click();
        };

        // Auto completar demo - Profesor
        document.getElementById('btnProfesor').onclick = () => {
            document.getElementById('email').value = "prof1@example.com";
            document.getElementById('password').value = "Prof123!";
            document.getElementById('submit').click();
        };

        // Auto completar demo - Estudiante
        document.getElementById('btnEstudiante').onclick = () => {
            document.getElementById('email').value = "student1@example.com";
            document.getElementById('password').value = "Stud123!";
            document.getElementById('submit').click();
        };

        // Pequeña animación al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const leftPanel = document.querySelector('.panel-izquierdo');
            const rightPanel = document.querySelector('.col-md-6.p-5');
            
            leftPanel.style.opacity = '0';
            leftPanel.style.transform = 'translateX(-20px)';
            rightPanel.style.opacity = '0';
            rightPanel.style.transform = 'translateX(20px)';
            
            setTimeout(() => {
                leftPanel.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                rightPanel.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                
                leftPanel.style.opacity = '1';
                leftPanel.style.transform = 'translateX(0)';
                rightPanel.style.opacity = '1';
                rightPanel.style.transform = 'translateX(0)';
            }, 100);
        });
    </script>
</body>
</html>