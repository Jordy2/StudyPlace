<?php

$origen = $_SERVER['HTTP_ORIGIN'] ?? '*';

header("Access-Control-Allow-Origin: {$origen}");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS') {
  http_response_code(204);
  exit;
}
