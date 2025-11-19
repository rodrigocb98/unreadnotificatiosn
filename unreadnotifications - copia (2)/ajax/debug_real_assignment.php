<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "=== DEBUG REAL ASSIGNMENT ===\n";

require_once '/var/www/html/cns/inc/includes.php';

// Incluir archivos del plugin
$plugin_dir = Plugin::getPhpDir('unreadnotifications');
include_once $plugin_dir . '/hook.php';

// Crear un ticket para probar
echo "1. Creating test ticket...\n";
$ticket = new Ticket();
$ticket_data = [
    'name' => 'Debug Real Assignment - ' . date('H:i:s'),
    'content' => 'Testing real assignment from Actors section',
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

// Simular EXACTAMENTE lo que hace GLPI cuando se asigna desde Actors
echo "3. Simulating Actors assignment...\n";

// Método 1: Usar Group_Ticket (como lo hace GLPI en Actors)
$group_ticket = new Group_Ticket();
$assignment_data = [
    'tickets_id' => $ticket_id,
    'groups_id' => 23, // Fábrica de Software
    'type' => CommonITILActor::ASSIGN // 2 = Assign
];

echo "4. Adding group assignment via Group_Ticket...\n";
$assignment_id = $group_ticket->add($assignment_data);

if ($assignment_id) {
    echo "5. Group assignment added: $assignment_id\n";
    
    // Verificar si se activó algún hook
    echo "6. Checking if hooks were triggered...\n";
    
    // También probar actualizando el ticket directamente
    echo "7. Testing direct ticket update...\n";
    $ticket->update([
        'id' => $ticket_id,
        '_groups_id_assign' => 23
    ]);
    
} else {
    echo "5. ❌ Failed to add group assignment\n";
}

echo "=== DEBUG COMPLETE ===\n";
?>