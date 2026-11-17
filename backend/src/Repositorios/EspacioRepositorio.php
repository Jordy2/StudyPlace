<?php

namespace App\Repositorios;

use App\Contratos\EspacioRepositorioInterface;
use App\Modelos\Espacio;
use PDO;
use RuntimeException;

class EspacioRepositorio implements EspacioRepositorioInterface {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function obtenerTodos(?string $tipo = null, ?string $estado = null): array {
    $sql = 'SELECT id, nombre, tipo, capacidad, ubicacion, enlace_virtual, estado, created_at, updated_at
            FROM espacios WHERE 1=1';
    $params = [];

    if ($tipo !== null) {
      $sql .= ' AND tipo = :tipo';
      $params[':tipo'] = $tipo;
    }

    if ($estado !== null) {
      $sql .= ' AND estado = :estado';
      $params[':estado'] = $estado;
    }

    $sql .= ' ORDER BY nombre ASC';

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);

    $espacios = [];
    while ($fila = $stmt->fetch()) {
      $espacios[] = Espacio::fromArray($fila);
    }

    return $espacios;
  }

  public function buscarPorId(int $id): ?Espacio {
    $sql = 'SELECT id, nombre, tipo, capacidad, ubicacion, enlace_virtual, estado, created_at, updated_at
            FROM espacios
            WHERE id = :id
            LIMIT 1';

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':id' => $id]);

    $fila = $stmt->fetch();
    if (!$fila) {
      return null;
    }

    return Espacio::fromArray($fila);
  }

  public function crear(Espacio $espacio): Espacio {
    $sql = 'INSERT INTO espacios (nombre, tipo, capacidad, ubicacion, enlace_virtual, estado)
            VALUES (:nombre, :tipo, :capacidad, :ubicacion, :enlace_virtual, :estado)';

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
      ':nombre' => $espacio->getNombre(),
      ':tipo' => $espacio->getTipo(),
      ':capacidad' => $espacio->getCapacidad(),
      ':ubicacion' => $espacio->getUbicacion(),
      ':enlace_virtual' => $espacio->getEnlaceVirtual(),
      ':estado' => $espacio->getEstado(),
    ]);

    $idGenerado = (int)$this->pdo->lastInsertId();
    $creado = $this->buscarPorId($idGenerado);

    if ($creado === null) {
      throw new RuntimeException('No se pudo recuperar el espacio recién creado.');
    }

    return $creado;
  }

  public function actualizar(Espacio $espacio): bool {
    $sql = 'UPDATE espacios
            SET nombre = :nombre,
                tipo = :tipo,
                capacidad = :capacidad,
                ubicacion = :ubicacion,
                enlace_virtual = :enlace_virtual,
                estado = :estado
            WHERE id = :id';

    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([
      ':id' => $espacio->getId(),
      ':nombre' => $espacio->getNombre(),
      ':tipo' => $espacio->getTipo(),
      ':capacidad' => $espacio->getCapacidad(),
      ':ubicacion' => $espacio->getUbicacion(),
      ':enlace_virtual' => $espacio->getEnlaceVirtual(),
      ':estado' => $espacio->getEstado(),
    ]);
  }

  public function eliminar(int $id, bool $bajaLogica = true): bool {
    if ($bajaLogica) {
      $sql = 'UPDATE espacios SET estado = "inactivo" WHERE id = :id';
      $stmt = $this->pdo->prepare($sql);
      return $stmt->execute([':id' => $id]);
    }

    $sql = 'DELETE FROM espacios WHERE id = :id';
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([':id' => $id]);
  }
}
