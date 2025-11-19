<?php
include ('../../../inc/includes.php');

header('Content-Type: application/json');

if (!Session::getLoginUserID()) {
   echo json_encode(['error' => 'No autenticado']);
   exit;
}

$plugin_dir = Plugin::getPhpDir('unreadnotifications');
include_once($plugin_dir . '/inc/notification.class.php');

$changes = PluginUnreadnotificationsNotification::debugChangesStatus();

echo json_encode([
   'success' => true,
   'changes' => $changes,
   'current_user' => Session::getLoginUserID()
]);