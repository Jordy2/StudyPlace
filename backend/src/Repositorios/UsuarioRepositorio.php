<?php

namespace App\Repositorios;

use App\Contratos\UsuarioRepositorioInterface;
use App\Modelos\Usuario;
use PDO;

class UsuarioRepositorio implements UsuarioRepositorioInterface {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function buscarPorId(int $id): ?Usuario {
    $sql = 'SELECT id, nombre, correo, password_hash, rol, semestre, estado, created_at, updated_at
            FROM usuarios
            WHERE id = :id
            LIMIT 1';

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':id' => $id]);

    $fila = $stmt->fetch();
    if (!$fila) {
      return null;
    }

    return Usuario::fromArray($fila);
  }

  public function buscarPorCorreo(string $correo): ?Usuario {
    $sql = 'SELECT id, nombre, correo, password_hash, rol, semestre, estado, created_at, updated_at
            FROM usuarios
            WHERE correo = :correo
            LIMIT 1';

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':correo' => $correo]);

    $fila = $stmt->fetch();
    if (!$fila) {
      return null;
    }

    return Usuario::fromArray($fila);
  }

  public function crear(Usuario $usuario): Usuario {
    $sql = 'INSERT INTO usuarios (nombre, correo, password_hash, rol, semestre, estado)
            VALUES (:nombre, :correo, :password_hash, :rol, :semestre, :estado)';

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
      ':nombre' => $usuario->getNombre(),
      ':correo' => $usuario->getCorreo(),
      ':password_hash' => $usuario->getPasswordHash(),
      ':rol' => $usuario->getRol(),
      ':semestre' => $usuario->getSemestre(),
      ':estado' => $usuario->getEstado(),
    ]);

    $idGenerado = (int)$this->pdo->lastInsertId();
    $usuarioCreado = $this->buscarPorId($idGenerado);

    if ($usuarioCreado === null) {
      throw new \RuntimeException('No se pudo recuperar el usuario recién creado.');
    }

    return $usuarioCreado;
  }

  public function existeCorreo(string $correo): bool {
    $sql = 'SELECT COUNT(*) FROM usuarios WHERE correo = :correo';
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':correo' => $correo]);

    return (int)$stmt->fetchColumn() > 0;
  }

  public function existeOtroConCorreo(string $correo, int $idExcluir): bool {
    $sql = 'SELECT COUNT(*) FROM usuarios WHERE correo = :correo AND id != :id';
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
      ':correo' => $correo,
      ':id' => $idExcluir,
    ]);

    return (int)$stmt->fetchColumn() > 0;
  }

  public function actualizar(Usuario $usuario): bool {
    $sql = 'UPDATE usuarios
            SET nombre = :nombre,
                correo = :correo,
                password_hash = :password_hash,
                semestre = :semestre,
                estado = :estado
            WHERE id = :id';

    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([
      ':id' => $usuario->getId(),
      ':nombre' => $usuario->getNombre(),
      ':correo' => $usuario->getCorreo(),
      ':password_hash' => $usuario->getPasswordHash(),
      ':semestre' => $usuario->getSemestre(),
      ':estado' => $usuario->getEstado(),
    ]);
  }

  public function listar(array $filtros = []): array {
    $sql = 'SELECT id, nombre, correo, rol, semestre, estado, created_at, updated_at
            FROM usuarios
            WHERE 1 = 1';
    $params = [];

    if (!empty($filtros['rol'])) {
      $sql .= ' AND rol = :rol';
      $params[':rol'] = $filtros['rol'];
    }

    if (!empty($filtros['estado'])) {
      $sql .= ' AND estado = :estado';
      $params[':estado'] = $filtros['estado'];
    }

    if (!empty($filtros['q'])) {
      $sql .= ' AND (nombre LIKE :q_nombre OR correo LIKE :q_correo)';
      $params[':q_nombre'] = '%' . $filtros['q'] . '%';
      $params[':q_correo'] = '%' . $filtros['q'] . '%';
    }

    $sql .= ' ORDER BY created_at DESC, id DESC LIMIT 500';

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
  }

  public function actualizarRol(int $id, string $rol): bool {
    $stmt = $this->pdo->prepare('UPDATE usuarios SET rol = :rol WHERE id = :id');
    return $stmt->execute([':rol' => $rol, ':id' => $id]);
  }

  public function actualizarEstado(int $id, string $estado): bool {
    $stmt = $this->pdo->prepare('UPDATE usuarios SET estado = :estado WHERE id = :id');
    return $stmt->execute([':estado' => $estado, ':id' => $id]);
  }
}
