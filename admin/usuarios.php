<?php
declare(strict_types=1);

require_once __DIR__ . '/../components/conf/conf.php';
Auth::requireAdmin();

$db = db();

if (isset($_GET['eliminar'])) {
    $id = (int) $_GET['eliminar'];

    $stmt = $db->prepare("DELETE FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $id]);

    set_flash('success', 'Usuario eliminado correctamente.');
    redirect('admin/usuarios.php');
}

$usuarioEditar = null;

if (isset($_GET['editar'])) {
    $id = (int) $_GET['editar'];

    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $id]);

    $usuarioEditar = $stmt->fetch();
}

try {
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $id = $_POST['id'] ?? null;

		$nombre = trim((string) ($_POST['nombre'] ?? ''));
		$email = trim((string) ($_POST['email'] ?? ''));
		$password = (string) ($_POST['password'] ?? '');
		$rol = trim((string) ($_POST['rol'] ?? 'empleado'));

		if ($nombre === '' || $email === '') {
			throw new InvalidArgumentException('Nombre y email son obligatorios.');
		}

		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			throw new InvalidArgumentException('Email invalido.');
		}

		if (!in_array($rol, ['empleado', 'administrador'], true)) {
			throw new InvalidArgumentException('Rol invalido.');
		}

        if ($id) {

            if ($password !== '' && strlen($password) < 6) {
                throw new InvalidArgumentException('La contrasena debe tener al menos 6 caracteres.');
            }

            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $db->prepare("
                    UPDATE usuarios 
                    SET nombre = :nombre, email = :email, password = :password, rol = :rol
                    WHERE id = :id
                ");

                $stmt->execute([
                    'id' => $id,
                    'nombre' => $nombre,
                    'email' => $email,
                    'password' => $hash,
                    'rol' => $rol,
                ]);
            } else {
                $stmt = $db->prepare("
                    UPDATE usuarios 
                    SET nombre = :nombre, email = :email, rol = :rol
                    WHERE id = :id
                ");

                $stmt->execute([
                    'id' => $id,
                    'nombre' => $nombre,
                    'email' => $email,
                    'rol' => $rol,
                ]);
            }

            set_flash('success', 'Usuario actualizado.');
        }

        else {

            if ($password === '') {
                throw new InvalidArgumentException('La contrasena es obligatoria.');
            }

            if (strlen($password) < 6) {
                throw new InvalidArgumentException('La contrasena debe tener al menos 6 caracteres.');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $db->prepare('INSERT INTO usuarios (nombre, email, password, rol) VALUES (:nombre, :email, :password, :rol)');
            $stmt->execute([
                'nombre' => $nombre,
                'email' => $email,
                'password' => $hash,
                'rol' => $rol,
            ]);

            set_flash('success', 'Usuario creado correctamente.');
        }

		redirect('admin/usuarios.php');
	}
} catch (Throwable $e) {
	set_flash('error', $e->getMessage());
	redirect('admin/usuarios.php');
}

$stmt = $db->query('SELECT id, nombre, email, rol, creado_en FROM usuarios ORDER BY id DESC');
$usuarios = $stmt->fetchAll();

require_once __DIR__ . '/../components/header.php';
?>

<section class="panel">
	<h2><?= $usuarioEditar ? 'Editar usuario' : 'Alta de usuario' ?></h2>

	<form method="post" class="form-grid">

        <input type="hidden" name="id" value="<?= $usuarioEditar['id'] ?? '' ?>">

		<label for="nombre">Nombre</label>
		<input id="nombre" name="nombre" type="text" required
        value="<?= htmlspecialchars($usuarioEditar['nombre'] ?? '') ?>">

		<label for="email">Email</label>
		<input id="email" name="email" type="email" required
        value="<?= htmlspecialchars($usuarioEditar['email'] ?? '') ?>">

		<label for="password">Contrasena</label>
		<input id="password" name="password" type="password" minlength="6"
        <?= $usuarioEditar ? '' : 'required' ?>
        placeholder="<?= $usuarioEditar ? 'Dejar vacio para no cambiar' : '' ?>">

		<label for="rol">Rol</label>
		<select id="rol" name="rol" required>
			<option value="empleado" <?= (($usuarioEditar['rol'] ?? '') === 'empleado') ? 'selected' : '' ?>>Empleado</option>
			<option value="administrador" <?= (($usuarioEditar['rol'] ?? '') === 'administrador') ? 'selected' : '' ?>>Administrador</option>
		</select>

		<button type="submit" class="btn">
            <?= $usuarioEditar ? 'Actualizar usuario' : 'Crear usuario' ?>
        </button>
	</form>
</section>

<section class="panel">
	<h2>Listado de usuarios</h2>
	<div class="table-wrap">
		<table>
			<thead>
				<tr>
					<th>ID</th>
					<th>Nombre</th>
					<th>Email</th>
					<th>Rol</th>
					<th>Fecha alta</th>
                    <th>Acciones</th>
				</tr>
			</thead>
			<tbody>
				<?php if (count($usuarios) === 0): ?>
					<tr>
						<td colspan="6">No hay usuarios registrados.</td>
					</tr>
				<?php endif; ?>
				<?php foreach ($usuarios as $item): ?>
					<tr>
						<td><?= (int) $item['id'] ?></td>
						<td><?= htmlspecialchars((string) $item['nombre']) ?></td>
						<td><?= htmlspecialchars((string) $item['email']) ?></td>
						<td><?= htmlspecialchars((string) $item['rol']) ?></td>
						<td><?= htmlspecialchars((string) $item['creado_en']) ?></td>
						<td>
							<div class="actions">
								<a href="usuarios.php?editar=<?= $item['id'] ?>" class="btn btn-small">
									Editar
								</a>

								<a href="usuarios.php?eliminar=<?= $item['id'] ?>" 
								class="btn btn-danger btn-small">
									Eliminar
								</a>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>