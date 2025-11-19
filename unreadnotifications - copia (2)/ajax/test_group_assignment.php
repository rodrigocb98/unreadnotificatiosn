<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "=== GROUP ASSIGNMENT TEST ===\n";

require_once '/var/www/html/cns/inc/includes.php';

// Incluir archivos del plugin
$plugin_dir = Plugin::getPhpDir('unreadnotifications');
include_once $plugin_dir . '/hook.php';
include_once $plugin_dir . '/inc/notificationevent.class.php';

// Crear un ticket de prueba
echo "1. Creating test ticket...\n";
$ticket = new Ticket();
$ticket_data = [
    'name' => 'Test Group Assignment - ' . date('H:i:s'),
    'content' => 'Testing group assignment notifications',
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

// Simular asignación a un grupo (usar un grupo existente)
echo "3. Testing group assignment...\n";

// Obtener un grupo existente para prueba
$group = new Group();
$groups = $group->find(['LIMIT' => 1]);

if (count($groups) > 0) {
    $group_data = reset($groups);
    $group_id = $group_data['id'];
    $group_name = $group_data['name'];
    
    echo "4. Using group: $group_name (ID: $group_id)\n";
    
    // Probar el método processGroupAssignment directamente
    $result = PluginUnreadnotificationsNotificationEvent::processGroupAssignment($ticket, $group_id);
    
    echo "5. Group assignment result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    
    // Verificar notificaciones creadas
    global $DB;
    $query = "SELECT COUNT(*) as count FROM glpi_plugin_unreadnotifications_notifications 
              WHERE item_id = $ticket_id AND event_type = 'group_assignment'";
    $result = $DB->query($query);
    $data = $DB->fetchAssoc($result);
    
    echo "6. Group assignment notifications created: " . $data['count'] . "\n";
    
} else {
    echo "❌ No groups found for testing\n";
}

echo "=== GROUP ASSIGNMENT TEST COMPLETE ===\n";
?>