<?php
include ('../../inc/includes.php');

header('Content-Type: text/plain');

if (!Session::getLoginUserID()) {
   die("No autenticado");
}

$user_id = Session::getLoginUserID();
echo "=== DEBUG UNREAD NOTIFICATIONS ===\n";
echo "User ID: $user_id\n\n";

// Test tickets asignados
$query = "SELECT COUNT(*) as count FROM glpi_tickets WHERE users_id_recipient = $user_id AND status IN (1,2,3,4,5) AND is_deleted = 0";
$result = $DB->query($query);
$data = $DB->fetchAssoc($result);
echo "Tickets asignados (directo): " . $data['count'] . "\n";

// Test tickets via glpi_tickets_users
$query = "SELECT COUNT(*) as count FROM glpi_tickets t 
          JOIN glpi_tickets_users tu ON t.id = tu.tickets_id 
          WHERE tu.users_id = $user_id AND tu.type = 2 AND t.status IN (1,2,3,4,5) AND t.is_deleted = 0";
$result = $DB->query($query);
$data = $DB->fetchAssoc($result);
echo "Tickets asignados (via tickets_users): " . $data['count'] . "\n";

// Test tickets solicitante
$query = "SELECT COUNT(*) as count FROM glpi_tickets t 
          JOIN glpi_tickets_users tu ON t.id = tu.tickets_id 
          WHERE tu.users_id = $user_id AND tu.type = 1 AND t.status IN (1,2,3,4,5) AND t.is_deleted = 0";
$result = $DB->query($query);
$data = $DB->fetchAssoc($result);
echo "Tickets solicitante: " . $data['count'] . "\n";

// Test cambios
$query = "SELECT COUNT(*) as count FROM glpi_changes WHERE users_id_recipient = $user_id AND status IN (1,2,3,4,5,7) AND is_deleted = 0";
$result = $DB->query($query);
$data = $DB->fetchAssoc($result);
echo "Cambios asignados (directo): " . $data['count'] . "\n";

$query = "SELECT COUNT(*) as count FROM glpi_changes c 
          JOIN glpi_changes_users cu ON c.id = cu.changes_id 
          WHERE cu.users_id = $user_id AND cu.type IN (2,3) AND c.status IN (1,2,3,4,5,7) AND c.is_deleted = 0";
$result = $DB->query($query);
$data = $DB->fetchAssoc($result);
echo "Cambios (via changes_users): " . $data['count'] . "\n";