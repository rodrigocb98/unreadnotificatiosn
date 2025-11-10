<?php

class PluginUnreadnotificationsNotification extends CommonDBTM {
   
   static $rightname = 'plugin_unreadnotifications';
   /**
 * Obtiene el conteo de notificaciones no leídas
 */
/**
 * Obtiene el conteo de notificaciones no leídas (incluyendo grupos)
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
      // Tickets asignados directamente al usuario
      $ticket_assigned = self::getUnreadTicketsAssigned($users_id);
      
      // Tickets en los que es observador
      $ticket_observer = self::getUnreadTicketsObserver($users_id);
      
      // Tickets en los que es solicitante
      $ticket_requester = self::getUnreadTicketsRequester($users_id);
      
      // Tickets asignados a grupos del usuario
      $ticket_group = self::getUnreadTicketsGroup($users_id);
      
      // Cambios asignados directamente al usuario
      $changes = self::getUnreadChanges($users_id);
      
      // Cambios asignados a grupos del usuario
      $changes_group = self::getUnreadChangesGroup($users_id);
      
      // Problemas asignados directamente al usuario
      $problems = self::getUnreadProblems($users_id);
      
      // Problemas asignados a grupos del usuario
      $problems_group = self::getUnreadProblemsGroup($users_id);
      
      $count = $ticket_assigned + $ticket_observer + $ticket_requester + $ticket_group +
               $changes + $changes_group + $problems + $problems_group;
      
   } catch (Exception $e) {
      Toolbox::logError("Unread Notifications Error: " . $e->getMessage());
   }
   
   return $count;
}
   
   /**
    * Obtiene tickets asignados sin leer
    */
/**
 * Obtiene tickets asignados sin leer (solo estados activos)
 */
static function getUnreadTicketsAssigned($users_id) {
   global $DB;
   
   // Estados que se consideran "activos" (no cerrados o resueltos)
   $active_statuses = [
      1, // Nuevo
      2, // En curso (asignado)
      3, // Planificado
      4, // Pendiente
      5  // Resuelto (pero aún no cerrado)
   ];
   
   $iterator = $DB->request([
      'SELECT' => ['COUNT' => '* AS count'],
      'FROM'   => 'glpi_tickets',
      'WHERE'  => [
         'is_deleted' => 0,
         'status'     => $active_statuses, // Solo estados activos
         'OR'         => [
            ['users_id_recipient' => $users_id],
            ['id' => new QuerySubQuery([
               'SELECT' => 'tickets_id',
               'FROM'   => 'glpi_tickets_users',
               'WHERE'  => [
                  'users_id' => $users_id,
                  'type'     => CommonITILActor::ASSIGN
               ]
            ])]
         ]
      ]
   ]);
   
   if ($data = $iterator->current()) {
      return (int)$data['count'];
   }
   
   return 0;
}

/**
 * Obtiene tickets como observador sin leer (solo estados activos)
 */
static function getUnreadTicketsObserver($users_id) {
   global $DB;
   
   $active_statuses = [1, 2, 3, 4, 5]; // Estados activos
   
   $iterator = $DB->request([
      'SELECT' => ['COUNT' => '* AS count'],
      'FROM'   => 'glpi_tickets',
      'WHERE'  => [
         'is_deleted' => 0,
         'status'     => $active_statuses,
         'id'         => new QuerySubQuery([
            'SELECT' => 'tickets_id',
            'FROM'   => 'glpi_tickets_users',
            'WHERE'  => [
               'users_id' => $users_id,
               'type'     => CommonITILActor::OBSERVER
            ]
         ])
      ]
   ]);
   
   if ($data = $iterator->current()) {
      return (int)$data['count'];
   }
   
   return 0;
}

/**
 * Obtiene tickets como solicitante sin leer (solo estados activos)
 */
static function getUnreadTicketsRequester($users_id) {
   global $DB;
   
   $active_statuses = [1, 2, 3, 4, 5]; // Estados activos
   
   $iterator = $DB->request([
      'SELECT' => ['COUNT' => '* AS count'],
      'FROM'   => 'glpi_tickets',
      'WHERE'  => [
         'is_deleted' => 0,
         'status'     => $active_statuses,
         'id'         => new QuerySubQuery([
            'SELECT' => 'tickets_id',
            'FROM'   => 'glpi_tickets_users',
            'WHERE'  => [
               'users_id' => $users_id,
               'type'     => CommonITILActor::REQUESTER
            ]
         ])
      ]
   ]);
   
   if ($data = $iterator->current()) {
      return (int)$data['count'];
   }
   
   return 0;
}

/**
 * Obtiene cambios sin leer (solo estados activos)
 */
/**
 * Obtiene cambios sin leer (solo estados activos)
 */
/**
 * Obtiene cambios sin leer (solo estados activos)
 */
static function getUnreadChanges($users_id) {
   global $DB;
   
   if (!class_exists('Change')) {
      return 0;
   }
   
   // Estados activos para cambios (GLPI Change statuses)
   // 1: Nuevo, 2: En curso, 3: Validación, 4: Aprobado, 5: En pruebas, 7: Aceptado
   // 6: Cerrado (NO incluir)
   $active_statuses = [1, 2, 3, 4, 5, 7]; // Excluir estado 6 (Cerrado)
   
   $iterator = $DB->request([
      'SELECT' => ['COUNT' => '* AS count'],
      'FROM'   => 'glpi_changes',
      'WHERE'  => [
         'is_deleted' => 0,
         'status'     => $active_statuses, // Solo estados activos
         'OR'         => [
            ['users_id_recipient' => $users_id],
            ['id' => new QuerySubQuery([
               'SELECT' => 'changes_id',
               'FROM'   => 'glpi_changes_users',
               'WHERE'  => [
                  'users_id' => $users_id,
                  'type'     => [CommonITILActor::ASSIGN, CommonITILActor::OBSERVER]
               ]
            ])]
         ]
      ]
   ]);
   
   if ($data = $iterator->current()) {
      return (int)$data['count'];
   }
   
   return 0;
}
/**
 * Obtiene tickets asignados a grupos del usuario
 */
static function getUnreadTicketsGroup($users_id) {
   global $DB;
   
   $active_statuses = [1, 2, 3, 4, 5];
   
   $iterator = $DB->request([
      'SELECT' => ['COUNT' => '* AS count'],
      'FROM'   => 'glpi_tickets',
      'WHERE'  => [
         'is_deleted' => 0,
         'status'     => $active_statuses,
         'id'         => new QuerySubQuery([
            'SELECT' => 'tickets_id',
            'FROM'   => 'glpi_groups_tickets',
            'WHERE'  => [
               'groups_id' => new QuerySubQuery([
                  'SELECT' => 'groups_id',
                  'FROM'   => 'glpi_groups_users',
                  'WHERE'  => [
                     'users_id' => $users_id
                  ]
               ]),
               'type' => CommonITILActor::ASSIGN
            ]
         ])
      ]
   ]);
   
   if ($data = $iterator->current()) {
      return (int)$data['count'];
   }
   
   return 0;
}

/**
 * Obtiene cambios asignados a grupos del usuario
 */
static function getUnreadChangesGroup($users_id) {
   global $DB;
   
   if (!class_exists('Change')) {
      return 0;
   }
   
   $active_statuses = [1, 2, 3, 4, 5, 7];
   
   $iterator = $DB->request([
      'SELECT' => ['COUNT' => '* AS count'],
      'FROM'   => 'glpi_changes',
      'WHERE'  => [
         'is_deleted' => 0,
         'status'     => $active_statuses,
         'id'         => new QuerySubQuery([
            'SELECT' => 'changes_id',
            'FROM'   => 'glpi_groups_changes',
            'WHERE'  => [
               'groups_id' => new QuerySubQuery([
                  'SELECT' => 'groups_id',
                  'FROM'   => 'glpi_groups_users',
                  'WHERE'  => [
                     'users_id' => $users_id
                  ]
               ]),
               'type' => CommonITILActor::ASSIGN
            ]
         ])
      ]
   ]);
   
   if ($data = $iterator->current()) {
      return (int)$data['count'];
   }
   
   return 0;
}

/**
 * Obtiene problemas asignados a grupos del usuario
 */
static function getUnreadProblemsGroup($users_id) {
   global $DB;
   
   if (!class_exists('Problem')) {
      return 0;
   }
   
   $active_statuses = [1, 2, 3, 4, 5, 6];
   
   $iterator = $DB->request([
      'SELECT' => ['COUNT' => '* AS count'],
      'FROM'   => 'glpi_problems',
      'WHERE'  => [
         'is_deleted' => 0,
         'status'     => $active_statuses,
         'id'         => new QuerySubQuery([
            'SELECT' => 'problems_id',
            'FROM'   => 'glpi_groups_problems',
            'WHERE'  => [
               'groups_id' => new QuerySubQuery([
                  'SELECT' => 'groups_id',
                  'FROM'   => 'glpi_groups_users',
                  'WHERE'  => [
                     'users_id' => $users_id
                  ]
               ]),
               'type' => CommonITILActor::ASSIGN
            ]
         ])
      ]
   ]);
   
   if ($data = $iterator->current()) {
      return (int)$data['count'];
   }
   
   return 0;
}
/**
 * Función de debug para ver estados de cambios
 */
static function debugChangesStatus($users_id = null) {
   global $DB;
   
   if ($users_id === null) {
      $users_id = Session::getLoginUserID();
   }
   
   $iterator = $DB->request([
      'SELECT' => ['id', 'name', 'status'],
      'FROM'   => 'glpi_changes',
      'WHERE'  => [
         'is_deleted' => 0,
         'OR'         => [
            ['users_id_recipient' => $users_id],
            ['id' => new QuerySubQuery([
               'SELECT' => 'changes_id',
               'FROM'   => 'glpi_changes_users',
               'WHERE'  => [
                  'users_id' => $users_id,
                  'type'     => [CommonITILActor::ASSIGN, CommonITILActor::OBSERVER]
               ]
            ])]
         ]
      ]
   ]);
   
   $changes = [];
   while ($data = $iterator->next()) {
      $changes[] = $data;
   }
   
   return $changes;
}

/**
 * Obtiene problemas sin leer (solo estados activos)
 */
/**
 * Obtiene problemas sin leer (solo estados activos)
 */
static function getUnreadProblems($users_id) {
   global $DB;
   
   if (!class_exists('Problem')) {
      return 0;
   }
   
   // Estados activos para problemas (GLPI Problem statuses)
   // 1: Nuevo, 2: En curso, 3: Validación, 4: Aprobado, 5: En pruebas, 6: Evaluación
   // 7: Cerrado (NO incluir)
   $active_statuses = [1, 2, 3, 4, 5, 6]; // Excluir estado 7 (Cerrado)
   
   $iterator = $DB->request([
      'SELECT' => ['COUNT' => '* AS count'],
      'FROM'   => 'glpi_problems',
      'WHERE'  => [
         'is_deleted' => 0,
         'status'     => $active_statuses,
         'OR'         => [
            ['users_id_recipient' => $users_id],
            ['id' => new QuerySubQuery([
               'SELECT' => 'problems_id',
               'FROM'   => 'glpi_problems_users',
               'WHERE'  => [
                  'users_id' => $users_id,
                  'type'     => [CommonITILActor::ASSIGN, CommonITILActor::OBSERVER]
               ]
            ])]
         ]
      ]
   ]);
   
   if ($data = $iterator->current()) {
      return (int)$data['count'];
   }
   
   return 0;
}
   
