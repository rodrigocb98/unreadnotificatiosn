/**
 * Unread Notifications Bell Plugin for GLPI - VERSIÓN COMPLETA
 * Incluye sonidos, vista previa y notificaciones personalizadas por perfil
 */

/**
 * SISTEMA DINÁMICO DE COLORES GLPI
 * Detecta automáticamente los colores de la paleta de GLPI
 */
class GLPIColorManager {
    constructor() {
        this.colors = {};
        this.isInitialized = false;
    }

    init() {
        if (this.isInitialized) return;
        
        console.log('🎨 Inicializando sistema de colores GLPI...');
        this.detectGLPIColors();
        this.applyDynamicColors();
        this.isInitialized = true;
    }

    detectGLPIColors() {
        // Crear elementos de prueba para detectar colores
        const testContainer = document.createElement('div');
        testContainer.style.cssText = 'position: absolute; left: -9999px; opacity: 0;';
        
        testContainer.innerHTML = `
            <div class="text-primary"></div>
            <div class="text-success"></div>
            <div class="text-danger"></div>
            <div class="text-warning"></div>
            <div class="text-info"></div>
            <div class="text-secondary"></div>
            <div class="bg-primary"></div>
            <div class="bg-success"></div>
            <div class="bg-danger"></div>
        `;
        
        document.body.appendChild(testContainer);

        // Detectar colores
        const elements = testContainer.children;
        for (let element of elements) {
            const styles = window.getComputedStyle(element);
            const className = element.className;
            
            if (className.includes('text-')) {
                this.colors[className] = styles.color;
            } else if (className.includes('bg-')) {
                this.colors[className] = styles.backgroundColor;
            }
        }

        document.body.removeChild(testContainer);

        console.log('🎨 Colores GLPI detectados:', this.colors);
        return this.colors;
    }

    applyDynamicColors() {
        if (Object.keys(this.colors).length === 0) {
            console.log('🎨 No se detectaron colores, usando valores por defecto');
            return;
        }

        // Aplicar colores dinámicamente via CSS variables
        const style = document.createElement('style');
        style.id = 'glpi-dynamic-colors';
        
        let css = ':root {\n';
        
        // Mapear clases Bootstrap a variables CSS
        const colorMap = {
            'text-primary': '--glpi-dynamic-primary',
            'bg-primary': '--glpi-dynamic-primary-bg',
            'text-success': '--glpi-dynamic-success', 
            'bg-success': '--glpi-dynamic-success-bg',
            'text-danger': '--glpi-dynamic-danger',
            'bg-danger': '--glpi-dynamic-danger-bg',
            'text-warning': '--glpi-dynamic-warning',
            'text-info': '--glpi-dynamic-info',
            'text-secondary': '--glpi-dynamic-secondary'
        };

        for (const [bootstrapClass, cssVar] of Object.entries(colorMap)) {
            if (this.colors[bootstrapClass]) {
                css += `  ${cssVar}: ${this.colors[bootstrapClass]};\n`;
            }
        }

        css += '}\n';
        style.textContent = css;
        
        // Remover estilo anterior si existe
        const existingStyle = document.getElementById('glpi-dynamic-colors');
        if (existingStyle) {
            existingStyle.remove();
        }
        
        document.head.appendChild(style);
        console.log('🎨 Colores dinámicos aplicados');
    }

    getColor(type) {
        const colorMap = {
            'primary': this.colors['text-primary'] || '#0d6efd',
            'success': this.colors['text-success'] || '#198754',
            'danger': this.colors['text-danger'] || '#dc3545',
            'warning': this.colors['text-warning'] || '#ffc107',
            'info': this.colors['text-info'] || '#0dcaf0',
            'secondary': this.colors['text-secondary'] || '#6c757d'
        };
        
        return colorMap[type] || '#6c757d';
    }

    getBackgroundColor(type) {
        const colorMap = {
            'primary': this.colors['bg-primary'] || '#0d6efd',
            'success': this.colors['bg-success'] || '#198754', 
            'danger': this.colors['bg-danger'] || '#dc3545'
        };
        
        return colorMap[type] || '#6c757d';
    }
}

// Inicializar el sistema de colores
window.glpiColorManager = new GLPIColorManager();

/**
 * SOLUCIÓN TEMPORAL: Forzar activación de sonidos
 */
console.log('🔔 TEMPORARY FIX: Ensuring sounds are enabled');

