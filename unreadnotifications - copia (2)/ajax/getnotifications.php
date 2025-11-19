<?php
include ('../../../inc/includes.php');

header('Content-Type: application/json');

if (!Session::getLoginUserID()) {
   echo json_encode(['success' => false, 'error' => 'No autenticado']);
   exit;
}

try {
   // Incluir la clase
   $plugin_dir = Plugin::getPhpDir('unreadnotifications');
   if (!file_exists($plugin_dir . '/inc/notification.class.php')) {
      throw new Exception('Notification class file not found');
   }
   
   include_once($plugin_dir . '/inc/notification.class.php');
   
   if (!class_exists('PluginUnreadnotificationsNotification')) {
      throw new Exception('Notification class not found');
   }
   
   // Obtener conteo y detalles reales
   $count = PluginUnreadnotificationsNotification::getUnreadCount();
   $details = PluginUnreadnotificationsNotification::getNotificationDetails();
   
   echo json_encode([
      'success' => true,
      'count' => $count,
      'details' => $details
   ]);
   
} catch (Exception $e) {
   error_log("❌ ERROR in getnotifications.php: " . $e->getMessage());
   
   echo json_encode([
      'success' => false,
      'error' => $e->getMessage(),
      'count' => 0,
      'details' => [
         'tickets_assigned' => 0, 'tickets_observer' => 0, 'tickets_requester' => 0,
         'tickets_group' => 0, 'changes' => 0, 'changes_group' => 0,
         'problems' => 0, 'problems_group' => 0
      ]
   ]);
}