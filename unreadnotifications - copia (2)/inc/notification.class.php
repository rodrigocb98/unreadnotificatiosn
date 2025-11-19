<?php

class PluginUnreadnotificationsNotification extends CommonDBTM {
   
   static $rightname = 'plugin_unreadnotifications';
   
   /**
    * Obtiene el conteo de notificaciones no leídas
    */
   static function getUnreadCount($users_id = null) {
      global $DB;
      
      if ($users_id === null) {
         $users_id = Session::getLoginUserID();
      }
      
      if (!$users_id) {
         return 0;
      }
      
      $count = 0;
      
      try {
         $table = 'glpi_plugin_unreadnotifications_notifications';
         if ($DB->tableExists($table)) {
            $query = "SELECT COUNT(*) as count FROM $table WHERE users_id = $users_id AND is_read = 0";
            $result = $DB->query($query);
            if ($result && $data = $DB->fetchAssoc($result)) {
               $count = (int)$data['count'];
            }
         }
      } catch (Exception $e) {
         error_log("🔔 ERROR in getUnreadCount: " . $e->getMessage());
      }
      
      return $count;
   }
   
   /**
    * Obtiene detalles de las notificaciones
    */
   static function getNotificationDetails($users_id = null) {
      return [
         'tickets_assigned' => 0,
         'tickets_observer' => 0,
         'tickets_requester' => 0,
         'tickets_group' => 0,
         'changes' => 0,
         'changes_group' => 0,
         'problems' => 0,
         'problems_group' => 0
      ];
   }
   
   /**
    * Obtiene notificaciones personales
    */
   static function getPersonalNotifications($users_id = null, $limit = 50) {
      global $DB;
      
      if ($users_id === null) {
         $users_id = Session::getLoginUserID();
      }
      
      if (!$users_id) {
         return [];
      }
      
      $notifications = [];
      
      try {
         $table = 'glpi_plugin_unreadnotifications_notifications';
         
         if (!$DB->tableExists($table)) {
            return [];
         }
         
         $query = "SELECT id, event_type, item_type, item_id, data, date_creation 
                   FROM $table 
                   WHERE users_id = $users_id AND is_read = 0 
                   ORDER BY date_creation DESC 
                   LIMIT " . (int)$limit;
         
         $result = $DB->query($query);
         
         if (!$result) {
            return [];
         }
         
         while ($data = $DB->fetchAssoc($result)) {
            $notification_data = [];
            
            if (!empty($data['data'])) {
               $decoded_data = json_decode($data['data'], true);
               if (json_last_error() === JSON_ERROR_NONE) {
                  $notification_data = $decoded_data;
               }
            }
            
            $notifications[] = [
               'id' => $data['id'],
               'event_type' => $data['event_type'],
               'item_type' => $data['item_type'],
               'item_id' => $data['item_id'],
               'data' => $notification_data,
               'date_creation' => $data['date_creation'],
               'message' => self::formatPersonalNotificationMessage($data['event_type'], $notification_data)
            ];
         }
      } catch (Exception $e) {
         error_log("🔔 ERROR in getPersonalNotifications: " . $e->getMessage());
      }
      
      return $notifications;
   }
   
   /**
    * Obtiene conteo de notificaciones personales
    */
   static function getPersonalNotificationsCount($users_id = null) {
      global $DB;
      
      if ($users_id === null) {
         $users_id = Session::getLoginUserID();
      }
      
      if (!$users_id) {
         return 0;
      }
      
      try {
         $table = 'glpi_plugin_unreadnotifications_notifications';
         if (!$DB->tableExists($table)) {
            return 0;
         }
         
         $query = "SELECT COUNT(*) as count FROM $table WHERE users_id = $users_id AND is_read = 0";
         $result = $DB->query($query);
         
         if ($result && $data = $DB->fetchAssoc($result)) {
            return (int)$data['count'];
         }
      } catch (Exception $e) {
         error_log("🔔 ERROR in getPersonalNotificationsCount: " . $e->getMessage());
      }
      
      return 0;
   }
   
   /**
    * Formatea mensajes para notificaciones personales
    */
   static function formatPersonalNotificationMessage($event_type, $data) {
      switch ($event_type) {
         case 'new_ticket':
            $creator_name = $data['creator_name'] ?? 'Usuario';
            return "📝 $creator_name ha creado un nuevo ticket: {$data['ticket_name']}";
            
         case 'user_mentioned':
            $mentioner_name = $data['mentioned_by_name'] ?? 'Usuario';
            $ticket_ref = $data['ticket_name'] ?? "Ticket #{$data['ticket_id']}";
            $private_label = isset($data['is_private']) && $data['is_private'] ? ' (Privado)' : '';
            return "👤 $mentioner_name te mencionó en $ticket_ref$private_label";
            
         case 'group_assignment':
         $group_name = $data['group_name'] ?? 'Grupo';
         $assigned_by = $data['assigned_by_name'] ?? 'Sistema';
         return "👥 $assigned_name te ha asignado al grupo '$group_name' en el ticket: {$data['ticket_name']}";
            
         case 'status_change':
            $changer_name = $data['changed_by_name'] ?? 'Sistema';
            $status_name = $data['status_name'] ?? 'nuevo estado';
            return "🔄 $changer_name cambió el estado a '$status_name' en: {$data['item_name']}";
         case 'new_comment':
            $commenter_name = $data['commented_by_name'] ?? 'Usuario';
            $ticket_ref = $data['ticket_name'] ?? "Ticket #{$data['ticket_id']}";
            $private_label = isset($data['is_private']) && $data['is_private'] ? ' (Privado)' : '';
            return "💬 $commenter_name añadió un nuevo seguimiento en $ticket_ref$private_label";
            
         case 'test_notification':
         case 'manual_test':
         case 'simple_test':
         case 'super_simple_test':
            return "🧪 Notificación de prueba: " . ($data['test_message'] ?? $data['message'] ?? 'Test');
            
         default:
            return "🔔 Tienes una nueva notificación ($event_type)";
      }
   }
   
   /**
    * Marca una notificación como leída
    */
   static function markAsRead($notification_id) {
      global $DB;
      
      try {
         $table = 'glpi_plugin_unreadnotifications_notifications';
         $DB->update($table, [
            'is_read' => 1,
            'date_mod' => date('Y-m-d H:i:s')
         ], [
            'id' => $notification_id,
            'users_id' => Session::getLoginUserID()
         ]);
         
         return true;
      } catch (Exception $e) {
         error_log("🔔 ERROR in markAsRead: " . $e->getMessage());
         return false;
      }
   }
}