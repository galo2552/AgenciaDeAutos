<?php
declare(strict_types=1);

require_once __DIR__ . '/../components/conf/conf.php';

$db = db();

$filtroBuscar = trim((string) ($_GET['buscar'] ?? ''));
$filtroAnio = trim((string) ($_GET['anio'] ?? ''));
$filtroPrecioMax = trim((string) ($_GET['precio_max'] ?? ''));

$where = [];
$params = [];

if ($filtroBuscar !== '') {
	$where[] = '(marca LIKE :buscar OR modelo LIKE :buscar)';
	$params['buscar'] = '%' . $filtroBuscar . '%';
}

if ($filtroAnio !== '' && ctype_digit($filtroAnio) && Auto::esAnioValido((int) $filtroAnio)) {
	$where[] = 'anio = :anio';
	$params['anio'] = (int) $filtroAnio;
}

if ($filtroPrecioMax !== '' && is_numeric($filtroPrecioMax)) {
	$where[] = 'precio <= :precio_max';
	$params['precio_max'] = (float) $filtroPrecioMax;
}

$sql = 'SELECT id, marca, modelo, anio, precio, imagen FROM vehiculos';
if (count($where) > 0) {
	$sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY id DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$autos = [];
while ($row = $stmt->fetch()) {
	$autos[] = Auto::fromRow($row);
}

require_once __DIR__ . '/../components/header.php';
?>

<section class="hero catalogo-hero">
	<h2>Catálogo de Vehículos</h2>
	<p>Explorá nuestra selección de autos disponibles. Sin necesidad de registro.</p>
</section>

<section class="panel catalogo-filtros-panel">
	<form method="get" class="form-grid filtros-grid catalogo-filtros-grid">
		<label for="buscar">Buscar</label>
		<input id="buscar" name="buscar" type="text"
			value="<?= htmlspecialchars($filtroBuscar) ?>"
			placeholder="Marca o modelo">

		<label for="anio">Año</label>
		<input id="anio" name="anio" type="number" min="1900" max="2100"
			value="<?= htmlspecialchars($filtroAnio) ?>"
			placeholder="Ej: 2023">

		<label for="precio_max">Precio máximo</label>
		<input id="precio_max" name="precio_max" type="number" min="0" step="0.01"
			value="<?= htmlspecialchars($filtroPrecioMax) ?>"
			placeholder="Ej: 35000">

		<div class="filtros-actions">
			<button type="submit" class="btn btn-small">Aplicar filtros</button>
			<a href="<?= htmlspecialchars(app_url('pages/catalogo.php')) ?>" class="btn btn-secondary btn-small">Limpiar</a>
		</div>
	</form>
</section>

<?php if (count($autos) === 0): ?>
	<section class="panel">
		<p style="color:var(--soft);text-align:center;padding:24px 0;">
			No hay vehículos disponibles por el momento.
		</p>
	</section>
<?php else: ?>
	<div class="catalogo-grid">
		<?php foreach ($autos as $auto): ?>
			<article class="auto-card">
				<div class="auto-card-img">
					<?php if ($auto->getImagen()): ?>
						<img
							src="<?= htmlspecialchars(app_url('uploads/' . $auto->getImagen())) ?>"
							alt="<?= htmlspecialchars($auto->getMarca() . ' ' . $auto->getModelo()) ?>"
							loading="lazy"
						>
					<?php else: ?>
						<div class="auto-card-placeholder">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 40" fill="none">
								<rect width="64" height="40" rx="6" fill="#e8f0fb"/>
								<path d="M10 28l6-12h32l6 12H10z" fill="#c5d8f0"/>
								<rect x="8" y="26" width="48" height="8" rx="3" fill="#a0bcde"/>
								<circle cx="18" cy="34" r="4" fill="#6e8098"/>
								<circle cx="46" cy="34" r="4" fill="#6e8098"/>
								<circle cx="18" cy="34" r="2" fill="#b0bec5"/>
								<circle cx="46" cy="34" r="2" fill="#b0bec5"/>
								<rect x="24" y="18" width="16" height="8" rx="2" fill="#d4e6f8"/>
							</svg>
							<span>Sin imagen</span>
						</div>
					<?php endif; ?>
				</div>
				<div class="auto-card-body">
					<h3 class="auto-card-titulo">
						<?= htmlspecialchars($auto->getMarca()) ?>
						<span><?= htmlspecialchars($auto->getModelo()) ?></span>
					</h3>
					<ul class="auto-card-detalles">
						<li><span>Año </span><strong><?= $auto->getAnio() ?></strong></li>
						<li><span>Precio </span><strong class="auto-precio"><?= htmlspecialchars(Auto::formatearPrecio($auto->getPrecio())) ?></strong></li>
					</ul>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
