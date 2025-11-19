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
   include_once($plugin_dir . '/inc/notification.class.php');
   
   if (!class_exists('PluginUnreadnotificationsNotification')) {
      throw new Exception('Notification class not found');
   }
   
   $current_user_id = Session::getLoginUserID();
   error_log("Unread Notifications Debug: User ID = " . $current_user_id);
   
   $items = PluginUnreadnotificationsNotification::getNotificationItems($current_user_id);
   
   // Log para debug
   error_log("Unread Notifications Debug: Items = " . json_encode($items));
   
   echo json_encode([
      'success' => true,
      'items' => $items,
      'debug' => [
         'user_id' => $current_user_id,
         'timestamp' => date('Y-m-d H:i:s')
      ]
   ]);
   
} catch (Exception $e) {
   error_log("Unread Notifications Error: " . $e->getMessage());
   echo json_encode([
      'success' => false,
      'error' => $e->getMessage(),
      'items' => [
         'tickets_assigned' => [],
         'tickets_requester' => [],
         'changes' => []
      ]
   ]);
}