<?php
require_once __DIR__ . '/functions.php';
require_login();
require_role('professor');

$pdo = getDB();
$user = current_user();

// list pending requests
$stmt = $pdo->prepare("SELECT lr.*, l.name as lab_name, u.full_name as student_name, u.email as student_email FROM lab_requests lr JOIN labs l ON lr.lab_id = l.id JOIN users u ON lr.student_id = u.id WHERE lr.status = 'PENDING' ORDER BY lr.created_at ASC");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Profesor - Lab Requests</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background-color: #f5f7fb;
      color: #333;
      min-height: 100vh;
    }

    .professor-container {
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
      width: 280px;
      background: linear-gradient(180deg, #2e7d32 0%, #388e3c 100%);
      color: white;
      padding: 30px 0;
      box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
      position: fixed;
      height: 100vh;
      z-index: 100;
    }

    .sidebar-header {
      padding: 0 30px 30px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      margin-bottom: 30px;
    }

    .sidebar-header h1 {
      font-size: 24px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .sidebar-header h1 i {
      color: #a5d6a7;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-top: 25px;
      background: rgba(255, 255, 255, 0.1);
      padding: 15px;
      border-radius: 12px;
      backdrop-filter: blur(5px);
    }

    .user-avatar {
      width: 55px;
      height: 55px;
      background: linear-gradient(135deg, #43a047, #66bb6a);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      font-weight: bold;
      border: 3px solid rgba(255, 255, 255, 0.2);
    }

    .user-details h3 {
      font-size: 17px;
      margin-bottom: 5px;
    }

    .user-details p {
      font-size: 14px;
      opacity: 0.9;
    }

    .user-role-badge {
      display: inline-block;
      background: rgba(255, 255, 255, 0.2);
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      margin-top: 5px;
    }

    .nav-menu {
      list-style: none;
      padding: 0 20px;
    }

    .nav-item {
      margin-bottom: 8px;
    }

    .nav-item a {
      display: flex;
      align-items: center;
      gap: 15px;
      padding: 16px 20px;
      color: rgba(255, 255, 255, 0.85);
      text-decoration: none;
      border-radius: 12px;
      transition: all 0.3s ease;
      font-weight: 500;
      font-size: 15px;
    }

    .nav-item a:hover, .nav-item a.active {
      background-color: rgba(255, 255, 255, 0.15);
      color: white;
      transform: translateX(5px);
    }

    .nav-item a i {
      width: 22px;
      text-align: center;
      font-size: 18px;
    }

    /* Main Content */
    .main-content {
      flex: 1;
      margin-left: 280px;
      padding: 35px;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 35px;
      padding-bottom: 25px;
      border-bottom: 1px solid #e0e0e0;
    }

    .header-left h2 {
      color: #2e7d32;
      font-size: 32px;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .header-left p {
      color: #666;
      font-size: 16px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .header-left p i {
      color: #2e7d32;
    }

    .stats-badge {
      background: linear-gradient(135deg, #2e7d32, #4caf50);
      color: white;
      padding: 10px 20px;
      border-radius: 10px;
      font-size: 18px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
      box-shadow: 0 5px 15px rgba(46, 125, 50, 0.2);
    }

    /* Content Card */
    .content-card {
      background: white;
      border-radius: 20px;
      padding: 35px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      margin-bottom: 40px;
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }

    .card-title {
      font-size: 26px;
      color: #2e7d32;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .card-title i {
      color: #4caf50;
    }

    .pending-count {
      background-color: #fff8e1;
      color: #ff8f00;
      padding: 8px 18px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Table */
    .table-container {
      overflow-x: auto;
      border-radius: 15px;
      border: 1px solid #e0e0e0;
      margin-top: 20px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 1000px;
    }

    thead {
      background: linear-gradient(to right, #2e7d32, #43a047);
      color: white;
    }

    th {
      padding: 20px 18px;
      text-align: left;
      font-weight: 600;
      font-size: 15px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    tbody tr {
      border-bottom: 1px solid #f0f0f0;
      transition: all 0.3s ease;
    }

    tbody tr:hover {
      background-color: #f8fbf8;
      transform: scale(1.002);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    td {
      padding: 18px;
      color: #555;
      font-size: 15px;
      vertical-align: top;
    }

    .student-info {
      min-width: 250px;
    }

    .student-name {
      font-weight: 600;
      color: #333;
      margin-bottom: 5px;
    }

    .student-email {
      font-size: 14px;
      color: #666;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .student-email i {
      color: #2e7d32;
      font-size: 14px;
    }

    .lab-name {
      font-weight: 600;
      color: #2e7d32;
      font-size: 16px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .lab-name i {
      color: #4caf50;
    }

    .date-time {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .date-cell {
      font-weight: 600;
      color: #333;
    }

    .time-cell {
      background-color: #e8f5e9;
      color: #2e7d32;
      padding: 5px 10px;
      border-radius: 8px;
      font-weight: 600;
      display: inline-block;
      width: fit-content;
    }

    .notes-cell {
      max-width: 300px;
      line-height: 1.5;
    }

    .notes-content {
      background-color: #f5f5f5;
      padding: 12px;
      border-radius: 10px;
      border-left: 4px solid #4caf50;
      font-size: 14px;
      max-height: 100px;
      overflow-y: auto;
    }

    /* Action Buttons */
    .action-buttons {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .btn-accept, .btn-reject {
      border: none;
      padding: 12px 24px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      min-width: 120px;
    }

    .btn-accept {
      background: linear-gradient(135deg, #2e7d32, #4caf50);
      color: white;
      box-shadow: 0 4px 12px rgba(46, 125, 50, 0.2);
    }

    .btn-accept:hover {
      background: linear-gradient(135deg, #1b5e20, #388e3c);
      transform: translateY(-3px);
      box-shadow: 0 6px 18px rgba(46, 125, 50, 0.3);
    }

    .btn-reject {
      background: linear-gradient(135deg, #c62828, #e53935);
      color: white;
      box-shadow: 0 4px 12px rgba(198, 40, 40, 0.2);
    }

    .btn-reject:hover {
      background: linear-gradient(135deg, #b71c1c, #d32f2f);
      transform: translateY(-3px);
      box-shadow: 0 6px 18px rgba(198, 40, 40, 0.3);
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 80px 20px;
      color: #777;
    }

    .empty-state i {
      font-size: 70px;
      color: #e0e0e0;
      margin-bottom: 25px;
    }

    .empty-state h3 {
      font-size: 24px;
      color: #999;
      margin-bottom: 15px;
    }

    .empty-state p {
      font-size: 16px;
      max-width: 500px;
      margin: 0 auto;
      line-height: 1.6;
    }

    /* Footer */
    .footer {
      text-align: center;
      padding: 25px;
      color: #777;
      font-size: 14px;
      border-top: 1px solid #eee;
      margin-top: 40px;
      background-color: white;
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    /* Responsive */
    @media (max-width: 1200px) {
      .sidebar {
        width: 240px;
      }
      
      .main-content {
        margin-left: 240px;
        padding: 25px;
      }
    }

    @media (max-width: 992px) {
      .sidebar {
        width: 80px;
      }
      
      .sidebar-header h1 span, 
      .user-details,
      .nav-item a span {
        display: none;
      }
      
      .main-content {
        margin-left: 80px;
      }
      
      .sidebar-header {
        padding: 0 15px 25px;
        text-align: center;
      }
      
      .user-info {
        justify-content: center;
        padding: 10px;
      }
      
      .user-avatar {
        width: 45px;
        height: 45px;
      }
    }

    @media (max-width: 768px) {
      .main-content {
        padding: 20px;
      }
      
      .content-card {
        padding: 25px 20px;
      }
      
      .header {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
      }
      
      .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      
      .action-buttons {
        flex-direction: column;
        width: 100%;
      }
      
      .btn-accept, .btn-reject {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <div class="professor-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <h1><i class="fas fa-chalkboard-teacher"></i> <span>Panel Profesor</span></h1>
        <div class="user-info">
          <div class="user-avatar">
            <?= strtoupper(substr($user['name'], 0, 1)) ?>
          </div>
          <div class="user-details">
            <h3><?= htmlspecialchars($user['name']) ?></h3>
            <p><?= htmlspecialchars($user['email']) ?></p>
            <span class="user-role-badge">PROFESOR</span>
          </div>
        </div>
      </div>
      
      <ul class="nav-menu">
        <li class="nav-item"><a href="professor.php" class="active"><i class="fas fa-tasks"></i> <span>Solicitudes Pendientes</span></a></li>
        <li class="nav-item"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Cerrar Sesión</span></a></li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <div class="header">
        <div class="header-left">
          <h2>Gestión de Solicitudes</h2>
          <p><i class="fas fa-info-circle"></i> Revise y apruebe las solicitudes de laboratorio pendientes</p>
        </div>
        <div class="stats-badge">
          <i class="fas fa-clock"></i>
          <span><?= count($requests) ?> Solicitud<?= count($requests) !== 1 ? 'es' : '' ?> Pendiente<?= count($requests) !== 1 ? 's' : '' ?></span>
        </div>
      </div>

      <!-- Main Card -->
      <div class="content-card">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-clipboard-list"></i> Solicitudes Pendientes de Revisión</h3>
          <div class="pending-count">
            <i class="fas fa-exclamation-circle"></i>
            <span>Requieren su atención</span>
          </div>
        </div>
        
        <?php if (count($requests) > 0): ?>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Estudiante</th>
                  <th>Laboratorio</th>
                  <th>Fecha y Hora</th>
                  <th>Notas</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($requests as $r): ?>
                  <tr>
                    <td><strong>#<?= $r['id'] ?></strong></td>
                    <td class="student-info">
                      <div class="student-name"><?= htmlspecialchars($r['student_name']) ?></div>
                      <div class="student-email">
                        <i class="fas fa-envelope"></i>
                        <?= htmlspecialchars($r['student_email']) ?>
                      </div>
                    </td>
                    <td>
                      <div class="lab-name">
                        <i class="fas fa-flask"></i>
                        <?= htmlspecialchars($r['lab_name']) ?>
                      </div>
                    </td>
                    <td>
                      <div class="date-time">
                        <div class="date-cell"><?= date('d/m/Y', strtotime($r['requested_date'])) ?></div>
                        <div class="time-cell"><?= date('H:i', strtotime($r['requested_time'])) ?></div>
                      </div>
                    </td>
                    <td class="notes-cell">
                      <div class="notes-content">
                        <?= htmlspecialchars($r['notes'] ?: 'Sin notas adicionales') ?>
                      </div>
                    </td>
                    <td>
                      <div class="action-buttons">
                        <form method="post" action="actions.php" style="display:inline">
                          <input type="hidden" name="action" value="accept">
                          <input type="hidden" name="id" value="<?= $r['id'] ?>">
                          <button type="submit" class="btn-accept">
                            <i class="fas fa-check"></i> Aceptar
                          </button>
                        </form>
                        <form method="post" action="actions.php" style="display:inline">
                          <input type="hidden" name="action" value="reject">
                          <input type="hidden" name="id" value="<?= $r['id'] ?>">
                          <button type="submit" class="btn-reject">
                            <i class="fas fa-times"></i> Rechazar
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <i class="fas fa-check-double"></i>
            <h3>¡No hay solicitudes pendientes!</h3>
            <p>Todas las solicitudes de laboratorio han sido procesadas. Los estudiantes podrán crear nuevas solicitudes que aparecerán aquí para su revisión.</p>
          </div>
        <?php endif; ?>
      </div>

      <div class="footer">
        <p>Sistema de Gestión de Laboratorios &copy; <?= date('Y') ?> | Panel de Profesor | Última actualización: <?= date('d/m/Y H:i') ?></p>
      </div>
    </main>
  </div>

  <script>
    // Confirmación antes de rechazar una solicitud
    document.querySelectorAll('.btn-reject').forEach(button => {
      button.addEventListener('click', function(e) {
        if (!confirm('¿Está seguro de que desea RECHAZAR esta solicitud? Esta acción no se puede deshacer.')) {
          e.preventDefault();
        }
      });
    });

    // Confirmación antes de aceptar una solicitud
    document.querySelectorAll('.btn-accept').forEach(button => {
      button.addEventListener('click', function(e) {
        if (!confirm('¿Está seguro de que desea ACEPTAR esta solicitud?')) {
          e.preventDefault();
        }
      });
    });

    // Animación de carga
    document.addEventListener('DOMContentLoaded', function() {
      // Efecto de entrada
      document.querySelector('.content-card').style.opacity = '0';
      document.querySelector('.content-card').style.transform = 'translateY(30px)';
      
      setTimeout(() => {
        document.querySelector('.content-card').style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        document.querySelector('.content-card').style.opacity = '1';
        document.querySelector('.content-card').style.transform = 'translateY(0)';
      }, 300);
    });

    // Actualizar automáticamente cada 60 segundos
    setTimeout(function() {
      location.reload();
    }, 60000);
  </script>
</body>
</html>