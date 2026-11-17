<?php

namespace App\Repositorios;

use App\Contratos\MateriaRepositorioInterface;
use App\Modelos\Materia;
use PDO;
use RuntimeException;

class MateriaRepositorio implements MateriaRepositorioInterface {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function obtenerTodas(?string $estado = null): array {
    $sql = 'SELECT id, codigo, nombre, descripcion, estado, created_at, updated_at
            FROM materias';
    $params = [];

    if ($estado !== null) {
      $sql .= ' WHERE estado = :estado';
      $params[':estado'] = $estado;
    }

    $sql .= ' ORDER BY nombre ASC';

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);

    $materias = [];
    while ($fila = $stmt->fetch()) {
      $materias[] = Materia::fromArray($fila);
    }

    return $materias;
  }

  public function buscarPorId(int $id): ?Materia {
    $sql = 'SELECT id, codigo, nombre, descripcion, estado, created_at, updated_at
            FROM materias
            WHERE id = :id
            LIMIT 1';

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':id' => $id]);

    $fila = $stmt->fetch();
    if (!$fila) {
      return null;
    }

    return Materia::fromArray($fila);
  }

  public function buscarPorCodigo(string $codigo): ?Materia {
    $sql = 'SELECT id, codigo, nombre, descripcion, estado, created_at, updated_at
            FROM materias
            WHERE codigo = :codigo
            LIMIT 1';

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':codigo' => $codigo]);

    $fila = $stmt->fetch();
    if (!$fila) {
      return null;
    }

    return Materia::fromArray($fila);
  }

  public function crear(Materia $materia): Materia {
    $sql = 'INSERT INTO materias (codigo, nombre, descripcion, estado)
            VALUES (:codigo, :nombre, :descripcion, :estado)';

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
      ':codigo' => $materia->getCodigo(),
      ':nombre' => $materia->getNombre(),
      ':descripcion' => $materia->getDescripcion(),
      ':estado' => $materia->getEstado(),
    ]);

    $idGenerado = (int)$this->pdo->lastInsertId();
    $creada = $this->buscarPorId($idGenerado);

    if ($creada === null) {
      throw new RuntimeException('No se pudo recuperar la materia recién creada.');
    }

    return $creada;
  }

  public function actualizar(Materia $materia): bool {
    $sql = 'UPDATE materias
            SET codigo = :codigo,
                nombre = :nombre,
                descripcion = :descripcion,
                estado = :estado
            WHERE id = :id';

    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([
      ':id' => $materia->getId(),
      ':codigo' => $materia->getCodigo(),
      ':nombre' => $materia->getNombre(),
      ':descripcion' => $materia->getDescripcion(),
      ':estado' => $materia->getEstado(),
    ]);
  }

  public function eliminar(int $id, bool $bajaLogica = true): bool {
    if ($bajaLogica) {
      $sql = 'UPDATE materias SET estado = "inactivo" WHERE id = :id';
      $stmt = $this->pdo->prepare($sql);
      return $stmt->execute([':id' => $id]);
    }

    $sql = 'DELETE FROM materias WHERE id = :id';
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([':id' => $id]);
  }

  public function existeCodigo(string $codigo, ?int $excluirId = null): bool {
    $sql = 'SELECT COUNT(*) FROM materias WHERE codigo = :codigo';
    $params = [':codigo' => $codigo];

    if ($excluirId !== null) {
      $sql .= ' AND id != :excluirId';
      $params[':excluirId'] = $excluirId;
    }

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);

    return (int)$stmt->fetchColumn() > 0;
  }
}
