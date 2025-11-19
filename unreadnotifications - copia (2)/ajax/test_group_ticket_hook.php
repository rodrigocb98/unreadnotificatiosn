<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "=== GROUP_TICKET HOOK TEST ===\n";

require_once '/var/www/html/cns/inc/includes.php';

// Incluir archivos del plugin
$plugin_dir = Plugin::getPhpDir('unreadnotifications');
include_once $plugin_dir . '/hook.php';

// Crear un ticket para probar
echo "1. Creating test ticket...\n";
$ticket = new Ticket();
$ticket_data = [
    'name' => 'Test Group_Ticket Hook - ' . date('H:i:s'),
    'content' => 'Testing Group_Ticket hook from Actors section',
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

// Simular EXACTAMENTE lo que hace GLPI en Actors
echo "3. Simulating Actors assignment via Group_Ticket...\n";

$group_ticket = new Group_Ticket();
$assignment_data = [
    'tickets_id' => $ticket_id,
    'groups_id' => 23, // Fábrica de Software
    'type' => 2 // CommonITILActor::ASSIGN
];

// Llamar manualmente al hook como lo haría GLPI
plugin_unreadnotifications_group_ticket_add($group_ticket);

echo "4. Hook called. Waiting for processing...\n";

// Esperar a que se procese
sleep(3);

// Verificar resultados
global $DB;
$query = "SELECT COUNT(*) as count FROM glpi_plugin_unreadnotifications_notifications 
          WHERE item_id = $ticket_id AND event_type = 'group_assignment'";
$result = $DB->query($query);
$data = $DB->fetchAssoc($result);

echo "5. Notifications created: {$data['count']}\n";

if ($data['count'] > 0) {
    echo "✅ SUCCESS: Group_Ticket hook is working!\n";
} else {
    echo "❌ No notifications created\n";
}

echo "=== TEST COMPLETE ===\n";
?>