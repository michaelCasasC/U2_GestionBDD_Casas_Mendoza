<?php
$u = current_user();
?>
<div class="topnav">
  <div class="left">Logged as: <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['role']) ?>)</div>
  <div class="right">
    <a href="index.php">Inicio</a>
    <?php if ($u && strtolower($u['role'])==='student'): ?>
      <a href="student.php">Mis solicitudes</a>
    <?php endif; ?>
    <?php if ($u && strtolower($u['role'])==='professor'): ?>
      <a href="professor.php">Solicitudes</a>
    <?php endif; ?>
    <?php if ($u && strtolower($u['role'])==='admin'): ?>
      <a href="admin.php">Admin</a>
    <?php endif; ?>
    <a href="logout.php">Salir</a>
  </div>
</div>
