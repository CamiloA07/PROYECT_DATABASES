<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireAdmin();
$pageTitle = 'Clientes';
$msg = ''; $tipo = ''; $editando = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'crear') {
            $pdo->prepare("INSERT INTO Cliente (nombre,telefono,direccion) VALUES (?,?,?)")
                ->execute([$_POST['nombre'],$_POST['telefono'],$_POST['direccion']]);
            $msg='✅ Cliente creado.'; $tipo='success';
        } elseif ($accion === 'actualizar') {
            $pdo->prepare("UPDATE Cliente SET nombre=?,telefono=?,direccion=? WHERE id_cliente=?")
                ->execute([$_POST['nombre'],$_POST['telefono'],$_POST['direccion'],$_POST['id_cliente']]);
            $msg='✅ Cliente actualizado.'; $tipo='success';
        } elseif ($accion === 'eliminar') {
            $pdo->prepare("DELETE FROM Cliente WHERE id_cliente=?")->execute([$_POST['id_cliente']]);
            $msg='✅ Cliente eliminado.'; $tipo='success';
        }
    } catch (PDOException $e) { $msg='❌ '.$e->getMessage(); $tipo='error'; }
}

if (isset($_GET['editar'])) {
    $s = $pdo->prepare("SELECT * FROM Cliente WHERE id_cliente=?");
    $s->execute([$_GET['editar']]); $editando = $s->fetch();
}

$clientes = $pdo->query("SELECT * FROM Cliente ORDER BY nombre")->fetchAll();
require_once 'includes/header.php';
?>
<?php if ($msg): ?><div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card">
  <div class="card-title"><?= $editando ? 'Editar cliente' : 'Nuevo cliente' ?></div>
  <form method="POST">
    <input type="hidden" name="accion" value="<?= $editando ? 'actualizar' : 'crear' ?>">
    <?php if ($editando): ?><input type="hidden" name="id_cliente" value="<?= $editando['id_cliente'] ?>"><?php endif; ?>
    <div class="form-row">
      <div class="form-group">
        <label>Nombre</label>
        <input type="text" name="nombre" required value="<?= htmlspecialchars($editando['nombre'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Teléfono</label>
        <input type="text" name="telefono" value="<?= htmlspecialchars($editando['telefono'] ?? '') ?>">
      </div>
    </div>
    <div class="form-group">
      <label>Dirección</label>
      <input type="text" name="direccion" value="<?= htmlspecialchars($editando['direccion'] ?? '') ?>">
    </div>
    <button class="btn btn-primary"><?= $editando ? 'Guardar' : 'Crear cliente' ?></button>
    <?php if ($editando): ?><a href="clientes.php" class="btn btn-ghost" style="margin-left:8px">Cancelar</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-title">Clientes registrados</div>
  <table>
    <thead><tr><th>#</th><th>Nombre</th><th>Teléfono</th><th>Dirección</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($clientes as $c): ?>
      <tr>
        <td><?= $c['id_cliente'] ?></td>
        <td><?= htmlspecialchars($c['nombre']) ?></td>
        <td><?= htmlspecialchars($c['telefono'] ?? '—') ?></td>
        <td><?= htmlspecialchars($c['direccion'] ?? '—') ?></td>
        <td style="display:flex;gap:8px">
          <a href="?editar=<?= $c['id_cliente'] ?>" class="btn btn-ghost btn-sm">Editar</a>
          <form method="POST" onsubmit="return confirm('¿Eliminar cliente?')">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id_cliente" value="<?= $c['id_cliente'] ?>">
            <button class="btn btn-danger btn-sm">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once 'includes/footer.php'; ?>
