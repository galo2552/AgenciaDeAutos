<?php
declare(strict_types=1);

class Auto
{
	private int $id;
	private string $marca;
	private string $modelo;
	private int $anio;
	private float $precio;
	private ?string $imagen;

	private static int $contadorInstancias = 0;

	public function __construct(int $id, string $marca, string $modelo, int $anio, float $precio, ?string $imagen = null)
	{
		$this->id = $id;
		$this->setMarca($marca);
		$this->setModelo($modelo);
		$this->setAnio($anio);
		$this->setPrecio($precio);
		$this->imagen = $imagen;
		self::$contadorInstancias++;
	}

	public static function getContadorInstancias(): int
	{
		return self::$contadorInstancias;
	}

	public static function esAnioValido(int $anio): bool
	{
		$maximo = (int) date('Y') + 1;
		return $anio >= 1900 && $anio <= $maximo;
	}

	public static function formatearPrecio(float $precio): string
	{
		return '$' . number_format($precio, 2, ',', '.');
	}

	public static function fromRow(array $row): self
	{
		return new self(
			(int) $row['id'],
			(string) $row['marca'],
			(string) $row['modelo'],
			(int) $row['anio'],
			(float) $row['precio'],
			isset($row['imagen']) && $row['imagen'] !== '' ? (string) $row['imagen'] : null
		);
	}

	public function getId(): int { return $this->id; }
	public function setId(int $id): void { $this->id = $id; }

	public function getMarca(): string { return $this->marca; }
	public function setMarca(string $marca): void
	{
		$marca = trim($marca);
		if ($marca === '') throw new InvalidArgumentException('La marca no puede estar vacia.');
		$this->marca = $marca;
	}

	public function getModelo(): string { return $this->modelo; }
	public function setModelo(string $modelo): void
	{
		$modelo = trim($modelo);
		if ($modelo === '') throw new InvalidArgumentException('El modelo no puede estar vacio.');
		$this->modelo = $modelo;
	}

	public function getAnio(): int { return $this->anio; }
	public function setAnio(int $anio): void
	{
		if (!self::esAnioValido($anio)) throw new InvalidArgumentException('El anio no es valido.');
		$this->anio = $anio;
	}

	public function getPrecio(): float { return $this->precio; }
	public function setPrecio(float $precio): void
	{
		if ($precio <= 0) throw new InvalidArgumentException('El precio debe ser mayor a 0.');
		$this->precio = $precio;
	}

	public function getImagen(): ?string { return $this->imagen; }
	public function setImagen(?string $imagen): void { $this->imagen = $imagen; }
}
