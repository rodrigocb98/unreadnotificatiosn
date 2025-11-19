<?php

/**
 * Hooks para el plugin Unread Notifications
 */

function plugin_unreadnotifications_item_add($item) {
    error_log("🎯 HOOK FIRED: item_add - " . $item->getType() . " ID: " . $item->getID());
    
    if ($item->getType() == 'Ticket') {
        error_log("🎯 PROCESSING NEW TICKET: " . $item->getID());
        
        register_shutdown_function(function() use ($item) {
            $plugin_dir = Plugin::getPhpDir('unreadnotifications');
            $event_file = $plugin_dir . '/inc/notificationevent.class.php';
            
            if (file_exists($event_file)) {
                include_once $event_file;
                if (class_exists('PluginUnreadnotificationsNotificationEvent')) {
                    try {
                        PluginUnreadnotificationsNotificationEvent::processNewTicket($item);
                        error_log("🎯 SUCCESS: Ticket processed by NotificationEvent");
                    } catch (Exception $e) {
                        error_log("❌ ERROR processing ticket: " . $e->getMessage());
                    }
                } else {
                    error_log("❌ NotificationEvent class not found");
                }
            } else {
                error_log("❌ Event file not found: $event_file");
            }
        });
    }
}

// En el hook de followup_add, verifica el valor de is_private
function plugin_unreadnotifications_followup_add($followup) {
    error_log("💬 HOOK FIRED: followup_add - ID: " . $followup->getID());
    error_log("💬 Followup is_private value: " . $followup->getField('is_private'));
    error_log("💬 Followup users_id value: " . $followup->getField('users_id'));
    
    register_shutdown_function(function() use ($followup) {
        $plugin_dir = Plugin::getPhpDir('unreadnotifications');
        $event_file = $plugin_dir . '/inc/notificationevent.class.php';
        
        if (file_exists($event_file)) {
            include_once $event_file;
            if (class_exists('PluginUnreadnotificationsNotificationEvent')) {
                try {
                    PluginUnreadnotificationsNotificationEvent::processNewComment(
                        $followup->getField('items_id'),
                        $followup->getField('content'),
                        $followup->getField('users_id'),
                        $followup->getField('is_private'),
                        'Ticket'
                    );
                    error_log("💬 SUCCESS: Followup processed by NotificationEvent");
                } catch (Exception $e) {
                    error_log("❌ ERROR processing followup: " . $e->getMessage());
                }
            }
        }
    });
}

/**
 * Hook cuando se añade un Group_Ticket (ASIGNACIÓN DESDE ACTORS)
 */
function plugin_unreadnotifications_group_ticket_add($group_ticket) {
    error_log("👥 HOOK FIRED: group_ticket_add - Ticket: " . $group_ticket->getField('tickets_id') . 
              ", Group: " . $group_ticket->getField('groups_id') . 
              ", Type: " . $group_ticket->getField('type'));
    
    // Solo procesar asignaciones (type = 2)
    if ($group_ticket->getField('type') == CommonITILActor::ASSIGN) {
        $ticket_id = $group_ticket->getField('tickets_id');
        $group_id = $group_ticket->getField('groups_id');
        
        error_log("👥 GROUP ASSIGNMENT VIA ACTORS: Ticket $ticket_id to group $group_id");
        
        register_shutdown_function(function() use ($ticket_id, $group_id) {
            plugin_unreadnotifications_process_group_assignment_from_actors($ticket_id, $group_id);
        });
    }
}

/**
 * Hook cuando se actualiza un ticket (para capturar asignaciones desde otros lugares)
 */
function plugin_unreadnotifications_item_update($item) {
    if ($item->getType() == 'Ticket') {
        error_log("🔄 HOOK FIRED: item_update - Ticket ID: " . $item->getID());
        
        // Verificar si se asignó un grupo via _groups_id_assign
        if (isset($item->input['_groups_id_assign']) && $item->input['_groups_id_assign'] > 0) {
            $group_id = $item->input['_groups_id_assign'];
            error_log("👥 GROUP ASSIGNMENT DETECTED: Ticket " . $item->getID() . " to group $group_id");
            
            register_shutdown_function(function() use ($item, $group_id) {
                plugin_unreadnotifications_process_group_assignment($item, $group_id);
            });
        }
    }
}

/**
 * Hook cuando se añade un followup (comentario)
 */


/**
 * Procesar nuevo ticket
 */
