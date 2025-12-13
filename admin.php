<?php
require_once __DIR__ . '/functions.php';
require_login();
require_role('admin');

$pdo = getDB();
$user = current_user();

// list all requests
$requests = $pdo->query("SELECT lr.*, l.name as lab_name, u.full_name as student_name FROM lab_requests lr JOIN labs l ON lr.lab_id = l.id JOIN users u ON lr.student_id = u.id ORDER BY lr.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// list audit logs (last 200)
$audit = $pdo->query("SELECT TOP 200 * FROM audit_logs ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Administración - Lab Requests</title>
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

    .admin-container {
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
      width: 260px;
      background: linear-gradient(180deg, #1a237e 0%, #283593 100%);
      color: white;
      padding: 25px 0;
      box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
      position: fixed;
      height: 100vh;
      z-index: 100;
    }

    .sidebar-header {
      padding: 0 25px 25px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      margin-bottom: 25px;
    }

    .sidebar-header h1 {
      font-size: 22px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .sidebar-header h1 i {
      color: #5c6bc0;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-top: 20px;
    }

    .user-avatar {
      width: 45px;
      height: 45px;
      background: linear-gradient(135deg, #3949ab, #5c6bc0);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      font-weight: bold;
    }

    .user-details h3 {
      font-size: 16px;
      margin-bottom: 5px;
    }

    .user-details p {
      font-size: 13px;
      opacity: 0.8;
    }

    .nav-menu {
      list-style: none;
      padding: 0 15px;
    }

    .nav-item {
      margin-bottom: 8px;
    }

    .nav-item a {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 20px;
      color: rgba(255, 255, 255, 0.8);
      text-decoration: none;
      border-radius: 10px;
      transition: all 0.3s ease;
      font-weight: 500;
    }

    .nav-item a:hover, .nav-item a.active {
      background-color: rgba(255, 255, 255, 0.1);
      color: white;
    }

    .nav-item a i {
      width: 20px;
      text-align: center;
    }

    /* Main Content */
    .main-content {
      flex: 1;
      margin-left: 260px;
      padding: 30px;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 1px solid #e0e0e0;
    }

    .header h2 {
      color: #1a237e;
      font-size: 28px;
      font-weight: 600;
    }

    .stats-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      display: flex;
      align-items: center;
      gap: 20px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      color: white;
    }

    .icon-requests {
      background: linear-gradient(135deg, #1a237e, #3949ab);
    }

    .icon-audit {
      background: linear-gradient(135deg, #00897b, #4db6ac);
    }

    .stat-info h3 {
      font-size: 14px;
      color: #666;
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .stat-number {
      font-size: 32px;
      font-weight: 700;
      color: #333;
    }

    /* Tables */
    .section {
      background: white;
      border-radius: 15px;
      padding: 30px;
      margin-bottom: 30px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }

    .section-title {
      font-size: 22px;
      color: #1a237e;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .section-title i {
      color: #5c6bc0;
    }

    .table-container {
      overflow-x: auto;
      border-radius: 10px;
      border: 1px solid #e0e0e0;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 800px;
    }

    thead {
      background: linear-gradient(to right, #1a237e, #3949ab);
      color: white;
    }

    th {
      padding: 18px 15px;
      text-align: left;
      font-weight: 600;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    tbody tr {
      border-bottom: 1px solid #f0f0f0;
      transition: background-color 0.2s ease;
    }

    tbody tr:hover {
      background-color: #f8f9ff;
    }

    td {
      padding: 16px 15px;
      color: #555;
      font-size: 14px;
    }

    .status-badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
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

    .audit-details {
      max-width: 300px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .audit-details pre {
      margin: 0;
      font-family: 'Segoe UI', monospace;
      font-size: 12px;
    }

    /* Responsive */
    @media (max-width: 1024px) {
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
      }
    }

    @media (max-width: 768px) {
      .main-content {
        padding: 20px;
      }
      
      .section {
        padding: 20px;
      }
      
      .header h2 {
        font-size: 24px;
      }
      
      .stats-cards {
        grid-template-columns: 1fr;
      }
    }

    /* Empty state */
    .empty-state {
      text-align: center;
      padding: 50px 20px;
      color: #777;
    }

    .empty-state i {
      font-size: 60px;
      color: #ddd;
      margin-bottom: 20px;
    }

    /* Footer */
    .footer {
      text-align: center;
      padding: 20px;
      color: #777;
      font-size: 14px;
      border-top: 1px solid #eee;
      margin-top: 30px;
    }
  </style>
</head>
<body>
  <div class="admin-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <h1><i class="fas fa-flask"></i> <span>Lab Admin</span></h1>
        <div class="user-info">
          <div class="user-avatar">
            <?= strtoupper(substr($user['name'], 0, 1)) ?>
          </div>
          <div class="user-details">
            <h3><?= htmlspecialchars($user['name']) ?></h3>
            <p><?= htmlspecialchars($user['role']) ?></p>
          </div>
        </div>
      </div>
      
      <ul class="nav-menu">
        <li class="nav-item"><a href="admin.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li class="nav-item"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Cerrar Sesión</span></a></li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <div class="header">
        <h2>Panel de Administración</h2>
        <div class="date-display"><?= date('d/m/Y - H:i') ?></div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-cards">
        <div class="stat-card">
          <div class="stat-icon icon-requests">
            <i class="fas fa-clipboard-list"></i>
          </div>
          <div class="stat-info">
            <h3>Solicitudes Totales</h3>
            <div class="stat-number"><?= count($requests) ?></div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon icon-audit">
            <i class="fas fa-history"></i>
          </div>
          <div class="stat-info">
            <h3>Registros de Auditoría</h3>
            <div class="stat-number"><?= count($audit) ?></div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon" style="background: linear-gradient(135deg, #ff9800, #ffb74d);">
            <i class="fas fa-user-graduate"></i>
          </div>
          <div class="stat-info">
            <h3>Estudiantes Únicos</h3>
            <div class="stat-number">
              <?php
              $uniqueStudents = array_unique(array_column($requests, 'student_id'));
              echo count($uniqueStudents);
              ?>
            </div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon" style="background: linear-gradient(135deg, #9c27b0, #ba68c8);">
            <i class="fas fa-vial"></i>
          </div>
          <div class="stat-info">
            <h3>Laboratorios Activos</h3>
            <div class="stat-number">
              <?php
              $uniqueLabs = array_unique(array_column($requests, 'lab_id'));
              echo count($uniqueLabs);
              ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Solicitudes -->
      <div class="section">
        <div class="section-header">
          <h3 class="section-title"><i class="fas fa-clipboard-list"></i> Todas las Solicitudes</h3>
          <button class="btn-export"><i class="fas fa-download"></i> Exportar</button>
        </div>
        
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Estudiante</th>
                <th>Laboratorio</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Estado</th>
                <th>Procesado por</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($requests) > 0): ?>
                <?php foreach($requests as $r): ?>
                  <tr>
                    <td><strong>#<?= $r['id'] ?></strong></td>
                    <td><?= htmlspecialchars($r['student_name']) ?></td>
                    <td><?= htmlspecialchars($r['lab_name']) ?></td>
                    <td><?= date('d/m/Y', strtotime($r['requested_date'])) ?></td>
                    <td><?= date('H:i', strtotime($r['requested_time'])) ?></td>
                    <td>
                      <span class="status-badge status-<?= strtolower($r['status']) ?>">
                        <?= $r['status'] ?>
                      </span>
                    </td>
                    <td><?= $r['processed_by'] ? htmlspecialchars($r['processed_by']) : '<span style="color:#999">No procesado</span>' ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7">
                    <div class="empty-state">
                      <i class="fas fa-clipboard"></i>
                      <h3>No hay solicitudes registradas</h3>
                      <p>Todavía no se han creado solicitudes de laboratorio.</p>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Audit Logs -->
      <div class="section">
        <div class="section-header">
          <h3 class="section-title"><i class="fas fa-history"></i> Registros de Auditoría (Últimos 200)</h3>
        </div>
        
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Tiempo</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Acción</th>
                <th>Objetivo</th>
                <th>Detalles</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($audit) > 0): ?>
                <?php foreach($audit as $a): ?>
                  <tr>
                    <td><?= date('d/m/Y H:i:s', strtotime($a['created_at'])) ?></td>
                    <td><strong><?= htmlspecialchars($a['user_email']) ?></strong></td>
                    <td><span class="status-badge"><?= htmlspecialchars($a['user_role']) ?></span></td>
                    <td><span class="status-badge" style="background:#e1bee7;color:#7b1fa2;"><?= htmlspecialchars($a['action']) ?></span></td>
                    <td><?= htmlspecialchars($a['target_table'] . ' #' . $a['target_id']) ?></td>
                    <td class="audit-details" title="<?= htmlspecialchars($a['details']) ?>">
                      <pre><?= htmlspecialchars(substr($a['details'], 0, 100)) . (strlen($a['details']) > 100 ? '...' : '') ?></pre>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6">
                    <div class="empty-state">
                      <i class="fas fa-history"></i>
                      <h3>No hay registros de auditoría</h3>
                      <p>Los registros de auditoría aparecerán aquí.</p>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="footer">
        <p>Sistema de Gestión de Laboratorios &copy; <?= date('Y') ?> | Panel de Administración</p>
      </div>
    </main>
  </div>

  <script>
    // Funcionalidad para expandir detalles de auditoría
    document.querySelectorAll('.audit-details').forEach(cell => {
      cell.addEventListener('click', function() {
        const details = this.getAttribute('title');
        if (details) {
          alert('Detalles completos:\n\n' + details);
        }
      });
    });

    // Alternar sidebar en móviles
    document.addEventListener('DOMContentLoaded', function() {
      // Efecto de carga
      document.querySelectorAll('.stat-card, .section').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
      });

      setTimeout(() => {
        document.querySelectorAll('.stat-card, .section').forEach((el, index) => {
          setTimeout(() => {
            el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
          }, index * 100);
        });
      }, 300);
    });

    // Exportar datos (funcionalidad básica)
    document.querySelector('.btn-export').addEventListener('click', function() {
      alert('Funcionalidad de exportación. En una implementación real, aquí se generarían archivos CSV/Excel.');
    });
  </script>
</body>
</html>