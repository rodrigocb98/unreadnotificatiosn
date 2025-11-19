<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "=== TEST WITH REAL GROUP ===\n";

require_once '/var/www/html/cns/inc/includes.php';

// Incluir archivos del plugin
$plugin_dir = Plugin::getPhpDir('unreadnotifications');
include_once $plugin_dir . '/hook.php';
include_once $plugin_dir . '/inc/notificationevent.class.php';

global $DB;

// Usar el grupo "Fabrica" (ID: 23)
$group_id = 23;
$group = new Group();

if (!$group->getFromDB($group_id)) {
    echo "❌ Group ID 23 not found\n";
    exit;
}

$group_name = $group->getField('name');
echo "1. Using group: $group_name (ID: $group_id)\n";

// Verificar técnicos en el grupo
echo "2. Checking technicians in group...\n";
$technicians = PluginUnreadnotificationsNotificationEvent::getTechniciansInGroup($group_id);

echo "3. Technicians found: " . count($technicians) . "\n";

if (!empty($technicians)) {
    echo "4. Technician IDs: " . implode(', ', $technicians) . "\n";
    
    // Mostrar nombres de técnicos
    echo "5. Technician details:\n";
    foreach ($technicians as $tech_id) {
        $user = new User();
        if ($user->getFromDB($tech_id)) {
            echo "   - ID: $tech_id, Name: {$user->getField('name')}, Realname: {$user->getField('realname')} {$user->getField('firstname')}\n";
        }
    }
} else {
    echo "4. ❌ No technicians found in group\n";
    
    // Verificar si hay cualquier usuario en el grupo
    echo "5. Checking for any users in group...\n";
    $any_users = PluginUnreadnotificationsNotificationEvent::getAnyUsersInGroup($group_id);
    echo "   Any users in group: " . count($any_users) . "\n";
    
    if (!empty($any_users)) {
        echo "6. User IDs in group: " . implode(', ', $any_users) . "\n";
    }
}

// Crear ticket de prueba
echo "7. Creating test ticket...\n";
$ticket = new Ticket();
$ticket_data = [
    'name' => 'Prueba Asignación a Soporte - ' . date('H:i:s'),
    'content' => 'Ticket de prueba para notificaciones de asignación al grupo de Soporte',
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

echo "8. Ticket created: $ticket_id\n";
$ticket->getFromDB($ticket_id);

// Probar asignación de grupo
echo "9. Testing group assignment notifications...\n";
$result = PluginUnreadnotificationsNotificationEvent::processGroupAssignment($ticket, $group_id);

echo "10. Group assignment result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";

// Verificar notificaciones creadas
$query = "SELECT COUNT(*) as count FROM glpi_plugin_unreadnotifications_notifications 
          WHERE item_id = $ticket_id AND event_type = 'group_assignment'";
$db_result = $DB->query($query);
$notif_data = $DB->fetchAssoc($db_result);

echo "11. Group assignment notifications created: {$notif_data['count']}\n";

// Mostrar todas las notificaciones recientes para este ticket
$detail_query = "SELECT id, users_id, event_type, date_creation 
                FROM glpi_plugin_unreadnotifications_notifications 
                WHERE item_id = $ticket_id 
                ORDER BY id DESC";
$detail_result = $DB->query($detail_query);

echo "12. All notifications for ticket $ticket_id:\n";
$found_notifications = false;
while ($detail = $DB->fetchAssoc($detail_result)) {
    $found_notifications = true;
    $user = new User();
    $user_name = 'Unknown';
    if ($user->getFromDB($detail['users_id'])) {
        $user_name = $user->getField('name');
    }
    
    echo "    - ID: {$detail['id']}, User: $user_name ({$detail['users_id']}), Event: {$detail['event_type']}, Time: {$detail['date_creation']}\n";
}

if (!$found_notifications) {
    echo "    No notifications found for this ticket\n";
}

echo "=== TEST COMPLETE ===\n";
?>