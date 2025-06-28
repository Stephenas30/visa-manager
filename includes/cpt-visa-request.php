<?php
// Enregistre le CPT Visa Request
function register_visa_request_post_type() {
    $labels = array(
        'name' => 'Demandes de Visa',
        'singular_name' => 'Demande de Visa',
        'menu_name' => 'Demandes de Visa',
        'add_new' => 'Ajouter une Demande',
        'add_new_item' => 'Nouvelle Demande de Visa',
        'edit_item' => 'Modifier la Demande',
        'view_item' => 'Voir la Demande',
        'all_items' => 'Toutes les Demandes',
        'search_items' => 'Rechercher une Demande',
        'not_found' => 'Aucune demande trouvée',
    );

    $args = array(
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'capability_type' => 'post',
        'supports' => array('title'),
        'menu_icon' => 'dashicons-id',
    );

    register_post_type('visa_request', $args);

    // Ajout des colonnes personnalisées
    add_filter('manage_visa_request_posts_columns', 'vm_add_custom_columns');
    add_action('manage_visa_request_posts_custom_column', 'vm_show_custom_columns', 10, 2);

    // Enregistrement des pages admin
    add_action('admin_menu', 'vm_register_admin_pages');
    
    // Gestion des actions admin
    add_action('admin_init', 'vm_handle_admin_actions');
}

function vm_add_custom_columns($columns) {
    $columns['status'] = 'Statut';
    $columns['dossier_link'] = 'Dossier';
    return $columns;
}

function vm_show_custom_columns($column, $post_id) {
    switch ($column) {
        case 'status':
            $status = get_field('status', $post_id);
            $colors = [
                'Nouvelle' => '#3498db',
                'En cours' => '#f39c12',
                'RDV fixé' => '#9b59b6',
                'Dossier renvoyé auprès du demandeur' => '#2ecc71',
                'En attente de documents supplémentaires' => '#e74c3c'
            ];
            
            if ($status && isset($colors[$status])) {
                echo '<span style="background:'.$colors[$status].'; color:white; padding:2px 6px; border-radius:3px;">'.$status.'</span>';
            } else {
                echo $status ?: '—';
            }
            break;
            
        case 'dossier_link':
            $url = admin_url("admin.php?page=visa_dossier&request_id={$post_id}");
            echo '<a href="' . esc_url($url) . '" class="button">Voir Dossier</a>';
            break;
    }
}

function vm_register_admin_pages() {
    // Menu principal
    add_submenu_page(
        'edit.php?post_type=visa_request',
        'Dossiers Visa',
        'Dossiers Visa',
        'manage_options',
        'visa_dossiers',
        'vm_render_dossiers_page'
    );

    // Page individuelle de dossier (masquée dans le menu)
    add_submenu_page(
        null, // masqué
        'Détails du dossier',
        'Détails',
        'manage_options',
        'visa_dossier',
        'vm_render_dossier_page'
    );
}

