<?php

class PluginUnreadnotificationsNotificationEvent extends CommonDBTM {
   
   static $rightname = 'plugin_unreadnotifications';
   
   /**
    * Procesa un nuevo ticket
    */
   static function processNewTicket($ticket) {
      global $DB;
      
      $ticket_id = $ticket->getID();
      if (!$ticket_id) {
         error_log("🔔 ERROR: Ticket ID not available");
         return false;
      }
      
      error_log("🎯 PROCESSING NEW TICKET: $ticket_id - '{$ticket->getField('name')}'");
      
      // Obtener el creador del ticket
      $creator_id = $ticket->getField('users_id_recipient');
      $creator = new User();
      $creator_name = "Usuario";
      if ($creator->getFromDB($creator_id)) {
         $creator_name = $creator->getField('name');
      }
      
      error_log("🎯 Ticket created by: $creator_name (ID: $creator_id)");
      
      // Obtener hotliners (perfil 5)
      $hotliners = self::getUsersByProfile(5, 'Mesa de Servicios');
      
      if (empty($hotliners)) {
         error_log("🎯 WARNING: No hotliners found. Trying technicians (profile 6)...");
         $hotliners = self::getUsersByProfile(6, 'Technician');
      }
      
      if (empty($hotliners)) {
         error_log("🎯 WARNING: No technicians found either. Trying any active user...");
         $hotliners = self::getAnyActiveUsers();
      }
      
      error_log("🎯 Found " . count($hotliners) . " users to notify");
      
      $notified_count = 0;
      foreach ($hotliners as $hotliner_id) {
         // No notificar al creador del ticket
         if ($hotliner_id != $creator_id) {
            $result = self::createNotification($hotliner_id, 'new_ticket', $ticket_id, [
               'ticket_name' => $ticket->getField('name'),
               'ticket_id' => $ticket_id,
               'creator_id' => $creator_id,
               'creator_name' => $creator_name,
               'ticket_content' => substr($ticket->getField('content'), 0, 200)
            ]);
            
            if ($result) {
               $notified_count++;
               error_log("🎯 NOTIFIED user: $hotliner_id about new ticket: $ticket_id");
            } else {
               error_log("🎯 FAILED to notify user: $hotliner_id");
            }
         } else {
            error_log("🎯 SKIPPED creator: $hotliner_id");
         }
      }
      
      error_log("🎯 FINAL: Notified $notified_count users about new ticket $ticket_id");
      return $notified_count > 0;
   }

   /**
    * Obtiene usuarios por perfil
    */
   static function getUsersByProfile($profile_id, $profile_name = null) {
      global $DB;
      
      $users = [];
      
      // Primero intentar por ID de perfil
      $query = "SELECT DISTINCT u.id 
                FROM glpi_users u
                INNER JOIN glpi_profiles_users pu ON u.id = pu.users_id
                WHERE pu.profiles_id = $profile_id 
                AND u.is_active = 1
                AND u.id > 0";
      
      error_log("🔔 Profile query by ID: $query");
      
      $result = $DB->query($query);
      if ($result) {
         while ($data = $DB->fetchAssoc($result)) {
            $users[] = $data['id'];
         }
      }
      
      // Si no se encontraron usuarios y hay nombre de perfil, intentar por nombre
      if (empty($users) && $profile_name) {
         $query = "SELECT DISTINCT u.id 
                   FROM glpi_users u
                   INNER JOIN glpi_profiles_users pu ON u.id = pu.users_id
                   INNER JOIN glpi_profiles p ON pu.profiles_id = p.id
                   WHERE p.name LIKE '%$profile_name%' 
                   AND u.is_active = 1
                   AND u.id > 0";
         
         error_log("🔔 Profile query by name: $query");
         
         $result = $DB->query($query);
         if ($result) {
            while ($data = $DB->fetchAssoc($result)) {
               $users[] = $data['id'];
            }
         }
      }
      
      error_log("🔔 Found " . count($users) . " users for profile $profile_id ($profile_name)");
      return $users;
   }

   /**
    * Obtiene cualquier usuario activo como fallback
    */
   static function getAnyActiveUsers() {
      global $DB;
      
      $users = [];
      $query = "SELECT id FROM glpi_users WHERE is_active = 1 AND id > 0 LIMIT 10";
      
      $result = $DB->query($query);
      if ($result) {
         while ($data = $DB->fetchAssoc($result)) {
            $users[] = $data['id'];
         }
      }
      
      error_log("🔔 Found " . count($users) . " active users as fallback");
      return $users;
   }

