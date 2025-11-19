<?php

class PluginUnreadnotificationsNotificationRule extends CommonDBTM {
   
   static $rightname = 'plugin_unreadnotifications';
   
   /**
    * Obtiene las reglas de notificación para un perfil específico
    */
   static function getRulesForProfile($profiles_id) {
      $profile = new Profile();
      if ($profile->getFromDB($profiles_id)) {
         $profile_name = strtolower($profile->getField('name'));
         
         if (strpos($profile_name, 'hotliner') !== false) {
            return [
               'new_ticket' => true,
               'ticket_update' => true,
               'technical_comment' => true,
               'client_comment' => true,
               'status_change' => true,
               'group_assignment' => true,
               'user_mentioned' => true
            ];
         } elseif (strpos($profile_name, 'tec') !== false || strpos($profile_name, 'soporte') !== false) {
            return [
               'group_assignment' => true,
               'ticket_update' => true,
               'hotliner_comment' => true,
               'client_comment' => true,
               'technical_comment' => true,
               'status_change' => true,
               'user_mentioned' => true
            ];
         } elseif (strpos($profile_name, 'cliente') !== false) {
            return [
               'hotliner_response' => true,
               'status_change' => true,
               'user_mentioned' => true
            ];
         }
      }
      
      // Regla por defecto para técnicos
      return [
         'group_assignment' => true,
         'ticket_update' => true,
         'status_change' => true,
         'user_mentioned' => true
      ];
   }
}