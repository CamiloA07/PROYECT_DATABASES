<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireAdmin();
$pageTitle = 'Productos';
$msg = '';
$tipo = '';
$editando = null;

// CREAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear') {
    try {
        $pdo->prepare("INSERT INTO Producto (nombre,descripcion,precio,categoria) VALUES (?,?,?,?)")
            ->execute([$_POST['nombre'], $_POST['descripcion'], $_POST['precio'], $_POST['categoria']]);
        $msg = '✅ Producto creado.'; $tipo = 'success';
    } catch (PDOException $e) { $msg = '❌ '.$e->getMessage(); $tipo='error'; }
}

// ACTUALIZAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'actualizar') {
    try {
        $pdo->prepare("UPDATE Producto SET nombre=?,descripcion=?,precio=?,categoria=? WHERE id_producto=?")
            ->execute([$_POST['nombre'],$_POST['descripcion'],$_POST['precio'],$_POST['categoria'],$_POST['id_producto']]);
        $msg = '✅ Producto actualizado.'; $tipo = 'success';
    } catch (PDOException $e) { $msg = '❌ '.$e->getMessage(); $tipo='error'; }
}

// DESACTIVAR (soft delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'desactivar') {
    $pdo->prepare("UPDATE Producto SET activo=0 WHERE id_producto=?")->execute([$_POST['id_producto']]);
    $msg = '✅ Producto desactivado.'; $tipo = 'success';
}

// Cargar para editar
if (isset($_GET['editar'])) {
    $editando = $pdo->prepare("SELECT * FROM Producto WHERE id_producto=?");
    $editando->execute([$_GET['editar']]);
    $editando = $editando->fetch();
}

$productos = $pdo->query("SELECT * FROM Producto ORDER BY activo DESC, nombre")->fetchAll();

require_once 'includes/header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-title"><?= $editando ? 'Editar producto' : 'Nuevo producto' ?></div>
  <form method="POST">
    <input type="hidden" name="accion" value="<?= $editando ? 'actualizar' : 'crear' ?>">
    <?php if ($editando): ?>
    <input type="hidden" name="id_producto" value="<?= $editando['id_producto'] ?>">
    <?php endif; ?>
    <div class="form-row">
      <div class="form-group">
        <label>Nombre</label>
        <input type="text" name="nombre" required value="<?= htmlspecialchars($editando['nombre'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Categoría</label>
        <select name="categoria">
          <?php foreach (['Salada','Dulce','Complemento'] as $cat): ?>
          <option <?= ($editando['categoria'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Precio</label>
        <input type="number" name="precio" step="100" min="0" required value="<?= $editando['precio'] ?? '' ?>">
      </div>
      <div class="form-group">
        <label>Descripción</label>
        <input type="text" name="descripcion" value="<?= htmlspecialchars($editando['descripcion'] ?? '') ?>">
      </div>
    </div>
    <button class="btn btn-primary"><?= $editando ? 'Guardar cambios' : 'Crear producto' ?></button>
    <?php if ($editando): ?>
    <a href="productos.php" class="btn btn-ghost" style="margin-left:8px">Cancelar</a>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-title">Lista de productos</div>
  <table>
    <thead><tr><th>#</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($productos as $p): ?>
      <tr>
        <td><?= $p['id_producto'] ?></td>
        <td><?= htmlspecialchars($p['nombre']) ?></td>
        <td><?= $p['categoria'] ?></td>
        <td>$<?= number_format($p['precio'],0,',','.') ?></td>
        <td><span class="badge <?= $p['activo'] ? 'badge-ok' : 'badge-danger' ?>"><?= $p['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
        <td style="display:flex;gap:8px">
          <a href="?editar=<?= $p['id_producto'] ?>" class="btn btn-ghost btn-sm">Editar</a>
          <?php if ($p['activo']): ?>
          <form method="POST" onsubmit="return confirm('¿Desactivar?')">
            <input type="hidden" name="accion" value="desactivar">
            <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
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
