<?php

define('PLUGIN_UNREADNOTIFICATIONS_VERSION', '2.2.0');
define('PLUGIN_UNREADNOTIFICATIONS_MIN_GLPI', '10.0.0');
define('PLUGIN_UNREADNOTIFICATIONS_MAX_GLPI', '10.0.99');

function plugin_version_unreadnotifications() {
   return [
      'name'           => 'Unread Notifications',
      'version'        => PLUGIN_UNREADNOTIFICATIONS_VERSION,
      'author'         => 'Rodrigo Cruz',
      'license'        => 'GPLv2+',
      'homepage'       => '',
      'minGlpiVersion' => PLUGIN_UNREADNOTIFICATIONS_MIN_GLPI,
      'maxGlpiVersion' => PLUGIN_UNREADNOTIFICATIONS_MAX_GLPI,
   ];
}

function plugin_unreadnotifications_check_prerequisites() {
   if (version_compare(GLPI_VERSION, PLUGIN_UNREADNOTIFICATIONS_MIN_GLPI, 'lt')) {
      echo "This plugin requires GLPI >= " . PLUGIN_UNREADNOTIFICATIONS_MIN_GLPI;
      return false;
   }
   return true;
}

function plugin_unreadnotifications_check_config($verbose = false) {
   return true;
}

function plugin_init_unreadnotifications() {
   global $PLUGIN_HOOKS;
   
   $PLUGIN_HOOKS['csrf_compliant']['unreadnotifications'] = true;
   
   // CSS
   $PLUGIN_HOOKS['add_css']['unreadnotifications'] = [
      'css/notificationbell.css'
   ];
   
   // JavaScript
   $PLUGIN_HOOKS['add_javascript']['unreadnotifications'] = [
      'front/notificationbell.js'
   ];
   
   // ✅ HOOKS CRÍTICOS - Deben coincidir con las funciones en hook.php
   $PLUGIN_HOOKS['item_add']['unreadnotifications'] = [
      'Ticket'    => 'plugin_unreadnotifications_item_add',
      'Followup'  => 'plugin_unreadnotifications_followup_add',
      'ITILFollowup' => 'plugin_unreadnotifications_followup_add',
      'Group_Ticket' => 'plugin_unreadnotifications_group_ticket_add'
   ];
   
    // Hook para actualizaciones (para asignaciones de grupo)
   $PLUGIN_HOOKS['item_update']['unreadnotifications'] = [
      'Ticket'    => 'plugin_unreadnotifications_item_update'
   ];


   // Hook para la cabecera
   $PLUGIN_HOOKS['display_header']['unreadnotifications'] = 'plugin_unreadnotifications_display_header';
}
function plugin_unreadnotifications_install() {
   global $DB;
   
   $default_charset = DBConnection::getDefaultCharset();
   $default_collation = DBConnection::getDefaultCollation();
   $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();
   
   $table = 'glpi_plugin_unreadnotifications_notifications';
   
   if (!$DB->tableExists($table)) {
      $query = "CREATE TABLE `$table` (
         `id` INT {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `users_id` INT {$default_key_sign} NOT NULL,
         `item_type` VARCHAR(255) NOT NULL DEFAULT 'Ticket',
         `item_id` INT {$default_key_sign} NOT NULL,
         `event_type` VARCHAR(255) NOT NULL,
         `data` TEXT NULL,
         `is_read` TINYINT DEFAULT 0,
         `date_creation` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
         `date_mod` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
         PRIMARY KEY (`id`),
         KEY `users_id` (`users_id`),
         KEY `is_read` (`is_read`),
         KEY `item_type_item_id` (`item_type`, `item_id`),
         KEY `date_creation` (`date_creation`)
      ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
      
      $result = $DB->query($query);
      
      if (!$result) {
         echo "Error creating table: " . $DB->error() . "\n";
         return false;
      }
   }
   
   return true;
}

function plugin_unreadnotifications_uninstall() {
   global $DB;
   
   $table = 'glpi_plugin_unreadnotifications_notifications';
   if ($DB->tableExists($table)) {
      $DB->query("DROP TABLE `$table`");
   }
   
   return true;
}