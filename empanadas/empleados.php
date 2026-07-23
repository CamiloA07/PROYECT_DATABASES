<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireAdmin();
$pageTitle = 'Empleados';
$msg = ''; $tipo = ''; $editando = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'crear') {
            $pdo->prepare("INSERT INTO Usuario (nombre,apellido,cargo,rol,salario,fecha_pago,contrasena)
                           VALUES (?,?,?,?,?,?,SHA2(?,256))")
                ->execute([$_POST['nombre'],$_POST['apellido'],$_POST['cargo'],$_POST['rol'],
                           $_POST['salario'],$_POST['fecha_pago'],$_POST['contrasena']]);
            $msg='✅ Empleado creado.'; $tipo='success';
        } elseif ($accion === 'actualizar') {
            $pdo->prepare("UPDATE Usuario SET nombre=?,apellido=?,cargo=?,rol=?,salario=?,fecha_pago=? WHERE id_usuario=?")
                ->execute([$_POST['nombre'],$_POST['apellido'],$_POST['cargo'],$_POST['rol'],
                           $_POST['salario'],$_POST['fecha_pago'],$_POST['id_usuario']]);
            $msg='✅ Empleado actualizado.'; $tipo='success';
        } elseif ($accion === 'desactivar') {
            $pdo->prepare("UPDATE Usuario SET activo=0 WHERE id_usuario=?")->execute([$_POST['id_usuario']]);
            $msg='✅ Empleado desactivado.'; $tipo='success';
        } elseif ($accion === 'pagar') {
            $pdo->query("CALL sp_pagar_empleados()");
            $msg='✅ Nómina pagada y registrada en egresos.'; $tipo='success';
        }
    } catch (PDOException $e) { $msg='❌ '.$e->getMessage(); $tipo='error'; }
}

if (isset($_GET['editar'])) {
    $s = $pdo->prepare("SELECT * FROM Usuario WHERE id_usuario=?");
    $s->execute([$_GET['editar']]); $editando = $s->fetch();
}

$empleados = $pdo->query("SELECT * FROM Usuario ORDER BY activo DESC, nombre")->fetchAll();
require_once 'includes/header.php';
?>
<?php if ($msg): ?><div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<!-- Pagar nómina -->
<div class="card">
  <div class="card-title">Pago de nómina — sp_pagar_empleados()</div>
  <p style="font-size:14px;color:var(--muted);margin-bottom:16px">
    Este procedimiento registra el salario de todos los empleados activos en egresos y actualiza la fecha de pago.
  </p>
  <form method="POST" onsubmit="return confirm('¿Ejecutar pago de nómina a todos los empleados activos?')">
    <input type="hidden" name="accion" value="pagar">
    <button class="btn btn-primary">Ejecutar pago de nómina</button>
  </form>
</div>

<!-- Formulario -->
<div class="card">
  <div class="card-title"><?= $editando ? 'Editar empleado' : 'Nuevo empleado' ?></div>
  <form method="POST">
    <input type="hidden" name="accion" value="<?= $editando ? 'actualizar' : 'crear' ?>">
    <?php if ($editando): ?><input type="hidden" name="id_usuario" value="<?= $editando['id_usuario'] ?>"><?php endif; ?>
    <div class="form-row">
      <div class="form-group">
        <label>Nombre</label>
        <input type="text" name="nombre" required value="<?= htmlspecialchars($editando['nombre'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Apellido</label>
        <input type="text" name="apellido" required value="<?= htmlspecialchars($editando['apellido'] ?? '') ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Cargo</label>
        <input type="text" name="cargo" required value="<?= htmlspecialchars($editando['cargo'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Rol</label>
        <select name="rol">
          <option value="empleado" <?= ($editando['rol'] ?? '') === 'empleado' ? 'selected' : '' ?>>Empleado</option>
          <option value="admin"    <?= ($editando['rol'] ?? '') === 'admin'    ? 'selected' : '' ?>>Admin</option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Salario ($)</label>
        <input type="number" name="salario" min="1160000" step="10000" required value="<?= $editando['salario'] ?? 1300000 ?>">
      </div>
      <div class="form-group">
        <label>Fecha de pago</label>
        <input type="date" name="fecha_pago" required value="<?= $editando['fecha_pago'] ?? date('Y-m-d') ?>">
      </div>
    </div>
    <?php if (!$editando): ?>
    <div class="form-group" style="max-width:280px">
      <label>Contraseña inicial</label>
      <input type="text" name="contrasena" required>
    </div>
    <?php endif; ?>
    <button class="btn btn-primary"><?= $editando ? 'Guardar cambios' : 'Crear empleado' ?></button>
    <?php if ($editando): ?><a href="empleados.php" class="btn btn-ghost" style="margin-left:8px">Cancelar</a><?php endif; ?>
  </form>
</div>

<!-- Lista -->
<div class="card">
  <div class="card-title">Empleados</div>
  <table>
    <thead><tr><th>#</th><th>Nombre</th><th>Cargo</th><th>Rol</th><th>Salario</th><th>Fecha pago</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($empleados as $e): ?>
      <tr>
        <td><?= $e['id_usuario'] ?></td>
        <td><?= htmlspecialchars($e['nombre'].' '.$e['apellido']) ?></td>
        <td><?= htmlspecialchars($e['cargo']) ?></td>
        <td><?= $e['rol'] ?></td>
        <td>$<?= number_format($e['salario'],0,',','.') ?></td>
        <td><?= date('d/m/Y', strtotime($e['fecha_pago'])) ?></td>
        <td><span class="badge <?= $e['activo'] ? 'badge-ok' : 'badge-danger' ?>"><?= $e['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
        <td style="display:flex;gap:8px">
          <a href="?editar=<?= $e['id_usuario'] ?>" class="btn btn-ghost btn-sm">Editar</a>
          <?php if ($e['activo'] && $e['id_usuario'] != $_SESSION['usuario_id']): ?>
          <form method="POST" onsubmit="return confirm('¿Desactivar empleado?')">
            <input type="hidden" name="accion" value="desactivar">
            <input type="hidden" name="id_usuario" value="<?= $e['id_usuario'] ?>">
            <button class="btn btn-danger btn-sm">Desactivar</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once 'includes/footer.php'; ?>
