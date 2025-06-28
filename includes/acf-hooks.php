<?php

add_action('acf/save_post', 'vm_check_status_change', 20);

function vm_check_status_change($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (strpos($post_id, 'user_') !== false) {
        return;
    }
    if (get_post_type($post_id) !== 'visa_request') {
        return;
    }

    $new_status = get_field('status', $post_id);
    if (!$new_status) {
        return;
    }

    $old_status = get_post_meta($post_id, '_vm_last_status', true);
    if (empty($old_status)) {
        update_post_meta($post_id, '_vm_last_status', $new_status);
        return;
    }
    if ($new_status === $old_status) {
        return;
    }

    update_post_meta($post_id, '_vm_last_status', $new_status);

    // Récupération infos client
    $client_email = sanitize_email(get_post_meta($post_id, 'email', true));
    $prenom = get_post_meta($post_id, 'prenom', true);
    $nom = get_post_meta($post_id, 'nom', true);

    if (!is_email($client_email)) {
        return;
    }

    // Email admin
    $admin_email = get_option('admin_email');
    $admin_subject = "Changement de statut - Demande #$post_id";
    $admin_message = "Statut changé de $old_status à $new_status.";

    $subject = $text = '';

    switch ($new_status) {
        case 'Nouvelle':
            $subject = "📬 Votre demande de visa a été enregistrée";
            $text = "Nous avons bien reçu votre demande (ID: $post_id).<br>Elle sera traitée sous 48h.";
            break;

        case 'En cours':
            $subject = "📂 Votre demande est en cours de traitement";
            $text = "Votre dossier est actuellement en cours de traitement par notre équipe.";
            break;

        case 'En attente de documents supplémentaires':
            $subject = "🔍 Documents supplémentaires requis";
            $text = "Votre dossier nécessite des documents complémentaires.<br>Nous vous recontacterons par email avec les détails.";
            break;

        case 'Dossier renvoyé auprès du demandeur':
            $subject = "📤 Votre dossier est prêt – prochaine étape : l'ambassade";

            // Génération du ZIP
            $upload_dir = wp_upload_dir();
            $dossier_path = $upload_dir['basedir'] . "/visa_dossiers/{$post_id}";
            $zip_path = $dossier_path . '/dossier_complet.zip';
            $zip_url = $upload_dir['baseurl'] . "/visa_dossiers/{$post_id}/dossier_complet.zip";

            // Crée le ZIP si non existant ou ancien
            if (file_exists($zip_path)) {
                unlink($zip_path); // on écrase l'ancien pour éviter les doublons
            }

            if (file_exists($dossier_path)) {
                $files = array_diff(scandir($dossier_path), ['.', '..', 'index.php']);
                $zip = new ZipArchive();
                if ($zip->open($zip_path, ZipArchive::CREATE) === true) {
                    foreach ($files as $file) {
                        $file_path = $dossier_path . '/' . $file;
                        if (is_file($file_path)) {
                            $zip->addFile($file_path, $file);
                        }
                    }

                    // Ajouter un fichier info.txt dans le ZIP
                    $infos = "Dossier Visa #{$post_id}<br>";
                    $infos .= "Nom : {$nom}<br>";
                    $infos .= "Statut : {$new_status}<br>";
                    $infos .= "Date : " . current_time('d/m/Y H:i') . "<br>";
                    $zip->addFromString('info.txt', $infos);

                    $zip->close();
                }
            }

            // Contenu du mail
            $text = "Votre demande de visa a été traitée et finalisée par notre équipe.<br><br>"
                  . "Vous devez maintenant vous rendre à l'ambassade ou au centre consulaire pour finaliser votre demande.<br><br>"
                  . "📂 <a href=\"$zip_url\" target=\"_blank\">Cliquez ici pour télécharger votre dossier complet</a><br><br>"
                  . "Merci pour votre confiance.<br>L’équipe Visa Manager.";
            break;


        case 'RDV fixé':
            $date = get_field('meeting_date', $post_id);
            $date_text = $date ? date('d/m/Y', strtotime($date)) : '[Date non précisée]';
            $subject = "📅 Votre RDV consulaire est fixé";
            $text = "Votre rendez-vous consulaire est fixé à la date suivante : $date_text.<br>Merci de consulter vos documents pour les détails.";
            break;

        default:
            return;
    }

    $message = vm_build_html_email($prenom, $text);

    $headers = ['Content-Type: text/html; charset=UTF-8'];

    wp_mail($client_email, $subject, $message, $headers);
    wp_mail($admin_email, $admin_subject, $admin_message);

    $log_entry = date('[Y-m-d H:i:s]') . " $old_status → $new_status<br>";
    add_post_meta($post_id, '_vm_status_log', $log_entry, false);
}

function vm_build_html_email($prenom, $message_content)
{
    return '
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f7f7f7;
                padding: 20px;
                margin: 0;
            }
            .email-wrapper {
                max-width: 600px;
                margin: auto;
                background: #ffffff;
                border-radius: 8px;
                padding: 30px;
                box-shadow: 0 0 10px rgba(0,0,0,0.05);
                color: #2c3e50;
            }
            .email-header {
                font-size: 20px;
                font-weight: bold;
                margin-bottom: 20px;
                color: #3498db;
            }
            .email-body {
                font-size: 16px;
                line-height: 1.6;
                white-space: pre-line;
            }
            .email-footer {
                margin-top: 30px;
                font-size: 13px;
                color: #888;
                text-align: center;
            }
            .button {
                display: inline-block;
                margin-top: 20px;
                padding: 12px 20px;
                background-color: #3498db;
                color: #fff;
                text-decoration: none;
                border-radius: 4px;
            }
        </style>
    </head>
    <body>
        <div class="email-wrapper">
            <div class="email-header">
                Bonjour ' . esc_html($prenom) . ',
            </div>
            <div class="email-body">
                ' . wp_kses_post($message_content) . '
            </div>
            <div class="email-footer">
                Cet email vous a été envoyé par le service Visa Manager.
            </div>
        </div>
    </body>
    </html>';
}