   /**
    * Procesa nuevo comentario con menciones
    */
   /**
 * Procesa nuevo comentario con notificaciones a grupos asignados y hotliners
 */
/**
 * Procesa nuevo comentario con notificaciones a grupos asignados y hotliners
 */
static function processNewComment($item_id, $comment, $commenting_user_id, $is_private = false, $item_type = 'Ticket') {
    error_log("💬 PROCESSING NEW COMMENT on $item_type: $item_id by user: $commenting_user_id, Private: " . ($is_private ? 'Yes' : 'No'));
    
    $item = new $item_type();
    if (!$item->getFromDB($item_id)) {
        error_log("❌ Item not found: $item_type $item_id");
        return 0;
    }
    
    $item_name = "#" . $item->getID() . " " . $item->getField('name');
    
    $commenter = new User();
    $commenter_name = "Usuario";
    if ($commenter->getFromDB($commenting_user_id)) {
        $commenter_name = $commenter->getField('name');
    }
    
    // Determinar si el comentarista es hotliner/técnico
    $is_commenter_hotliner = self::isHotlinerOrTechnician($commenting_user_id);
    error_log("💬 Commenter $commenter_name (ID: $commenting_user_id) is hotliner/technician: " . ($is_commenter_hotliner ? 'Yes' : 'No'));
    error_log("💬 Comment is private: " . ($is_private ? 'Yes' : 'No'));
    
    // 1. Buscar menciones de usuarios (comportamiento existente)
    $mentioned_users = self::findMentions($comment);
    error_log("💬 Found " . count($mentioned_users) . " mentions in comment");
    
    // 2. Obtener usuarios a notificar por grupos y hotliners
    $users_to_notify = [];
    
    if ($item_type == 'Ticket') {
        // Obtener grupos asignados al ticket
        $assigned_groups = self::getAssignedGroups($item_id);
        error_log("💬 Found " . count($assigned_groups) . " assigned groups");
        
        // Obtener usuarios de los grupos asignados
        $group_users = self::getUsersFromGroups($assigned_groups);
        error_log("💬 Found " . count($group_users) . " users from assigned groups");
        
        // Obtener hotliners y técnicos
        $hotliners = self::getHotlinersAndTechnicians();
        error_log("💬 Found " . count($hotliners) . " hotliners/technicians");
        
        // Combinar todos los usuarios
        $users_to_notify = array_merge($group_users, $hotliners);
        $users_to_notify = array_unique($users_to_notify);
        
        error_log("💬 Total users to notify (before filtering): " . count($users_to_notify));
        
        // Filtrar usuarios según reglas de negocio
        $filtered_users = [];
        foreach ($users_to_notify as $user_id) {
            // No notificar al que hizo el comentario
            if ($user_id == $commenting_user_id) {
                error_log("💬 SKIPPING commenter user: $user_id");
                continue;
            }
            
            $is_self_service = self::isSelfServiceUser($user_id);
            $user = new User();
            $user_name = "User";
            if ($user->getFromDB($user_id)) {
                $user_name = $user->getField('name');
            }
            
            error_log("💬 Checking user: $user_name (ID: $user_id) - Self-Service: " . ($is_self_service ? 'Yes' : 'No'));
            
            // Si el usuario es Self-Service, solo notificar si:
            // - El comentario es público (no privado) Y
            // - El comentarista es hotliner/técnico
            if ($is_self_service) {
                error_log("💬 Self-Service user $user_name - Private comment: " . ($is_private ? 'Yes' : 'No') . ", Commenter is hotliner: " . ($is_commenter_hotliner ? 'Yes' : 'No'));
                
                if (!$is_private && $is_commenter_hotliner) {
                    $filtered_users[] = $user_id;
                    error_log("💬 ✅ ADDING Self-Service user $user_name to notifications");
                } else {
                    error_log("💬 ❌ SKIPPING Self-Service user $user_name - Conditions not met");
                }
            } else {
                // Para otros usuarios (hotliners, técnicos, etc.), notificar siempre
                $filtered_users[] = $user_id;
                error_log("💬 ✅ ADDING non-Self-Service user $user_name to notifications");
            }
        }
        
        $users_to_notify = $filtered_users;
    }
    
    // Combinar con usuarios mencionados
    $all_users_to_notify = array_merge($users_to_notify, $mentioned_users);
    $all_users_to_notify = array_unique($all_users_to_notify);
    
    // Remover el usuario que comentó si está en la lista
    $all_users_to_notify = array_diff($all_users_to_notify, [$commenting_user_id]);
    
    error_log("💬 Final users to notify: " . count($all_users_to_notify));
    
    $notified_count = 0;
    
    // Notificar a todos los usuarios
    foreach ($all_users_to_notify as $user_id) {
        // Verificar que el usuario existe y está activo
        $user = new User();
        if (!$user->getFromDB($user_id) || !$user->getField('is_active')) {
            error_log("💬 SKIPPING inactive or non-existent user: $user_id");
            continue;
        }
        
        // Determinar el tipo de evento
        $event_type = in_array($user_id, $mentioned_users) ? 'user_mentioned' : 'new_comment';
        
        $result = self::createNotification($user_id, $event_type, $item_id, [
            'item_type' => $item_type,
            'commented_by' => $commenting_user_id,
            'commented_by_name' => $commenter_name,
            'comment_preview' => substr($comment, 0, 100),
            'ticket_name' => $item_name,
            'ticket_id' => $item_id,
            'is_private' => $is_private,
            'is_commenter_hotliner' => $is_commenter_hotliner
        ]);
        
        if ($result) {
            $notified_count++;
            $event_label = $event_type == 'user_mentioned' ? 'MENTIONED' : 'NOTIFIED';
            error_log("💬 $event_label user: $user_id - $commenter_name añadió un comentario");
        } else {
            error_log("💬 FAILED to notify user: $user_id");
        }
    }
    
    error_log("💬 SUCCESS: Notified $notified_count users about new comment");
    return $notified_count;
}
/**
 * Obtiene hotliners (perfil 5) y técnicos (perfil 6)
 */
static function getHotlinersAndTechnicians() {
    global $DB;
    
    $users = [];
    
    // Obtener hotliners (perfil 5 - Mesa de Servicios)
    $query = "SELECT DISTINCT u.id 
              FROM glpi_users u
              INNER JOIN glpi_profiles_users pu ON u.id = pu.users_id
              WHERE pu.profiles_id = 5 
              AND u.is_active = 1
              AND u.id > 0";
    
    $result = $DB->query($query);
    if ($result) {
        while ($data = $DB->fetchAssoc($result)) {
            $users[] = $data['id'];
        }
    }
    
    // Obtener técnicos (perfil 6 - Technician)
    $query = "SELECT DISTINCT u.id 
              FROM glpi_users u
              INNER JOIN glpi_profiles_users pu ON u.id = pu.users_id
              WHERE pu.profiles_id = 6 
              AND u.is_active = 1
              AND u.id > 0";
    
    $result = $DB->query($query);
    if ($result) {
        while ($data = $DB->fetchAssoc($result)) {
            $users[] = $data['id'];
        }
    }
    
    return array_unique($users);
}

/**
 * Obtiene grupos asignados a un ticket
 */
static function getAssignedGroups($ticket_id) {
    global $DB;
    
    $groups = [];
    $query = "SELECT groups_id 
              FROM glpi_groups_tickets 
              WHERE tickets_id = $ticket_id 
              AND type = " . CommonITILActor::ASSIGN;
    
    $result = $DB->query($query);
    if ($result) {
        while ($data = $DB->fetchAssoc($result)) {
            $groups[] = $data['groups_id'];
        }
    }
    
    return $groups;
}

/**
 * Obtiene usuarios de grupos específicos
 */
static function getUsersFromGroups($group_ids) {
    global $DB;
    
    if (empty($group_ids)) {
        return [];
    }
    
    $users = [];
    $group_ids_str = implode(',', $group_ids);
    
    $query = "SELECT DISTINCT u.id 
              FROM glpi_users u
              INNER JOIN glpi_groups_users gu ON u.id = gu.users_id
              WHERE gu.groups_id IN ($group_ids_str)
              AND u.is_active = 1
              AND u.id > 0";
    
    $result = $DB->query($query);
    if ($result) {
        while ($data = $DB->fetchAssoc($result)) {
            $users[] = $data['id'];
        }
    }
    
    return $users;
}


/**
 * Verifica si un usuario tiene perfil Self-Service
 */
/**
 * Verifica si un usuario tiene perfil Self-Service (versión mejorada)
 */
static function isSelfServiceUser($user_id) {
    // Primero intentar por ID del perfil (más confiable)
    if (self::isSelfServiceUserByProfileId($user_id)) {
        return true;
    }
    
    // Si no funciona, intentar por nombre
    global $DB;
    
    $query = "SELECT COUNT(*) as count 
              FROM glpi_profiles_users pu
              INNER JOIN glpi_profiles p ON pu.profiles_id = p.id
              WHERE pu.users_id = $user_id 
              AND p.name = 'Usuario(Self-Service)'";
    
    error_log("🔍 Checking Self-Service for user $user_id - Query: $query");
    
    $result = $DB->query($query);
    if ($result && $data = $DB->fetchAssoc($result)) {
        $is_self_service = $data['count'] > 0;
        error_log("🔍 User $user_id is Self-Service: " . ($is_self_service ? 'Yes' : 'No'));
        return $is_self_service;
    }
    
    error_log("🔍 User $user_id is Self-Service: No (query failed or no results)");
    return false;
}
/**
 * Verifica si un usuario tiene perfil Self-Service por ID (más confiable)
 */
static function isSelfServiceUserByProfileId($user_id) {
    global $DB;
    
    // El perfil 1 es "Usuario(Self-Service)" según tu base de datos
    $query = "SELECT COUNT(*) as count 
              FROM glpi_profiles_users pu
              WHERE pu.users_id = $user_id 
              AND pu.profiles_id = 1";
    
    error_log("🔍 Checking Self-Service by Profile ID for user $user_id - Query: $query");
    
    $result = $DB->query($query);
    if ($result && $data = $DB->fetchAssoc($result)) {
        $is_self_service = $data['count'] > 0;
        error_log("🔍 User $user_id is Self-Service (by Profile ID): " . ($is_self_service ? 'Yes' : 'No'));
        return $is_self_service;
    }
    
    return false;
}
/**
 * Verifica si un usuario es hotliner o técnico
 */
static function isHotlinerOrTechnician($user_id) {
    global $DB;
    
    $query = "SELECT COUNT(*) as count 
              FROM glpi_profiles_users pu
              WHERE pu.users_id = $user_id 
              AND (pu.profiles_id = 5 OR pu.profiles_id = 6)";
    
    error_log("🔍 Checking Hotliner/Technician for user $user_id - Query: $query");
    
    $result = $DB->query($query);
    if ($result && $data = $DB->fetchAssoc($result)) {
        $is_hotliner = $data['count'] > 0;
        error_log("🔍 User $user_id is Hotliner/Technician: " . ($is_hotliner ? 'Yes' : 'No'));
        return $is_hotliner;
    }
    
    error_log("🔍 User $user_id is Hotliner/Technician: No (query failed or no results)");
    return false;
}






