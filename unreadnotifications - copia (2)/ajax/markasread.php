<?php

include ('../../../inc/includes.php');

header('Content-Type: application/json');

if (!Session::getLoginUserID()) {
   echo json_encode(['success' => false, 'error' => 'No autenticado']);
   exit;
}

if (!isset($_POST['notification_id'])) {
   echo json_encode(['success' => false, 'error' => 'ID de notificación requerido']);
   exit;
}

try {
   $plugin_dir = Plugin::getPhpDir('unreadnotifications');
   if (file_exists($plugin_dir . '/inc/notification.class.php')) {
      include_once($plugin_dir . '/inc/notification.class.php');
   }
   
   if (class_exists('PluginUnreadnotificationsNotification')) {
      PluginUnreadnotificationsNotification::markAsRead(intval($_POST['notification_id']));
   }
   
   echo json_encode([
      'success' => true
   ]);
   
} catch (Exception $e) {
   error_log("Unread Notifications Error: " . $e->getMessage());
   echo json_encode([
      'success' => false,
      'error' => $e->getMessage()
   ]);
}