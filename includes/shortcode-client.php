<?php
add_shortcode('visa-dossier', 'vm_render_client_dossier');
function vm_render_client_dossier() {
    if (!is_user_logged_in()) {
        return '<p class="visa-alert">Vous devez être connecté pour voir votre dossier.</p>';
    }

    $user_id = get_current_user_id();

    $args = [
        'post_type'   => 'visa_request',
        'post_status' => 'publish',
        'meta_key'    => 'user_id',
        'meta_value'  => $user_id,
        'numberposts' => 1
    ];
    $visa_posts = get_posts($args);

    if (!$visa_posts) {
        return '<p class="visa-alert">Aucune demande de visa trouvée pour votre compte.</p>';
    }

    $post = $visa_posts[0];
    $post_id = $post->ID;

    // Champs
    $status         = get_field('status', $post_id);
    $type           = get_field('visa_type', $post_id);
    $destination_country = get_field('destination_country', $post_id);

    $meeting_date   = get_field('meeting_date', $post_id);
    $admin_message  = get_field('admin_message', $post_id);

    // Fichiers depuis /uploads
    $upload_dir = wp_upload_dir();
    $dossier_path = vm_get_dossier_path($post_id);
    $dossier_url = vm_get_dossier_url($post_id);

    // Récupérer les fichiers depuis les métadonnées
    $proof = get_post_meta($post_id, '_proof_path', true);
    $docs = get_post_meta($post_id, '_documents_paths', false);

    echo '<h3>Fichiers fournis :</h3>';
    echo '<ul>';

    if ($proof && file_exists($dossier_path . '/' . $proof)) {
        echo '<li><a href="' . esc_url($dossier_url . '/' . $proof) . '" target="_blank">Justificatif de paiement</a></li>';
    }

    if ($docs && is_array($docs)) {
        foreach ($docs as $doc) {
            if (file_exists($dossier_path . '/' . $doc)) {
                echo '<li><a href="' . esc_url($dossier_url . '/' . $doc) . '" target="_blank">' . esc_html($doc) . '</a></li>';
            }
        }
    }

    if (!$proof && empty($docs)) {
        echo '<li>Aucun fichier disponible.</li>';
    }

    echo '</ul>';
    ob_start();
    ?>
    <style>
        .visa-dossier {
            background: #fff;
            border: 1px solid #ccc;
            padding: 2em;
            margin-top: 2em;
            border-radius: 8px;
            font-family: "Segoe UI", sans-serif;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        .visa-dossier h2 {
            margin-top: 0;
            font-size: 1.6em;
            color: #333;
        }

        .visa-dossier ul {
            list-style: none;
            padding-left: 0;
        }

        .visa-dossier ul li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .visa-dossier a {
            color: #0073aa;
            text-decoration: none;
        }

        .visa-dossier a:hover {
            text-decoration: underline;
        }

        .visa-refus {
            background: #ffeaea;
            border-left: 4px solid #d63638;
            padding: 1em;
            margin-top: 1.5em;
        }

        .visa-alert {
            padding: 1em;
            background: #fef9e7;
            border-left: 4px solid #e6b800;
            font-style: italic;
        }
    </style>

    <div class="visa-dossier">
        <h2>Votre demande de visa</h2>
        <ul>
            <li><strong>Statut :</strong> <?= esc_html($status); ?></li>
            <li><strong>Type de visa :</strong> <?= esc_html($type); ?></li>
            <li><strong>Pays :</strong> <?= esc_html($destination_country); ?></li>
            <?php if ($meeting_date): ?>
                <li><strong>Rendez-vous :</strong> <?= date('d/m/Y', strtotime($meeting_date)); ?></li>
            <?php endif; ?>
        </ul>

        <h3>Fichiers fournis :</h3>
        <ul>
            <?php
            $fichiers = glob($dossier_path . '/*.{jpg,png,pdf,doc,docx}', GLOB_BRACE);
            if ($fichiers) {
                foreach ($fichiers as $file) {
                    $url = $dossier_url . '/' . basename($file);
                    echo '<li><a href="' . esc_url($url) . '" target="_blank">' . esc_html(basename($file)) . '</a></li>';
                }
            } else {
                echo '<li>Aucun fichier disponible.</li>';
            }
            ?>
        </ul>

        <?php if (strtolower($status) === 'refusé'): ?>
            <div class="visa-refus">
                <h3>Votre demande a été refusée</h3>
                <p><?= esc_html($admin_message ?: "Vous pouvez contester cette décision dans un délai de 30 jours. Veuillez nous contacter avec le courrier de refus et votre dossier complet."); ?></p>
            </div>
        <?php endif; ?>
    </div>
    <?php

    return ob_get_clean();
}