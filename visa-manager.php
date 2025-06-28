<?php
/**
 * Plugin Name: Visa Manager
 * Description: Gestion des demandes de visa (CPT + logique custom).
 * Version: 1.21
 * Author: Joël Stéphanas
 * Author URI: https://joel-stephanas.com
 */

if (!defined('ABSPATH')) exit;

// ======================
// 1. VÉRIFICATION DES DÉPENDANCES
// ======================
add_action('admin_init', 'vm_check_dependencies');
function vm_check_dependencies() {
    $missing = [];

    if (!function_exists('acf_add_local_field_group')) {
        $missing[] = 'Advanced Custom Fields PRO';
    }

    if (!defined('WPCF7_VERSION')) {
        $missing[] = 'Contact Form 7';
    }

    if (!empty($missing)) {
        add_action('admin_notices', function() use ($missing) {
            echo '<div class="error"><p>Visa Manager nécessite : <strong>' 
                 . implode(', ', $missing) 
                 . '</strong>. Installez-les avant d\'utiliser ce plugin.</p></div>';
        });
    }
}

// ======================
// 2. CHARGEMENT DES FICHIERS
// ======================
$includes = [
    'cpt-visa-request',
    'acf-hooks',
    'cf7-handler',
    'roles',
    'dossier-manager',
    'admin-dossiers',
    'admin-metabox',
    'shortcode-client',
    'shortcode-form',
    'form-handler'
];

foreach ($includes as $file) {
    $path = plugin_dir_path(__FILE__) . "includes/{$file}.php";
    if (file_exists($path)) {
        require_once $path;
    } else {
        error_log("Fichier manquant dans Visa Manager : {$file}.php");
    }
}

// ======================
// 3. INITIALISATION ACF
// ======================
add_action('acf/init', 'vm_register_visa_fields');
function vm_register_visa_fields() {
    if (!function_exists('acf_add_local_field_group')) return;

    acf_add_local_field_group([
        'key' => 'group_visa_request',
        'title' => 'Informations sur le visa',
        'fields' => [
            [
                'key' => 'field_visa_status',
                'label' => 'Statut',
                'name' => 'status',
                'type' => 'select',
                'choices' => [
                    'Nouvelle' => 'Nouvelle',
                    'En cours' => 'En cours',
                    'RDV fixé' => 'RDV fixé',
                    'Dossier renvoyé auprès du demandeur' => 'Dossier renvoyé auprès du demandeur',
                    'En attente de documents supplémentaires' => 'En attente de documents supplémentaires',
                ],
                'default_value' => 'Nouvelle'
            ],
            [
                'key' => 'field_visa_type',
                'label' => 'Type de visa',
                'name' => 'visa_type',
                'type' => 'select',
                'choices' => [],
                'default_value' => ''
            ],
            [
                'key' => 'field_visa_country',
                'label' => 'Pays de destination',
                'name' => 'destination_country',
                'type' => 'text',
            ],
            [
                'key' => 'field_meeting_date',
                'label' => 'Date de RDV',
                'name' => 'meeting_date',
                'type' => 'date_picker',
                'display_format' => 'd/m/Y'
            ],
            [
                'key' => 'field_admin_message',
                'label' => 'Message de l\'administration',
                'name' => 'admin_message',
                'type' => 'textarea',
            ],
        ],
        'location' => [[[ 'param' => 'post_type', 'operator' => '==', 'value' => 'visa_request' ]]],
        'position' => 'normal'
    ]);
}

// Injection dynamique des types de visa
add_filter('acf/load_field/name=visa_type', function($field) {
    $field['choices'] = vm_get_flat_visa_choices();
    return $field;
});

// Fonction qui retourne toutes les options à plat
function vm_get_flat_visa_choices() {
    return [
        'Longs séjours' => 'Longs séjours',
        'Cours séjours' => 'Cours séjours',
        'TVA' => 'TVA',
    
    ];
}

function vm_get_dossier_path($post_id) {
    $upload = wp_upload_dir();
    $path = $upload['basedir'] . "/visa-dossiers/{$post_id}";
    
    if (!file_exists($path)) {
        wp_mkdir_p($path);
        file_put_contents($path . '/index.php', '<?php // Silence is golden');
    }
    
    return $path;
}

function vm_get_dossier_url($post_id) {
    $upload = wp_upload_dir();
    return $upload['baseurl'] . "/visa-dossiers/{$post_id}";
}

// ======================
// 4. HOOKS D'ACTIVATION
// ======================
register_activation_hook(__FILE__, 'vm_plugin_activation');

function vm_plugin_activation() {
    if (function_exists('vm_init_roles')) {
        vm_init_roles();
    }

    $upload_dir = wp_upload_dir();
    $dossier_path = $upload_dir['basedir'] . '/visa-dossiers';

    if (!file_exists($dossier_path)) {
        wp_mkdir_p($dossier_path);
        file_put_contents($dossier_path . '/index.php', '<?php // Silence is golden');
    }
}

// ======================
// 5. GÉNÉRATION ZIP
// ======================
function vm_telechargement_zip($atts) {
    add_action('admin_init', function() {
        if (!current_user_can('manage_options')) return;
        if (!isset($_GET['download_zip']) || !isset($_GET['request_id'])) return;

        $post_id = intval($_GET['request_id']);
        $dossier_path = vm_get_dossier_path($post_id);
        $zip_path = $dossier_path . '/dossier_complet.zip';

        if (!file_exists($dossier_path)) {
            wp_die('Le dossier n\'existe pas. Chemin: ' . esc_html($dossier_path));
        }

        $files = scandir($dossier_path);
        $valid_files = array_diff($files, ['..', '.', 'index.php']);
        
        if (count($valid_files) === 0) {
            wp_die('Le dossier est vide.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            wp_die('Impossible de créer l\'archive ZIP.');
        }

        foreach ($valid_files as $file) {
            $file_path = $dossier_path . '/' . $file;
            if (is_file($file_path)) {
                $zip->addFile($file_path, $file);
            }
        }

        $infos = "Dossier Visa #{$post_id}\n";
        $infos .= "Nom: " . get_the_title($post_id) . "\n";
        $infos .= "Statut: " . get_field('status', $post_id) . "\n";
        $infos .= "Date création: " . get_the_date('', $post_id) . "\n";
        
        $zip->addFromString('info.txt', $infos);
        
        if (!$zip->close()) {
            wp_die('Erreur lors de la finalisation du ZIP.');
        }

        if (!file_exists($zip_path)) {
            wp_die('Le fichier ZIP n\'a pas pu être créé.');
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="dossier_visa_'.$post_id.'.zip"');
        header('Content-Length: ' . filesize($zip_path));
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($zip_path);
        
        register_shutdown_function(function() use ($zip_path) {
            if (file_exists($zip_path)) {
                unlink($zip_path);
            }
        });
        
        exit;
    });
}

add_shortcode('visa_zip_download', 'vm_telechargement_zip');