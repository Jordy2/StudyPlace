<?php

namespace App\Controladores;

use App\Contratos\DisponibilidadServicioInterface;
use InvalidArgumentException;
use Throwable;

class BusquedaControlador {
  public function __construct(private DisponibilidadServicioInterface $servicio) {}

  public function manejar(): void {
    try {
      $materiaId = isset($_GET['materia_id']) && $_GET['materia_id'] !== '' ? (int)$_GET['materia_id'] : null;
      $dia = isset($_GET['dia']) && $_GET['dia'] !== '' ? (string)$_GET['dia'] : null;
      $resultado = $this->servicio->buscarMentores($materiaId, $dia);

      $agrupados = [];
      foreach ($resultado as $fila) {
        $id = (int)$fila['id'];
        if (!isset($agrupados[$id])) {
          $agrupados[$id] = [
            'id' => $id,
            'nombre' => $fila['nombre'],
            'correo' => $fila['correo'],
            'semestre' => $fila['semestre'],
            'horarios' => [],
          ];
        }
        $agrupados[$id]['horarios'][] = [
          'materia_id' => (int)$fila['materia_id'],
          'materia_codigo' => $fila['materia_codigo'],
          'materia_nombre' => $fila['materia_nombre'],
          'dia_semana' => $fila['dia_semana'],
          'hora_inicio' => substr($fila['hora_inicio'], 0, 5),
          'hora_fin' => substr($fila['hora_fin'], 0, 5),
        ];
      }
      respuestaJson(true, array_values($agrupados), 200);
    } catch (InvalidArgumentException $e) {
      respuestaError($e->getMessage(), 400);
    } catch (Throwable $e) {
      respuestaError('Error al buscar mentores: ' . $e->getMessage(), 500);
    }
  }
}
