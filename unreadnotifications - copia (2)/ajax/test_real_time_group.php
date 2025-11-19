<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "=== REAL-TIME GROUP ASSIGNMENT TEST ===\n";

require_once '/var/www/html/cns/inc/includes.php';

// Incluir archivos del plugin
$plugin_dir = Plugin::getPhpDir('unreadnotifications');
include_once $plugin_dir . '/hook.php';

// Simular una actualización de ticket con asignación de grupo
echo "1. Creating ticket...\n";
$ticket = new Ticket();
$ticket_data = [
    'name' => 'Test Real-time Group Assignment - ' . date('H:i:s'),
    'content' => 'Testing real-time group assignment notifications',
    'entities_id' => 0,
    'status' => 1,
    'type' => 1,
    'requesttypes_id' => 1,
    'users_id_recipient' => Session::getLoginUserID()
];

$ticket_id = $ticket->add($ticket_data);

if (!$ticket_id) {
    echo "❌ Failed to create ticket\n";
    exit;
}

echo "2. Ticket created: $ticket_id\n";
$ticket->getFromDB($ticket_id);

// Simular la llamada al hook como lo haría GLPI
echo "3. Simulating GLPI hook call...\n";

// Crear un input simulado como lo haría GLPI
$ticket->input = [
    '_groups_id_assign' => 23, // Fabrica
    'status' => 2 // En curso
];

$ticket->oldvalues = [
    'status' => 1
];

// Llamar manualmente al hook de actualización
plugin_unreadnotifications_item_update($ticket);

echo "4. Hook called. Waiting for shutdown function...\n";

// Esperar a que se ejecuten las shutdown functions
sleep(3);

// Verificar resultados
global $DB;
$query = "SELECT COUNT(*) as count FROM glpi_plugin_unreadnotifications_notifications 
          WHERE item_id = $ticket_id AND event_type = 'group_assignment'";
$result = $DB->query($query);
$data = $DB->fetchAssoc($result);

echo "5. Group assignment notifications created: {$data['count']}\n";

if ($data['count'] > 0) {
    echo "✅ SUCCESS: Group assignment notifications are working!\n";
} else {
    echo "❌ No notifications created. Check the logs for errors.\n";
}

echo "=== REAL-TIME TEST COMPLETE ===\n";
?>