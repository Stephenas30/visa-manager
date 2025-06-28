<?php

// Création des rôles à l'activation
function vm_add_custom_roles()
{
    // Suppression des rôles existants pour éviter les doublons
    remove_role('visa_manager');
    remove_role('agent');
    remove_role('client');

    // Rôle Gestionnaire Visa (accès complet au plugin)
    add_role('visa_manager', 'Gestionnaire Visa', [
        // Capacités de base
        'read' => true,
        'upload_files' => true,
        'edit_posts' => true, // Nécessaire pour l'accès admin

        // Capacités spécifiques au CPT Visa (toujours au pluriel)
        'edit_visa_requests' => true,
        'edit_others_visa_requests' => true,
        'publish_visa_requests' => true,
        'delete_visa_requests' => true,
        'delete_others_visa_requests' => true,
        'read_private_visa_requests' => true,
        'edit_private_visa_requests' => true,
        'edit_published_visa_requests' => true,
        'delete_private_visa_requests' => true,
        'delete_published_visa_requests' => true,
        'create_visa_requests' => true,
    ]);

    // Rôle Agent (accès limité)
    add_role('agent', 'Agent', [
        'read' => true,
        'edit_visa_requests' => true,
        'read_visa_requests' => true,
        'publish_visa_requests' => true,
        'upload_files' => true,
    ]);

    // Rôle Client (accès très limité)
    add_role('client', 'Client', [
        'read' => true,
    ]);

    // Donner toutes les capacités à l'administrateur
    $admin = get_role('administrator');
    if ($admin) {
        $admin->add_cap('edit_visa_requests');
        $admin->add_cap('edit_others_visa_requests');
        // Ajouter toutes les autres capacités visa_requests...
    }
}

// Initialisation des rôles
function vm_init_roles()
{
    vm_add_custom_roles();
}

// Suppression des rôles à la désactivation
function vm_remove_custom_roles()
{
    remove_role('agent');
    remove_role('client');
    remove_role('visa_manager');
}
register_deactivation_hook(__FILE__, 'vm_remove_custom_roles');

// Vérification des capacités des agents
function vm_grant_agent_caps()
{
    $role = get_role('agent');
    if (!$role) {
        return;
    }

    $caps = [
        'edit_visa_requests',
        'read_visa_requests',
        'publish_visa_requests',
        'upload_files'
    ];

    foreach ($caps as $cap) {
        if (!$role->has_cap($cap)) {
            $role->add_cap($cap);
        }
    }
}
add_action('admin_init', 'vm_grant_agent_caps');

// Restriction d'accès pour le visa_manager
function vm_restrict_admin_access()
{
    if (current_user_can('visa_manager') && !current_user_can('administrator') && !defined('DOING_AJAX')) {
        global $pagenow;

        // Pages autorisées
        $allowed_pages = [
            'index.php',                     // Tableau de bord
            'edit.php?post_type=visa_request', // Liste des demandes
            'post.php?post_type=visa_request', // Édition de demande
            'post-new.php?post_type=visa_request', // Nouvelle demande
            'admin.php?page=visa_dossier',   // Page des dossiers
            'upload.php'                     // Médiathèque
        ];

        $current_page = $pagenow . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');

        $allowed = false;
        foreach ($allowed_pages as $allowed_page) {
            if (strpos($current_page, $allowed_page) === 0) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            wp_redirect(admin_url('index.php'));
            exit;
        }
    }
}
add_action('admin_init', 'vm_restrict_admin_access');

// S'assurer que les rôles existent même après mise à jour
add_action('admin_init', function () {
    if (!get_role('visa_manager') || !get_role('agent') || !get_role('client')) {
        vm_add_custom_roles();
    }
});
