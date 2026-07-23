<?php
// $pageTitle debe estar definido antes de incluir este archivo
$rol = $_SESSION['rol'] ?? 'empleado';
$user = $_SESSION['usuario_nombre'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'La Empanada') ?> — La Empanada</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --brand:     #b84a00;
    --brand-dk:  #8f3800;
    --bg:        #faf7f4;
    --sidebar:   #1a0a00;
    --text:      #1a0a00;
    --muted:     #7a6a60;
    --border:    #e5ddd6;
    --white:     #ffffff;
    --success:   #2d7a4f;
    --danger:    #b84a00;
    --warn:      #a06800;
  }
  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    display: flex;
    min-height: 100vh;
  }

  /* ── Sidebar ── */
  .sidebar {
    width: 230px;
    min-height: 100vh;
    background: var(--sidebar);
    display: flex;
    flex-direction: column;
    padding: 0;
    flex-shrink: 0;
    position: sticky;
    top: 0;
    height: 100vh;
  }
  .sidebar-brand {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    color: #fff;
    padding: 28px 24px 20px;
    border-bottom: 1px solid rgba(255,255,255,.08);
  }
  .sidebar-brand span {
    display: block;
    font-family: 'DM Sans', sans-serif;
    font-size: 11px;
    color: rgba(255,255,255,.4);
    font-weight: 400;
    letter-spacing: .06em;
    text-transform: uppercase;
    margin-top: 2px;
  }
  .nav-section {
    font-size: 10px;
    color: rgba(255,255,255,.3);
    text-transform: uppercase;
    letter-spacing: .1em;
    padding: 20px 24px 6px;
  }
  .nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 24px;
    color: rgba(255,255,255,.65);
    text-decoration: none;
    font-size: 14px;
    transition: all .15s;
    border-left: 3px solid transparent;
  }
  .nav-item:hover { background: rgba(255,255,255,.05); color: #fff; }
  .nav-item.active {
    background: rgba(184,74,0,.25);
    color: #fff;
    border-left-color: var(--brand);
  }
  .sidebar-footer {
    margin-top: auto;
    padding: 16px 24px;
    border-top: 1px solid rgba(255,255,255,.08);
    font-size: 13px;
    color: rgba(255,255,255,.4);
  }
  .sidebar-footer strong { display: block; color: rgba(255,255,255,.7); }
  .sidebar-footer a {
    color: var(--brand);
    text-decoration: none;
    font-size: 12px;
  }

  /* ── Main ── */
  .main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
  .topbar {
    background: var(--white);
    border-bottom: 1px solid var(--border);
    padding: 16px 32px;
    font-size: 20px;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .topbar .role-badge {
    font-size: 11px;
    font-weight: 500;
    padding: 4px 10px;
    border-radius: 20px;
    background: <?= $rol === 'admin' ? '#fff0eb' : '#f0f7eb' ?>;
    color: <?= $rol === 'admin' ? 'var(--brand)' : 'var(--success)' ?>;
  }
  .content { padding: 32px; flex: 1; }

  /* ── Components ── */
  .card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 24px 28px;
    margin-bottom: 24px;
  }
  .card-title {
    font-size: 15px;
    font-weight: 500;
    margin-bottom: 20px;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .05em;
    font-size: 11px;
  }
  .stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
  }
  .stat-box {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
  }
  .stat-box .label { font-size: 12px; color: var(--muted); margin-bottom: 6px; }
  .stat-box .value { font-size: 26px; font-weight: 500; color: var(--text); }
  .stat-box .value.brand { color: var(--brand); }

  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
  }
  th {
    text-align: left;
    padding: 10px 14px;
    font-size: 11px;
    font-weight: 500;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .06em;
    border-bottom: 1px solid var(--border);
  }
  td {
    padding: 11px 14px;
    border-bottom: 1px solid #f0ebe5;
    vertical-align: middle;
  }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: #fdf9f6; }

  .badge {
    display: inline-block;
    font-size: 11px;
    font-weight: 500;
    padding: 3px 9px;
    border-radius: 20px;
  }
  .badge-ok     { background: #e8f5ee; color: #2d7a4f; }
  .badge-warn   { background: #fff8e6; color: #a06800; }
  .badge-danger { background: #fff0eb; color: #b84a00; }

  .form-group { margin-bottom: 18px; }
  .form-group label {
    display: block;
    font-size: 12px;
    font-weight: 500;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: 6px;
  }
  .form-group input,
  .form-group select,
  .form-group textarea {
    width: 100%;
    padding: 10px 13px;
    border: 1.5px solid var(--border);
    border-radius: 9px;
    font-size: 14px;
    font-family: inherit;
    color: var(--text);
    background: var(--white);
    outline: none;
    transition: border-color .2s;
  }
  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus { border-color: var(--brand); }

  .btn {
    padding: 10px 20px;
    border-radius: 9px;
    font-size: 14px;
    font-weight: 500;
    font-family: inherit;
    cursor: pointer;
    border: none;
    transition: .15s;
  }
  .btn-primary { background: var(--brand); color: #fff; }
  .btn-primary:hover { background: var(--brand-dk); }
  .btn-ghost { background: transparent; border: 1.5px solid var(--border); color: var(--text); }
  .btn-ghost:hover { border-color: var(--brand); color: var(--brand); }
  .btn-danger { background: #fff0eb; color: var(--brand); border: 1.5px solid #fbd5c4; }
  .btn-danger:hover { background: var(--brand); color: #fff; }
  .btn-sm { padding: 6px 12px; font-size: 12px; }

  .alert {
    padding: 12px 16px;
    border-radius: 9px;
    font-size: 14px;
    margin-bottom: 20px;
  }
  .alert-success { background: #e8f5ee; color: #2d7a4f; border-left: 3px solid #2d7a4f; }
  .alert-error   { background: #fff0eb; color: var(--brand); border-left: 3px solid var(--brand); }

  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-brand">
    La Empanada
    <span>Gestión de negocio</span>
  </div>

  <div class="nav-section">Principal</div>
  <a href="/empanadas/dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
    📊 Dashboard
  </a>

  <div class="nav-section">Operaciones</div>
  <a href="/empanadas/ventas.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'ventas.php' ? 'active' : '' ?>">
    🧾 Ventas
  </a>
  <a href="/empanadas/inventario.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'inventario.php' ? 'active' : '' ?>">
    📦 Inventario
  </a>
  <a href="/empanadas/egresos.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'egresos.php' ? 'active' : '' ?>">
    💸 Egresos
  </a>

  <?php if ($rol === 'admin'): ?>
  <div class="nav-section">Administración</div>
  <a href="/empanadas/productos.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'productos.php' ? 'active' : '' ?>">
    🫓 Productos
  </a>
  <a href="/empanadas/clientes.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'clientes.php' ? 'active' : '' ?>">
    👤 Clientes
  </a>
  <a href="/empanadas/empleados.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'empleados.php' ? 'active' : '' ?>">
    👷 Empleados
  </a>
  <?php endif; ?>

  <div class="sidebar-footer">
    <strong><?= htmlspecialchars($user) ?></strong>
    <?= $rol === 'admin' ? 'Administrador' : 'Empleado' ?><br>
    <a href="/empanadas/logout.php">Cerrar sesión</a>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <span><?= htmlspecialchars($pageTitle ?? '') ?></span>
    <span class="role-badge"><?= $rol === 'admin' ? 'Admin' : 'Empleado' ?></span>
  </div>
  <div class="content">
