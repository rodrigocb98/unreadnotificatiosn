<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "=== PLUGIN INSTALLATION ===\n";

// Cargar GLPI
require_once '/var/www/html/cns/inc/includes.php';

// Verificar si el plugin ya está instalado
$plugin = new Plugin();
$is_installed = $plugin->getFromDBbyName('unreadnotifications', 'name');

if ($is_installed) {
    echo "Plugin already installed in database.\n";
    $state = $plugin->getField('state');
    echo "Current state: $state\n";
    
    if ($state != 1) {
        echo "Activating plugin...\n";
        $plugin->activate($plugin->getID());
        echo "Plugin activated!\n";
    }
} else {
    echo "Plugin not found in database. Installing...\n";
    
    // Instalar el plugin
    $plugin_dir = Plugin::getPhpDir('unreadnotifications');
    
    if (!is_dir($plugin_dir)) {
        echo "❌ ERROR: Plugin directory not found: $plugin_dir\n";
        exit;
    }
    
    echo "Plugin directory: $plugin_dir\n";
    
    // Ejecutar función de instalación
    $result = plugin_unreadnotifications_install();
    
    if ($result) {
        echo "✅ Plugin installed successfully!\n";
        
        // Registrar el plugin en la base de datos
        $plugin->add([
            'name' => 'unreadnotifications',
            'version' => '2.2.0',
            'state' => 1, // Activado
            'directory' => 'unreadnotifications'
        ]);
        
        echo "✅ Plugin registered in database!\n";
    } else {
        echo "❌ ERROR: Plugin installation failed\n";
    }
}

echo "=== INSTALLATION COMPLETE ===\n";
?>