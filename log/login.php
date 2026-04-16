<?php
declare(strict_types=1);

require_once __DIR__ . '/../components/conf/conf.php';

if (Auth::check()) {
	redirect('pages/inicio.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = trim((string) ($_POST['email'] ?? ''));
	$password = (string) ($_POST['password'] ?? '');

	if ($email === '' || $password === '') {
		set_flash('error', 'Completa email y contraseña.');
		redirect('log/login.php');
	}

	if (!Auth::login($email, $password, db())) {
		set_flash('error', 'Credenciales incorrectas.');
		redirect('log/login.php');
	}

	set_flash('success', 'Sesion iniciada correctamente.');
	redirect('pages/inicio.php');
}

require_once __DIR__ . '/../components/header.php';
?>

<section class="login-box">
	<h2>Iniciar sesion</h2>
	<p>Ingresa con tu usuario para acceder al sistema.</p>

	<form method="post" class="form-grid">
		<label for="email">Email</label>
		<input id="email" name="email" type="email" required>

		<label for="password">Contraseña</label>
		<input id="password" name="password" type="password" required>

		<button type="submit" class="btn">Ingresar</button>
	</form>
</section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
