<?php
add_action('admin_post_submit_visa_request', 'vm_handle_visa_request');
add_action('admin_post_nopriv_submit_visa_request', 'vm_handle_visa_request');

function vm_handle_visa_request() {
    // Vérification du quota de demandes
    // Remplacer la partie vérification du quota par :
    $max_requests_per_day = get_option('vm_max_daily_requests', 10);
    $today = date('Y-m-d');
    $args = array(
        'post_type' => 'visa_request',
        'post_status' => 'publish',
        'date_query' => array(
            array(
                'year' => date('Y'),
                'month' => date('m'),
                'day' => date('d'),
            ),
        ),
        'fields' => 'ids', // Optimisation pour ne récupérer que les IDs
        'posts_per_page' => -1,
    );

    $today_requests = new WP_Query($args);
    $request_count = $today_requests->post_count;

    if ($request_count >= $max_requests_per_day) {
        wp_die('Désolé, nous avons atteint le nombre maximum de demandes acceptées pour aujourd\'hui ('.$max_requests_per_day.'). Veuillez réessayer demain.');
    }
    if (!isset($_POST['vm_nonce']) || !wp_verify_nonce($_POST['vm_nonce'], 'vm_visa_form')) {
        wp_die('Erreur de sécurité.');
    }

    // Récupération des données de base
    $visa_type = isset($_POST['visa_type']) ? sanitize_text_field($_POST['visa_type']) : '';
    $depot_ville = isset($_POST['depot_ville']) ? sanitize_text_field($_POST['depot_ville']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : '';

    // Vérification des champs obligatoires
    if (empty($visa_type) || empty($depot_ville)) {
        wp_die('Le type de visa et la ville de dépôt sont obligatoires.');
    }

    $user_id = get_current_user_id();

    // Champs obligatoires par type de visa
    $required_fields_by_visa_type = [
        'long_sejour' => [
            'nom', 'prenom', 'date_naissance', 'lieu_naissance', 'pays_naissance',
            'Nationalite_actuelle', 'sexe', 'etat_civil', 'type_doc_voyage',
            'numero_doc_voyage', 'date_delivrance_doc_voyage', 'date_expiration_doc_voyage',
            'delivre_doc_voyage', 'profession', 'motif_visa_type'
        ],
        'court_sejour' => [
            'nom', 'prenom', 'date_naissance', 'lieu_naissance', 'pays_naissance',
            'Nationalite_actuelle', 'sexe', 'etat_civil', 'type_doc_voyage',
            'numero_doc_voyage', 'date_delivrance_doc_voyage', 'date_expiration_doc_voyage',
            'delivre_doc_voyage', 'motif_visa_type'
        ]
    ];

    // Validation des champs obligatoires
    foreach ($required_fields_by_visa_type[$visa_type] as $field) {
        if (empty($_POST[$field])) {
            wp_die("Le champ $field est obligatoire.");
        }
    }

    // Données communes à tous les types de visa
    $common_data = [
        'user_id' => $user_id,
        'visa_type' => $visa_type,
        'depot_ville' => $depot_ville,
        'user_email' => $email,
        'password' => $password,
        'nom' => sanitize_text_field($_POST['nom']),
        'prenom' => sanitize_text_field($_POST['prenom']),
        'email' => sanitize_email($email),
        'date_naissance' => sanitize_text_field($_POST['date_naissance']),
        'lieu_naissance' => sanitize_text_field($_POST['lieu_naissance']),
        'pays_naissance' => sanitize_text_field($_POST['pays_naissance']),
        'Nationalite_actuelle' => sanitize_text_field($_POST['Nationalite_actuelle']),
        'sexe' => sanitize_text_field($_POST['sexe']),
        'etat_civil' => sanitize_text_field($_POST['etat_civil']),
        'type_doc_voyage' => sanitize_text_field($_POST['type_doc_voyage']),
        'numero_doc_voyage' => sanitize_text_field($_POST['numero_doc_voyage']),
        'date_delivrance_doc_voyage' => sanitize_text_field($_POST['date_delivrance_doc_voyage']),
        'date_expiration_doc_voyage' => sanitize_text_field($_POST['date_expiration_doc_voyage']),
        'delivre_doc_voyage' => sanitize_text_field($_POST['delivre_doc_voyage']),
        'motif_visa_type' => sanitize_text_field($_POST['motif_visa_type']),
        'nationalite_autorite_parentale' => sanitize_text_field($_POST['nationalite_autorite_parentale']),
        'employeur' => sanitize_text_field($_POST['employeur']),
        'status' => 'Nouvelle',
        'approve_mandat' => isset($_POST['approve_mandat']) ? 1 : 0
    ];

    // Données spécifiques au long séjour
    $long_sejour_data = [
        'nom_famille' => sanitize_text_field($_POST['nom_famille'] ?? ''),
        'nationalite_naissance' => sanitize_text_field($_POST['nationalite_naissance'] ?? ''),
        'dossier_groupe' => sanitize_text_field($_POST['dossier_groupe'] ?? 'non'),
        'nom_groupe' => sanitize_text_field($_POST['nom_groupe'] ?? ''),
        'nombre_personnes_groupe' => intval($_POST['nombre_personnes_groupe'] ?? 0),
        'profession' => sanitize_text_field($_POST['profession']),
        'Numero_national_echeant' => sanitize_text_field($_POST['Numero_national_echeant']),
        'nom_employeur' => sanitize_text_field($_POST['nom_employeur'] ?? ''),
        'adresse' => sanitize_text_field($_POST['adresse'] ?? ''),
        'telephone' => sanitize_text_field($_POST['telephone'] ?? ''),
        'adresse_domicile' => sanitize_text_field($_POST['adresse_domicile'] ?? ''),
        'numero_titre_sejour' => sanitize_text_field($_POST['numero_titre_sejour'] ?? ''),
        'date_delivrance_titre_sejour' => sanitize_text_field($_POST['date_delivrance_titre_sejour'] ?? ''),
        'date_expiration_titre_sejour' => sanitize_text_field($_POST['date_expiration_titre_sejour'] ?? ''),
        'nom_invitant' => sanitize_text_field($_POST['nom_invitant'] ?? ''),
        'duree_prevue_sejour' => sanitize_text_field($_POST['duree_prevue_sejour'] ?? ''),
        'moyens_dexistence' => sanitize_textarea_field($_POST['moyens_dexistence'] ?? ''),
        'bourse' => sanitize_text_field($_POST['bourse'] ?? ''),
        'prise_charge_personnes' => sanitize_text_field($_POST['prise_charge_personnes'] ?? ''),
        'nom_prise_en_charge' => sanitize_textarea_field($_POST['nom_prise_en_charge'] ?? ''),
        'adresse_prise_en_charge'=> sanitize_text_field($_POST['adresse_prise_en_charge'] ??''),
        'telephone_prise_en_charge'=> sanitize_text_field($_POST['telephone_prise_en_charge'] ??''),
        'membre_famille_prise_en_charge'=> sanitize_text_field($_POST['membre_famille_prise_en_charge'] ??''),
        'bourse_oui' => sanitize_textarea_field($_POST['bourse_oui'] ?? ''),
        'adresse_email' => sanitize_textarea_field($_POST['adresse_email'] ?? ''),
        'adresse_deja_reside_france' => sanitize_textarea_field($_POST['adresse_deja_reside_france'] ?? ''),
        'prise_en_charge' => sanitize_text_field($_POST['prise_en_charge'] ?? ''),
        'prise_en_charge_oui' => sanitize_textarea_field($_POST['prise_en_charge_oui'] ?? ''),
        'famille_resident' => sanitize_text_field($_POST['famille_resident'] ?? ''),
        'famille_resident_oui' => sanitize_textarea_field($_POST['famille_resident_oui'] ?? ''),
        'resident_plus_de_trois_mois' => sanitize_text_field($_POST['resident_plus_de_trois_mois'] ?? ''),
        'resident_plus_de_trois_mois_oui' => sanitize_textarea_field($_POST['resident_plus_de_trois_mois_oui'] ?? ''),
        'resident_plus_de_trois_mois_adresse' => sanitize_textarea_field($_POST['resident_plus_de_trois_mois_adresse'] ?? '')
    ];

    // Données spécifiques au court séjour
    $court_sejour_data = [
        'autre_nationalite' => sanitize_text_field($_POST['autre_nationalite'] ?? ''),
        'adresse_demandeur' => sanitize_textarea_field($_POST['adresse_demandeur'] ?? ''),
        'telephone_demandeur' => sanitize_text_field($_POST['telephone_demandeur'] ?? ''),
        'residence_autre_pays' => sanitize_text_field($_POST['residence_autre_pays'] ?? ''),
        'residence_autre_pays_numero' => sanitize_text_field($_POST['residence_autre_pays_numero'] ?? ''),
        'residence_autre_pays_validité' => sanitize_text_field($_POST['residence_autre_pays_validité'] ?? ''),
        'nom_resortisant_EE' => sanitize_text_field($_POST['nom_resortisant_EE'] ?? ''),
        'prenom_resortisant_EE' => sanitize_text_field($_POST['prenom_resortisant_EE'] ?? ''),
        'date_naissance_resortisant_EE' => sanitize_text_field($_POST['date_naissance_resortisant_EE'] ?? ''),
        'nationalite_resortisant_EE' => sanitize_text_field($_POST['nationalite_resortisant_EE'] ?? ''),
        'numero_doc_resortisant_EE' => sanitize_text_field($_POST['numero_doc_resortisant_EE'] ?? ''),
        'lien_parente_resortissant' => sanitize_text_field($_POST['lien_parente_resortissant'] ?? ''),
        'lien_parente_resortissant_autre' => sanitize_text_field($_POST['lien_parente_resortissant_autre'] ?? ''),
        'motif' => sanitize_textarea_field($_POST['motif'] ?? ''),
        'etat_membre' => sanitize_text_field($_POST['etat_membre'] ?? ''),
        'etat_membre_premiere_entree' => sanitize_text_field($_POST['etat_membre_premiere_entree'] ?? ''),
        'entrees' => sanitize_text_field($_POST['entrees'] ?? ''),
        'date_arrivee' => sanitize_text_field($_POST['date_arrivee'] ?? ''),
        'date_depart' => sanitize_text_field($_POST['date_depart'] ?? ''),
        'empreinte' => sanitize_text_field($_POST['empreinte'] ?? ''),
        'empreinte_date_connue' => sanitize_text_field($_POST['empreinte_date_connue'] ?? ''),
        'empreinte_num_visa_connu' => sanitize_text_field($_POST['empreinte_num_visa_connu'] ?? ''),
        'empreinte_autorisation' => sanitize_text_field($_POST['empreinte_autorisation'] ?? ''),
        'valabilite_debut' => sanitize_text_field($_POST['valabilite_debut'] ?? ''),
        'valabilite_fin' => sanitize_text_field($_POST['valabilite_fin'] ?? ''),
        'nom_invitant' => sanitize_text_field($_POST['nom_invitant'] ?? ''),
        'adresse_invitant' => sanitize_textarea_field($_POST['adresse_invitant'] ?? ''),
        'telephone_invitant' => sanitize_text_field($_POST['telephone_invitant'] ?? ''),
        'organisation_hote' => sanitize_textarea_field($_POST['organisation_hote'] ?? ''),
        'telephone_organisation' => sanitize_text_field($_POST['telephone_organisation'] ?? ''),
        'nom_adresse_invitant' => sanitize_textarea_field($_POST['nom_adresse_invitant'] ?? ''),
        'frais_subsistance' => sanitize_text_field($_POST['frais_subsistance'] ?? ''),
        'frais_subsistance_garant' => sanitize_text_field($_POST['frais_subsistance_garant'] ?? ''),
        'frais_subsistance_garant_autre' => sanitize_textarea_field($_POST['frais_subsistance_garant_autre'] ?? ''),
        'subsistance_garant' => isset($_POST['subsistance_garant']) ? serialize($_POST['subsistance_garant']) : '',
        'subsistance_garant_autre' => sanitize_textarea_field($_POST['subsistance_garant_autre'] ?? ''),
        'subsistance_demandeur' => isset($_POST['subsistance_demandeur']) ? serialize($_POST['subsistance_demandeur']) : '',
        'subsistance_demandeur_autre' => sanitize_textarea_field($_POST['subsistance_demandeur_autre'] ?? ''),
        'nom_remplisseur' => sanitize_textarea_field($_POST['nom_remplisseur'] ?? ''),
        'remplisseur_nom' => sanitize_textarea_field($_POST['remplisseur_nom'] ?? ''),
        'remplisseur_telephone' => sanitize_textarea_field($_POST['remplisseur_telephone'] ?? ''),
        'adresse_electronique_remplisseur' => sanitize_textarea_field($_POST['adresse_electronique_remplisseur'] ?? ''),
        'telephone_remplisseur' => sanitize_text_field($_POST['telephone_remplisseur'] ?? '')
    ];

    // Fusion des données selon le type de visa
    $post_data = array_merge($common_data, 
        ($visa_type === 'long_sejour') ? $long_sejour_data : $court_sejour_data
    );

    // Traitement des champs dynamiques famille
    $liens_famille = [];
    if (!empty($_POST['Lien_parente']) && is_array($_POST['Lien_parente'])) {
        $lien_parente = $_POST['Lien_parente'];
        $nom_prenoms = $_POST['nom_prenoms_lien_parente'];
        $dates_naissance = $_POST['date_naissance_lien_parente'];
        $nationalites = $_POST['Nationalite_lien_parente'];

        for ($i = 0; $i < count($lien_parente); $i++) {
            $liens_famille[] = [
                'lien' => sanitize_text_field($lien_parente[$i]),
                'nom_prenoms' => sanitize_text_field($nom_prenoms[$i]),
                'date_naissance' => sanitize_text_field($dates_naissance[$i]),
                'nationalite' => sanitize_text_field($nationalites[$i]),
            ];
        }
    }

    $post_data['liens_parente_json'] = json_encode($liens_famille);

    // Création du post
    $post_id = wp_insert_post([
        'post_type' => 'visa_request',
        'post_status' => 'publish',
        'post_title' => 'Demande de visa - ' . sanitize_text_field($_POST['nom']),
        'meta_input' => $post_data
    ]);

    if (is_wp_error($post_id)) {
        wp_die('Erreur lors de l\'enregistrement de la demande : ' . $post_id->get_error_message());
    }

    // Répertoire de stockage
    $upload_dir = wp_upload_dir();
    $base_dir = trailingslashit($upload_dir['basedir']) . 'visa_dossiers/';
    wp_mkdir_p($base_dir);

    $dossier = $base_dir . $post_id;
    wp_mkdir_p($dossier);

    if (!file_exists($dossier)) {
        wp_die("Erreur : le dossier {$dossier} n'a pas pu être créé.");
    }

    // === Justificatif de paiement ===
    if (!empty($_FILES['proof']['tmp_name'])) {
        $proof = $_FILES['proof'];
        $allowed_mimes = ['application/pdf', 'image/jpeg', 'image/png'];

        if (!in_array($proof['type'], $allowed_mimes)) {
            wp_die('Type de fichier non autorisé pour le justificatif.');
        }

        $clean_name = sanitize_file_name($proof['name']);
        $ext = pathinfo($clean_name, PATHINFO_EXTENSION);
        $target_path = $dossier . "/justificatif_paiement." . $ext;

        if (move_uploaded_file($proof['tmp_name'], $target_path)) {
            update_post_meta($post_id, '_proof_path', $target_path);
        } else {
            error_log("Échec de l'upload du justificatif.");
        }
    }

    // === Photos d'identité ===
    if (!empty($_FILES['identity']['tmp_name'][0])) {
        foreach ($_FILES['identity']['tmp_name'] as $i => $tmp_name) {
            if (!empty($tmp_name)) {
                $name = sanitize_file_name($_FILES['identity']['name'][$i]);
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $target = $dossier . "/identity_" . uniqid() . '.' . $ext;

                if (move_uploaded_file($tmp_name, $target)) {
                    add_post_meta($post_id, '_identity_photos', $target);
                }
            }
        }
    }

    // === Carte d'identité ===
    /*
    if (!empty($_FILES['CIN']['tmp_name'][0])) {
        foreach ($_FILES['CIN']['tmp_name'] as $i => $tmp_name) {
            if (!empty($tmp_name)) {
                $name = sanitize_file_name($_FILES['CIN']['name'][$i]);
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $target = $dossier . "/cin_" . uniqid() . '.' . $ext;

                if (move_uploaded_file($tmp_name, $target)) {
                    add_post_meta($post_id, '_CIN_files', $target);
                }
            }
        }
    }
    */
    // === Documents financiers ===
    if (!empty($_FILES['documentsFinanciere']['tmp_name'][0])) {
        foreach ($_FILES['documentsFinanciere']['tmp_name'] as $i => $tmp_name) {
            if (!empty($tmp_name)) {
                $name = sanitize_file_name($_FILES['documentsFinanciere']['name'][$i]);
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $target = $dossier . "/finance_" . uniqid() . '.' . $ext;

                if (move_uploaded_file($tmp_name, $target)) {
                    add_post_meta($post_id, '_financial_docs', $target);
                }
            }
        }
    }

    // === Autres documents ===
    if (!empty($_FILES['documents']['tmp_name'][0])) {
        foreach ($_FILES['documents']['tmp_name'] as $i => $tmp_name) {
            if (!empty($tmp_name)) {
                $name = sanitize_file_name($_FILES['documents']['name'][$i]);
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $target = $dossier . "/doc_" . uniqid() . '.' . $ext;

                if (move_uploaded_file($tmp_name, $target)) {
                    add_post_meta($post_id, '_documents_paths', $target);
                }
            }
        }
    }

    // === Création ZIP ===
    $zip_path = $dossier . '/documents.zip';
    $zip = new ZipArchive();

    if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $file_types = [
            '_proof_path',
            '_identity_photos',
            // '_CIN_files', // supprimée
            '_financial_docs',
            '_documents_paths'
        ];

        foreach ($file_types as $meta_key) {
            $files = get_post_meta($post_id, $meta_key, false);
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $zip->addFile($file, basename($file));
                }
            }
        }

        $zip->close();
        update_post_meta($post_id, '_zip_path', $zip_path);
    } else {
        error_log("Impossible de créer l'archive ZIP.");
    }

    // Envoi d'email de confirmation
    $to = sanitize_email($_POST['email']);
    $subject = 'Confirmation de votre demande de visa';
    $message = "Bonjour " . sanitize_text_field($_POST['prenom']) . ",\n\n";
    $message .= "Nous avons bien reçu votre demande de visa. Voici les détails :\n\n";
    $message .= "Type de visa : " . sanitize_text_field($_POST['visa_type']) . "\n";
    $message .= "Référence : VISA-" . $post_id . "\n";
    $message .= "Date de soumission : " . current_time('d/m/Y H:i') . "\n\n";
    $message = "Ville de dépôt: $depot_ville\n";
    $message .= "Type de visa: $visa_type\n";
    $message .= "Vous pouvez suivre l'état de votre demande sur notre site.\n\n";
    $message .= "Cordialement,\nL'équipe Visa";

    wp_mail($to, $subject, $message);

    do_action('vm_after_visa_request_created', $post_id, $user_id);

    wp_redirect(home_url('/suivi-de-la-demande-de-visa/?confirm=1'));
    exit;
}