function vm_render_dossiers_page() {
    // Récupération des dates soumises (ou vides)
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';

    // Construction des arguments de requête
    $args = [
        'post_type' => 'visa_request',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    ];

    // Ajout d'un filtre de date si fourni
    if (!empty($start_date) || !empty($end_date)) {
        $date_query = ['inclusive' => true];
    
        if (!empty($start_date)) {
            $date_query['after'] = $start_date;
        }
    
        if (!empty($end_date)) {
            $date_query['before'] = $end_date;
        }
    
        $args['date_query'] = [$date_query];
    }

    $requests = get_posts($args);

    echo '<div class="wrap">';
    echo '<h1>Liste des Dossiers Visa</h1>';
    echo '<div style="display:flex;justify-content: space-between;">';

    // Formulaire de filtre
    echo '<form method="get" style="margin-bottom: 20px;">';
    echo '<input type="hidden" name="post_type" value="visa_request">';
    echo '<input type="hidden" name="page" value="visa_dossiers">';
    echo 'De : <input type="date" name="start_date" value="' . esc_attr($start_date) . '"> ';
    echo 'à : <input type="date" name="end_date" value="' . esc_attr($end_date) . '"> ';
    echo '<input type="submit" class="button button-primary" value="Filtrer">';
    echo '</form>';

    // Télécharger tout
    if (!empty($requests)) {
        $download_url = admin_url('edit.php');
        $download_url = add_query_arg([
            'post_type' => 'visa_request',
            'page' => 'visa_dossiers',
            'download_all_zip' => 1,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ], $download_url);
    
        echo '<form method="get" action="' . esc_url($download_url) . '" style="margin-bottom: 20px;">';
        echo '<input type="hidden" name="post_type" value="visa_request">';
        echo '<input type="hidden" name="page" value="visa_dossiers">';
        echo '<input type="hidden" name="download_all_zip" value="1">';
        echo '<input type="hidden" name="start_date" value="' . esc_attr($start_date) . '">';
        echo '<input type="hidden" name="end_date" value="' . esc_attr($end_date) . '">';
        echo '<button type="submit" class="button button-primary">Télécharger tous les dossiers affichés (.zip)</button>';
        echo '</form>';
    }

    echo '</div>';

    // Tableau des dossiers
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Nom</th><th>Statut</th><th>Actions</th></tr></thead>';
    echo '<tbody>';

    foreach ($requests as $request) {
        $post_id = $request->ID;
        $title = get_the_title($post_id);
        $status = get_field('status', $post_id);
        $url = admin_url("admin.php?page=visa_dossier&request_id={$post_id}");
        echo "<tr>";
        echo "<td>{$post_id}</td>";
        echo "<td>{$title}</td>";
        echo "<td>{$status}</td>";
        echo "<td><a href='" . esc_url($url) . "' class='button'>Voir Dossier</a></td>";
        echo "</tr>";
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

function vm_render_dossier_page() {
    $post_id = intval($_GET['request_id'] ?? 0);
    if (!$post_id) {
        echo '<div class="notice notice-error"><p>ID de requête manquant.</p></div>';
        return;
    }

    echo '<div class="wrap">';
    echo '<h1>Dossier Visa #' . esc_html($post_id) . '</h1>';

    // Section informations
    echo '<div class="card" style="margin-bottom: 20px;">';
    echo '<h2>Informations de la demande</h2>';
    echo '<p><strong>Nom :</strong> ' . esc_html(get_the_title($post_id)) . '</p>';
    echo '<p><strong>Email :</strong> ' . esc_html(get_post_meta($post_id, 'email', true)) . '</p>';
    echo '<p><strong>Statut :</strong> ' . esc_html(get_field('status', $post_id)) . '</p>';
    echo '<p><strong>Type de visa :</strong> ' . esc_html(get_field('visa_type', $post_id)) . '</p>';
    echo '<p><strong>Pays :</strong> ' . esc_html(get_field('country', $post_id)) . '</p>';
    echo '</div>';

    // Section documents
    $upload_dir = wp_upload_dir();
    $dossier_path = $upload_dir['baseurl'] . "/visa_dossiers/{$post_id}";


    echo '<div class="card">';
    echo '<h2>Documents du dossier</h2>';
    echo '<ul style="list-style: disc; padding-left: 20px;">';

    $has_files = false;
    $file_types = [
        '_proof_path' => 'Justificatif de paiement',
        '_documents_paths' => 'Document',
        '_identity_photos' => 'Photo d\'identité',
        '_CIN_files' => 'Carte d\'identité (CIN)',
        '_financial_docs' => 'Document financier'
    ];

    foreach ($file_types as $meta_key => $label) {
        $files = get_post_meta($post_id, $meta_key, false);
        if ($files) {
            foreach ($files as $file) {
                if (is_array($file)) continue;
                $url = $dossier_path . '/' . basename($file);
                echo '<li><a href="' . esc_url($url) . '" target="_blank">' . esc_html($label) . ' - ' . esc_html(basename($file)) . '</a></li>';
                $has_files = true;
            }
        }
    }

    echo '</ul>';

    if (!$has_files) {
        echo '<div class="notice notice-warning"><p>Aucun document trouvé dans ce dossier.</p></div>';
    }

    // Bouton téléchargement ZIP
    echo '<div style="margin-top: 20px;">';
    echo '<form method="get" action="' . esc_url(admin_url('admin.php')) . '">';
    echo '<input type="hidden" name="page" value="visa_dossier">';
    echo '<input type="hidden" name="request_id" value="' . esc_attr($post_id) . '">';
    echo '<input type="hidden" name="download_zip" value="1">';
    echo '<button type="submit" class="button button-primary">Télécharger le dossier complet (ZIP)</button>';
    echo '</form>';
    echo '</div>';

    // Formulaire d'ajout de document
    echo '<div class="card" style="margin-top: 20px;">';
    echo '<h2>Ajouter un document</h2>';
    echo '<form method="post" enctype="multipart/form-data">';
    wp_nonce_field('vm_manual_upload_' . $post_id);

    echo '<div style="margin-bottom: 15px;">';
    echo '<label for="vm_file_type"><strong>Type de document :</strong></label><br>';
    echo '<select name="vm_file_type" id="vm_file_type" required style="margin-top: 5px; width: 100%; padding: 8px;">';
    echo '<option value="">-- Sélectionnez un type --</option>';
    echo '<option value="_proof_path">Justificatif de paiement</option>';
    echo '<option value="_documents_paths">Document supplémentaire</option>';
    echo '<option value="_identity_photos">Photo d\'identité</option>';
    echo '<option value="_CIN_files">Carte d\'identité (CIN)</option>';
    echo '<option value="_financial_docs">Document financier</option>';
    echo '</select>';
    echo '</div>';

    echo '<div style="margin-bottom: 15px;">';
    echo '<label for="vm_file"><strong>Fichier :</strong></label><br>';
    echo '<input type="file" name="vm_file" id="vm_file" required style="margin-top: 5px; width: 100%;">';
    echo '</div>';

    echo '<input type="hidden" name="vm_upload_file" value="1">';
    echo '<button type="submit" class="button button-primary">Uploader le document</button>';
    
    // Section envoi d'email au demandeur
    echo '<div class="card" style="margin-bottom: 20px;">';
    echo '<h2>Communication avec le demandeur</h2>';

    // Vérifier si l'email a été envoyé
    if (isset($_GET['email_sent'])) {
        echo '<div class="notice notice-success"><p>L\'email a bien été envoyé au demandeur.</p></div>';
    }

    // Récupérer l'email du demandeur
    $demandeur_email = get_post_meta($post_id, 'email', true);

    if ($demandeur_email) {
        echo '<p><strong>Email du demandeur :</strong> ' . esc_html($demandeur_email) . '</p>';
        
        echo '<form method="post" style="margin-top: 20px;">';
        wp_nonce_field('vm_send_email_' . $post_id);
        
        echo '<div style="margin-bottom: 15px;">';
        echo '<label for="vm_email_type"><strong>Type d\'email :</strong></label><br>';
        echo '<select name="vm_email_type" id="vm_email_type" required style="margin-top: 5px; width: 100%; padding: 8px;">';
        echo '<option value="">-- Sélectionnez un type --</option>';
        echo '<option value="documents_manquants">Demande de documents manquants</option>';
        echo '<option value="dossier_complet">Dossier complet - Retour final</option>';
        echo '<option value="autre">Autre message</option>';
        echo '</select>';
        echo '</div>';
        
        echo '<div style="margin-bottom: 15px;">';
        echo '<label for="vm_email_subject"><strong>Sujet :</strong></label><br>';
        echo '<input type="text" name="vm_email_subject" id="vm_email_subject" required style="margin-top: 5px; width: 100%; padding: 8px;">';
        echo '</div>';
        
        echo '<div style="margin-bottom: 15px;">';
        echo '<label for="vm_email_message"><strong>Message :</strong></label><br>';
        echo '<textarea name="vm_email_message" id="vm_email_message" required style="margin-top: 5px; width: 100%; padding: 8px; min-height: 150px;"></textarea>';
        echo '</div>';
        
        echo '<input type="hidden" name="vm_send_email" value="1">';
        echo '<button type="submit" class="button button-primary">Envoyer l\'email</button>';
        echo '</form>';
    } else {
        echo '<div class="notice notice-warning"><p>Aucun email enregistré pour ce demandeur.</p></div>';
    }

    echo '</div>'; // fin de la section email
    echo '</form>';
    echo '</div>';

    echo '</div>'; // fin .wrap
}

// Ajouter cette fonction pour la page de paramètres
function vm_register_settings_page() {
    add_submenu_page(
        'edit.php?post_type=visa_request',
        'Paramètres Visa',
        'Paramètres',
        'manage_options',
        'visa_settings',
        'vm_render_settings_page'
    );
}
add_action('admin_menu', 'vm_register_settings_page');

// Fonction pour afficher la page de paramètres
function vm_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Paramètres des demandes de visa</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('vm_settings_group');
            do_settings_sections('visa_settings');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Nombre maximum de demandes par jour</th>
                    <td>
                        <input type="number" name="vm_max_daily_requests" 
                               value="<?php echo esc_attr(get_option('vm_max_daily_requests', 10)); ?>" 
                               min="1" step="1" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Nombre maximum de jours (visa court séjour)</th>
                    <td>
                        <input type="number" name="vm_max_schengendays" 
                               value="<?php echo esc_attr(get_option('vm_max_schengendays', 90)); ?>" 
                               min="1" step="1" />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Enregistrement des paramètres
function vm_register_settings() {
    register_setting('vm_settings_group', 'vm_max_daily_requests', 'absint');
    register_setting('vm_settings_group', 'vm_max_schengendays', 'absint');
}
add_action('admin_init', 'vm_register_settings');
function vm_handle_admin_actions() {
    if (!current_user_can('manage_options')) return;

    // Téléchargement d'un dossier individuel
    if (isset($_GET['download_zip']) && isset($_GET['request_id'])) {
        $post_id = intval($_GET['request_id']);
        if (!$post_id) wp_die('ID de dossier invalide');

        $zip = new ZipArchive();
        $zip_path = sys_get_temp_dir() . "/visa_dossier_{$post_id}.zip";

        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            wp_die('Impossible de créer l\'archive ZIP.');
        }

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

        // Ajout des infos texte
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

        header('Content-Type: application/zip');
        header("Content-Disposition: attachment; filename=visa_dossier_{$post_id}.zip");
        header('Content-Length: ' . filesize($zip_path));
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($zip_path);
        unlink($zip_path);
        exit;
    }

    // Téléchargement de tous les dossiers
    if (isset($_GET['download_all_zip']) && $_GET['page'] === 'visa_dossiers') {
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
        foreach ($requests as $request) {
            $post_id = $request->ID;
            $dossier_dir = $upload_dir['basedir'] . "/visa_dossiers/{$post_id}";

            if (!file_exists($dossier_dir)) continue;

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

            // Ajout infos .txt
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
    }

    // Upload manuel de document
    if (isset($_POST['vm_upload_file']) && isset($_POST['vm_file_type'])) {
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
    }
    // Envoi d'email au demandeur
    if (isset($_POST['vm_send_email']) && isset($_POST['vm_email_type'])) {
        $post_id = intval($_GET['request_id'] ?? 0);
        if (!$post_id) wp_die('ID de dossier invalide');

        if (!wp_verify_nonce($_POST['_wpnonce'], 'vm_send_email_' . $post_id)) {
            wp_die('Erreur de sécurité.');
        }

        $demandeur_email = get_post_meta($post_id, 'email', true);
        if (!$demandeur_email) {
            wp_die('Aucun email enregistré pour ce demandeur.');
        }

        $email_type = sanitize_text_field($_POST['vm_email_type']);
        $subject = sanitize_text_field($_POST['vm_email_subject']);
        $message = wp_kses_post($_POST['vm_email_message']);
        
        // Ajouter l'entête standard
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        // Construire le contenu de l'email selon le type
        $email_content = '<html><body>';
        $email_content .= '<p>Bonjour,</p>';
        
        switch ($email_type) {
            case 'documents_manquants':
                $email_content .= '<p>Concernant votre demande de visa (référence #' . $post_id . '), nous avons besoin des documents supplémentaires suivants :</p>';
                break;
            case 'dossier_complet':
                $email_content .= '<p>Votre demande de visa (référence #' . $post_id . ') est maintenant complète :</p>';
                break;
            default:
                $email_content .= '<p>Concernant votre demande de visa (référence #' . $post_id . ') :</p>';
        }
        
        $email_content .= '<div style="margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 5px;">';
        $email_content .= wpautop($message);
        $email_content .= '</div>';
        
        $email_content .= '<p>Cordialement,<br>L\'équipe Visa</p>';
        $email_content .= '</body></html>';
        
        // Envoyer l'email
        $sent = wp_mail($demandeur_email, $subject, $email_content, $headers);
        
        if ($sent) {
            // Enregistrer dans l'historique
            $history_entry = [
                'timestamp' => current_time('timestamp'),
                'action' => 'Email envoyé',
                'user' => wp_get_current_user()->display_name,
                'details' => 'Type: ' . $email_type . ' - Sujet: ' . $subject
            ];
            
            $history = get_post_meta($post_id, '_visa_request_history', true);
            if (empty($history)) {
                $history = [];
            }
            
            $history[] = $history_entry;
            update_post_meta($post_id, '_visa_request_history', $history);
            
            wp_redirect(admin_url("admin.php?page=visa_dossier&request_id={$post_id}&email_sent=1"));
            exit;
        } else {
            wp_die('Une erreur est survenue lors de l\'envoi de l\'email.');
        }
    }
}

add_action('init', 'register_visa_request_post_type');