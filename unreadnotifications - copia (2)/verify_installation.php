<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "=== INSTALLATION VERIFICATION ===\n";

require_once '/var/www/html/cns/inc/includes.php';

// Verificar plugin en BD
$plugin = new Plugin();
$is_installed = $plugin->getFromDBbyName('unreadnotifications', 'name');

echo "Plugin in database: " . ($is_installed ? 'YES' : 'NO') . "\n";

if ($is_installed) {
    $state = $plugin->getField('state');
    $version = $plugin->getField('version');
    echo "State: $state\n";
    echo "Version: $version\n";
}

// Verificar tabla
global $DB;
$table = 'glpi_plugin_unreadnotifications_notifications';
$table_exists = $DB->tableExists($table);
echo "Table exists: " . ($table_exists ? 'YES' : 'NO') . "\n";

// Verificar hooks
global $PLUGIN_HOOKS;
echo "Registered hooks:\n";

if (isset($PLUGIN_HOOKS['item_add']['unreadnotifications'])) {
    echo "✅ item_add hooks: " . print_r($PLUGIN_HOOKS['item_add']['unreadnotifications'], true) . "\n";
} else {
    echo "❌ NO item_add hooks\n";
}

// Verificar funciones de hook
if (function_exists('plugin_unreadnotifications_item_add')) {
    echo "✅ Hook function exists\n";
} else {
    echo "❌ Hook function not found\n";
}

echo "=== VERIFICATION COMPLETE ===\n";
?>