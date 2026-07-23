<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();
$pageTitle = 'Egresos';
$msg = '';
$tipo = '';

// Crear egreso — sp_registrar_egreso()
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear' && isAdmin()) {
    try {
        $stmt = $pdo->prepare("CALL sp_registrar_egreso(?,?,?,?)");
        $stmt->execute([
            $_SESSION['usuario_id'],
            $_POST['categoria'],
            $_POST['descripcion'],
            (float)$_POST['monto'],
        ]);
        $msg  = '✅ Egreso registrado.';
        $tipo = 'success';
    } catch (PDOException $e) {
        $msg  = '❌ ' . $e->getMessage();
        $tipo = 'error';
    }
}

// Eliminar egreso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar' && isAdmin()) {
    $pdo->prepare("DELETE FROM Egresos WHERE id_egreso=?")->execute([$_POST['id_egreso']]);
    $msg  = '✅ Egreso eliminado.';
    $tipo = 'success';
}

// Vista egresos por categoría + lista
$porCategoria = $pdo->query("SELECT * FROM v_egresos_por_categoria")->fetchAll();
$egresos = $pdo->query("
    SELECT e.*, u.nombre as empleado
    FROM Egresos e JOIN Usuario u ON e.id_usuario=u.id_usuario
    ORDER BY e.fecha DESC LIMIT 50
")->fetchAll();

require_once 'includes/header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if (isAdmin()): ?>
<div class="card">
  <div class="card-title">Registrar egreso — sp_registrar_egreso()</div>
  <form method="POST">
    <input type="hidden" name="accion" value="crear">
    <div class="form-row">
      <div class="form-group">
        <label>Categoría</label>
        <select name="categoria" required>
          <option>Arriendo</option>
          <option>Servicios públicos</option>
          <option>Materia prima</option>
          <option>Nómina</option>
          <option>Mantenimiento</option>
          <option>Otro</option>
        </select>
      </div>
      <div class="form-group">
        <label>Monto ($)</label>
        <input type="number" name="monto" min="1" step="100" required>
      </div>
    </div>
    <div class="form-group">
      <label>Descripción</label>
      <textarea name="descripcion" rows="2" required></textarea>
    </div>
    <button class="btn btn-primary">Registrar egreso</button>
  </form>
</div>
<?php endif; ?>

<!-- Vista por categoría -->
<div class="card">
  <div class="card-title">Resumen por categoría — vista v_egresos_por_categoria</div>
  <table>
    <thead><tr><th>Categoría</th><th>Registros</th><th>Total gastado</th><th>Último</th></tr></thead>
    <tbody>
    <?php foreach ($porCategoria as $c): ?>
      <tr>
        <td><?= htmlspecialchars($c['categoria']) ?></td>
        <td><?= $c['num_registros'] ?></td>
        <td>$<?= number_format($c['total_gastado'], 0, ',', '.') ?></td>
        <td><?= date('d/m/Y', strtotime($c['ultimo_egreso'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Lista de egresos -->
<div class="card">
  <div class="card-title">Todos los egresos</div>
  <table>
    <thead>
      <tr>
        <th>#</th><th>Fecha</th><th>Categoría</th>
        <th>Descripción</th><th>Monto</th><th>Usuario</th>
        <?php if (isAdmin()): ?><th></th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($egresos as $e): ?>
      <tr>
        <td><?= $e['id_egreso'] ?></td>
        <td><?= date('d/m/Y', strtotime($e['fecha'])) ?></td>
        <td><?= htmlspecialchars($e['categoria']) ?></td>
        <td><?= htmlspecialchars($e['descripcion']) ?></td>
        <td>$<?= number_format($e['monto'], 0, ',', '.') ?></td>
        <td><?= htmlspecialchars($e['empleado']) ?></td>
        <?php if (isAdmin()): ?>
        <td>
          <form method="POST" onsubmit="return confirm('¿Eliminar este egreso?')">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id_egreso" value="<?= $e['id_egreso'] ?>">
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