// Sobrescribir la configuración si existe
if (typeof UNREAD_NOTIFICATIONS_CONFIG !== 'undefined') {
    console.log('🔔 Before override - enable_sounds:', UNREAD_NOTIFICATIONS_CONFIG.enable_sounds);
    UNREAD_NOTIFICATIONS_CONFIG.enable_sounds = true;
    console.log('🔔 After override - enable_sounds:', UNREAD_NOTIFICATIONS_CONFIG.enable_sounds);
} else {
    console.log('🔔 Config not defined yet, creating it');
    window.UNREAD_NOTIFICATIONS_CONFIG = {
        refresh_interval: 30000,
        enable_sounds: true,  // FORZADO a true
        enable_websocket: false,
        websocket_url: 'ws://10.20.50.22:8080'
    };
}

// ========== CONFIGURACIÓN Y VARIABLES GLOBALES ==========
window.UNREAD_NOTIFICATIONS_CONFIG = window.UNREAD_NOTIFICATIONS_CONFIG || {
    refresh_interval: 30000,
    enable_sounds: false,
    enable_websocket: false
};

// ========== SISTEMA DE SONIDOS MEJORADO ==========
class NotificationSoundManager {
    constructor() {
        this.sounds = {};
        this.isInitialized = false;
    }

    init() {
        if (this.isInitialized) return;
        
        console.log('🔔 Initializing sound manager...');
        this.loadRealSounds();
        this.isInitialized = true;
    }

    loadRealSounds() {
        try {
            // Usar los archivos MP3 reales en lugar del fallback
            const pluginWebroot = window.GLPI_PLUGIN_WEBROOT_UNREAD || '/cns/plugins/unreadnotifications';
            
            this.sounds.notification = new Audio(pluginWebroot + '/sounds/notification.mp3');
            this.sounds.alert = new Audio(pluginWebroot + '/sounds/alert.mp3');
            
            // Precargar los sonidos
            this.sounds.notification.load();
            this.sounds.alert.load();
            
            console.log('🔔 Real sounds loaded successfully');
            
        } catch (error) {
            console.error('🔔 Error loading real sounds:', error);
            this.loadFallbackSounds();
        }
    }

    loadFallbackSounds() {
        // Fallback solo si los sonidos reales fallan
        this.sounds.notification = this.createFallbackSound();
        this.sounds.alert = this.createFallbackSound();
        console.log('🔔 Using fallback sounds');
    }

    createFallbackSound() {
        return {
            play: function() {
                console.log('🔔 Sound played (fallback)');
                return Promise.resolve();
            }
        };
    }

    playSound(soundType) {
        if (!this.isInitialized) {
            console.log('🔔 Sound manager not initialized');
            return;
        }
        
        if (!UNREAD_NOTIFICATIONS_CONFIG.enable_sounds) {
            console.log('🔔 Sounds disabled in config');
            return;
        }
        
        console.log('🔔 Attempting to play sound:', soundType);
        
        try {
            const sound = this.sounds[soundType];
            if (sound) {
                // Reiniciar el sonido si ya se estaba reproduciendo
                sound.currentTime = 0;
                
                // Reproducir el sonido
                const playPromise = sound.play();
                
                if (playPromise !== undefined) {
                    playPromise.catch(error => {
                        console.log('🔔 Error playing sound:', error);
                        // Mostrar mensaje útil para el usuario
                        if (error.name === 'NotAllowedError') {
                            console.log('🔔 Browser blocked autoplay. User interaction required.');
                        }
                    });
                }
            }
        } catch (error) {
            console.error('🔔 Sound play error:', error);
        }
    }
}

// ========== FUNCIONES PRINCIPALES ==========

/**
 * Crear la campana de notificaciones en la navbar
 */
