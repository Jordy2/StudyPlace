<?php

namespace App;

use App\Config\Conexion;
use App\Contratos\AutenticacionServicioInterface;
use App\Contratos\EspacioRepositorioInterface;
use App\Contratos\EspacioServicioInterface;
use App\Contratos\DisponibilidadRepositorioInterface;
use App\Contratos\DisponibilidadServicioInterface;
use App\Contratos\MateriaRepositorioInterface;
use App\Contratos\MateriaServicioInterface;
use App\Contratos\TokenRepositorioInterface;
use App\Contratos\UsuarioRepositorioInterface;
use App\Controladores\AutenticacionControlador;
use App\Controladores\EspacioControlador;
use App\Controladores\MateriaControlador;
use App\Controladores\DisponibilidadControlador;
use App\Middleware\AutenticacionMiddleware;
use App\Repositorios\EspacioRepositorio;
use App\Repositorios\MateriaRepositorio;
use App\Repositorios\DisponibilidadRepositorio;
use App\Repositorios\TokenRepositorio;
use App\Repositorios\UsuarioRepositorio;
use App\Servicios\AutenticacionServicio;
use App\Servicios\EspacioServicio;
use App\Servicios\MateriaServicio;
use App\Servicios\DisponibilidadServicio;
use PDO;

class Contenedor {
  private ?PDO $pdo = null;
  private ?UsuarioRepositorioInterface $usuarioRepo = null;
  private ?TokenRepositorioInterface $tokenRepo = null;
  private ?AutenticacionServicioInterface $authServicio = null;
  private ?AutenticacionMiddleware $authMiddleware = null;
  private ?AutenticacionControlador $authControlador = null;

  private ?MateriaRepositorioInterface $materiaRepo = null;
  private ?MateriaServicioInterface $materiaServicio = null;
  private ?MateriaControlador $materiaControlador = null;

  private ?EspacioRepositorioInterface $espacioRepo = null;
  private ?EspacioServicioInterface $espacioServicio = null;
  private ?EspacioControlador $espacioControlador = null;

  private ?DisponibilidadRepositorioInterface $disponibilidadRepo = null;
  private ?DisponibilidadServicioInterface $disponibilidadServicio = null;
  private ?DisponibilidadControlador $disponibilidadControlador = null;

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

  public function obtenerMateriaRepositorio(): MateriaRepositorioInterface {
    if ($this->materiaRepo === null) {
      $this->materiaRepo = new MateriaRepositorio($this->obtenerConexion());
    }

    return $this->materiaRepo;
  }

  public function obtenerMateriaServicio(): MateriaServicioInterface {
    if ($this->materiaServicio === null) {
      $this->materiaServicio = new MateriaServicio(
        $this->obtenerMateriaRepositorio()
      );
    }

    return $this->materiaServicio;
  }

  public function obtenerMateriaControlador(): MateriaControlador {
    if ($this->materiaControlador === null) {
      $this->materiaControlador = new MateriaControlador(
        $this->obtenerMateriaServicio(),
        $this->obtenerAutenticacionMiddleware()
      );
    }

    return $this->materiaControlador;
  }

  public function obtenerEspacioRepositorio(): EspacioRepositorioInterface {
    if ($this->espacioRepo === null) {
      $this->espacioRepo = new EspacioRepositorio($this->obtenerConexion());
    }

    return $this->espacioRepo;
  }

  public function obtenerEspacioServicio(): EspacioServicioInterface {
    if ($this->espacioServicio === null) {
      $this->espacioServicio = new EspacioServicio(
        $this->obtenerEspacioRepositorio()
      );
    }

    return $this->espacioServicio;
  }

  public function obtenerEspacioControlador(): EspacioControlador {
    if ($this->espacioControlador === null) {
      $this->espacioControlador = new EspacioControlador(
        $this->obtenerEspacioServicio(),
        $this->obtenerAutenticacionMiddleware()
      );
    }

    return $this->espacioControlador;
  }

  public function obtenerDisponibilidadRepositorio(): DisponibilidadRepositorioInterface {
    if ($this->disponibilidadRepo === null) {
      $this->disponibilidadRepo = new DisponibilidadRepositorio($this->obtenerConexion());
    }
    return $this->disponibilidadRepo;
  }

  public function obtenerDisponibilidadServicio(): DisponibilidadServicioInterface {
    if ($this->disponibilidadServicio === null) {
      $this->disponibilidadServicio = new DisponibilidadServicio(
        $this->obtenerDisponibilidadRepositorio(),
        $this->obtenerMateriaRepositorio()
      );
    }
    return $this->disponibilidadServicio;
  }

  public function obtenerDisponibilidadControlador(): DisponibilidadControlador {
    if ($this->disponibilidadControlador === null) {
      $this->disponibilidadControlador = new DisponibilidadControlador(
        $this->obtenerDisponibilidadServicio(),
        $this->obtenerAutenticacionMiddleware()
      );
    }
    return $this->disponibilidadControlador;
  }
}
