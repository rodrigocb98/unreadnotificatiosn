<?php
include ('../../../inc/includes.php');

header('Content-Type: application/json');

if (!Session::getLoginUserID()) {
   echo json_encode(['success' => false, 'error' => 'No autenticado']);
   exit;
}

try {
   global $DB;
   
   $default_charset = DBConnection::getDefaultCharset();
   $default_collation = DBConnection::getDefaultCollation();
   $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();
   
   $table = 'glpi_plugin_unreadnotifications_notifications';
   
   if ($DB->tableExists($table)) {
      echo json_encode([
         'success' => true,
         'message' => 'Table already exists',
         'table' => $table
      ]);
      exit;
   }
   
   $query = "CREATE TABLE `$table` (
      `id` INT {$default_key_sign} NOT NULL AUTO_INCREMENT,
      `users_id` INT {$default_key_sign} NOT NULL,
      `event_type` VARCHAR(255) NOT NULL,
      `item_type` VARCHAR(255) NOT NULL,
      `item_id` INT {$default_key_sign} NOT NULL,
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
   
   if ($result) {
      echo json_encode([
         'success' => true,
         'message' => 'Table created successfully',
         'table' => $table
      ]);
   } else {
      throw new Exception("Failed to create table: " . $DB->error());
   }
   
} catch (Exception $e) {
   echo json_encode([
      'success' => false,
      'error' => $e->getMessage()
   ]);
}