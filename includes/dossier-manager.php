<?php
// Gestion des fichiers uploadés
add_action('wpcf7_before_send_mail', 'vm_handle_uploads', 10, 3);
function vm_handle_uploads($contact_form, $abort, $submission) {
    if ($contact_form->title() !== 'form visa') return;

    $post_id = vm_get_associated_request($submission->get_posted_data());
    if (!$post_id) return;

    $upload_dir = wp_upload_dir();
    $dossier_path = $upload_dir['basedir'] . "/visa-dossiers/{$post_id}";

    // S'assure que le dossier du post existe
    if (!file_exists($dossier_path)) {
        wp_mkdir_p($dossier_path);
        file_put_contents($dossier_path . '/index.php', '<?php // Silence is golden');
    }

    // 1. Justificatif de paiement
    if (isset($submission->uploaded_files()['proof'])) {
        $proof_file = $submission->uploaded_files()['proof'];
        $new_name = "paiement-" . uniqid() . "." . pathinfo($proof_file, PATHINFO_EXTENSION);
        $new_path = "{$dossier_path}/{$new_name}";
        rename($proof_file, $new_path);
        update_post_meta($post_id, '_proof_path', $new_path);
    }

    // 2. Documents supplémentaires
    if (isset($submission->uploaded_files()['documents'])) {
        $docs = (array)$submission->uploaded_files()['documents'];
        foreach ($docs as $doc) {
            $new_name = "doc-" . uniqid() . "." . pathinfo($doc, PATHINFO_EXTENSION);
            $new_path = "{$dossier_path}/{$new_name}";
            rename($doc, $new_path);
            add_post_meta($post_id, '_documents_paths', $new_path);
        }
    }
     // 3. Photos d'identité
     if (!empty($submission->uploaded_files()['identity'])) {
        $photos = (array)$submission->uploaded_files()['identity'];
        foreach ($photos as $file) {
            $new_name = "photo-identite-" . uniqid() . "." . pathinfo($file, PATHINFO_EXTENSION);
            $new_path = "{$dossier_path}/{$new_name}";
            rename($file, $new_path);
            add_post_meta($post_id, '_identity_photos', $new_path);
        }
    }

    // 4. Carte d'identité (CIN)
    /*
    if (!empty($submission->uploaded_files()['CIN'])) {
        $cins = (array)$submission->uploaded_files()['CIN'];
        foreach ($cins as $file) {
            $new_name = "carte-identite-" . uniqid() . "." . pathinfo($file, PATHINFO_EXTENSION);
            $new_path = "{$dossier_path}/{$new_name}";
            rename($file, $new_path);
            add_post_meta($post_id, '_CIN_files', $new_path);
        }
    }
*/
    // 5. Documents financiers
    if (!empty($submission->uploaded_files()['documentsFinanciere'])) {
        $finances = (array)$submission->uploaded_files()['documentsFinanciere'];
        foreach ($finances as $file) {
            $new_name = "ressources-financieres-" . uniqid() . "." . pathinfo($file, PATHINFO_EXTENSION);
            $new_path = "{$dossier_path}/{$new_name}";
            rename($file, $new_path);
            add_post_meta($post_id, '_financial_docs', $new_path);
        }
    }


}