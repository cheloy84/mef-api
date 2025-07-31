<?php

// ============================================
// PARA VERCEL: api/mef.php
// Deploy gratis en: https://vercel.com
// ============================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function consultarMEF($codigoCUI) {
    try {
        $urlSSI = 'https://ofi5.mef.gob.pe/inviertews/Dashboard/traeDetInvSSI';
        $payload = "id={$codigoCUI}&tipo=SIAF";
        
        $headers = [
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Accept-Encoding: gzip, deflate, br, zstd',
            'Accept-Language: es-ES,es;q=0.9',
            'Connection: keep-alive',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Origin: https://ofi5.mef.gob.pe',
            'Referer: https://ofi5.mef.gob.pe/ssi/',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',
            'X-Requested-With: XMLHttpRequest'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $urlSSI,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'error' => "cURL Error: $error",
                'debug' => [
                    'url' => $urlSSI,
                    'payload' => $payload,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ];
        }
        
        if ($http_code !== 200) {
            return [
                'error' => "HTTP Error: $http_code",
                'debug' => [
                    'url' => $urlSSI,
                    'http_code' => $http_code,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ];
        }
        
        $data = json_decode($response, true);
        
        if (!$data || !is_array($data) || empty($data)) {
            return [
                'error' => 'Respuesta inválida del MEF',
                'debug' => [
                    'response_length' => strlen($response),
                    'response_preview' => substr($response, 0, 200),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ];
        }
        
        // Procesar los datos
        $first = $data[0];
        $devAcumulado = floatval($first['DEV_ACUMULADO'] ?? 0);
        $costoActualizado = floatval($first['COSTO_ACTUALIZADO'] ?? 0);
        $porcentaje = $costoActualizado > 0 ? ($devAcumulado / $costoActualizado) * 100 : 0;
        
        return [
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => $first,
            'formatted' => [
                'proyecto' => $first['DES_INVERSION'] ?? 'Sin descripción',
                'entidad' => $first['ENTIDAD'] ?? 'N/A',
                'funcion' => $first['FUNCION'] ?? 'N/A',
                'costo_actualizado' => $costoActualizado,
                'devengado_acumulado' => $devAcumulado,
                'porcentaje_avance' => round($porcentaje, 1),
                'codigo_unico' => $first['CODIGO_UNICO'] ?? $codigoCUI,
                'pim_ano_vigente' => floatval($first['PIM_ANO_VIGENTE'] ?? 0),
                'dev_ano_vigente' => floatval($first['DEV_ANO_VIGENTE'] ?? 0)
            ],
            'monday_format' => "💰 PROYECTO: " . ($first['DES_INVERSION'] ?? 'Sin descripción') . "\\\\n" .
                             "🏛️ ENTIDAD: " . ($first['ENTIDAD'] ?? 'N/A') . "\\\\n" .
                             "📊 FUNCIÓN: " . ($first['FUNCION'] ?? 'N/A') . "\\\\n" .
                             "💵 COSTO ACTUALIZADO: S/ " . number_format($costoActualizado, 2) . "\\\\n" .
                             "💸 DEVENGADO ACUMULADO: S/ " . number_format($devAcumulado, 2) . "\\\\n" .
                             "📈 AVANCE: " . round($porcentaje, 1) . "%\\\\n" .
                             "💰 PIM AÑO VIGENTE: S/ " . number_format(floatval($first['PIM_ANO_VIGENTE'] ?? 0), 2) . "\\\\n" .
                             "💳 DEV AÑO VIGENTE: S/ " . number_format(floatval($first['DEV_ANO_VIGENTE'] ?? 0), 2) . "\\\\n" .
                             "🎯 CÓDIGO ÚNICO: " . ($first['CODIGO_UNICO'] ?? $codigoCUI)
        ];
        
    } catch (Exception $e) {
        return [
            'error' => $e->getMessage(),
            'debug' => [
                'exception' => get_class($e),
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
    }
}

// Para múltiples CUIs en una sola petición
function consultarMultiplesMEF($codigos) {
    $resultados = [];
    
    foreach ($codigos as $codigo) {
        $resultado = consultarMEF($codigo);
        $resultados[$codigo] = $resultado;
        
        // Pequeña pausa entre consultas para evitar sobrecarga
        usleep(500000); // 0.5 segundos
    }
    
    return $resultados;
}

// Obtener parámetros
$codigoCUI = $_GET['id'] ?? $_POST['id'] ?? null;
$multipleCUIs = $_GET['ids'] ?? $_POST['ids'] ?? null;

// Múltiples IDs (separados por coma)
if ($multipleCUIs) {
    $codigos = array_map('trim', explode(',', $multipleCUIs));
    $codigos = array_filter($codigos); // Remover elementos vacíos
    
    if (count($codigos) > 10) {
        echo json_encode([
            'error' => 'Máximo 10 códigos por petición',
            'provided' => count($codigos)
        ]);
        exit;
    }
    
    $results = consultarMultiplesMEF($codigos);
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Un solo ID
if (!$codigoCUI) {
    echo json_encode([
        'error' => 'Parámetro ID requerido',
        'usage' => [
            'single' => '?id=2465550',
            'multiple' => '?ids=2465550,1234567,9876543'
        ]
    ]);
    exit;
}

// Hacer la consulta
$result = consultarMEF($codigoCUI);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

?>

<!-- 
ARCHIVO: vercel.json (en la raíz del proyecto)
{
  "functions": {
    "api/*.php": {
      "runtime": "vercel-php@0.6.0"
    }
  },
  "routes": [
    {
      "src": "/api/(.*)",
      "dest": "/api/$1"
    }
  ]
}

PASOS PARA DESPLEGAR EN VERCEL:
1. Crear cuenta en https://vercel.com (gratis)
2. Crear carpeta del proyecto:
   proyecto/
   ├── api/
   │   └── mef.php (este archivo)
   └── vercel.json (configuración de arriba)
3. Subir a GitHub
4. Conectar GitHub con Vercel
5. Deploy automático

URL resultante: https://tu-proyecto.vercel.app/api/mef?id=2465550

VENTAJAS:
✅ Gratis
✅ Sin configuración compleja
✅ SSL automático
✅ CDN global
✅ Logs automáticos
-->