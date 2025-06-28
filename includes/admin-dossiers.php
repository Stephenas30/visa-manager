<?php
/*
// 1. Ajout colonne personnalisée "Dossier" dans le listing des demandes
add_filter('manage_visa_request_posts_columns', function($columns) {
    $columns['dossier_link'] = 'Dossier';
    return $columns;
});

add_action('manage_visa_request_posts_custom_column', function($column, $post_id) {
    if ($column === 'dossier_link') {
        $url = admin_url("admin.php?page=visa_dossier&request_id={$post_id}");
        echo '<a href="' . esc_url($url) . '" class="button">Voir Dossier</a>';
    }
}, 10, 2);

// 2. Enregistrement silencieux de la page admin.php?page=visa_dossier
add_action('admin_menu', function() {
    // Menu principal (caché)
    add_menu_page(
        'Dossiers Visa', 
        'Dossiers Visa', 
        'manage_options', 
        'visa_dossiers', 
        'vm_render_dossiers_page',
        'dashicons-portfolio',
        30
    );

    // Sous-menu (identique au parent pour avoir un premier élément cliquable)
    add_submenu_page(
        'visa_dossiers',
        'Dossiers Visa',
        'Tous les dossiers',
        'manage_options',
        'visa_dossiers',
        'vm_render_dossiers_page'
    );

    // Page individuelle de dossier
    add_submenu_page(
        'visa_dossiers',
        'Détails du dossier',
        'Détails',
        'manage_options',
        'visa_dossier',
        'vm_render_dossier_page'
    );
});

// 4. Génération du ZIP (MODIFIÉE)
add_action('admin_init', function() {
    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['download_zip']) || !isset($_GET['request_id'])) return;

    $post_id = intval($_GET['request_id']);
    if (!$post_id) wp_die('ID de dossier invalide');

    $zip = new ZipArchive();
    $zip_path = sys_get_temp_dir() . "/visa_dossier_{$post_id}.zip";

    if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        wp_die('Impossible de créer l\'archive ZIP.');
    }

    // Liste de tous les types de fichiers à inclure
    $file_types = [
        '_proof_path',
        '_documents_paths',
        '_identity_photos',
        '_CIN_files',
        '_financial_docs'
    ];

    foreach ($file_types as $meta_key) {
        $files = get_post_meta($post_id, $meta_key, false);
        if ($files) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $zip->addFile($file, basename($file));
                }
            }
        }
    }

    // Ajout des infos texte (inchangé)
    $infos = "Informations du demandeur de visa\n";
    $infos .= "-------------------------------\n";
    $infos .= "ID Demande : {$post_id}\n";
    $infos .= "Nom : " . get_the_title($post_id) . "\n";
    $infos .= "Email : " . get_post_meta($post_id, 'email', true) . "\n";
    $infos .= "Statut : " . get_field('status', $post_id) . "\n";
    $infos .= "Type de visa : " . get_field('visa_type', $post_id) . "\n";
    $infos .= "Pays de destination : " . get_field('country', $post_id) . "\n";
    $infos .= "Date de RDV : " . get_field('meeting_date', $post_id) . "\n";
    $infos .= "Message admin : " . get_field('admin_message', $post_id) . "\n";

    $zip->addFromString('infos_demandeur.txt', $infos);
    $zip->close();

    // Envoi du fichier (inchangé)
    header('Content-Type: application/zip');
    header("Content-Disposition: attachment; filename=visa_dossier_{$post_id}.zip");
    header('Content-Length: ' . filesize($zip_path));
    header('Pragma: no-cache');
    header('Expires: 0');
    
    readfile($zip_path);
    unlink($zip_path);
    exit;
});

// 5. Télécharger tous les dossiers affichés (MODIFIÉE)
add_action('admin_init', function() {
    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['download_all_zip']) || $_GET['page'] !== 'visa_dossiers') return;

    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';

    $args = [
        'post_type' => 'visa_request',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    ];

    if (!empty($start_date) || !empty($end_date)) {
        $date_query = ['inclusive' => true];
        if (!empty($start_date)) $date_query['after'] = $start_date;
        if (!empty($end_date)) $date_query['before'] = $end_date;
        $args['date_query'] = [$date_query];
    }

    $requests = get_posts($args);
    if (empty($requests)) {
        wp_die('Aucun dossier à inclure dans l\'archive ZIP.');
    }

    $zip = new ZipArchive();
    $zip_path = sys_get_temp_dir() . "/tous_les_dossiers_visa.zip";

    if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        wp_die('Impossible de créer l\'archive ZIP.');
    }

    $upload_dir = wp_upload_dir();
    $file_types = [
        '_proof_path',
        '_documents_paths',
        '_identity_photos',
        '_CIN_files',
        '_financial_docs'
    ];

    foreach ($requests as $request) {
        $post_id = $request->ID;
        $dossier_dir = $upload_dir['basedir'] . "/visa_dossiers/{$post_id}";

        if (!file_exists($dossier_dir)) continue;

        // Ajout de tous les fichiers du dossier
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dossier_dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $file_path = $file->getRealPath();
                $relative_path = "dossier_{$post_id}/" . substr($file_path, strlen($dossier_dir) + 1);
                $zip->addFile($file_path, $relative_path);
            }
        }

        // Ajout infos .txt dans chaque sous-dossier
        $infos = "Dossier Visa #{$post_id}\n";
        $infos .= "Nom : " . get_the_title($post_id) . "\n";
        $infos .= "Email : " . get_post_meta($post_id, 'email', true) . "\n";
        $infos .= "Statut : " . get_field('status', $post_id) . "\n";
        $infos .= "Type de visa : " . get_field('visa_type', $post_id) . "\n";
        $infos .= "Pays : " . get_field('country', $post_id) . "\n";
        $infos .= "Date de RDV : " . get_field('meeting_date', $post_id) . "\n";
        $infos .= "Message admin : " . get_field('admin_message', $post_id) . "\n";

        $zip->addFromString("dossier_{$post_id}/infos.txt", $infos);
    }

    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="tous_les_dossiers_visa.zip"');
    header('Content-Length: ' . filesize($zip_path));
    readfile($zip_path);
    unlink($zip_path);
    exit;
});
// Gestion de l'upload manuel
add_action('admin_init', function() {
    if (!current_user_can('manage_options')) return;
    if (!isset($_POST['vm_upload_file']) || !isset($_POST['vm_file_type'])) return;

    $post_id = intval($_GET['request_id'] ?? 0);
    if (!$post_id) wp_die('ID de dossier invalide');

    if (!wp_verify_nonce($_POST['_wpnonce'], 'vm_manual_upload_' . $post_id)) {
        wp_die('Erreur de sécurité.');
    }

    $file_type = sanitize_text_field($_POST['vm_file_type']);
    $allowed_types = ['_proof_path', '_documents_paths', '_identity_photos', '_CIN_files', '_financial_docs'];
    
    if (!in_array($file_type, $allowed_types)) {
        wp_die('Type de document non autorisé.');
    }

    if (!empty($_FILES['vm_file']['tmp_name'])) {
        $upload_dir = wp_upload_dir();
        $dossier_path = $upload_dir['basedir'] . "/visa_dossiers/{$post_id}";
        wp_mkdir_p($dossier_path);

        $file = $_FILES['vm_file'];
        $file_name = sanitize_file_name($file['name']);
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_name = uniqid() . '.' . $file_ext;
        $target_path = $dossier_path . '/' . $new_name;

        $allowed_mimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        $filetype = wp_check_filetype($file_name, $allowed_mimes);
        if (!$filetype['ext']) {
            wp_die('Type de fichier non autorisé. Formats acceptés : JPG, PNG, PDF, DOC, DOCX');
        }

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            add_post_meta($post_id, $file_type, $target_path);
            
            // Enregistrer aussi en tant que média WordPress
            $attachment = [
                'guid' => $upload_dir['baseurl'] . "/visa_dossiers/{$post_id}/{$new_name}",
                'post_mime_type' => $filetype['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', $file_name),
                'post_status' => 'inherit'
            ];
            
            $attach_id = wp_insert_attachment($attachment, $target_path, $post_id);
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $target_path);
            wp_update_attachment_metadata($attach_id, $attach_data);

            wp_redirect(admin_url("admin.php?page=visa_dossier&request_id={$post_id}&upload_success=1"));
            exit;
        } else {
            wp_die('Erreur lors de l\'upload du fichier.');
        }
    }
});
*/