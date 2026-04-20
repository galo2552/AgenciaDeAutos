<?php
declare(strict_types=1);

require_once __DIR__ . '/conf/conf.php';

$usuario = Auth::user();
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Agencia de Autos</title>
	<link rel="stylesheet" href="<?= htmlspecialchars(app_url('css/style.css')) ?>">
</head>
<body>
	<header class="site-header">
		<div class="container topbar">
			<a href="<?= htmlspecialchars(app_url($usuario ? 'pages/inicio.php' : 'pages/catalogo.php')) ?>" class="brand">
				Agencia de Autos
			</a>
			<nav class="nav">
				<a href="<?= htmlspecialchars(app_url('pages/catalogo.php')) ?>">Catálogo</a>
				<?php if ($usuario): ?>
					<a href="<?= htmlspecialchars(app_url('pages/inicio.php')) ?>">Panel</a>
					<a href="<?= htmlspecialchars(app_url('pages/vehiculos.php')) ?>">Vehículos</a>
					<?php if (Auth::isAdmin()): ?>
						<a href="<?= htmlspecialchars(app_url('admin/usuarios.php')) ?>">Usuarios</a>
					<?php endif; ?>
					<a href="<?= htmlspecialchars(app_url('log/logout.php')) ?>">Salir</a>
				<?php else: ?>
					<a href="<?= htmlspecialchars(app_url('log/login.php')) ?>" class="btn btn-nav-login">Iniciar sesión</a>
				<?php endif; ?>
			</nav>
		</div>
	</header>

	<main class="container main-content">
		<?php if ($flash): ?>
			<div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
				<?= htmlspecialchars($flash['message']) ?>
			</div>
		<?php endif; ?>
