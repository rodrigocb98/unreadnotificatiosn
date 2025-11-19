<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "=== ASSIGN TECHNICIANS TO GROUP ===\n";

require_once '/var/www/html/cns/inc/includes.php';

global $DB;

$group_id = 23; // Fabrica

echo "1. Assigning technicians to group ID: $group_id\n";

// Buscar usuarios con perfil de técnico (6)
$tech_query = "SELECT DISTINCT u.id, u.name, u.realname, u.firstname 
              FROM glpi_users u
              INNER JOIN glpi_profiles_users pu ON u.id = pu.users_id
              WHERE pu.profiles_id = 6  -- Technician
              AND u.is_active = 1
              AND u.id > 0
              LIMIT 5";
$tech_result = $DB->query($tech_query);

$assigned_count = 0;
echo "2. Found technicians:\n";
while ($tech_data = $DB->fetchAssoc($tech_result)) {
    echo "   - ID: {$tech_data['id']}, Name: {$tech_data['name']}, Realname: {$tech_data['realname']} {$tech_data['firstname']}\n";
    
    // Verificar si ya está en el grupo
    $check_query = "SELECT id FROM glpi_groups_users 
                   WHERE groups_id = $group_id AND users_id = {$tech_data['id']}";
    $check_result = $DB->query($check_query);
    
    if ($DB->numrows($check_result) > 0) {
        echo "     ✅ Already in group\n";
        $assigned_count++;
    } else {
        // Asignar al grupo
        $group_user = new Group_User();
        $assign_result = $group_user->add([
            'groups_id' => $group_id,
            'users_id' => $tech_data['id'],
            'entities_id' => 0
        ]);
        
        if ($assign_result) {
            echo "     ✅ Added to group\n";
            $assigned_count++;
        } else {
            echo "     ❌ Failed to add to group\n";
        }
    }
}

echo "3. Total technicians in group: $assigned_count\n";

// Verificar resultado final
$final_check = PluginUnreadnotificationsNotificationEvent::getTechniciansInGroup($group_id);
echo "4. Final technician count in group: " . count($final_check) . "\n";

if (!empty($final_check)) {
    echo "5. Technician IDs: " . implode(', ', $final_check) . "\n";
}

echo "=== ASSIGNMENT COMPLETE ===\n";
?>