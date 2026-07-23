<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();
$pageTitle = 'Ventas';
$msg = '';
$tipo = '';

// ── CREAR venta (llama al procedimiento almacenado sp_registrar_venta) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear') {
    try {
        $stmt = $pdo->prepare("CALL sp_registrar_venta(?,?,?,?,?, @id_venta)");
        $stmt->execute([
            $_POST['id_cliente'],
            $_SESSION['usuario_id'],
            $_POST['metodo_pago'],
            $_POST['id_producto'],
            (int)$_POST['cantidad'],
        ]);
        $id = $pdo->query("SELECT @id_venta as id")->fetchColumn();
        $msg = "✅ Venta #$id registrada correctamente.";
        $tipo = 'success';
    } catch (PDOException $e) {
        $msg  = '❌ ' . $e->getMessage();
        $tipo = 'error';
    }
}

// ── ELIMINAR venta (solo admin) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar' && isAdmin()) {
    try {
        $pdo->prepare("DELETE FROM Detalle_Venta WHERE id_venta=?")->execute([$_POST['id_venta']]);
        $pdo->prepare("DELETE FROM Venta WHERE id_venta=?")->execute([$_POST['id_venta']]);
        $msg = '✅ Venta eliminada.';
        $tipo = 'success';
    } catch (PDOException $e) {
        $msg  = '❌ ' . $e->getMessage();
        $tipo = 'error';
    }
}

$clientes  = $pdo->query("SELECT id_cliente, nombre FROM Cliente ORDER BY nombre")->fetchAll();
$productos = $pdo->query("SELECT id_producto, nombre, precio FROM Producto WHERE activo=1 ORDER BY nombre")->fetchAll();
$ventas    = $pdo->query("
    SELECT v.id_venta, v.fecha, c.nombre as cliente, v.total, v.metodo_pago,
           u.nombre as empleado
    FROM Venta v
    JOIN Cliente c ON v.id_cliente=c.id_cliente
    JOIN Usuario u ON v.id_usuario=u.id_usuario
    ORDER BY v.fecha DESC LIMIT 40
")->fetchAll();

require_once 'includes/header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- Formulario nueva venta -->
<div class="card">
  <div class="card-title">Registrar nueva venta — sp_registrar_venta()</div>
  <form method="POST">
    <input type="hidden" name="accion" value="crear">
    <div class="form-row">
      <div class="form-group">
        <label>Cliente</label>
        <select name="id_cliente" required>
          <?php foreach ($clientes as $c): ?>
          <option value="<?= $c['id_cliente'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Método de pago</label>
        <select name="metodo_pago">
          <option value="efectivo">Efectivo</option>
          <option value="tarjeta">Tarjeta</option>
          <option value="transferencia">Transferencia</option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Producto</label>
        <select name="id_producto" required id="sel-prod">
          <?php foreach ($productos as $p): ?>
          <option value="<?= $p['id_producto'] ?>" data-precio="<?= $p['precio'] ?>">
            <?= htmlspecialchars($p['nombre']) ?> — $<?= number_format($p['precio'],0,',','.') ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Cantidad</label>
        <input type="number" name="cantidad" min="1" value="1" required id="inp-cant">
      </div>
    </div>
    <p style="font-size:14px;color:var(--muted);margin-bottom:16px">
      Subtotal estimado: <strong id="subtotal">$0</strong>
    </p>
    <button class="btn btn-primary">Registrar venta</button>
  </form>
</div>

<!-- Tabla de ventas recientes -->
<div class="card">
  <div class="card-title">Ventas recientes</div>
  <table>
    <thead>
      <tr>
        <th>#</th><th>Fecha</th><th>Cliente</th><th>Empleado</th>
        <th>Total</th><th>Pago</th>
        <?php if (isAdmin()): ?><th>Acción</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($ventas as $v): ?>
      <tr>
        <td><?= $v['id_venta'] ?></td>
        <td><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
        <td><?= htmlspecialchars($v['cliente']) ?></td>
        <td><?= htmlspecialchars($v['empleado']) ?></td>
        <td>$<?= number_format($v['total'], 0, ',', '.') ?></td>
        <td><?= $v['metodo_pago'] ?></td>
        <?php if (isAdmin()): ?>
        <td>
          <form method="POST" onsubmit="return confirm('¿Eliminar esta venta?')">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id_venta" value="<?= $v['id_venta'] ?>">
            <button class="btn btn-danger btn-sm">Eliminar</button>
          </form>
        </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
const sel  = document.getElementById('sel-prod');
const cant = document.getElementById('inp-cant');
const out  = document.getElementById('subtotal');
function calc() {
  const precio = parseFloat(sel.selectedOptions[0]?.dataset.precio || 0);
  const q = parseInt(cant.value) || 0;
  out.textContent = '$' + (precio * q).toLocaleString('es-CO');
}
sel.addEventListener('change', calc);
cant.addEventListener('input', calc);
calc();
</script>

<?php require_once 'includes/footer.php'; ?>
