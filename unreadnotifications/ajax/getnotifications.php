<?php

include ('../../../inc/includes.php');

header('Content-Type: application/json');

// Verificar sesión
if (!Session::getLoginUserID()) {
   echo json_encode(['success' => false, 'error' => 'No autenticado']);
   exit;
}

try {
   // Forzar la carga de la clase
   $plugin_dir = Plugin::getPhpDir('unreadnotifications');
   if (file_exists($plugin_dir . '/inc/notification.class.php')) {
      include_once($plugin_dir . '/inc/notification.class.php');
   }
   
   if (!class_exists('PluginUnreadnotificationsNotification')) {
      throw new Exception('Notification class not found');
   }
   
// Debería seguir funcionando igual, ya que getNotificationDetails ahora incluye grupos
   $count = PluginUnreadnotificationsNotification::getUnreadCount();
   $details = PluginUnreadnotificationsNotification::getNotificationDetails();
   
   echo json_encode([
      'success' => true,
      'count' => $count,
      'details' => $details
   ]);
   
} catch (Exception $e) {
   echo json_encode([
      'success' => false,
      'error' => $e->getMessage(),
      'count' => 0,
      'details' => [
         'tickets_assigned' => 0,
         'tickets_observer' => 0,
         'tickets_requester' => 0,
         'changes' => 0,
         'problems' => 0
      ]
   ]);
}