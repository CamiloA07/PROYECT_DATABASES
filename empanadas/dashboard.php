<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();
$pageTitle = 'Dashboard';

// Estadísticas del día
$ventasHoy   = $pdo->query("SELECT COUNT(*) as n, COALESCE(SUM(total),0) as total FROM Venta WHERE DATE(fecha)=CURDATE()")->fetch();
$egresosHoy  = $pdo->query("SELECT COALESCE(SUM(monto),0) as total FROM Egresos WHERE DATE(fecha)=CURDATE()")->fetch();
$alertasInv  = $pdo->query("SELECT COUNT(*) as n FROM v_inventario_alertas WHERE estado != '🟢 OK'")->fetch();

// Vista resumen financiero últimos 3 meses
$resumen = $pdo->query("SELECT * FROM v_resumen_financiero LIMIT 12")->fetchAll();

// Vista ventas de hoy
$ventas = $pdo->query("SELECT * FROM v_ventas_hoy LIMIT 20")->fetchAll();

// Vista top productos
$topProductos = $pdo->query("SELECT * FROM v_productos_top LIMIT 5")->fetchAll();

// Log de alertas de inventario
$logs = $pdo->query("SELECT l.*, p.nombre as producto FROM Log_Inventario l JOIN Producto p ON l.id_producto=p.id_producto ORDER BY l.fecha DESC LIMIT 5")->fetchAll();

require_once 'includes/header.php';
?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'sin_permiso'): ?>
<div class="alert alert-error">No tienes permisos para acceder a esa sección.</div>
<?php endif; ?>

<!-- Estadísticas -->
<div class="stat-grid">
  <div class="stat-box">
    <div class="label">Ventas hoy</div>
    <div class="value brand"><?= $ventasHoy['n'] ?></div>
  </div>
  <div class="stat-box">
    <div class="label">Ingresos hoy</div>
    <div class="value">$<?= number_format($ventasHoy['total'], 0, ',', '.') ?></div>
  </div>
  <div class="stat-box">
    <div class="label">Egresos hoy</div>
    <div class="value">$<?= number_format($egresosHoy['total'], 0, ',', '.') ?></div>
  </div>
  <div class="stat-box">
    <div class="label">Alertas inventario</div>
    <div class="value <?= $alertasInv['n'] > 0 ? 'brand' : '' ?>"><?= $alertasInv['n'] ?></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

  <!-- Ventas del día — VISTA v_ventas_hoy -->
  <div class="card" style="grid-column:1/-1">
    <div class="card-title">Ventas de hoy — vista v_ventas_hoy</div>
    <?php if (empty($ventas)): ?>
      <p style="color:var(--muted);font-size:14px;">Aún no hay ventas registradas hoy.</p>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>#</th><th>Hora</th><th>Cliente</th><th>Producto</th>
          <th>Cant.</th><th>Subtotal</th><th>Total</th><th>Método</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($ventas as $v): ?>
        <tr>
          <td><?= $v['id_venta'] ?></td>
          <td><?= date('H:i', strtotime($v['fecha'])) ?></td>
          <td><?= htmlspecialchars($v['cliente']) ?></td>
          <td><?= htmlspecialchars($v['producto']) ?></td>
          <td><?= $v['cantidad'] ?></td>
          <td>$<?= number_format($v['subtotal'], 0, ',', '.') ?></td>
          <td>$<?= number_format($v['total'], 0, ',', '.') ?></td>
          <td><?= $v['metodo_pago'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- Top productos — VISTA v_productos_top -->
  <div class="card">
    <div class="card-title">Productos más vendidos — vista v_productos_top</div>
    <table>
      <thead><tr><th>Producto</th><th>Vendidos</th><th>Ingresos</th></tr></thead>
      <tbody>
        <?php foreach ($topProductos as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['nombre']) ?></td>
          <td><?= $p['total_vendido'] ?></td>
          <td>$<?= number_format($p['ingresos_generados'], 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($topProductos)): ?>
        <tr><td colspan="3" style="color:var(--muted)">Sin datos aún</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Resumen financiero — VISTA v_resumen_financiero -->
  <div class="card">
    <div class="card-title">Resumen financiero — vista v_resumen_financiero</div>
    <table>
      <thead><tr><th>Mes</th><th>Tipo</th><th>Monto</th></tr></thead>
      <tbody>
        <?php foreach ($resumen as $r): ?>
        <tr>
          <td><?= $r['mes'] ?></td>
          <td>
            <span class="badge <?= $r['tipo']==='Ingreso' ? 'badge-ok' : 'badge-danger' ?>">
              <?= $r['tipo'] ?>
            </span>
          </td>
          <td>$<?= number_format($r['monto'], 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($resumen)): ?>
        <tr><td colspan="3" style="color:var(--muted)">Sin datos aún</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<!-- Logs de inventario — trigger automático -->
<?php if (!empty($logs)): ?>
<div class="card">
  <div class="card-title">Alertas automáticas del sistema (triggers)</div>
  <?php foreach ($logs as $log): ?>
  <div style="padding:10px 0;border-bottom:1px solid #f0ebe5;font-size:14px;">
    <strong><?= htmlspecialchars($log['producto']) ?></strong>
    — <?= htmlspecialchars($log['mensaje']) ?>
    <span style="color:var(--muted);font-size:12px;margin-left:8px">
      <?= date('d/m H:i', strtotime($log['fecha'])) ?>
    </span>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
