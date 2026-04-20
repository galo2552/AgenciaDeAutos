<?php
declare(strict_types=1);

require_once __DIR__ . '/../components/conf/conf.php';
Auth::requireLogin();

$db = db();

define('UPLOAD_DIR', ROOT_PATH . '/uploads/');
define('UPLOAD_MAX_MB', 5);
$tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];

define('IMG_ANCHO', 480);
define('IMG_ALTO',  270);

function procesarImagen(array $file): ?string
{
	global $tiposPermitidos;

	if ($file['error'] === UPLOAD_ERR_NO_FILE) {
		return null;
	}
	if ($file['error'] !== UPLOAD_ERR_OK) {
		throw new RuntimeException('Error al subir la imagen (código ' . $file['error'] . ').');
	}
	if ($file['size'] > UPLOAD_MAX_MB * 1024 * 1024) {
		throw new RuntimeException('La imagen no puede superar ' . UPLOAD_MAX_MB . ' MB.');
	}

	$mime = mime_content_type($file['tmp_name']);
	if (!in_array($mime, $tiposPermitidos, true)) {
		throw new RuntimeException('Tipo de archivo no permitido. Usá JPG, PNG o WEBP.');
	}

	$origen = match ($mime) {
		'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
		'image/png'  => imagecreatefrompng($file['tmp_name']),
		'image/webp' => imagecreatefromwebp($file['tmp_name']),
		default      => false,
	};
	if ($origen === false) {
		throw new RuntimeException('No se pudo procesar la imagen.');
	}

	$anchoOrig    = imagesx($origen);
	$altoOrig     = imagesy($origen);
	$ratioOrig    = $anchoOrig / $altoOrig;
	$ratioDestino = IMG_ANCHO / IMG_ALTO;

	if ($ratioOrig > $ratioDestino) {
		$altoSrc  = $altoOrig;
		$anchoSrc = (int) round($altoOrig * $ratioDestino);
		$xSrc     = (int) round(($anchoOrig - $anchoSrc) / 2);
		$ySrc     = 0;
	} else {
		$anchoSrc = $anchoOrig;
		$altoSrc  = (int) round($anchoOrig / $ratioDestino);
		$xSrc     = 0;
		$ySrc     = (int) round(($altoOrig - $altoSrc) / 2);
	}

	$destImg = imagecreatetruecolor(IMG_ANCHO, IMG_ALTO);

	if ($mime === 'image/png') {
		imagealphablending($destImg, false);
		imagesavealpha($destImg, true);
		$transparente = imagecolorallocatealpha($destImg, 0, 0, 0, 127);
		imagefilledrectangle($destImg, 0, 0, IMG_ANCHO, IMG_ALTO, $transparente);
	}

	imagecopyresampled($destImg, $origen, 0, 0, $xSrc, $ySrc, IMG_ANCHO, IMG_ALTO, $anchoSrc, $altoSrc);
	imagedestroy($origen);

	$ext     = ($mime === 'image/png') ? 'png' : 'jpg';
	$nombre  = uniqid('auto_', true) . '.' . $ext;
	$destino = UPLOAD_DIR . $nombre;

	$ok = match ($ext) {
		'jpg' => imagejpeg($destImg, $destino, 85),
		'png' => imagepng($destImg, $destino, 7),
	};
	imagedestroy($destImg);

	if (!$ok) {
		throw new RuntimeException('No se pudo guardar la imagen procesada.');
	}

	return $nombre;
}

function eliminarImagen(?string $nombre): void
{
	if ($nombre === null || $nombre === '') return;
	$ruta = UPLOAD_DIR . basename($nombre);
	if (file_exists($ruta)) {
		@unlink($ruta);
	}
}