function plugin_unreadnotifications_process_new_ticket($ticket) {
    $ticket_id = $ticket->getID();
    error_log("🎯 PROCESSING NEW TICKET: $ticket_id - '{$ticket->getField('name')}'");
    
    $plugin_dir = Plugin::getPhpDir('unreadnotifications');
    $event_file = $plugin_dir . '/inc/notificationevent.class.php';
    
    if (!file_exists($event_file)) {
        error_log("❌ EVENT FILE NOT FOUND: $event_file");
        return;
    }
    
    include_once $event_file;
    
    if (!class_exists('PluginUnreadnotificationsNotificationEvent')) {
        error_log("❌ EVENT CLASS NOT FOUND");
        return;
    }
    
    try {
        $result = PluginUnreadnotificationsNotificationEvent::processNewTicket($ticket);
        error_log("🎯 TICKET PROCESSING RESULT: " . ($result ? 'SUCCESS' : 'FAILED'));
    } catch (Exception $e) {
        error_log("❌ TICKET PROCESSING ERROR: " . $e->getMessage());
    }
}

/**
 * Procesar asignación de grupo desde Actors
 */
function plugin_unreadnotifications_process_group_assignment_from_actors($ticket_id, $group_id) {
    error_log("👥 PROCESSING GROUP ASSIGNMENT FROM ACTORS: Ticket $ticket_id to group $group_id");
    
    $plugin_dir = Plugin::getPhpDir('unreadnotifications');
    $event_file = $plugin_dir . '/inc/notificationevent.class.php';
    
    if (!file_exists($event_file)) {
        error_log("❌ EVENT FILE NOT FOUND: $event_file");
        return;
    }
    
    include_once $event_file;
    
    if (!class_exists('PluginUnreadnotificationsNotificationEvent')) {
        error_log("❌ EVENT CLASS NOT FOUND");
        return;
    }
    
    try {
        $ticket = new Ticket();
        if ($ticket->getFromDB($ticket_id)) {
            $result = PluginUnreadnotificationsNotificationEvent::processGroupAssignment($ticket, $group_id);
            error_log("👥 GROUP ASSIGNMENT FROM ACTORS RESULT: " . ($result ? 'SUCCESS' : 'FAILED'));
        } else {
            error_log("❌ Ticket not found: $ticket_id");
        }
    } catch (Exception $e) {
        error_log("❌ GROUP ASSIGNMENT FROM ACTORS ERROR: " . $e->getMessage());
    }
}

/**
 * Procesar asignación de grupo desde otros lugares
 */
function plugin_unreadnotifications_process_group_assignment($ticket, $group_id) {
    $ticket_id = $ticket->getID();
    error_log("👥 PROCESSING GROUP ASSIGNMENT: Ticket $ticket_id to group $group_id");
    
    $plugin_dir = Plugin::getPhpDir('unreadnotifications');
    $event_file = $plugin_dir . '/inc/notificationevent.class.php';
    
    if (!file_exists($event_file)) {
        error_log("❌ EVENT FILE NOT FOUND: $event_file");
        return;
    }
    
    include_once $event_file;
    
    if (!class_exists('PluginUnreadnotificationsNotificationEvent')) {
        error_log("❌ EVENT CLASS NOT FOUND");
        return;
    }
    
    try {
        $result = PluginUnreadnotificationsNotificationEvent::processGroupAssignment($ticket, $group_id);
        error_log("👥 GROUP ASSIGNMENT PROCESSING RESULT: " . ($result ? 'SUCCESS' : 'FAILED'));
    } catch (Exception $e) {
        error_log("❌ GROUP ASSIGNMENT PROCESSING ERROR: " . $e->getMessage());
    }
}

/**
 * Procesar nuevo followup (comentario)
 */
function plugin_unreadnotifications_process_new_followup($followup) {
    $followup_id = $followup->getID();
    $item_id = $followup->getField('items_id');
    $content = $followup->getField('content');
    $user_id = $followup->getField('users_id');
    
    error_log("💬 PROCESSING NEW FOLLOWUP: $followup_id for item $item_id");
    
    $plugin_dir = Plugin::getPhpDir('unreadnotifications');
    $event_file = $plugin_dir . '/inc/notificationevent.class.php';
    
    if (!file_exists($event_file)) {
        error_log("❌ EVENT FILE NOT FOUND: $event_file");
        return;
    }
    
    include_once $event_file;
    
    if (!class_exists('PluginUnreadnotificationsNotificationEvent')) {
        error_log("❌ EVENT CLASS NOT FOUND");
        return;
    }
    
    try {
        PluginUnreadnotificationsNotificationEvent::processNewComment(
            $item_id,
            $content,
            $user_id,
            $followup->getField('is_private'),
            'Ticket'
        );
    } catch (Exception $e) {
        error_log("❌ ERROR processing followup: " . $e->getMessage());
    }
}

/**
 * Procesar cambio de estado (opcional)
 */
function plugin_unreadnotifications_process_status_change($ticket) {
    $ticket_id = $ticket->getID();
    $old_status = $ticket->oldvalues['status'];
    $new_status = $ticket->input['status'];
    
    error_log("🔄 PROCESSING STATUS CHANGE: Ticket $ticket_id from $old_status to $new_status");
    
    // Aquí puedes añadir lógica para notificar cambios de estado si lo deseas
}