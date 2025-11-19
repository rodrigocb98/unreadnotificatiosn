<?php
include ('../../../inc/includes.php');

header('Content-Type: application/json');

if (!Session::getLoginUserID()) {
   echo json_encode(['success' => false, 'error' => 'No autenticado']);
   exit;
}

try {
   $current_user_id = Session::getLoginUserID();
   error_log("🔔 Loading personal notifications for user: $current_user_id");
   
   $plugin_dir = Plugin::getPhpDir('unreadnotifications');
   $notification_file = $plugin_dir . '/inc/notification.class.php';
   
   if (!file_exists($notification_file)) {
      throw new Exception("Notification file not found: $notification_file");
   }
   
   include_once($notification_file);
   
   if (!class_exists('PluginUnreadnotificationsNotification')) {
      throw new Exception("Notification class not found after inclusion");
   }
   
   error_log("🔔 Notification class loaded successfully");
   
   // Obtener notificaciones
   $personal_notifications = PluginUnreadnotificationsNotification::getPersonalNotifications($current_user_id);
   $personal_count = PluginUnreadnotificationsNotification::getPersonalNotificationsCount($current_user_id);
   
   error_log("🔔 Found $personal_count personal notifications");
   
   echo json_encode([
      'success' => true,
      'notifications' => $personal_notifications,
      'count' => $personal_count
   ]);
   
} catch (Exception $e) {
   error_log("❌ ERROR in getpersonalnotifications.php: " . $e->getMessage());
   
   echo json_encode([
      'success' => false,
      'error' => $e->getMessage(),
      'notifications' => [],
      'count' => 0
   ]);
}