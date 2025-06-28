<?php

// Ajout d'une metabox pour afficher tous les détails de la demande
add_action('add_meta_boxes', function () {
    add_meta_box(
        'vm_post_details_full',
        'Détails complets de la demande',
        'vm_render_full_details_box',
        'visa_request',
        'normal',
        'high'
    );
});

function vm_render_full_details_box($post)
{
    $meta = get_post_meta($post->ID);

    // Champs personnalisés du formulaire
    $fields = [
        'visa_type' => 'Type de visa',
        'user_id' => 'Utilisateur',
        'user_email' => 'Email utilisateur',
        'depot_ville' => 'Ville de dépôt',
        'nom' => 'Nom',
        'prenom' => 'Prénom',
        'email' => 'Email',
        'password' => 'Mot de passe',
        'telephone_demandeur' => 'Téléphone du demandeur',
        'telephone_employeur' => 'Téléphone de l’employeur',
        'telephone' => 'Téléphone',
        'nom_famille' => 'Nom de famille',
        'date_naissance' => 'Date de naissance',
        'lieu_naissance' => 'Lieu de naissance',
        'pays_naissance' => 'Pays de naissance',
        'Nationalite_actuelle' => 'Nationalité actuelle',
        'nationalite_naissance' => 'Nationalité de naissance',
        'sexe' => 'Sexe',
        'etat_civil' => 'État civil',
        'autre_nationalite' => 'Autre nationalité',
        'liens_parente_json' => 'Liens de parenté',
        'dossier_groupe' => 'Dossier de groupe',
        'nom_groupe' => 'Nom du groupe',
        'nombre_personnes_groupe' => 'Nombre de personnes dans le groupe',
        'adresse_demandeur' => 'Adresse du demandeur',
        'nationalite_autorite_parentale' => 'Nationalité de l’autorité parentale',
        'bourse_oui' => 'Boursier',
        'Numero_national_echeant' => 'Numéro national si applicable',
        'type_doc_voyage' => 'Type de document de voyage',
        'numero_doc_voyage' => 'Numéro du document de voyage',
        'date_delivrance_doc_voyage' => 'Date de délivrance du document',
        'date_expiration_doc_voyage' => 'Date d’expiration du document',
        'delivre_doc_voyage' => 'Délivré par',
        'adresse_domicile' => 'Adresse de domicile',
        'numero_titre_sejour' => 'Numéro du titre de séjour',
        'date_delivrance_titre_sejour' => 'Date de délivrance du titre de séjour',
        'date_expiration_titre_sejour' => 'Date d’expiration du titre de séjour',
        'profession' => 'Profession',
        'employeur' => 'Nom de l’employeur',
        'adresse_employeur' => 'Adresse de l’employeur',
        'motif_visa_type' => 'Type de motif du visa',
        'motif' => 'Motif du voyage',
        'etat_membre' => 'État membre de destination',
        'etat_membre_premiere_entree' => 'État membre de première entrée',
        'entrees' => 'Nombre d’entrées demandées',
        'date_arrivee' => 'Date d’arrivée',
        'date_depart' => 'Date de départ',
        'duree_prevue_sejour' => 'Durée prévue du séjour',
        'empreinte' => 'Empreinte digitale déjà fournie',
        'autorisation_entree' => 'Autorisation d’entrée existante',
        'empreinte_autorisation' => 'Date des empreintes/autorisations',
        'valabilite_debut' => 'Début de validité',
        'valabilite_fin' => 'Fin de validité',
        'adresse_electronique_organisation' => 'Email de l’organisation d’accueil',
        'nom_invitant' => 'Nom de la personne invitante',
        'adresse_invitant' => 'Adresse de la personne invitante',
        'telephone_invitant' => 'Téléphone de la personne invitante',
        'organisation_hote' => 'Organisation hôte',
        'telephone_organisation' => 'Téléphone de l’organisation',
        'nom_adresse_invitant' => 'Nom et adresse de l’invitant',
        'recouvrement' => 'Mode de recouvrement',
        'bourse' => 'Aide financière',
        'titulaire_bourse' => 'Nom du titulaire de la bourse',
        'prise_charge_personnes' => 'Prise en charge par d’autres',
        'nom_prise_en_charge' => 'Nom du responsable de la prise en charge',
        'adresse_prise_en_charge' => 'Adresse du responsable',
        'telephone_prise_en_charge' => 'Téléphone du responsable',
        'membre_famille_prise_en_charge' => 'Membre de la famille responsable',
        'adresse_deja_reside_france' => 'Adresse en France lors du précédent séjour',
        'nom_remplisseur' => 'Nom du remplisseur de formulaire si différent',
        'remplisseur_nom' => 'Nom du remplisseur du formulaire',
        'remplisseur_mail' => 'Email du remplisseur',
        'adresse_email' => 'Adresse electronique',
        'remplisseur_telephone' => 'Téléphone du remplisseur',
        'telephone_remplisseur' => 'Téléphone du remplisseur (bis)',
        'status' => 'Statut de la demande',
        'approve_mandat' => 'Mandat approuvé',
    ];

    echo '<table class="form-table"><tbody>';

    foreach ($fields as $key => $label) {
        $value = isset($meta[$key][0]) ? $meta[$key][0] : '';

        echo '<tr>';
        echo '<th><label>' . esc_html($label) . '</label></th>';
        echo '<td>';

        if (in_array($key, ['motif', 'adresse_employeur', 'adresse_domicile', 'adresse_invitant'])) {
            echo '<textarea style="width:100%" rows="3" readonly>' . esc_textarea($value) . '</textarea>';
        } elseif ($key === 'approve_mandat') {
            echo '<input type="checkbox" disabled ' . checked($value, '1', false) . ' /> Oui';
        } elseif (in_array($key, ['date_naissance', 'date_arrivee', 'date_depart'])) {
            echo '<input type="text" style="width:100%" value="' . esc_attr(date_i18n('d/m/Y', strtotime($value))) . '" readonly />';
        } else {
            echo '<input type="text" style="width:100%" value="' . esc_attr($value) . '" readonly />';
        }

        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    // Affichage des fichiers du dossier
    echo '<h4>Documents envoyés</h4>';
    $upload_dir = wp_upload_dir();
    $files_path = $upload_dir['basedir'] . "/visa_dossiers/{$post->ID}";
    $files_url = $upload_dir['baseurl'] . "/visa_dossiers/{$post->ID}";

    if (file_exists($files_path)) {
        echo '<ul style="list-style: disc; padding-left: 20px;">';

        // Liste des types de fichiers avec leurs libellés
        $file_types = [
            '_proof_path' => 'Justificatif de paiement',
            '_documents_paths' => 'Document supplémentaire',
            '_identity_photos' => 'Photo d\'identité',
            // '_CIN_files' => 'Carte d\'identité (CIN)', // Ligne supprimée
            '_financial_docs' => 'Document financier'
        ];

        $has_files = false;

        foreach ($file_types as $meta_key => $label) {
            $files = get_post_meta($post->ID, $meta_key, false);
            if ($files) {
                foreach ($files as $file) {
                    if (is_array($file)) {
                        continue;
                    }
                    $url = $files_url . '/' . basename($file);
                    echo '<li><a href="' . esc_url($url) . '" target="_blank">' . esc_html($label) . ' - ' . esc_html(basename($file)) . '</a></li>';
                    $has_files = true;
                }
            }
        }

        echo '</ul>';

        if (!$has_files) {
            echo '<p><em>Aucun document trouvé dans ce dossier.</em></p>';
        }
    } else {
        echo '<p><em>Dossier de fichiers introuvable.</em></p>';
    }
}