try {
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$accion = (string) ($_POST['accion'] ?? '');

		if ($accion === 'crear' || $accion === 'actualizar') {
			$id     = (int) ($_POST['id'] ?? 0);
			$marca  = (string) ($_POST['marca']  ?? '');
			$modelo = (string) ($_POST['modelo'] ?? '');
			$anio   = (int)    ($_POST['anio']   ?? 0);
			$precio = (float)  ($_POST['precio'] ?? 0);

			$auto = new Auto($id, $marca, $modelo, $anio, $precio);

			$nuevaImagen = procesarImagen($_FILES['imagen'] ?? ['error' => UPLOAD_ERR_NO_FILE]);

			if ($accion === 'crear') {
				$stmt = $db->prepare(
					'INSERT INTO vehiculos (marca, modelo, anio, precio, imagen)
					 VALUES (:marca, :modelo, :anio, :precio, :imagen)'
				);
				$stmt->execute([
					'marca'  => $auto->getMarca(),
					'modelo' => $auto->getModelo(),
					'anio'   => $auto->getAnio(),
					'precio' => $auto->getPrecio(),
					'imagen' => $nuevaImagen,
				]);
				set_flash('success', 'Vehículo creado correctamente.');
			} else {
				if ($id <= 0) throw new InvalidArgumentException('ID inválido para actualizar.');

				$stmtImg = $db->prepare('SELECT imagen FROM vehiculos WHERE id = :id LIMIT 1');
				$stmtImg->execute(['id' => $id]);
				$imagenAnterior = $stmtImg->fetchColumn() ?: null;

				if ($nuevaImagen !== null) {
					eliminarImagen($imagenAnterior);
					$imagenFinal = $nuevaImagen;
				} else {
					$imagenFinal = $imagenAnterior;
				}

				$stmt = $db->prepare(
					'UPDATE vehiculos SET marca = :marca, modelo = :modelo, anio = :anio,
					 precio = :precio, imagen = :imagen WHERE id = :id'
				);
				$stmt->execute([
					'id'     => $id,
					'marca'  => $auto->getMarca(),
					'modelo' => $auto->getModelo(),
					'anio'   => $auto->getAnio(),
					'precio' => $auto->getPrecio(),
					'imagen' => $imagenFinal,
				]);
				set_flash('success', 'Vehículo actualizado correctamente.');
			}
		}

		if ($accion === 'eliminar') {
			$id = (int) ($_POST['id'] ?? 0);
			if ($id <= 0) throw new InvalidArgumentException('ID inválido para eliminar.');

			$stmtImg = $db->prepare('SELECT imagen FROM vehiculos WHERE id = :id LIMIT 1');
			$stmtImg->execute(['id' => $id]);
			$imagenAEliminar = $stmtImg->fetchColumn() ?: null;
			eliminarImagen($imagenAEliminar);

			$stmt = $db->prepare('DELETE FROM vehiculos WHERE id = :id');
			$stmt->execute(['id' => $id]);
			set_flash('success', 'Vehículo eliminado correctamente.');
		}

		redirect('pages/vehiculos.php');
	}
} catch (Throwable $e) {
	set_flash('error', $e->getMessage());
	redirect('pages/vehiculos.php');
}

$autoEditar = null;
if (isset($_GET['edit'])) {
	$idEditar = (int) $_GET['edit'];
	if ($idEditar > 0) {
		$stmt = $db->prepare('SELECT id, marca, modelo, anio, precio, imagen FROM vehiculos WHERE id = :id LIMIT 1');
		$stmt->execute(['id' => $idEditar]);
		$row = $stmt->fetch();
		if ($row) {
			$autoEditar = Auto::fromRow($row);
		}
	}
}

$stmt = $db->query('SELECT id, marca, modelo, anio, precio, imagen FROM vehiculos ORDER BY id DESC');
$autos = [];
while ($row = $stmt->fetch()) {
	$autos[] = Auto::fromRow($row);
}

require_once __DIR__ . '/../components/header.php';
?>

