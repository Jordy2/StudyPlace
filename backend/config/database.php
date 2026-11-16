<?php

return [
  'host' => getenv('DB_HOST') ?: 'localhost',
  'dbname' => getenv('DB_NAME') ?: 'studyplace_db',
  'user' => getenv('DB_USER') ?: 'root',
  'password' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
  'port' => getenv('DB_PORT') ?: 3306,
  'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
];
