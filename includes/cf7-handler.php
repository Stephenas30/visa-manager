<?php

// Hook CF7 après soumission du formulaire
add_action('wpcf7_before_send_mail', 'vm_handle_cf7_submission');

function vm_handle_cf7_submission($cf7)
{
    $form_title = $cf7->title();

    if ($form_title !== 'form visa') {
        return;
    }

    $submission = WPCF7_Submission::get_instance();
    if (!$submission) {
        return;
    }

    $data = $submission->get_posted_data();
    $files = $submission->uploaded_files();

    // Création du CPT
    $post_id = wp_insert_post([
        'post_type'   => 'visa_request',
        'post_title'  => $data['nom'] . ' ' . $data['prenom'],
        'post_status' => 'publish',
    ]);

    if (!$post_id) {
        return;
    }

    // Initialisation du statut
    update_field('status', 'Nouvelle', $post_id);
    update_post_meta($post_id, '_vm_last_status', 'Nouvelle');

    // Sauvegarde des données textuelles
    vm_save_application_data($post_id, $data);

    // Gestion des fichiers uploadés
    $file_types = [
        'proof' => '_proof_path',
        'documents' => '_documents_paths',
        'identity' => '_identity_photos',
        //'CIN' => '_CIN_files',
        'documentsFinanciere' => '_financial_docs'
    ];

    foreach ($file_types as $field => $meta_key) {
        if (isset($files[$field])) {
            $uploaded_files = is_array($files[$field]) ? $files[$field] : [$files[$field]];
            foreach ($uploaded_files as $file) {
                vm_process_uploaded_file($file, $post_id, $meta_key);
            }
        }
    }

    // Notification
    vm_send_notification_email($data['email'], $post_id);
}

function vm_process_uploaded_file($file_path, $post_id, $meta_key)
{
    if (!file_exists($file_path)) {
        return;
    }

    $upload_dir = wp_upload_dir();
    $dossier_path = $upload_dir['basedir'] . "/visa_dossiers/{$post_id}";
    wp_mkdir_p($dossier_path);

    // Génération du nouveau nom de fichier
    $file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
    $new_filename = sanitize_file_name(uniqid() . '.' . $file_ext);
    $new_path = "{$dossier_path}/{$new_filename}";

    // Déplacement du fichier
    rename($file_path, $new_path);

    // Enregistrement en tant que média WordPress
    $filetype = wp_check_filetype($new_filename, null);
    $attachment = [
        'guid' => $upload_dir['baseurl'] . "/visa_dossiers/{$post_id}/{$new_filename}",
        'post_mime_type' => $filetype['type'],
        'post_title' => preg_replace('/\.[^.]+$/', '', $new_filename),
        'post_status' => 'inherit'
    ];

    $attach_id = wp_insert_attachment($attachment, $new_path, $post_id);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $new_path);
    wp_update_attachment_metadata($attach_id, $attach_data);

    // Ajout des métadonnées
    add_post_meta($post_id, $meta_key, $new_path);
}

function vm_save_application_data($post_id, $data)
{
    // Liste des champs à sauvegarder
    $fields = [
        'nom', 'prenom', 'email', 'telephone', 'visa_type', 'nationality',
        'destination_country', 'motif', 'date_naissance', 'lieu_naissance',
        'pays_naissance', 'Nationalite_actuelle', 'sexe', 'etat_civil',
        'autorite_parietale', 'Numero_national_echeant', 'type_doc_voyage',
        'numero_doc_voyage', 'date_delivrance_doc_voyage', 'delivre_doc_voyage',
        'nom_invitant', 'adresse_invitant', 'organisation_hote', 'hebergement'
    ];

    foreach ($fields as $field) {
        if (isset($data[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($data[$field]));
        }
    }

    // Création du fichier texte récapitulatif
    $upload_dir = wp_upload_dir();
    $dossier_path = $upload_dir['basedir'] . "/visa_dossiers/{$post_id}";
    wp_mkdir_p($dossier_path);

    $txt_content = "=== DEMANDE DE VISA #{$post_id} ===\n";
    $txt_content .= "Date : " . current_time('d/m/Y H:i:s') . "\n";
    $txt_content .= "Nom complet : " . $data['nom'] . ' ' . $data['prenom'] . "\n";
    $txt_content .= "Email : " . $data['email'] . "\n";
    $txt_content .= "Type de visa : " . $data['visa_type'] . "\n";
    $txt_content .= "Pays de destination : " . $data['destination_country'] . "\n";

    file_put_contents("{$dossier_path}/dossier-{$post_id}.txt", $txt_content);
}

function vm_send_notification_email($email, $post_id)
{
    $subject = "Votre demande de visa #{$post_id} a bien été reçue";
    $message = "Bonjour,\n\nVotre demande de visa (ID : {$post_id}) est enregistrée.\n\n";
    $message .= "Vous recevrez une mise à jour lorsque son statut évoluera.\n\nCordialement.";

    wp_mail($email, $subject, $message);

    // Notification admin
    $admin_email = get_option('admin_email');
    $admin_subject = "Nouvelle demande de visa #{$post_id}";
    $admin_message = "Une nouvelle demande a été soumise.\n";
    $admin_message .= "Voir la demande : " . admin_url("post.php?post={$post_id}&action=edit");

    wp_mail($admin_email, $admin_subject, $admin_message);
}