   // ========== MÉTODOS AUXILIARES ==========

   static function findMentions($text) {
      preg_match_all('/@([a-zA-Z0-9._-]+)/', $text, $matches);
      $mentioned_usernames = $matches[1];
      $mentioned_user_ids = [];
      
      foreach ($mentioned_usernames as $username) {
         $user_id = User::getUserIDByName($username);
         if ($user_id) {
            $mentioned_user_ids[] = $user_id;
            error_log("💬 Found mention: @$username -> user_id: $user_id");
         } else {
            error_log("💬 User not found for mention: @$username");
         }
      }
      
      return array_unique($mentioned_user_ids);
   }

   static function createNotification($users_id, $event_type, $item_id, $data = []) {
      global $DB;
      
      try {
         $notification = [
            'users_id' => $users_id,
            'event_type' => $event_type,
            'item_type' => 'Ticket',
            'item_id' => $item_id,
            'data' => json_encode($data),
            'is_read' => 0,
            'date_creation' => date('Y-m-d H:i:s')
         ];
         
         $result = $DB->insert('glpi_plugin_unreadnotifications_notifications', $notification);
         
         if ($result) {
            error_log("✅ NOTIFICATION CREATED: User $users_id, Event: $event_type, Item: $item_id");
            return true;
         } else {
            error_log("❌ NOTIFICATION FAILED: User $users_id, Event: $event_type - " . $DB->error());
            return false;
         }
         
      } catch (Exception $e) {
         error_log("🔔 ERROR creating notification: " . $e->getMessage());
         return false;
      }
   }

