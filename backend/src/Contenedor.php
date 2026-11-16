<?php

namespace App;

use App\Config\Conexion;
use App\Contratos\AutenticacionServicioInterface;
use App\Contratos\TokenRepositorioInterface;
use App\Contratos\UsuarioRepositorioInterface;
use App\Controladores\AutenticacionControlador;
use App\Middleware\AutenticacionMiddleware;
use App\Repositorios\TokenRepositorio;
use App\Repositorios\UsuarioRepositorio;
use App\Servicios\AutenticacionServicio;
use PDO;

class Contenedor {
  private ?PDO $pdo = null;
  private ?UsuarioRepositorioInterface $usuarioRepo = null;
  private ?TokenRepositorioInterface $tokenRepo = null;
  private ?AutenticacionServicioInterface $authServicio = null;
  private ?AutenticacionMiddleware $authMiddleware = null;
  private ?AutenticacionControlador $authControlador = null;

  public function __construct(?PDO $pdo = null) {
    $this->pdo = $pdo;
  }

  public function obtenerConexion(): PDO {
    if ($this->pdo === null) {
      $this->pdo = Conexion::obtenerConexion();
    }

    return $this->pdo;
  }

  public function obtenerUsuarioRepositorio(): UsuarioRepositorioInterface {
    if ($this->usuarioRepo === null) {
      $this->usuarioRepo = new UsuarioRepositorio($this->obtenerConexion());
    }

    return $this->usuarioRepo;
  }

  public function obtenerTokenRepositorio(): TokenRepositorioInterface {
    if ($this->tokenRepo === null) {
      $this->tokenRepo = new TokenRepositorio($this->obtenerConexion());
    }

    return $this->tokenRepo;
  }

  public function obtenerAutenticacionServicio(): AutenticacionServicioInterface {
    if ($this->authServicio === null) {
      $this->authServicio = new AutenticacionServicio(
        $this->obtenerUsuarioRepositorio(),
        $this->obtenerTokenRepositorio()
      );
    }

    return $this->authServicio;
  }

  public function obtenerAutenticacionMiddleware(): AutenticacionMiddleware {
    if ($this->authMiddleware === null) {
      $this->authMiddleware = new AutenticacionMiddleware(
        $this->obtenerAutenticacionServicio()
      );
    }

    return $this->authMiddleware;
  }

  public function obtenerAutenticacionControlador(): AutenticacionControlador {
    if ($this->authControlador === null) {
      $this->authControlador = new AutenticacionControlador(
        $this->obtenerAutenticacionServicio(),
        $this->obtenerAutenticacionMiddleware()
      );
    }

    return $this->authControlador;
  }
}