window.createNotificationBell = function() {
    console.log('🔔 Unread Notifications: Creating bell...');
    
    // Verificar si ya existe
    if ($('#unread-notifications-bell').length > 0) {
        console.log('🔔 Bell already exists');
        return;
    }
    
    // Buscar navbar en GLPI 10
    var navbar = $('nav.navbar:first, header:first, .navbar:first').first();
    
    if (navbar.length === 0) {
        console.error('❌ Navbar not found');
        return;
    }
    
    // HTML de la campana - Versión mejorada con notificaciones personales
    var bellHTML = `
    <li id="unread-notifications-bell" class="nav-item dropdown notification-bell-container">
        <a class="nav-link position-relative bell-link" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-bell bell-icon"></i>
            <span id="notification-counter" class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle">0</span>
        </a>
        <div class="dropdown-menu dropdown-menu-end notification-dropdown">
            <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                <h6 class="mb-0"><strong>Notificaciones</strong></h6>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="refresh-notifications-btn" title="Actualizar">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info ms-1" id="mark-all-read-btn" title="Marcar todo como leído">
                        <i class="fas fa-check-double"></i>
                    </button>
                </div>
            </div>
            <div id="notification-content" class="p-2">
                <div class="text-center text-muted">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    Cargando...
                </div>
            </div>
        </div>
    </li>
    `;
    
    // Intentar diferentes ubicaciones en la navbar
    var navbarNav = navbar.find('.navbar-nav.ms-auto:first, .navbar-nav.ml-auto:first, .navbar-nav:last').first();
    
    if (navbarNav.length > 0) {
        navbarNav.prepend(bellHTML);
        console.log('✅ Bell added to existing navbar-nav');
    } else {
        // Crear nuevo navbar-nav
        navbar.find('.container-fluid, .container').first().append(
            '<ul class="navbar-nav ms-auto">' + bellHTML + '</ul>'
        );
        console.log('✅ Bell added with new navbar-nav');
    }
    
    // Configurar eventos
    $('#refresh-notifications-btn').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        loadNotifications();
        loadPersonalNotifications();
    });
    
    $('#mark-all-read-btn').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        markAllAsRead();
    });
    
    // Cargar notificaciones iniciales
    loadNotifications();
    loadPersonalNotifications();
};

/**
 * Cargar notificaciones desde el servidor
 */
window.loadNotifications = function() {
    var pluginWebroot = window.GLPI_PLUGIN_WEBROOT_UNREAD || '/cns/plugins/unreadnotifications';
    
    console.log('🔔 Loading notifications from:', pluginWebroot);
    
    $.ajax({
        url: pluginWebroot + '/ajax/getnotifications.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('🔔 Notifications response:', response);
            if (response.success) {
                updateNotificationBell(response.count, response.details);
            }
        },
        error: function(xhr, status, error) {
            console.error('🔔 Error loading notifications:', error);
        }
    });
};

/**
 * Carga notificaciones personalizadas
 */
window.loadPersonalNotifications = function() {
    var pluginWebroot = window.GLPI_PLUGIN_WEBROOT_UNREAD || '/cns/plugins/unreadnotifications';
    
    $.ajax({
        url: pluginWebroot + '/ajax/getpersonalnotifications.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updatePersonalNotifications(response.notifications, response.count);
            }
        },
        error: function(xhr, status, error) {
            console.error('🔔 Error loading personal notifications:', error);
        }
    });
};

/**
 * Actualizar la campana con nuevos datos
 */
