<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();
$pageTitle = 'Inventario';
$msg = '';
$tipo = '';

// Reponer inventario — sp_reponer_inventario()
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'reponer' && isAdmin()) {
    try {
        $stmt = $pdo->prepare("CALL sp_reponer_inventario(?,?,?)");
        $stmt->execute([$_POST['id_producto'], (int)$_POST['cantidad'], $_POST['fecha_caducidad']]);
        $msg = '✅ Inventario reponido correctamente.';
        $tipo = 'success';
    } catch (PDOException $e) {
        $msg  = '❌ ' . $e->getMessage();
        $tipo = 'error';
    }
}

// Eliminar lote (solo admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar' && isAdmin()) {
    $pdo->prepare("DELETE FROM Inventario WHERE id_inventario=?")->execute([$_POST['id_inventario']]);
    $msg  = '✅ Lote eliminado.';
    $tipo = 'success';
}

$productos  = $pdo->query("SELECT id_producto, nombre FROM Producto WHERE activo=1 ORDER BY nombre")->fetchAll();
// Usa la vista v_inventario_alertas
$inventario = $pdo->query("SELECT * FROM v_inventario_alertas")->fetchAll();

require_once 'includes/header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if (isAdmin()): ?>
<div class="card">
  <div class="card-title">Reponer inventario — sp_reponer_inventario()</div>
  <form method="POST">
    <input type="hidden" name="accion" value="reponer">
    <div class="form-row">
      <div class="form-group">
        <label>Producto</label>
        <select name="id_producto" required>
          <?php foreach ($productos as $p): ?>
          <option value="<?= $p['id_producto'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Cantidad a agregar</label>
        <input type="number" name="cantidad" min="1" value="50" required>
      </div>
    </div>
    <div class="form-group" style="max-width:220px">
      <label>Fecha de caducidad</label>
      <input type="date" name="fecha_caducidad" required
             min="<?= date('Y-m-d') ?>"
             value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
    </div>
    <button class="btn btn-primary">Reponer</button>
  </form>
</div>
<?php endif; ?>

<!-- Tabla inventario con alertas — VISTA v_inventario_alertas -->
<div class="card">
  <div class="card-title">Estado del inventario — vista v_inventario_alertas</div>
  <table>
    <thead>
      <tr>
        <th>Producto</th><th>Categoría</th><th>Stock</th>
        <th>Mínimo</th><th>Caducidad</th><th>Días</th><th>Estado</th>
        <?php if (isAdmin()): ?><th>Acción</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($inventario as $i): ?>
      <?php
        $badgeClass = 'badge-ok';
        if (strpos($i['estado'],'bajo') !== false)    $badgeClass = 'badge-danger';
        if (strpos($i['estado'],'vencer') !== false)  $badgeClass = 'badge-warn';
      ?>
      <tr>
        <td><?= htmlspecialchars($i['producto']) ?></td>
        <td><?= htmlspecialchars($i['categoria']) ?></td>
        <td><strong><?= $i['stock_actual'] ?></strong></td>
        <td><?= $i['stock_minimo'] ?></td>
        <td><?= date('d/m/Y', strtotime($i['fecha_caducidad'])) ?></td>
        <td><?= $i['dias_para_vencer'] ?></td>
        <td><span class="badge <?= $badgeClass ?>"><?= $i['estado'] ?></span></td>
        <?php if (isAdmin()): ?>
        <td>
          <form method="POST" onsubmit="return confirm('¿Eliminar este lote?')">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id_inventario" value="<?= $i['id_inventario'] ?>">
            <button class="btn btn-danger btn-sm">Eliminar</button>
          </form>
        </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once 'includes/footer.php'; ?>