   /**
 * Procesa asignación de grupo a un ticket
 */
static function processGroupAssignment($ticket, $group_id) {
    $ticket_id = $ticket->getID();
    $group_id = intval($group_id);
    
    error_log("👥 PROCESSING GROUP ASSIGNMENT: Ticket $ticket_id to group $group_id");
    
    if (!$ticket_id || !$group_id) {
        error_log("❌ Invalid ticket ID or group ID");
        return false;
    }
    
    // Obtener información del grupo
    $group = new Group();
    if (!$group->getFromDB($group_id)) {
        error_log("❌ Group not found: $group_id");
        return false;
    }
    
    $group_name = $group->getField('name');
    error_log("👥 Group name: $group_name");
    
    // Obtener técnicos del grupo (perfil 6 - Technician)
    $technicians = self::getTechniciansInGroup($group_id);
    error_log("👥 Found " . count($technicians) . " technicians in group $group_id");
    
    if (empty($technicians)) {
        error_log("👥 No technicians found in group $group_id, trying any users in group...");
        // Fallback: cualquier usuario en el grupo
        $technicians = self::getAnyUsersInGroup($group_id);
        error_log("👥 Found " . count($technicians) . " users in group as fallback");
    }
    
    $notified_count = 0;
    foreach ($technicians as $technician_id) {
        $result = self::createNotification($technician_id, 'group_assignment', $ticket_id, [
            'item_type' => 'Ticket',
            'item_name' => $ticket->getField('name'),
            'group_id' => $group_id,
            'group_name' => $group_name,
            'ticket_id' => $ticket_id,
            'ticket_name' => $ticket->getField('name'),
            'assigned_by' => Session::getLoginUserID(),
            'assigned_by_name' => self::getUserName(Session::getLoginUserID())
        ]);
        
        if ($result) {
            $notified_count++;
            error_log("👥 NOTIFIED technician: $technician_id about group assignment to ticket $ticket_id");
        } else {
            error_log("👥 FAILED to notify technician: $technician_id");
        }
    }
    
    error_log("👥 FINAL: Notified $notified_count technicians about group assignment");
    return $notified_count > 0;
}

/**
 * Obtiene técnicos (perfil 6) en un grupo específico
 */
static function getTechniciansInGroup($group_id) {
    global $DB;
    
    $technicians = [];
    $query = "SELECT DISTINCT u.id 
              FROM glpi_users u
              INNER JOIN glpi_groups_users gu ON u.id = gu.users_id
              INNER JOIN glpi_profiles_users pu ON u.id = pu.users_id
              WHERE gu.groups_id = $group_id 
              AND pu.profiles_id = 6  -- Perfil Technician
              AND u.is_active = 1
              AND u.id > 0";
    
    error_log("👥 Technicians in group query: $query");
    
    $result = $DB->query($query);
    if ($result) {
        while ($data = $DB->fetchAssoc($result)) {
            $technicians[] = $data['id'];
        }
    }
    
    error_log("👥 Found " . count($technicians) . " technicians in group $group_id");
    return $technicians;
}

/**
 * Obtiene cualquier usuario activo en un grupo (fallback)
 */
static function getAnyUsersInGroup($group_id) {
    global $DB;
    
    $users = [];
    $query = "SELECT DISTINCT u.id 
              FROM glpi_users u
              INNER JOIN glpi_groups_users gu ON u.id = gu.users_id
              WHERE gu.groups_id = $group_id 
              AND u.is_active = 1
              AND u.id > 0";
    
    $result = $DB->query($query);
    if ($result) {
        while ($data = $DB->fetchAssoc($result)) {
            $users[] = $data['id'];
        }
    }
    
    return $users;
}

/**
 * Obtiene nombre de usuario
 */
static function getUserName($users_id) {
    $user = new User();
    if ($user->getFromDB($users_id)) {
        return $user->getField('name');
    }
    return 'Usuario';
}

}