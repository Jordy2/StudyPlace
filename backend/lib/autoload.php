<?php

spl_autoload_register(function (string $clase): void {
  $prefijoConfig = 'App\\Config\\';
  $prefijoSrc = 'App\\';

  if (str_starts_with($clase, $prefijoConfig)) {
    $subclase = substr($clase, strlen($prefijoConfig));
    $archivo = __DIR__ . '/../config/' . str_replace('\\', '/', $subclase) . '.php';
    if (file_exists($archivo)) {
      require_once $archivo;
      return;
    }
  }

  if (str_starts_with($clase, $prefijoSrc)) {
    $subclase = substr($clase, strlen($prefijoSrc));
    $archivo = __DIR__ . '/../src/' . str_replace('\\', '/', $subclase) . '.php';
    if (file_exists($archivo)) {
      require_once $archivo;
    }
  }
});

require_once __DIR__ . '/respuesta.php';