<section class="panel">
	<h2><?= $autoEditar ? 'Editar vehículo' : 'Alta de vehículo' ?></h2>
	<form method="post" enctype="multipart/form-data" class="form-grid">
		<input type="hidden" name="accion" value="<?= $autoEditar ? 'actualizar' : 'crear' ?>">
		<input type="hidden" name="id"     value="<?= $autoEditar ? $autoEditar->getId() : 0 ?>">

		<label for="marca">Marca</label>
		<input id="marca" name="marca" type="text" required
			value="<?= htmlspecialchars($autoEditar ? $autoEditar->getMarca() : '') ?>">

		<label for="modelo">Modelo</label>
		<input id="modelo" name="modelo" type="text" required
			value="<?= htmlspecialchars($autoEditar ? $autoEditar->getModelo() : '') ?>">

		<label for="anio">Año</label>
		<input id="anio" name="anio" type="number" min="1900" max="2100" required
			value="<?= htmlspecialchars((string) ($autoEditar ? $autoEditar->getAnio() : date('Y'))) ?>">

		<label for="precio">Precio (USD)</label>
		<input id="precio" name="precio" type="number" min="1" step="0.01" required
			value="<?= htmlspecialchars((string) ($autoEditar ? $autoEditar->getPrecio() : '')) ?>">

		<label for="imagen">
			Imagen del vehículo
			<?= $autoEditar ? '<small style="font-weight:400;color:var(--soft)">(dejá vacío para mantener la actual)</small>' : '' ?>
		</label>
		<div class="imagen-upload-wrap">
			<?php if ($autoEditar && $autoEditar->getImagen()): ?>
				<div class="imagen-preview-actual">
					<img
						src="<?= htmlspecialchars(app_url('uploads/' . $autoEditar->getImagen())) ?>"
						alt="Imagen actual"
					>
					<span>Imagen actual</span>
				</div>
			<?php endif; ?>
			<input id="imagen" name="imagen" type="file" accept="image/jpeg,image/png,image/webp"
				class="input-file">
			<small class="input-hint">JPG, PNG o WEBP · máx. 5 MB</small>
		</div>

		<button type="submit" class="btn"><?= $autoEditar ? 'Guardar cambios' : 'Crear vehículo' ?></button>
		<?php if ($autoEditar): ?>
			<a href="<?= htmlspecialchars(app_url('pages/vehiculos.php')) ?>" class="btn btn-secondary">Cancelar</a>
		<?php endif; ?>
	</form>
</section>

<section class="panel">
	<h2>Listado de vehículos</h2>
	<div class="table-wrap">
		<table>
			<thead>
				<tr>
					<th>ID</th>
					<th>Imagen</th>
					<th>Marca</th>
					<th>Modelo</th>
					<th>Año</th>
					<th>Precio</th>
					<th>Acciones</th>
				</tr>
			</thead>
			<tbody>
				<?php if (count($autos) === 0): ?>
					<tr>
						<td colspan="7">No hay vehículos cargados.</td>
					</tr>
				<?php endif; ?>
				<?php foreach ($autos as $auto): ?>
					<tr>
						<td><?= $auto->getId() ?></td>
						<td>
							<?php if ($auto->getImagen()): ?>
								<img
									src="<?= htmlspecialchars(app_url('uploads/' . $auto->getImagen())) ?>"
									alt="<?= htmlspecialchars($auto->getMarca() . ' ' . $auto->getModelo()) ?>"
									class="tabla-thumb"
								>
							<?php else: ?>
								<span class="sin-imagen">—</span>
							<?php endif; ?>
						</td>
						<td><?= htmlspecialchars($auto->getMarca()) ?></td>
						<td><?= htmlspecialchars($auto->getModelo()) ?></td>
						<td><?= $auto->getAnio() ?></td>
						<td><?= htmlspecialchars(Auto::formatearPrecio($auto->getPrecio())) ?></td>
						<td class="actions">
							<a href="<?= htmlspecialchars(app_url('pages/vehiculos.php?edit=' . $auto->getId())) ?>"
							   class="btn btn-small">Editar</a>
							<form method="post"
							      onsubmit="return confirm('¿Seguro que deseás eliminar este vehículo?');">
								<input type="hidden" name="accion" value="eliminar">
								<input type="hidden" name="id"     value="<?= $auto->getId() ?>">
								<button type="submit" class="btn btn-small btn-danger">Eliminar</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
