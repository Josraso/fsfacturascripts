<?php
/**
 * CRON ejecutable desde línea de comandos o wget
 *
 * Uso desde crontab del sistema (PHP CLI):
 * - Cada 10 minutos: * /10 * * * * php /ruta/a/prestashop/modules/fsfacturascripts/cron.php
 *
 * O usando wget (obtén el token desde la configuración del módulo):
 * - Cada 10 minutos: * /10 * * * * wget -q -O- "https://tutienda.com/modules/fsfacturascripts/cron.php?token=TU_TOKEN"
 */

// Cargar PrestaShop primero
$prestashop_path = dirname(__FILE__) . '/../..';
require_once($prestashop_path . '/config/config.inc.php');

// Seguridad: Verificar token si se ejecuta desde web
if (php_sapi_name() !== 'cli') {
    $token = isset($_GET['token']) ? $_GET['token'] : '';
    $expected_token = Configuration::get('FS_CRON_TOKEN');

    // Si no hay token configurado, generar uno
    if (!$expected_token) {
        $expected_token = bin2hex(random_bytes(16));
        Configuration::updateValue('FS_CRON_TOKEN', $expected_token);
    }

    if ($token !== $expected_token) {
        http_response_code(403);
        die('Token inválido');
    }
}

// Cargar el módulo
$module = Module::getInstanceByName('fsfacturascripts');

if (!$module || !$module->active) {
    die('Módulo no encontrado o inactivo');
}

// Ejecutar sincronización (forzada, sin verificar intervalo)
echo "[" . date('Y-m-d H:i:s') . "] Iniciando sincronización CRON...\n";

$result = $module->hookActionCronJob(['force' => true]);

if (is_array($result) && isset($result['error'])) {
    echo "[ERROR] " . $result['error'] . "\n";
    exit(1);
} else {
    echo "[OK] Sincronización completada\n";
    exit(0);
}
