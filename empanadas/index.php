<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $clave  = trim($_POST['contrasena'] ?? '');

    $stmt = $pdo->prepare("
        SELECT id_usuario, nombre, apellido, rol
        FROM Usuario
        WHERE nombre = ? AND contrasena = SHA2(?, 256) AND activo = 1
        LIMIT 1
    ");
    $stmt->execute([$nombre, $clave]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['usuario_id']     = $user['id_usuario'];
        $_SESSION['usuario_nombre'] = $user['nombre'] . ' ' . $user['apellido'];
        $_SESSION['rol']            = $user['rol'];
        header('Location: /empanadas/dashboard.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Empanadas — Acceso</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'DM Sans', sans-serif;
    min-height: 100vh;
    background: #1a0a00;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
  }
  body::before {
    content: '🫓';
    font-size: 320px;
    position: absolute;
    opacity: .04;
    top: -60px; right: -60px;
    pointer-events: none;
    filter: grayscale(1);
  }
  .card {
    background: #fff;
    border-radius: 20px;
    padding: 48px 40px;
    width: 100%;
    max-width: 400px;
    position: relative;
    z-index: 1;
  }
  .brand {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    color: #b84a00;
    margin-bottom: 4px;
  }
  .brand-sub {
    font-size: 13px;
    color: #888;
    margin-bottom: 36px;
  }
  label {
    display: block;
    font-size: 12px;
    font-weight: 500;
    color: #555;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 6px;
  }
  input {
    display: block;
    width: 100%;
    padding: 12px 14px;
    border: 1.5px solid #e0d6cc;
    border-radius: 10px;
    font-size: 15px;
    font-family: inherit;
    color: #1a0a00;
    margin-bottom: 20px;
    transition: border-color .2s;
    outline: none;
  }
  input:focus { border-color: #b84a00; }
  .btn {
    width: 100%;
    padding: 13px;
    background: #b84a00;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 500;
    font-family: inherit;
    cursor: pointer;
    transition: background .2s;
  }
  .btn:hover { background: #8f3800; }
  .error {
    background: #fff0eb;
    color: #b84a00;
    border-left: 3px solid #b84a00;
    padding: 10px 14px;
    border-radius: 6px;
    font-size: 13px;
    margin-bottom: 20px;
  }
</style>
</head>
<body>
<div class="card">
  <div class="brand">La Empanada</div>
  <div class="brand-sub">Sistema de gestión</div>

  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <label>Usuario</label>
    <input type="text" name="nombre" required autocomplete="username">
    <label>Contraseña</label>
    <input type="password" name="contrasena" required autocomplete="current-password">
    <button type="submit" class="btn">Ingresar</button>
  </form>
</div>
</body>
</html>