window.updateNotificationBell = function(count, details, previousCount = null) {
    var counter = $('#notification-counter');
    var content = $('#notification-content');
    var bell = $('#unread-notifications-bell');
    
    console.log('🔔 Updating bell with count:', count, 'Previous count:', previousCount);
    
    // Reproducir sonido si hay nuevo elemento
    if (previousCount !== null && count > previousCount && window.soundManager) {
        console.log('🔔 New notification detected! Playing sound...');
        window.soundManager.playSound('notification');
        
        // Animación para nuevas notificaciones
        bell.addClass('has-notifications');
        setTimeout(() => {
            bell.removeClass('has-notifications');
        }, 2000);
    }
    
    // Actualizar contador y clases
    if (count > 0) {
        counter.text(count).show();
        $('#unread-notifications-bell .fa-bell').css('color', '#ffc107');
        bell.addClass('has-notifications');
        
        // Aplicar clases para centrado según el número de dígitos
        counter.removeClass('single-digit double-digit triple-digit');
        if (count < 10) {
            counter.addClass('single-digit');
        } else if (count < 100) {
            counter.addClass('double-digit');
        } else {
            counter.addClass('triple-digit');
        }
        
        // Forzar reflow para asegurar el centrado
        counter.hide().show();
        
    } else {
        counter.hide();
        $('#unread-notifications-bell .fa-bell').css('color', '');
        bell.removeClass('has-notifications');
    }
    
    // Obtener la ruta base
    var glpiRoot = window.GLPI_ROOT_UNREAD || '/cns';
    
    // Actualizar contenido con VISTA PREVIA
    var html = '';
    
    if (count === 0) {
        html = '<div class="text-center text-muted py-3">No hay notificaciones</div>';
    } else {
        html = `
            <div class="notification-header border-bottom pb-2 mb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <strong class="text-dark">Notificaciones del Sistema</strong>
                    <small class="text-muted">Total: ${count}</small>
                </div>
            </div>
            <div class="notification-list" style="max-height: 400px; overflow-y: auto;">
        `;
        
        // Tickets de Grupos
        if (details.tickets_group > 0) {
            html += `
                <div class="notification-category mb-3">
                    <div class="category-header d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0 text-info">
                            <i class="fas fa-users me-1"></i>
                            Tickets de Grupo
                        </h6>
                        <span class="badge bg-info">${details.tickets_group}</span>
                    </div>
                    <div class="category-items" data-type="tickets_group">
                        <div class="text-center py-2">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <small class="text-muted ms-2">Cargando tickets...</small>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Cambios de Grupos
        if (details.changes_group > 0) {
            html += `
                <div class="notification-category mb-3">
                    <div class="category-header d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0 text-warning">
                            <i class="fas fa-users me-1"></i>
                            Cambios de Grupo
                        </h6>
                        <span class="badge bg-warning text-dark">${details.changes_group}</span>
                    </div>
                    <div class="category-items" data-type="changes_group">
                        <div class="text-center py-2">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <small class="text-muted ms-2">Cargando cambios...</small>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Tickets Asignados con vista previa
        if (details.tickets_assigned > 0) {
            html += `
                <div class="notification-category mb-3">
                    <div class="category-header d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0 text-primary">
                            <i class="fas fa-ticket-alt me-1"></i>
                            Tickets Asignados
                        </h6>
                        <span class="badge bg-primary">${details.tickets_assigned}</span>
                    </div>
                    <div class="category-items" data-type="tickets_assigned">
                        <div class="text-center py-2">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <small class="text-muted ms-2">Cargando tickets...</small>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Tickets Solicitante con vista previa
        if (details.tickets_requester > 0) {
            html += `
                <div class="notification-category mb-3">
                    <div class="category-header d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0 text-success">
                            <i class="fas fa-user me-1"></i>
                            Mis Tickets
                        </h6>
                        <span class="badge bg-success">${details.tickets_requester}</span>
                    </div>
                    <div class="category-items" data-type="tickets_requester">
                        <div class="text-center py-2">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <small class="text-muted ms-2">Cargando tickets...</small>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Cambios con vista previa
        if (details.changes > 0) {
            html += `
                <div class="notification-category mb-3">
                    <div class="category-header d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0 text-warning">
                            <i class="fas fa-exchange-alt me-1"></i>
                            Cambios
                        </h6>
                        <span class="badge bg-warning text-dark">${details.changes}</span>
                    </div>
                    <div class="category-items" data-type="changes">
                        <div class="text-center py-2">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <small class="text-muted ms-2">Cargando cambios...</small>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Tickets Observador
        if (details.tickets_observer > 0) {
            html += `
                <div class="notification-category mb-3">
                    <div class="category-header d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0 text-info">
                            <i class="fas fa-eye me-1"></i>
                            Tickets Observador
                        </h6>
                        <span class="badge bg-info">${details.tickets_observer}</span>
                    </div>
                    <div class="category-items">
                        <a href="${glpiRoot}/front/ticket.php?field[0]=2&searchtype[0]=contains&contains[0]=observer&itemtype=Ticket" 
                           class="btn btn-sm btn-outline-info w-100">
                            Ver todos los tickets
                        </a>
                    </div>
                </div>
            `;
        }
        
        // Problemas
        if (details.problems > 0) {
            html += `
                <div class="notification-category mb-3">
                    <div class="category-header d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0 text-danger">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Problemas
                        </h6>
                        <span class="badge bg-danger">${details.problems}</span>
                    </div>
                    <div class="category-items">
                        <a href="${glpiRoot}/front/problem.php" 
                           class="btn btn-sm btn-outline-danger w-100">
                            Ver todos los problemas
                        </a>
                    </div>
                </div>
            `;
        }
        
        html += `</div>`; // Cierre de notification-list
        
        // Footer con acciones
        html += `
            <div class="notification-footer border-top pt-2 mt-2">
                <div class="row g-1">
                    <div class="col-6">
                        <a href="${glpiRoot}/front/ticket.php" class="btn btn-sm btn-outline-primary w-100">
                            <i class="fas fa-list"></i> Todos
                        </a>
                    </div>
                    <div class="col-6">
                        <button id="refresh-notifications-detailed" class="btn btn-sm btn-outline-secondary w-100">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    content.html(html);
    
    // Cargar items detallados después de mostrar la estructura
    if (count > 0) {
        setTimeout(() => {
            loadDetailedItems();
        }, 100);
    }
    
    // Configurar evento de actualización detallada
    $('#refresh-notifications-detailed').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        loadDetailedItems();
        loadNotifications();
        loadPersonalNotifications();
    });
};

/**
 * Actualiza el panel de notificaciones personales
 */
window.updatePersonalNotifications = function(notifications, count) {
    if (count === 0) {
        // Remover sección si no hay notificaciones
        $('#personal-notifications-section').remove();
        return;
    }
    
    var personalSection = $('#personal-notifications-section');
    var personalList = $('#personal-notifications-list');
    
    if (personalSection.length === 0) {
        var personalHTML = `
            <div id="personal-notifications-section" class="notification-category mb-3">
                <div class="category-header d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 text-primary">
                        <i class="fas fa-user me-1"></i>
                        Notificaciones Personales
                    </h6>
                    <span class="badge bg-primary">${count}</span>
                </div>
                <div id="personal-notifications-list">
                    ${generatePersonalNotificationsList(notifications)}
                </div>
            </div>
        `;
        
        // Insertar al principio del contenido
        $('#notification-content').prepend(personalHTML);
    } else {
        personalList.html(generatePersonalNotificationsList(notifications));
    }
};

/**
 * Genera HTML para la lista de notificaciones personales - VERSIÓN MEJORADA
 */
window.generatePersonalNotificationsList = function(notifications) {
   var html = '';
   var glpiRoot = window.GLPI_ROOT_UNREAD || '/cns';
   
   notifications.forEach(notification => {
      var itemUrl = '';
      var icon = 'fas fa-bell';
      var badgeClass = 'bg-primary';
      var message = notification.message;

      // Determinar URL, ícono y color según el tipo de evento
      switch (notification.event_type) {
         case 'new_ticket':
            itemUrl = `${glpiRoot}/front/ticket.form.php?id=${notification.item_id}`;
            icon = 'fas fa-plus-circle';
            badgeClass = 'bg-success';
            break;
         case 'user_mentioned':
            itemUrl = `${glpiRoot}/front/ticket.form.php?id=${notification.item_id}`;
            icon = 'fas fa-at';
            badgeClass = 'bg-warning text-dark';
            
            // Formatear específicamente el mensaje de menciones
            if (notification.data && notification.data.mentioned_by_name && notification.data.ticket_id) {
               message = `${notification.data.mentioned_by_name} te mencionó en el ticket #${notification.data.ticket_id}`;
               
               // Si hay nombre del ticket, agregarlo para más contexto
               if (notification.data.ticket_name && notification.data.ticket_name !== `#${notification.data.ticket_id}`) {
                  message += ` - ${notification.data.ticket_name}`;
               }
               
               // Agregar indicador si es privado
               if (notification.data.is_private) {
                  message += ' (Privado)';
               }
            }
            break;
         case 'group_assignment':
            itemUrl = `${glpiRoot}/front/ticket.form.php?id=${notification.item_id}`;
            icon = 'fas fa-users';
            badgeClass = 'bg-info';
            break;
         case 'status_change':
            itemUrl = `${glpiRoot}/front/ticket.form.php?id=${notification.item_id}`;
            icon = 'fas fa-exchange-alt';
            badgeClass = 'bg-secondary';
            break;
         case 'new_comment':
            itemUrl = `${glpiRoot}/front/ticket.form.php?id=${notification.item_id}`;
            icon = 'fas fa-comment';
            badgeClass = 'bg-primary';
            break;
         default:
            itemUrl = `${glpiRoot}/front/ticket.form.php?id=${notification.item_id}`;
            icon = 'fas fa-ticket-alt';
      }
      
      html += `
         <div class="notification-item personal-notification mb-2 p-2 border rounded" 
              data-notification-id="${notification.id}">
            <div class="d-flex justify-content-between align-items-start">
               <div class="flex-grow-1">
                  <div class="d-flex align-items-start">
                     <i class="${icon} me-2 mt-1 ${badgeClass} text-white p-1 rounded" style="min-width: 24px; text-align: center;"></i>
                     <div class="flex-grow-1">
                        <a href="${itemUrl}" class="text-dark text-decoration-none mark-as-read" target="_blank">
                           <strong>${message}</strong>
                        </a>
                        <div class="small text-muted mt-1">
                           <i class="fas fa-clock me-1"></i>
                           ${new Date(notification.date_creation).toLocaleString()}
                           ${notification.event_type === 'user_mentioned' && notification.data.comment_preview ? 
                              `<div class="mt-1 p-1 bg-light rounded small">"${notification.data.comment_preview}..."</div>` : ''}
                        </div>
                     </div>
                  </div>
               </div>
               <button class="btn btn-sm btn-outline-secondary mark-as-read-btn ms-2" title="Marcar como leído">
                  <i class="fas fa-check"></i>
               </button>
            </div>
         </div>
      `;
   });
   
   return html;
};

window.formatNotificationMessage = function(notification) {
   if (notification.event_type === 'user_mentioned' && notification.data) {
      const mentioner = notification.data.mentioned_by_name || 'Usuario';
      const ticketId = notification.data.ticket_id || '';
      const ticketName = notification.data.ticket_name || '';
      
      let message = `${mentioner} te mencionó en el ticket #${ticketId}`;
      
      // Si el ticket_name es diferente al ID, agregar el nombre
      if (ticketName && ticketName !== `#${ticketId}`) {
         message += ` - ${ticketName}`;
      }
      
      // Agregar indicador de privado si aplica
      if (notification.data.is_private) {
         message += ' (Privado)';
      }
      
      return message;
   }
   
   return notification.message;
};

/**
 * Actualizar notificaciones personales con formato mejorado
 */
window.updatePersonalNotificationsWithFormat = function(notifications, count) {
   if (count === 0) {
      $('#personal-notifications-section').remove();
      return;
   }
   
   var personalSection = $('#personal-notifications-section');
   var personalList = $('#personal-notifications-list');
   
   // Aplicar formato a las notificaciones antes de mostrarlas
   const formattedNotifications = notifications.map(notification => {
      return {
         ...notification,
         message: window.formatNotificationMessage(notification)
      };
   });
   
   if (personalSection.length === 0) {
      var personalHTML = `
         <div id="personal-notifications-section" class="notification-category mb-3">
            <div class="category-header d-flex justify-content-between align-items-center mb-2">
               <h6 class="mb-0 text-primary">
                  <i class="fas fa-user me-1"></i>
                  Notificaciones Personales
               </h6>
               <span class="badge bg-primary">${count}</span>
            </div>
            <div id="personal-notifications-list">
               ${generatePersonalNotificationsList(formattedNotifications)}
            </div>
         </div>
      `;
      
      $('#notification-content').prepend(personalHTML);
   } else {
      personalList.html(generatePersonalNotificationsList(formattedNotifications));
   }
};

// Sobrescribir la función original para usar la versión mejorada
window.updatePersonalNotifications = function(notifications, count) {
   window.updatePersonalNotificationsWithFormat(notifications, count);
};

// ========== MEJORAS PARA LA ACTUALIZACIÓN AUTOMÁTICA ==========

/**
 * Cargar y actualizar notificaciones personales con manejo mejorado
 */
window.loadPersonalNotificationsEnhanced = function() {
   var pluginWebroot = window.GLPI_PLUGIN_WEBROOT_UNREAD || '/cns/plugins/unreadnotifications';
   
   console.log('🔔 Loading enhanced personal notifications...');
   
   $.ajax({
      url: pluginWebroot + '/ajax/getpersonalnotifications.php',
      type: 'GET',
      dataType: 'json',
      success: function(response) {
         if (response.success) {
            console.log('🔔 Enhanced personal notifications:', response.notifications);
            
            // Aplicar formato específico a las menciones
            const formattedNotifications = response.notifications.map(notification => {
               if (notification.event_type === 'user_mentioned' && notification.data) {
                  const mentioner = notification.data.mentioned_by_name || 'Usuario';
                  const ticketId = notification.data.ticket_id || '';
                  const ticketName = notification.data.ticket_name || '';
                  
                  let message = `${mentioner} te mencionó en el ticket #${ticketId}`;
                  
                  if (ticketName && ticketName !== `#${ticketId}`) {
                     message += ` - ${ticketName}`;
                  }
                  
                  if (notification.data.is_private) {
                     message += ' (Privado)';
                  }
                  
                  return {
                     ...notification,
                     message: message
                  };
               }
               return notification;
            });
            
            window.updatePersonalNotificationsWithFormat(formattedNotifications, response.count);
            
            // Reproducir sonido si hay nuevas notificaciones personales
            if (response.count > 0 && window.soundManager) {
               window.soundManager.playSound('notification');
            }
         }
      },
      error: function(xhr, status, error) {
         console.error('🔔 Error loading enhanced personal notifications:', error);
      }
   });
};

// Reemplazar la función original con la mejorada
window.loadPersonalNotifications = window.loadPersonalNotificationsEnhanced;

/**
 * Cargar items detallados para la vista previa
 */
window.loadDetailedItems = function() {
    var pluginWebroot = window.GLPI_PLUGIN_WEBROOT_UNREAD || '/cns/plugins/unreadnotifications';
    
    console.log('🔔 Loading detailed items...');
    
    $.ajax({
        url: pluginWebroot + '/ajax/getnotificationitems.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('🔔 Detailed items response:', response);
            if (response.success) {
                updateDetailedItems(response.items);
            } else {
                console.error('🔔 Error in detailed items:', response.error);
                showDetailedItemsError();
            }
        },
        error: function(xhr, status, error) {
            console.error('🔔 Error loading detailed items:', error);
            showDetailedItemsError();
        }
    });
};

/**
 * Actualizar items detallados en la vista
 */
window.updateDetailedItems = function(items) {
    console.log('🔔 Updating detailed items:', items);
    
    // Tickets Asignados
    if (items.tickets_assigned && items.tickets_assigned.length > 0) {
        var html = '';
        items.tickets_assigned.forEach(ticket => {
            html += `
                <div class="notification-item mb-2 p-2 border rounded">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <a href="${window.GLPI_ROOT_UNREAD || '/cns'}/front/ticket.form.php?id=${ticket.id}" 
                               class="text-dark text-decoration-none" target="_blank">
                                <strong>#${ticket.id} ${ticket.name}</strong>
                            </a>
                            <div class="small text-muted">
                                <span class="badge bg-${ticket.status_color}">${ticket.status}</span>
                                <span class="ms-1">${ticket.date_creation}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        $('.category-items[data-type="tickets_assigned"]').html(html);
    } else {
        $('.category-items[data-type="tickets_assigned"]').html('<div class="text-center text-muted py-2">No hay tickets</div>');
    }
    
    // Tickets de Grupo
    if (items.tickets_group && items.tickets_group.length > 0) {
        console.log('🔔 Group tickets:', items.tickets_group);
        var html = '';
        items.tickets_group.forEach(ticket => {
            html += `
                <div class="notification-item mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <a href="${window.GLPI_ROOT_UNREAD || '/cns'}/front/ticket.form.php?id=${ticket.id}" 
                               class="text-dark text-decoration-none" target="_blank">
                                <strong>#${ticket.id} ${ticket.name}</strong>
                            </a>
                            <div class="small text-muted mt-1">
                                <span class="badge bg-${ticket.status_color} me-1">${ticket.status}</span>
                                <span class="me-2">${ticket.date_creation}</span>
                                <br>
                                <small><i class="fas fa-users me-1 group-label"></i><span class="text-primary">Grupo: ${ticket.group_name}</span></small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        $('.category-items[data-type="tickets_group"]').html(html);
    }
    
    // Cambios de Grupo
    if (items.changes_group && items.changes_group.length > 0) {
        console.log('🔔 Group changes:', items.changes_group);
        var html = '';
        items.changes_group.forEach(change => {
            html += `
                <div class="notification-item mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <a href="${window.GLPI_ROOT_UNREAD || '/cns'}/front/change.form.php?id=${change.id}" 
                               class="text-dark text-decoration-none" target="_blank">
                                <strong>#${change.id} ${change.name}</strong>
                            </a>
                            <div class="small text-muted mt-1">
                                <span class="badge bg-${change.status_color} me-1">${change.status}</span>
                                <span class="me-2">${change.date_creation}</span>
                                <br>
                                <small><i class="fas fa-users me-1"></i>Grupo: ${change.group_name}</small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        $('.category-items[data-type="changes_group"]').html(html);
    }
    
    console.log('🔔 Detailed items update completed');

    // Tickets Solicitante
    if (items.tickets_requester && items.tickets_requester.length > 0) {
        var html = '';
        items.tickets_requester.forEach(ticket => {
            html += `
                <div class="notification-item mb-2 p-2 border rounded">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <a href="${window.GLPI_ROOT_UNREAD || '/cns'}/front/ticket.form.php?id=${ticket.id}" 
                               class="text-dark text-decoration-none" target="_blank">
                                <strong>#${ticket.id} ${ticket.name}</strong>
                            </a>
                            <div class="small text-muted">
                                <span class="badge bg-${ticket.status_color}">${ticket.status}</span>
                                <span class="ms-1">${ticket.date_creation}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        $('.category-items[data-type="tickets_requester"]').html(html);
    } else {
        $('.category-items[data-type="tickets_requester"]').html('<div class="text-center text-muted py-2">No hay tickets</div>');
    }
    
    // Cambios
    if (items.changes && items.changes.length > 0) {
        var html = '';
        items.changes.forEach(change => {
            html += `
                <div class="notification-item mb-2 p-2 border rounded">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <a href="${window.GLPI_ROOT_UNREAD || '/cns'}/front/change.form.php?id=${change.id}" 
                               class="text-dark text-decoration-none" target="_blank">
                                <strong>#${change.id} ${change.name}</strong>
                            </a>
                            <div class="small text-muted">
                                <span class="badge bg-${change.status_color}">${change.status}</span>
                                <span class="ms-1">${change.date_creation}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        $('.category-items[data-type="changes"]').html(html);
    } else {
        $('.category-items[data-type="changes"]').html('<div class="text-center text-muted py-2">No hay cambios</div>');
    }
};

/**
 * Mostrar error en items detallados
 */
window.showDetailedItemsError = function() {
    $('.category-items').html(`
        <div class="text-center py-2 text-muted">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <small>Error cargando detalles</small>
        </div>
    `);
};

/**
 * Marca una notificación como leída
 */
window.markNotificationAsRead = function(notificationId, element) {
    var pluginWebroot = window.GLPI_PLUGIN_WEBROOT_UNREAD || '/cns/plugins/unreadnotifications';
    
    $.ajax({
        url: pluginWebroot + '/ajax/markasread.php',
        type: 'POST',
        data: {
            notification_id: notificationId
        },
        success: function(response) {
            if (response.success) {
                element.fadeOut(300, function() {
                    $(this).remove();
                    // Recargar contadores
                    loadNotifications();
                    loadPersonalNotifications();
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('🔔 Error marking notification as read:', error);
        }
    });
};

/**
 * Marcar todas las notificaciones como leídas
 */
window.markAllAsRead = function() {
    var pluginWebroot = window.GLPI_PLUGIN_WEBROOT_UNREAD || '/cns/plugins/unreadnotifications';
    
    // Marcar todas las notificaciones personales como leídas
    $('.personal-notification').each(function() {
        var notificationId = $(this).data('notification-id');
        var element = $(this);
        
        $.ajax({
            url: pluginWebroot + '/ajax/markasread.php',
            type: 'POST',
            data: {
                notification_id: notificationId
            },
            success: function(response) {
                if (response.success) {
                    element.fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            }
        });
    });
    
    // Recargar contadores después de un breve delay
    setTimeout(function() {
        loadNotifications();
        loadPersonalNotifications();
    }, 500);
    
    console.log('🔔 All notifications marked as read');
};

// ========== INICIALIZACIÓN ==========

// Auto-inicialización cuando el DOM esté listo
$(document).ready(function() {
    console.log('🔔 DOM ready, initializing Unread Notifications...');
    
    // Inicializar sistema de colores
    window.glpiColorManager.init();
    
    // Inicializar sistema de sonidos
    window.soundManager = new NotificationSoundManager();
    window.soundManager.init();
    
    // Crear campana después de un delay
    setTimeout(function() {
        createNotificationBell();
    }, 1000);
    
    // Configurar actualización automática
    setInterval(function() {
        loadNotifications();
        loadPersonalNotifications();
    }, UNREAD_NOTIFICATIONS_CONFIG.refresh_interval || 30000);
    
    // Configurar eventos para marcar como leído
    $(document).on('click', '.mark-as-read-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var notificationItem = $(this).closest('.personal-notification');
        var notificationId = notificationItem.data('notification-id');
        markNotificationAsRead(notificationId, notificationItem);
    });
    
    // También permitir marcar como leído haciendo clic en el contenido
    $(document).on('click', '.mark-as-read', function(e) {
        e.preventDefault();
        var notificationItem = $(this).closest('.personal-notification');
        var notificationId = notificationItem.data('notification-id');
        markNotificationAsRead(notificationId, notificationItem);
        
        // Permitir que el link se abra después de marcar como leído
        var href = $(this).attr('href');
        if (href && href !== '#') {
            setTimeout(function() {
                window.open(href, '_blank');
            }, 300);
        }
    });
});

// ========== FUNCIONES DE DEBUG ==========

/**
 * Función para probar sonidos manualmente (ejecutar en consola)
 */
window.testNotificationSounds = function() {
    console.log('🔔 Testing notification sounds...');
    
    if (window.soundManager) {
        console.log('🔔 Playing notification sound');
        window.soundManager.playSound('notification');
        
        setTimeout(() => {
            console.log('🔔 Playing alert sound');
            window.soundManager.playSound('alert');
        }, 1000);
    } else {
        console.error('🔔 Sound manager not available');
    }
};

/**
 * Función para debug del sistema (ejecutar en consola)
 */
window.debugNotificationSystem = function() {
    console.log('🔔=== DEBUG NOTIFICATION SYSTEM ===');
    console.log('🔔 Config:', UNREAD_NOTIFICATIONS_CONFIG);
    console.log('🔔 Sound Manager:', window.soundManager);
    console.log('🔔 Color Manager:', window.glpiColorManager);
    console.log('🔔 Bell Element:', $('#unread-notifications-bell').length);
    console.log('🔔 Counter Element:', $('#notification-counter').length);
    console.log('🔔=== END DEBUG ===');
};