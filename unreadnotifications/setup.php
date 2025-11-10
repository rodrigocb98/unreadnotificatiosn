<?php

define('PLUGIN_UNREADNOTIFICATIONS_VERSION', '2.0.7');
define('PLUGIN_UNREADNOTIFICATIONS_MIN_GLPI', '10.0.0');
define('PLUGIN_UNREADNOTIFICATIONS_MAX_GLPI', '10.0.99');

function plugin_version_unreadnotifications() {
   return [
      'name'           => 'Unread Notifications',
      'version'        => PLUGIN_UNREADNOTIFICATIONS_VERSION,
      'author'         => 'Rodrigo Cruz',
      'license'        => 'GPLv2+',
      'homepage'       => 'https://github.com/tu-usuario/unreadnotifications',
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
   if ($verbose) {
      echo 'Installed / not configured';
   }
   return true;
}

function plugin_init_unreadnotifications() {
   global $PLUGIN_HOOKS;
   
   $PLUGIN_HOOKS['csrf_compliant']['unreadnotifications'] = true;
   
   // CSS
   $PLUGIN_HOOKS['add_css']['unreadnotifications'] = [
      'css/notificationbell.css'
   ];
   
   // JavaScript - SOLO UN ARCHIVO
   $PLUGIN_HOOKS['add_javascript']['unreadnotifications'] = [
      'front/notificationbell.js'
   ];
   
   // Hook para la cabecera
   $PLUGIN_HOOKS['display_header']['unreadnotifications'] = 'plugin_unreadnotifications_display_header';
   
   // Hooks para actualización automática
   $PLUGIN_HOOKS['item_update']['unreadnotifications'] = [
      'Ticket' => 'plugin_unreadnotifications_post_item_update',
      'Change' => 'plugin_unreadnotifications_post_item_update',
      'Problem' => 'plugin_unreadnotifications_post_item_update'
   ];
   
   $PLUGIN_HOOKS['item_add']['unreadnotifications'] = [
      'Ticket' => 'plugin_unreadnotifications_post_item_add',
      'Change' => 'plugin_unreadnotifications_post_item_add',
      'Problem' => 'plugin_unreadnotifications_post_item_add'
   ];
}
function plugin_unreadnotifications_display_header() {
   global $CFG_GLPI;
   
   $plugin_web = Plugin::getWebDir('unreadnotifications');
   $glpi_root = $CFG_GLPI['root_doc'];
   
   echo "<script type='text/javascript'>
      // Variables globales para el plugin - DEFINIR PRIMERO
      window.GLPI_PLUGIN_WEBROOT_UNREAD = '" . $plugin_web . "';
      window.GLPI_ROOT_UNREAD = '" . $glpi_root . "';
      window.UNREAD_NOTIFICATIONS_CONFIG = {
         refresh_interval: 30000,
         enable_sounds: true,  // <- DEBE SER true
         enable_websocket: false,
         websocket_url: 'ws://10.20.50.22:8080',
         sounds: {
            notification: '" . $plugin_web . "/sounds/notification.mp3',
            alert: '" . $plugin_web . "/sounds/alert.mp3'
         }
      };
      
      // DEBUG: Verificar configuración
      console.log('🔔 Unread Notifications Config Loaded:', window.UNREAD_NOTIFICATIONS_CONFIG);
   </script>";
}
function plugin_unreadnotifications_install() {
   global $DB;
   
   $default_charset = DBConnection::getDefaultCharset();
   $default_collation = DBConnection::getDefaultCollation();
   $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();
   
   // Crear tabla de configuraciones
   $table = 'glpi_plugin_unreadnotifications_configs';
   
   if (!$DB->tableExists($table)) {
      $query = "CREATE TABLE `$table` (
         `id` INT {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `name` VARCHAR(255) NOT NULL,
         `value` TEXT NULL,
         `date_creation` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
         `date_mod` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
         PRIMARY KEY (`id`),
         UNIQUE KEY `name` (`name`)
      ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
      
      $DB->queryOrDie($query, $DB->error());
   }
   
   // Configuración por defecto
   $config = new Config();
   $config->setConfigurationValues('plugin:UnreadNotifications', [
      'version' => PLUGIN_UNREADNOTIFICATIONS_VERSION,
      'refresh_interval' => 30,
      'enable_sounds' => 1,
      'enable_websocket' => 0, // Por defecto desactivado (requiere servidor WS)
      'websocket_url' => 'ws://10.20.50.22:8080',
      'show_ticket_assigned' => 1,
      'show_ticket_observer' => 1,
      'show_ticket_requester' => 1,
      'show_changes' => 1,
      'show_problems' => 1
   ]);
   
   return true;
}

function plugin_unreadnotifications_uninstall() {
   global $DB;
   
   // Eliminar tabla de configuración
   $table = 'glpi_plugin_unreadnotifications_configs';
   if ($DB->tableExists($table)) {
      $query = "DROP TABLE `$table`";
      $DB->queryOrDie($query, $DB->error());
   }
   
   // Eliminar configuración
   $config = new Config();
   $config->deleteByCriteria(['context' => 'plugin:UnreadNotifications']);
   
   return true;
}