   /**
    * Obtiene detalles de las notificaciones
    */
  /**
 * Obtiene detalles de las notificaciones (incluyendo grupos)
 */
static function getNotificationDetails($users_id = null) {
   if ($users_id === null) {
      $users_id = Session::getLoginUserID();
   }
   
   if (!$users_id) {
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
   
   $details = [
      'tickets_assigned' => self::getUnreadTicketsAssigned($users_id),
      'tickets_observer' => self::getUnreadTicketsObserver($users_id),
      'tickets_requester' => self::getUnreadTicketsRequester($users_id),
      'tickets_group' => self::getUnreadTicketsGroup($users_id),
      'changes' => self::getUnreadChanges($users_id),
      'changes_group' => self::getUnreadChangesGroup($users_id),
      'problems' => self::getUnreadProblems($users_id),
      'problems_group' => self::getUnreadProblemsGroup($users_id)
   ];
   
   return $details;
}

   /**
 * Obtiene items detallados para la vista previa
 */
/**
 * Obtiene items detallados para la vista previa
 */
/**
 * Obtiene items detallados para la vista previa (incluyendo grupos)
 */
static function getNotificationItems($users_id = null) {
   global $DB;
   
   if ($users_id === null) {
      $users_id = Session::getLoginUserID();
   }
   
   if (!$users_id) {
      return [
         'tickets_assigned' => [],
         'tickets_requester' => [],
         'tickets_group' => [],
         'changes' => [],
         'changes_group' => []
      ];
   }
   
   $items = [
      'tickets_assigned' => self::getAssignedTicketsDetailed($users_id),
      'tickets_requester' => self::getRequesterTicketsDetailed($users_id),
      'tickets_group' => self::getGroupTicketsDetailed($users_id),
      'changes' => self::getChangesDetailed($users_id),
      'changes_group' => self::getGroupChangesDetailed($users_id)
   ];
   
   return $items;
}
/**
 * Obtiene tickets asignados con detalles - VERSIÓN CORREGIDA
 */
static function getAssignedTicketsDetailed($users_id) {
   global $DB;
   
   $active_statuses = [1, 2, 3, 4, 5];
   
   // Consulta corregida para tickets asignados
   $query = "
      SELECT DISTINCT t.id, t.name, t.status, t.date_creation, t.content
      FROM glpi_tickets t
      LEFT JOIN glpi_tickets_users tu ON t.id = tu.tickets_id
      WHERE t.is_deleted = 0 
        AND t.status IN (" . implode(',', $active_statuses) . ")
        AND (t.users_id_recipient = $users_id 
             OR (tu.users_id = $users_id AND tu.type = " . CommonITILActor::ASSIGN . "))
      ORDER BY t.date_creation DESC
      LIMIT 5
   ";
   
   error_log("Unread Notifications: Assigned tickets query = " . $query);
   
   $result = $DB->query($query);
   $tickets = [];
   
   if ($result) {
      while ($data = $DB->fetchAssoc($result)) {
         $tickets[] = [
            'id' => $data['id'],
            'name' => $data['name'],
            'status' => self::getTicketStatusName($data['status']),
            'status_color' => self::getTicketStatusColor($data['status']),
            'date_creation' => date('d/m/Y H:i', strtotime($data['date_creation'])),
            'content' => substr($data['content'], 0, 100) . '...'
         ];
      }
   }
   
   error_log("Unread Notifications: Found " . count($tickets) . " assigned tickets");
   return $tickets;
}

/**
 * Obtiene tickets como solicitante con detalles - VERSIÓN CORREGIDA
 */
static function getRequesterTicketsDetailed($users_id) {
   global $DB;
   
   $active_statuses = [1, 2, 3, 4, 5];
   
   // Consulta corregida para tickets solicitante
   $query = "
      SELECT DISTINCT t.id, t.name, t.status, t.date_creation, t.content
      FROM glpi_tickets t
      INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id
      WHERE t.is_deleted = 0 
        AND t.status IN (" . implode(',', $active_statuses) . ")
        AND tu.users_id = $users_id 
        AND tu.type = " . CommonITILActor::REQUESTER . "
      ORDER BY t.date_creation DESC
      LIMIT 5
   ";
   
   error_log("Unread Notifications: Requester tickets query = " . $query);
   
   $result = $DB->query($query);
   $tickets = [];
   
   if ($result) {
      while ($data = $DB->fetchAssoc($result)) {
         $tickets[] = [
            'id' => $data['id'],
            'name' => $data['name'],
            'status' => self::getTicketStatusName($data['status']),
            'status_color' => self::getTicketStatusColor($data['status']),
            'date_creation' => date('d/m/Y H:i', strtotime($data['date_creation'])),
            'content' => substr($data['content'], 0, 100) . '...'
         ];
      }
   }
   
   error_log("Unread Notifications: Found " . count($tickets) . " requester tickets");
   return $tickets;
}

/**
 * Obtiene cambios con detalles - VERSIÓN CORREGIDA
 */
static function getChangesDetailed($users_id) {
   global $DB;
   
   if (!class_exists('Change')) {
      error_log("Unread Notifications: Change class not available");
      return [];
   }
   
   $active_statuses = [1, 2, 3, 4, 5, 7];
   
   // Consulta corregida para cambios
   $query = "
      SELECT DISTINCT c.id, c.name, c.status, c.date_creation, c.content
      FROM glpi_changes c
      LEFT JOIN glpi_changes_users cu ON c.id = cu.changes_id
      WHERE c.is_deleted = 0 
        AND c.status IN (" . implode(',', $active_statuses) . ")
        AND (c.users_id_recipient = $users_id 
             OR (cu.users_id = $users_id AND cu.type IN (" . 
                 CommonITILActor::ASSIGN . ", " . CommonITILActor::OBSERVER . ")))
      ORDER BY c.date_creation DESC
      LIMIT 5
   ";
   
   error_log("Unread Notifications: Changes query = " . $query);
   
   $result = $DB->query($query);
   $changes = [];
   
   if ($result) {
      while ($data = $DB->fetchAssoc($result)) {
         $changes[] = [
            'id' => $data['id'],
            'name' => $data['name'],
            'status' => self::getChangeStatusName($data['status']),
            'status_color' => self::getChangeStatusColor($data['status']),
            'date_creation' => date('d/m/Y H:i', strtotime($data['date_creation'])),
            'content' => substr($data['content'], 0, 100) . '...'
         ];
      }
   }
   
   error_log("Unread Notifications: Found " . count($changes) . " changes");
   return $changes;
}

/**
 * Obtiene tickets de grupos con detalles
 */
static function getGroupTicketsDetailed($users_id) {
   global $DB;
   
   $active_statuses = [1, 2, 3, 4, 5];
   
   $query = "SELECT DISTINCT t.id, t.name, t.status, t.date_creation, t.content, g.name as group_name
             FROM glpi_tickets t
             INNER JOIN glpi_groups_tickets gt ON t.id = gt.tickets_id
             INNER JOIN glpi_groups g ON gt.groups_id = g.id
             INNER JOIN glpi_groups_users gu ON g.id = gu.groups_id
             WHERE t.is_deleted = 0 
               AND t.status IN (" . implode(',', $active_statuses) . ")
               AND gt.type = " . CommonITILActor::ASSIGN . "
               AND gu.users_id = $users_id
             ORDER BY t.date_creation DESC
             LIMIT 5";
   
   $result = $DB->query($query);
   $tickets = [];
   
   if ($result && $DB->numrows($result) > 0) {
      while ($data = $DB->fetchAssoc($result)) {
         $tickets[] = [
            'id' => $data['id'],
            'name' => $data['name'],
            'status' => self::getTicketStatusName($data['status']),
            'status_color' => self::getTicketStatusColor($data['status']),
            'date_creation' => date('d/m/Y H:i', strtotime($data['date_creation'])),
            'content' => substr($data['content'] ?? '', 0, 100) . '...',
            'group_name' => $data['group_name']
         ];
      }
   }
   
   return $tickets;
}

/**
 * Obtiene cambios de grupos con detalles
 */
static function getGroupChangesDetailed($users_id) {
   global $DB;
   
   if (!class_exists('Change')) {
      return [];
   }
   
   $active_statuses = [1, 2, 3, 4, 5, 7];
   
   $query = "SELECT DISTINCT c.id, c.name, c.status, c.date_creation, c.content, g.name as group_name
             FROM glpi_changes c
             INNER JOIN glpi_groups_changes gc ON c.id = gc.changes_id
             INNER JOIN glpi_groups g ON gc.groups_id = g.id
             INNER JOIN glpi_groups_users gu ON g.id = gu.groups_id
             WHERE c.is_deleted = 0 
               AND c.status IN (" . implode(',', $active_statuses) . ")
               AND gc.type = " . CommonITILActor::ASSIGN . "
               AND gu.users_id = $users_id
             ORDER BY c.date_creation DESC
             LIMIT 5";
   
   $result = $DB->query($query);
   $changes = [];
   
   if ($result && $DB->numrows($result) > 0) {
      while ($data = $DB->fetchAssoc($result)) {
         $changes[] = [
            'id' => $data['id'],
            'name' => $data['name'],
            'status' => self::getChangeStatusName($data['status']),
            'status_color' => self::getChangeStatusColor($data['status']),
            'date_creation' => date('d/m/Y H:i', strtotime($data['date_creation'])),
            'content' => substr($data['content'] ?? '', 0, 100) . '...',
            'group_name' => $data['group_name']
         ];
      }
   }
   
   return $changes;
}

/**
 * Helper functions para nombres y colores de estados
 */
static function getTicketStatusName($status) {
   $statuses = [
      1 => 'Nuevo',
      2 => 'En curso',
      3 => 'Planificado', 
      4 => 'Pendiente',
      5 => 'Resuelto',
      6 => 'Cerrado'
   ];
   return $statuses[$status] ?? 'Desconocido';
}

static function getTicketStatusColor($status) {
   $colors = [
      1 => 'secondary',
      2 => 'primary', 
      3 => 'info',
      4 => 'warning',
      5 => 'success',
      6 => 'dark'
   ];
   return $colors[$status] ?? 'secondary';
}

static function getChangeStatusName($status) {
   $statuses = [
      1 => 'Nuevo',
      2 => 'En curso',
      3 => 'Validación',
      4 => 'Aprobado',
      5 => 'En pruebas', 
      6 => 'Cerrado',
      7 => 'Aceptado'
   ];
   return $statuses[$status] ?? 'Desconocido';
}

static function getChangeStatusColor($status) {
   $colors = [
      1 => 'secondary',
      2 => 'primary',
      3 => 'info', 
      4 => 'success',
      5 => 'warning',
      6 => 'dark',
      7 => 'success'
   ];
   return $colors[$status] ?? 'secondary';
}
}