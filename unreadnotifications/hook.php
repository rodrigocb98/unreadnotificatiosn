<?php

/**
 * Hook para actualizar notificaciones cuando se cierra un ticket
 */
/**
 * Hook para actualizar notificaciones cuando se cierra un ticket
 */
/**
 * Hook para actualizar notificaciones cuando se cierra un ticket
 */
function plugin_unreadnotifications_post_item_update($item) {
    
    // Si se actualiza un ticket
    if ($item->getType() == 'Ticket') {
        $ticket_id = $item->getID();
        
        // Verificar si el ticket fue cerrado (estado 6)
        if (isset($item->input['status']) && $item->input['status'] == 6) {
            error_log("Unread Notifications: Ticket $ticket_id cerrado, actualizando notificaciones");
            plugin_unreadnotifications_clear_cache();
        }
    }
    
    // Si se actualiza un cambio
    if ($item->getType() == 'Change') {
        $change_id = $item->getID();
        
        // Verificar si el cambio fue cerrado (estado 6) - CORREGIDO
        if (isset($item->input['status']) && $item->input['status'] == 6) {
            error_log("Unread Notifications: Change $change_id cerrado, actualizando notificaciones");
            plugin_unreadnotifications_clear_cache();
        }
    }
    
    // Si se actualiza un problema
    if ($item->getType() == 'Problem') {
        $problem_id = $item->getID();
        
        // Verificar si el problema fue cerrado (estado 7)
        if (isset($item->input['status']) && $item->input['status'] == 7) {
            error_log("Unread Notifications: Problem $problem_id cerrado, actualizando notificaciones");
            plugin_unreadnotifications_clear_cache();
        }
    }
}

/**
 * Limpiar cache del plugin
 */
function plugin_unreadnotifications_clear_cache() {
    // Podemos implementar un sistema de cache aquí si es necesario
    // Por ahora simplemente forzamos que la próxima consulta sea fresca
}

/**
 * Hook para después de añadir un item
 */
function plugin_unreadnotifications_post_item_add($item) {
    // Si se crea un nuevo ticket, cambio o problema
    if (in_array($item->getType(), ['Ticket', 'Change', 'Problem'])) {
        plugin_unreadnotifications_clear_cache();
    }
}