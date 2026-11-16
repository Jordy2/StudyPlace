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
}
