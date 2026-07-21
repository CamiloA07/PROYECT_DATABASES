<?php
$host = 'localhost';
$user = 'root';
$pass = 'Camilo12345.';

$seccion = $_GET['s'] ?? 'menu';
$mensaje = '';

function conectar($host, $user, $pass, $db) {
    // Desactivamos el modo silencioso de mysqli para que nos diga exactamente qué falla
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $con = new mysqli($host, $user, $pass, $db);
        return $con;
    } catch (Exception $e) {
        die("<div style='color:red;padding:10px;background:#f8d7da;'><b>Error de conexión a la BD [$db]:</b> " . $e->getMessage() . "</div>");
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Proyectos BD - Producción</title>
<style>
body { font-family: Arial, sans-serif; margin: 0; background: #f4f4f4; }
.nav { background: #2c3e50; padding: 1rem; display: flex; gap: 1rem; flex-wrap: wrap; }
.nav a { color: white; text-decoration: none; padding: 8px 16px; background: #34495e; border-radius: 6px; }
.nav a:hover { background: #1abc9c; }
.container { max-width: 900px; margin: 2rem auto; background: white; padding: 2rem; border-radius: 8px; }
h2 { color: #2c3e50; }
input, select { width: 100%; padding: 8px; margin: 6px 0 14px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
button { background: #1abc9c; color: white; padding: 10px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 15px; }
button:hover { background: #16a085; }
table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
th { background: #2c3e50; color: white; padding: 8px; }
td { padding: 8px; border-bottom: 1px solid #eee; }
.ok { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-top: 1rem; }
.err { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-top: 1rem; }
</style>
</head>
<body>
<div class="nav">
  <a href="?s=menu">Inicio</a>
  <a href="?s=pizza">Pizza Master</a>
  <a href="?s=zapateria">Zapatería</a>
  <a href="?s=veterinaria">Veterinaria</a>
  <a href="?s=biblioteca">Biblioteca</a>
  <a href="?s=zoologico">Zoológico</a>
</div>
<div class="container">

<?php if ($seccion === 'menu'): ?>
  <h2>Sistema de Bases de Datos Integrado</h2>
  <p>Selecciona un módulo en el menú superior para interactuar con los procedimientos, vistas y transacciones.</p>

<?php elseif ($seccion === 'pizza'): ?>
  <h2>Pizza Master — Registrar Pedido</h2>
  <?php
  $con = conectar($host, $user, $pass, 'pizzamaster_db');
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pizza_submit'])) {
      $id_clie = intval($_POST['id_cliente']);
      $id_pizz = intval($_POST['id_pizza']);
      $total   = floatval($_POST['total']);
      
      $con->begin_transaction();
      try {
          $stmt = $con->prepare("CALL registrar_pedido(?, ?, ?)");
          $stmt->bind_param("iid", $id_clie, $id_pizz, $total);
          $stmt->execute();
          $con->commit();
          $mensaje = '<div class="ok">¡Éxito! Pedido guardado mediante Transacción y SP. El TRIGGER de auditoría se ejecutó.</div>';
      } catch (Exception $e) {
          $con->rollback();
          $mensaje = '<div class="err">Error en la transacción: ' . $e->getMessage() . '</div>';
      }
  }
  $clientes = $con->query("SELECT id_cliente, nombre FROM clientes");
  $pizzas   = $con->query("SELECT id_pizza, nombre_pizza, precio FROM pizzas");
  echo $mensaje;
  ?>
  <form method="POST">
    <label>Cliente</label>
    <select name="id_cliente" required>
      <?php while($r = $clientes->fetch_assoc()): ?>
        <option value="<?= $r['id_cliente'] ?>"><?= $r['nombre'] ?></option>
      <?php endwhile; ?>
    </select>
    <label>Pizza</label>
    <select name="id_pizza" required>
      <?php while($r = $pizzas->fetch_assoc()): ?>
        <option value="<?= $r['id_pizza'] ?>"><?= $r['nombre_pizza'] ?> ($<?= $r['precio'] ?>)</option>
      <?php endwhile; ?>
    </select>
    <label>Total Pago</label>
    <input type="number" name="total" step="0.01" value="25000">
    <button name="pizza_submit">Registrar Pedido</button>
  </form>

  <h3>Vista: Gastos Totales por Cliente</h3>
  <table>
    <tr><th>ID Cliente</th><th>Nombre</th><th>Total Acumulado</th></tr>
    <?php
    $vista = $con->query("SELECT * FROM vista_gastos_clientes");
    while($r = $vista->fetch_assoc()): ?>
      <tr><td><?= $r['id_cliente'] ?></td><td><?= $r['nombre'] ?></td><td>$<?= $r['total_acumulado'] ?></td></tr>
    <?php endwhile; ?>
  </table>

<?php elseif ($seccion === 'zapateria'): ?>
  <h2>Zapatería — Registrar Producto</h2>
  <?php
  $con = conectar($host, $user, $pass, 'zapateria_db');
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['zap_submit'])) {
      $nombre = $_POST['nombre'];
      $talla  = floatval($_POST['talla']);
      $precio = floatval($_POST['precio']);
      $stock  = intval($_POST['stock']);
      $marca  = intval($_POST['id_marca']);

      $con->begin_transaction();
      try {
          $stmt = $con->prepare("CALL registrar_producto(?, ?, ?, ?, ?)");
          $stmt->bind_param("sddii", $nombre, $talla, $precio, $stock, $marca);
          $stmt->execute();
          $con->commit();
          $mensaje = '<div class="ok">Producto añadido correctamente mediante el Procedimiento Almacenado.</div>';
      } catch (Exception $e) {
          $con->rollback();
          $mensaje = '<div class="err">Error: ' . $e->getMessage() . '</div>';
      }
  }
  $marcas = $con->query("SELECT id_marca, nombre FROM marca");
  echo $mensaje;
  ?>
  <form method="POST">
    <label>Nombre del Zapato</label>
    <input type="text" name="nombre" required>
    <label>Talla</label>
    <input type="number" name="talla" step="0.5" value="38">
    <label>Precio</label>
    <input type="number" name="precio" step="0.01" value="150">
    <label>Stock Inicial</label>
    <input type="number" name="stock" value="10">
    <label>Marca</label>
    <select name="id_marca" required>
      <?php while($r = $marcas->fetch_assoc()): ?>
        <option value="<?= $r['id_marca'] ?>"><?= $r['nombre'] ?></option>
      <?php endwhile; ?>
    </select>
    <button name="zap_submit">Guardar Zapato</button>
  </form>

  <h3>Vista: Productos Premium (Más Costosos)</h3>
  <table>
    <tr><th>ID</th><th>Nombre</th><th>Talla</th><th>Precio</th><th>Marca</th></tr>
    <?php
    $vista = $con->query("SELECT * FROM vista_producto_premium");
    while($r = $vista->fetch_assoc()): ?>
      <tr><td><?= $r['id_producto'] ?></td><td><?= $r['nombre_producto'] ?></td><td><?= $r['talla'] ?></td><td>$<?= $r['precio'] ?></td><td><?= $r['marca'] ?></td></tr>
    <?php endwhile; ?>
  </table>

<?php elseif ($seccion === 'veterinaria'): ?>
  <h2>Veterinaria — Registrar Cita</h2>
  <?php
  $con = conectar($host, $user, $pass, 'veterinaria_db');
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vet_submit'])) {
      $fecha   = $_POST['fecha'];
      $motivo  = $_POST['motivo'];
      $mascota = intval($_POST['id_mascota']);

      $con->begin_transaction();
      try {
          $stmt = $con->prepare("CALL registrar_cita(?, ?, ?)");
          $stmt->bind_param("ssi", $fecha, $motivo, $mascota);
          $stmt->execute();
          $con->commit();
          $mensaje = '<div class="ok">Cita agendada vía SP. El TRIGGER procesó el motivo si venía vacío.</div>';
      } catch (Exception $e) {
          $con->rollback();
          $mensaje = '<div class="err">Error: ' . $e->getMessage() . '</div>';
      }
  }
  // Corregido a 'Mascota' con M mayúscula como tu esquema original en Linux
  $mascotas = $con->query("SELECT id_mascota, nombre FROM Mascota");
  echo $mensaje;
  ?>
  <form method="POST">
    <label>Fecha y Hora</label>
    <input type="datetime-local" name="fecha" required>
    <label>Motivo</label>
    <input type="text" name="motivo" placeholder="Dejar vacío para activar regla del Trigger">
    <label>Paciente (Mascota)</label>
    <select name="id_mascota" required>
      <?php while($r = $mascotas->fetch_assoc()): ?>
        <option value="<?= $r['id_mascota'] ?>"><?= $r['nombre'] ?></option>
      <?php endwhile; ?>
    </select>
    <button name="vet_submit">Agendar Cita</button>
  </form>

  <h3>Vista: Historial Clínico Completo</h3>
  <table>
    <tr><th>ID Cita</th><th>Fecha</th><th>Motivo Evaluado</th><th>Mascota</th><th>Dueño</th></tr>
    <?php
    $vista = $con->query("SELECT * FROM vista_citas_completa");
    while($r = $vista->fetch_assoc()): ?>
      <tr><td><?= $r['id_cita'] ?></td><td><?= $r['fecha_cita'] ?></td><td><?= $r['motivo'] ?></td><td><?= $r['mascota'] ?></td><td><?= $r['Propietario'] ?></td></tr>
    <?php endwhile; ?>
  </table>

<?php elseif ($seccion === 'biblioteca'): ?>
  <h2>Biblioteca — Registrar Retiro de Libro</h2>
  <?php
  $con = conectar($host, $user, $pass, 'Biblioteca'); // 'Biblioteca' con B mayúscula
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bib_submit'])) {
      $user_id = intval($_POST['id_usuario']);
      $book_id = $_POST['id_libro'];
      $fecha   = $_POST['fecha'];

      $con->begin_transaction();
      try {
          $stmt = $con->prepare("CALL registrar_retiro(?, ?, ?)");
          $stmt->bind_param("iss", $user_id, $book_id, $fecha);
          $stmt->execute();
          $con->commit();
          $mensaje = '<div class="ok">Retiro procesado con éxito en la transacción por el SP.</div>';
      } catch (Exception $e) {
          $con->rollback();
          $mensaje = '<div class="err">Error: ' . $e->getMessage() . '</div>';
      }
  }
  $usuarios = $con->query("SELECT ID_Usuario, Nombre_Usuario FROM Usuario");
  $libros   = $con->query("SELECT ID_Libro, Titulo_Libro FROM Libro");
  echo $mensaje;
  ?>
  <form method="POST">
    <label>Lector / Usuario</label>
    <select name="id_usuario" required>
      <?php while($r = $usuarios->fetch_assoc()): ?>
        <option value="<?= $r['ID_Usuario'] ?>"><?= $r['Nombre_Usuario'] ?></option>
      <?php endwhile; ?>
    </select>
    <label>Libro a Solicitar</label>
    <select name="id_libro" required>
      <?php while($r = $libros->fetch_assoc()): ?>
        <option value="<?= $r['ID_Libro'] ?>"><?= $r['Titulo_Libro'] ?></option>
      <?php endwhile; ?>
    </select>
    <label>Fecha Préstamo</label>
    <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
    <button name="bib_submit">Efectuar Préstamo</button>
  </form>

  <h3>Vista: Libros Prestados Actualmente</h3>
  <table>
    <tr><th>Lector</th><th>Título del Libro</th><th>Fecha Retiro</th></tr>
    <?php
    $vista = $con->query("SELECT * FROM vista_retiros_completos");
    while($r = $vista->fetch_assoc()): ?>
      <tr><td><?= $r['Nombre_Usuario'] ?></td><td><?= $r['Titulo_Libro'] ?></td><td><?= $r['Fecha_Retiro'] ?></td></tr>
    <?php endwhile; ?>
  </table>

<?php elseif ($seccion === 'zoologico'): ?>
  <h2>Zoológico — Registrar Nuevo Animal</h2>
  <?php
  $con = conectar($host, $user, $pass, 'Zoologico'); // 'Zoologico' con Z mayúscula
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['zoo_submit'])) {
      $id      = $_POST['id_animal'];
      $nombre  = $_POST['nombre'];
      $especie = $_POST['especie'];
      $dieta   = $_POST['dieta'];
      $habitat = $_POST['id_habitat'];

      $con->begin_transaction();
      try {
          $stmt = $con->prepare("CALL registrar_animal(?, ?, ?, ?, ?)");
          $stmt->bind_param("sssss", $id, $nombre, $especie, $dieta, $habitat);
          $stmt->execute();
          $con->commit();
          $mensaje = '<div class="ok">Animal ingresado correctamente al ecosistema mediante SP.</div>';
      } catch (Exception $e) {
          $con->rollback();
          $mensaje = '<div class="err">Error: ' . $e->getMessage() . '</div>';
      }
  }
  $habitats = $con->query("SELECT ID_Habitat, Nombre_Habitat FROM Habitat");
  echo $mensaje;
  ?>
  <form method="POST">
    <label>Código Único (ID)</label>
    <input type="text" name="id_animal" placeholder="Ej: A09" required>
    <label>Nombre Propio</label>
    <input type="text" name="nombre" required>
    <label>Especie</label>
    <input type="text" name="especie" required>
    <label>Tipo de Alimentación</label>
    <select name="dieta" required>
      <option value="Carnívoro">Carnívoro</option>
      <option value="Herbívoro">Herbívoro</option>
      <option value="Omnívoro">Omnívoro</option>
    </select>
    <label>Ubicación / Hábitat</label>
    <select name="id_habitat" required>
      <?php while($r = $habitats->fetch_assoc()): ?>
        <option value="<?= $r['ID_Habitat'] ?>"><?= $r['Nombre_Habitat'] ?></option>
      <?php endwhile; ?>
    </select>
    <button name="zoo_submit">Registrar Especie</button>
  </form>

  <h3>Vista: Censo de Animales por Hábitat</h3>
  <table>
    <tr><th>ID</th><th>Nombre</th><th>Especie</th><th>Dieta</th><th>Zona Asignada</th></tr>
    <?php
    $vista = $con->query("SELECT * FROM vista_animales_habitats");
    while($r = $vista->fetch_assoc()): ?>
      <tr><td><?= $r['ID_Animal'] ?></td><td><?= $r['Nombre_Animal'] ?></td><td><?= $r['Especie'] ?></td><td><?= $r['Dieta'] ?></td><td><?= $r['Nombre_Habitat'] ?></td></tr>
    <?php endwhile; ?>
  </table>

<?php endif; ?>

</div>
</body>
</html>
