<?php
require_once __DIR__ . '/functions.php';
require_login();
require_role('student');

$pdo = getDB();
$user = current_user();

// handle create request form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_request'])) {
    $lab_id = $_POST['lab_id'];
    $date = $_POST['requested_date'];
    $time = $_POST['requested_time'];
    $notes = $_POST['notes'] ?? '';

    $stmt = $pdo->prepare("INSERT INTO lab_requests (student_id, lab_id, requested_date, requested_time, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user['id'], $lab_id, $date, $time, $notes]);
    $newId = $pdo->lastInsertId();
    audit_log($user['id'], $user['email'], $user['role'], 'CREATE', 'lab_requests', $newId, json_encode(['lab_id'=>$lab_id,'date'=>$date,'time'=>$time]));
    header('Location: student.php');
    exit;
}

// list student's requests
$stmt = $pdo->prepare("SELECT lr.*, l.name as lab_name FROM lab_requests lr JOIN labs l ON lr.lab_id = l.id WHERE lr.student_id = ? ORDER BY lr.created_at DESC");
$stmt->execute([$user['id']]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// labs
$labs = $pdo->query("SELECT * FROM labs")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Estudiante - Lab Requests</title>
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

    .student-container {
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
      width: 380px;
      background: linear-gradient(180deg, #1565c0 0%, #1976d2 100%);
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
      color: #90caf9;
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
      background: linear-gradient(135deg, #1976d2, #42a5f5);
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
      margin-left: 380px;
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
      color: #1565c0;
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
      color: #1565c0;
    }

    .stats-container {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
    }

    .stats-badge {
      background: linear-gradient(135deg, #1565c0, #1976d2);
      color: white;
      padding: 12px 24px;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
      box-shadow: 0 5px 15px rgba(21, 101, 192, 0.2);
    }

    .stats-badge.pending {
      background: linear-gradient(135deg, #ff8f00, #ffb300);
    }

    .stats-badge.approved {
      background: linear-gradient(135deg, #2e7d32, #4caf50);
    }

    .stats-badge.rejected {
      background: linear-gradient(135deg, #c62828, #e53935);
    }

    /* Content Cards */
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
      color: #1565c0;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .card-title i {
      color: #1976d2;
    }

    .request-count {
      padding: 8px 18px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .new-request-count {
      background-color: #e3f2fd;
      color: #1565c0;
    }

    .my-requests-count {
      background-color: #f3e5f5;
      color: #7b1fa2;
    }

    /* Form Styles */
    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 25px;
      margin-bottom: 30px;
    }

    .form-group {
      margin-bottom: 25px;
    }

    .form-group label {
      display: block;
      margin-bottom: 10px;
      font-weight: 600;
      color: #444;
      font-size: 15px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .form-group label i {
      color: #1565c0;
      font-size: 16px;
    }

    .form-control {
      width: 100%;
      padding: 16px 20px;
      border: 2px solid #e0e0e0;
      border-radius: 12px;
      font-size: 16px;
      transition: all 0.3s ease;
      background-color: #fafafa;
    }

    .form-control:focus {
      border-color: #1976d2;
      box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
      outline: none;
      background-color: white;
    }

    select.form-control {
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%231565c0' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 20px center;
      background-size: 16px;
      padding-right: 50px;
    }

    textarea.form-control {
      min-height: 120px;
      resize: vertical;
      font-family: inherit;
      line-height: 1.5;
    }

    .form-actions {
      text-align: right;
      margin-top: 30px;
      padding-top: 25px;
      border-top: 1px solid #eee;
    }

    .btn-submit {
      background: linear-gradient(135deg, #1565c0, #1976d2);
      color: white;
      border: none;
      padding: 16px 40px;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      box-shadow: 0 5px 15px rgba(21, 101, 192, 0.2);
    }

    .btn-submit:hover {
      background: linear-gradient(135deg, #0d47a1, #1565c0);
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(21, 101, 192, 0.3);
    }

    /* Table Styles */
    .table-container {
      overflow-x: auto;
      border-radius: 15px;
      border: 1px solid #e0e0e0;
      margin-top: 20px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 900px;
    }

    thead {
      background: linear-gradient(to right, #1565c0, #1976d2);
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
      background-color: #f8fbff;
      transform: scale(1.002);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    td {
      padding: 18px;
      color: #555;
      font-size: 15px;
      vertical-align: middle;
    }

    .lab-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .lab-icon {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #1976d2, #42a5f5);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 18px;
    }

    .lab-name {
      font-weight: 600;
      color: #1565c0;
    }

    .date-time-cell {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .date-display {
      font-weight: 600;
      color: #333;
    }

    .time-display {
      background-color: #e3f2fd;
      color: #1565c0;
      padding: 6px 12px;
      border-radius: 8px;
      font-weight: 600;
      display: inline-block;
      width: fit-content;
    }

    /* Status Badges */
    .status-badge {
      padding: 8px 16px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 13px;
      display: inline-block;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .status-pending {
      background-color: #fff3e0;
      color: #f57c00;
    }

    .status-approved {
      background-color: #e8f5e9;
      color: #2e7d32;
    }

    .status-rejected {
      background-color: #ffebee;
      color: #c62828;
    }

    /* Action Buttons */
    .action-buttons {
      display: flex;
      gap: 10px;
    }

    .btn-cancel {
      background: linear-gradient(135deg, #c62828, #e53935);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      box-shadow: 0 4px 12px rgba(198, 40, 40, 0.2);
    }

    .btn-cancel:hover {
      background: linear-gradient(135deg, #b71c1c, #d32f2f);
      transform: translateY(-2px);
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

      .header {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
      }

      .stats-container {
        width: 100%;
        justify-content: space-between;
      }

      .form-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 768px) {
      .main-content {
        padding: 20px;
      }
      
      .content-card {
        padding: 25px 20px;
      }
      
      .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      
      .btn-submit {
        width: 100%;
        padding: 16px 20px;
      }

      .action-buttons {
        flex-direction: column;
      }

      .btn-cancel {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <div class="student-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <h1><i class="fas fa-user-graduate"></i> <span>Panel Estudiante</span></h1>
        <div class="user-info">
          <div class="user-avatar">
            <?= strtoupper(substr($user['name'], 0, 1)) ?>
          </div>
          <div class="user-details">
            <h3><?= htmlspecialchars($user['name']) ?></h3>
            <p><?= htmlspecialchars($user['email']) ?></p>
            <span class="user-role-badge">ESTUDIANTE</span>
          </div>
        </div>
      </div>
      
      <ul class="nav-menu">
        <li class="nav-item"><a href="student.php" class="active"><i class="fas fa-flask"></i> <span>Solicitudes de Laboratorio</span></a></li>
        <li class="nav-item"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Cerrar Sesión</span></a></li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <div class="header">
        <div class="header-left">
          <h2>Solicitudes de Laboratorio</h2>
          <p><i class="fas fa-info-circle"></i> Crea y gestiona tus solicitudes de uso de laboratorios</p>
        </div>
        <div class="stats-container">
          <?php 
          $pending_count = 0;
          $approved_count = 0;
          $rejected_count = 0;
          
          foreach ($requests as $r) {
            if ($r['status'] === 'PENDING') $pending_count++;
            if ($r['status'] === 'APPROVED') $approved_count++;
            if ($r['status'] === 'REJECTED') $rejected_count++;
          }
          ?>
          <div class="stats-badge pending">
            <i class="fas fa-clock"></i>
            <span><?= $pending_count ?> Pendientes</span>
          </div>
          <div class="stats-badge approved">
            <i class="fas fa-check-circle"></i>
            <span><?= $approved_count ?> Aprobadas</span>
          </div>
          <div class="stats-badge rejected">
            <i class="fas fa-times-circle"></i>
            <span><?= $rejected_count ?> Rechazadas</span>
          </div>
          <div class="stats-badge">
            <i class="fas fa-list-alt"></i>
            <span><?= count($requests) ?> Total</span>
          </div>
        </div>
      </div>

      <!-- New Request Card -->
      <div class="content-card">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-plus-circle"></i> Crear Nueva Solicitud</h3>
          <div class="request-count new-request-count">
            <i class="fas fa-vial"></i>
            <span><?= count($labs) ?> Laboratorios Disponibles</span>
          </div>
        </div>
        
        <form method="post" action="student.php" id="requestForm">
          <div class="form-grid">
            <div class="form-group">
              <label for="lab_id"><i class="fas fa-building"></i> Laboratorio</label>
              <select class="form-control" id="lab_id" name="lab_id" required>
                <option value="">Seleccione un laboratorio</option>
                <?php foreach($labs as $lab): ?>
                  <option value="<?= $lab['id'] ?>"><?= htmlspecialchars($lab['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="form-group">
              <label for="requested_date"><i class="fas fa-calendar-alt"></i> Fecha Solicitada</label>
              <input class="form-control" type="date" id="requested_date" name="requested_date" required min="<?= date('Y-m-d') ?>">
            </div>
            
            <div class="form-group">
              <label for="requested_time"><i class="fas fa-clock"></i> Hora Solicitada</label>
              <input class="form-control" type="time" id="requested_time" name="requested_time" required>
            </div>
          </div>
          
          <div class="form-group">
            <label for="notes"><i class="fas fa-sticky-note"></i> Notas Adicionales</label>
            <textarea class="form-control" id="notes" name="notes" placeholder="Describe brevemente el propósito de tu solicitud (opcional)"></textarea>
          </div>
          
          <div class="form-actions">
            <button type="submit" name="create_request" class="btn-submit">
              <i class="fas fa-paper-plane"></i> Enviar Solicitud
            </button>
          </div>
        </form>
      </div>

      <!-- My Requests Card -->
      <div class="content-card">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-history"></i> Mis Solicitudes</h3>
          <div class="request-count my-requests-count">
            <i class="fas fa-file-alt"></i>
            <span><?= count($requests) ?> Solicitudes</span>
          </div>
        </div>
        
        <?php if (count($requests) > 0): ?>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Laboratorio</th>
                  <th>Fecha y Hora</th>
                  <th>Estado</th>
                  <th>Creado el</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($requests as $r): ?>
                  <tr>
                    <td><strong>#<?= $r['id'] ?></strong></td>
                    <td>
                      <div class="lab-info">
                        <div class="lab-icon">
                          <i class="fas fa-flask"></i>
                        </div>
                        <div class="lab-name"><?= htmlspecialchars($r['lab_name']) ?></div>
                      </div>
                    </td>
                    <td>
                      <div class="date-time-cell">
                        <div class="date-display"><?= date('d/m/Y', strtotime($r['requested_date'])) ?></div>
                        <div class="time-display"><?= date('H:i', strtotime($r['requested_time'])) ?></div>
                      </div>
                    </td>
                    <td>
                      <?php if ($r['status'] === 'PENDING'): ?>
                        <span class="status-badge status-pending">
                          <i class="fas fa-clock"></i> Pendiente
                        </span>
                      <?php elseif ($r['status'] === 'APPROVED'): ?>
                        <span class="status-badge status-approved">
                          <i class="fas fa-check-circle"></i> Aprobado
                        </span>
                      <?php elseif ($r['status'] === 'REJECTED'): ?>
                        <span class="status-badge status-rejected">
                          <i class="fas fa-times-circle"></i> Rechazado
                        </span>
                      <?php else: ?>
                        <span class="status-badge"><?= $r['status'] ?></span>
                      <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                    <td>
                      <?php if ($r['status'] === 'PENDING'): ?>
                        <div class="action-buttons">
                          <form method="post" action="actions.php" style="display:inline">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button type="submit" class="btn-cancel">
                              <i class="fas fa-times"></i> Cancelar
                            </button>
                          </form>
                        </div>
                      <?php else: ?>
                        <span style="color:#999; font-size:14px;">No disponible</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>No hay solicitudes registradas</h3>
            <p>Comienza creando tu primera solicitud de laboratorio utilizando el formulario superior.</p>
          </div>
        <?php endif; ?>
      </div>

      <div class="footer">
        <p>Sistema de Gestión de Laboratorios &copy; <?= date('Y') ?> | Panel de Estudiante | Última actualización: <?= date('d/m/Y H:i') ?></p>
      </div>
    </main>
  </div>

  <script>
    // Confirmación antes de cancelar una solicitud
    document.querySelectorAll('.btn-cancel').forEach(button => {
      button.addEventListener('click', function(e) {
        if (!confirm('¿Está seguro de que desea CANCELAR esta solicitud? Esta acción no se puede deshacer.')) {
          e.preventDefault();
        }
      });
    });

    // Validación de fecha mínima (hoy)
    document.addEventListener('DOMContentLoaded', function() {
      const today = new Date().toISOString().split('T')[0];
      document.getElementById('requested_date').setAttribute('min', today);
      
      // Establecer hora por defecto (próxima hora)
      const now = new Date();
      const nextHour = new Date(now.getTime() + 60 * 60 * 1000);
      const timeString = nextHour.getHours().toString().padStart(2, '0') + ':' + 
                         nextHour.getMinutes().toString().padStart(2, '0');
      document.getElementById('requested_time').value = timeString;
    });

    // Animación de carga
    document.addEventListener('DOMContentLoaded', function() {
      // Efecto de entrada para las tarjetas
      document.querySelectorAll('.content-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
          card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
          card.style.opacity = '1';
          card.style.transform = 'translateY(0)';
        }, 300 + (index * 100));
      });
    });

    // Validación del formulario
    document.getElementById('requestForm').addEventListener('submit', function(e) {
      const labId = document.getElementById('lab_id').value;
      const date = document.getElementById('requested_date').value;
      const time = document.getElementById('requested_time').value;
      
      if (!labId || !date || !time) {
        e.preventDefault();
        alert('Por favor complete todos los campos obligatorios.');
        return false;
      }
      
      // Validar que la fecha no sea en el pasado
      const selectedDate = new Date(date + 'T' + time);
      const now = new Date();
      
      if (selectedDate < now) {
        e.preventDefault();
        alert('No puede seleccionar una fecha y hora en el pasado.');
        return false;
      }
      
      return true;
    });
  </script>
</body>
</html>