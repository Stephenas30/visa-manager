<?php
function visa_form_shortcode()
{
    ob_start();

    // Initialisation des variables utiles
    $is_logged_in = is_user_logged_in();
    $visa_type = isset($_POST['visa_type']) ? sanitize_text_field($_POST['visa_type']) : null;

    // Si l'utilisateur n'est pas connecté
    if (!$is_logged_in) {
        echo do_shortcode('[wpum_login_form register_link="yes"][/wpum_login_form]');
        return ob_get_clean();
    }

    echo '<div class="visa-form-wrapper">';

    // Si aucun type n'est sélectionné encore
    if (!$visa_type) {
        ?>
        <form method="post">
            <label for="visa_type">Choisissez un type de visa :</label><br><br>
            <select name="visa_type" id="visa_type" required>
                <option value="">-- Sélectionner --</option>
                <option value="long_sejour">Visa Long Séjour</option>
                <option value="court_sejour">Visa Court Séjour</option>
                <option value="tva">TVA</option>
            </select><br><br>
            <input type="submit" value="Continuer">
        </form>
        <?php
        echo '</div>';
        return ob_get_clean();
    }

    // Affichage du vrai formulaire après choix du visa
    ?>
    <form action="<?= esc_url(admin_url('admin-post.php')) ?>" method="post" enctype="multipart/form-data" id="visa-request-form">
        <?php wp_nonce_field('vm_visa_form', 'vm_nonce'); ?>
        <input type="hidden" name="action" value="submit_visa_request">
        <input type="hidden" name="visa_type_selected" value="<?= esc_attr($visa_type); ?>">

        <?php if ($visa_type === 'long_sejour') : ?>
            <!-- Champs spécifiques Long Séjour -->
            <!-- Section 1: Informations personnelles -->
            <div id="section-1" class="form-section active">
                <h2 class="form-section-title">Informations personnelles</h2>
                
                <div class="form-field">
                    <label>Nom <span class="required">*</span></label>
                    <input type="text" name="nom" required placeholder="Nom de famille">
                </div>
                
                <div class="form-field">
                    <label>Nom(s) de naissance <span class="required">*</span></label>
                    <input type="text" name="nom_famille" required placeholder="Nom(s) de famille antérieur(s)">
                </div>
                
                <div class="form-field">
                    <label>Prénom(s) <span class="required">*</span></label>
                    <input type="text" name="prenom" required placeholder="Prénom(s) usuel(s)">
                </div>
                
                <div class="form-field">
                    <label>Date de naissance <span class="required">*</span></label>
                    <input type="date" name="date_naissance" required>
                </div>
                
                <div class="form-field">
                    <label>Lieu de naissance <span class="required">*</span></label>
                    <input type="text" name="lieu_naissance" required>
                </div>
                
                <div class="form-field">
                    <label>Pays de naissance <span class="required">*</span></label>
                    <input type="text" name="pays_naissance" required>
                </div>
                
                <div class="form-field">
                    <label>Nationalité actuelle <span class="required">*</span></label>
                    <input type="text" name="Nationalite_actuelle" required>
                </div>
                
                <div class="form-field">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-field">
                    <label>Téléphone <span class="required">*</span></label>
                    <input type="text" name="telephone" required>
                </div>
                
                <h3 class="form-subtitle">État civil</h3>
                
                <div class="form-field">
                    <label>Sexe <span class="required">*</span></label>
                    <select name="sexe" required>
                        <option value="">-- Sélectionnez --</option>
                        <option value="Homme">Homme</option>
                        <option value="Femme">Femme</option>
                    </select>
                </div>
                
                <div class="form-field">
                    <label>État civil <span class="required">*</span></label>
                    <select name="etat_civil" required>
                        <option value="">-- Sélectionnez --</option>
                        <option value="Marié">Marié</option>
                        <option value="Célibataire">Célibataire</option>
                        <option value="Divorcé">Divorcé</option>
                    </select>
                </div>
                
                <h3 class="form-subtitle">Gestion des dossiers groupés</h3>
                
                <div class="form-field">
                    <label>Avez-vous une autre demande en cours de traitement ?</label>
                    <select name="dossier_groupe" id="dossier-groupe-select">
                        <option value="non">Non</option>
                        <option value="oui">Oui</option>
                    </select>
                </div>
                
                <div id="dossier-groupe-info" class="conditional-field">
                    <div class="form-field">
                        <label>Nom inscrit sur la demande principale</label>
                        <input type="text" name="nom_demande_principale" placeholder="Exactement comme inscrit sur l'autre demande">
                    </div>
                    
                    <div class="form-field">
                        <label>Numéro de référence de la demande principale (si connu)</label>
                        <input type="text" name="ref_demande_principale" placeholder="Référence ou numéro de dossier">
                    </div>
                    
                    <p class="form-help-text">Ces informations nous aideront à lier votre demande actuelle à votre dossier principal.</p>
                </div>
                
                <h3 class="form-subtitle">Autorité parentale</h3>
                
                <div class="form-field">
                    <label>Autorité parentale pour les mineurs</label>
                    <input type="text" name="autorite_parietale" placeholder="Père / Mère / Tuteur légal - Nom, Prénom, Adresse">
                </div>
                
                <div class="form-field">
                    <label>Numéro national d'identité (le cas échéant)</label>
                    <input type="text" name="Numero_national_echeant" placeholder="Cas échéant">
                </div>
                
                <div class="form-navigation">
                    <button type="button" class="next-btn">Suivant</button>
                </div>
            </div>

            <!-- Section 2: Document de voyage et informations du demandeur -->
            <div id="section-2" class="form-section">
                <h2 class="form-section-title">Document de voyage et informations professionnelles</h2>
                
                <h3 class="form-subtitle">Document de voyage</h3>
                
                <div class="form-field">
                    <label>Type de document <span class="required">*</span></label>
                    <select name="type_doc_voyage" required>
                        <option value="">-- Sélectionnez --</option>
                        <option value="Passeport ordinaire">Passeport ordinaire</option>
                        <option value="Passeport diplomatique">Passeport diplomatique</option>
                        <option value="Autre document">Autre document</option>
                    </select>
                </div>
                
                <div class="form-field">
                    <label>Numéro du document <span class="required">*</span></label>
                    <input type="text" name="numero_doc_voyage" required>
                </div>
                
                <div class="form-field">
                    <label>Date de délivrance <span class="required">*</span></label>
                    <input type="date" name="date_delivrance_doc_voyage" required>
                </div>
                
                <div class="form-field">
                    <label>Délivré par <span class="required">*</span></label>
                    <input type="text" name="delivre_doc_voyage" required>
                </div>
                
                <h3 class="form-subtitle">Informations professionnelles</h3>
                
                <div class="form-field">
                    <label>Adresse du domicile <span class="required">*</span></label>
                    <input type="text" name="adresse_domicile" required>
                </div>
                
                <div class="form-field">
                    <label>Résidence dans un pays autre que celui de la nationalité actuelle</label>
                    <input type="text" name="residence_etrangere" placeholder="+ numéro autorisation de séjour et date d'expiration">
                </div>
                
                <div class="form-field">
                    <label>Profession actuelle <span class="required">*</span></label>
                    <input type="text" name="profession" required>
                </div>
                
                <div class="form-field">
                    <label>Nom de l'employeur <span class="required">*</span></label>
                    <input type="text" name="nom_employeur" required>
                </div>
                
                <div class="form-field">
                    <label>Adresse de l'employeur <span class="required">*</span></label>
                    <input type="text" name="adresse_employeur" required>
                </div>
                
                <div class="form-field">
                    <label>Téléphone de l'employeur <span class="required">*</span></label>
                    <input type="text" name="telephone_employeur" required>
                </div>
                
                <div class="form-navigation">
                    <button type="button" class="prev-btn">Précédent</button>
                    <button type="button" class="next-btn">Suivant</button>
                </div>
            </div>

            <!-- Section 3: Informations sur le voyage -->
            <div id="section-3" class="form-section">
                <h2 class="form-section-title">Informations sur le voyage</h2>
                
                <div class="form-field">
                    <label>Objet principal du voyage <span class="required">*</span></label>
                    <select name="visa_type" required>
                        <option value="">-- Sélectionnez --</option>
                        <option value="Tourisme">Tourisme</option>
                        <option value="Visite familiale ou amicale">Visite familiale ou amicale</option>
                        <option value="Affaires">Affaires</option>
                        <option value="Formation / Stage de courte durée">Formation / Stage de courte durée</option>
                        <option value="Événement culturel, sportif ou artistique">Événement culturel, sportif ou artistique</option>
                        <option value="Transit aéroportuaire">Transit aéroportuaire (Type A)</option>
                        <option value="Études">Études</option>
                        <option value="Travail">Travail</option>
                        <option value="Regroupement familial">Regroupement familial</option>
                        <option value="Recherche scientifique">Recherche scientifique</option>
                        <option value="Tourisme prolongé">Tourisme prolongé</option>
                        <option value="Soins médicaux">Soins médicaux</option>
                        <option value="Mariage">Mariage avec un ressortissant français</option>
                        <option value="vvt">Visa Vacances-Travail (VVT)</option>
                    </select>
                </div>
                
                <div class="form-field">
                    <label>Informations complémentaires sur l'objet du voyage <span class="required">*</span></label>
                    <textarea name="motif" required></textarea>
                </div>
                
                <div class="form-field">
                    <label>État(s) membre(s) de destination <span class="required">*</span></label>
                    <input type="text" name="etat_membre" placeholder="France pour DROM/CTOM" required>
                </div>
                
                <div class="form-field">
                    <label>Nombre d'entrées demandées <span class="required">*</span></label>
                    <select name="entrees" required>
                        <option value="">-- Sélectionnez --</option>
                        <option value="1">Entrée unique</option>
                        <option value="2">Double entrée</option>
                        <option value="Multiple">Multiples entrées</option>
                    </select>
                </div>
                
                <div class="form-field">
                    <label>Date d'arrivée <span class="required">*</span></label>
                    <input type="date" name="date_arrivee" required>
                </div>
                
                <div class="form-field">
                    <label>Date de départ <span class="required">*</span></label>
                    <input type="date" name="date_depart" required>
                </div>
                
                <div class="form-field">
                    <label>Empreintes digitales relevées précédemment</label>
                    <input type="text" name="empreinte" placeholder="Date + numéro du visa si oui">
                </div>
                
                <div class="form-field">
                    <label>Autorisation d'entrée dans le pays de destination finale</label>
                    <input type="text" name="autorisation_entree" placeholder="Numéro et dates de validité si hors Schengen">
                </div>
                
                <h3 class="form-subtitle">Coordonnées en France</h3>
                
                <div class="form-field">
                    <label>Personne invitante</label>
                    <input type="text" name="nom_invitant" placeholder="Nom complet ou coordonnées d'hôtel">
                </div>
                
                <div class="form-field">
                    <label>Adresse de la personne invitante</label>
                    <textarea name="adresse_invitant" placeholder="Adresse complète"></textarea>
                </div>
                
                <div class="form-field">
                    <label>Organisation/entreprise hôte</label>
                    <textarea name="organisation_hote" placeholder="Nom et adresse complète"></textarea>
                </div>
                
                <div class="form-navigation">
                    <button type="button" class="prev-btn">Précédent</button>
                    <button type="button" class="next-btn">Suivant</button>
                </div>
            </div>

            <!-- Section 4: Financement et documents -->
            <div id="section-4" class="form-section">
                <h2 class="form-section-title">Financement et documents</h2>
                
                <h3 class="form-subtitle">Financement du séjour</h3>
                
                <div class="form-field">
                    <label>Moyen de financement <span class="required">*</span></label>
                    <select name="recouvrement" required>
                        <option value="">-- Sélectionnez --</option>
                        <optgroup label="Par le demandeur">
                            <option value="liquide">Liquide</option>
                            <option value="Chèque voyage">Chèque voyage</option>
                            <option value="Carte crédit">Carte crédit</option>
                            <option value="Hébergement prépayé">Hébergement prépayé</option>
                            <option value="Transport prépayé">Transport prépayé</option>
                            <option value="Autres">Autres</option>
                        </optgroup>
                        <optgroup label="Par un garant">
                            <option value="Entreprise">Entreprise</option>
                            <option value="Organisation">Organisation</option>
                            <option value="Particulier">Particulier</option>
                            <option value="Hébergement fourni">Hébergement fourni</option>
                            <option value="Tous frais financés">Tous frais financés</option>
                            <option value="Transport prépayé">Transport prépayé</option>
                            <option value="Autres">Autres</option>
                        </optgroup>
                    </select>
                </div>
                
                <div class="form-field">
                    <label>Nom et prénom de la personne qui remplit le formulaire</label>
                    <input type="text" name="remplisseur_nom">
                </div>
                
                <h3 class="form-subtitle">Documents obligatoires</h3>
                
                <div class="form-field">
                    <label>Photos d'identité récentes <span class="required">*</span></label>
                    <input type="file" name="identity[]" multiple accept=".jpg,.jpeg,.png" required>
                    <p class="form-help-text">2 photos conformes aux normes OACI (35x45mm, fond clair)</p>
                </div>
                
                <div class="form-field">
                    <label>Copie de la carte d'identité/passeport <span class="required">*</span></label>
                    <input type="file" name="CIN[]" multiple accept=".pdf,.jpg,.jpeg,.png" required>
                    <p class="form-help-text">Recto et verso (sélectionnez les deux fichiers)</p>
                </div>
                
                <div class="form-field">
                    <label>Preuve de ressources financières <span class="required">*</span></label>
                    <input type="file" name="documentsFinanciere[]" multiple accept=".pdf,.jpg,.jpeg,.png" required>
                    <p class="form-help-text">Relevés bancaires, attestation de prise en charge, etc.</p>
                </div>
                
                <div class="form-field">
                    <label>Preuve de paiement <span class="required">*</span></label>
                    <input type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                
                <h3 class="form-subtitle">Documents supplémentaires</h3>
                
                <div class="form-field">
                    <label>Autres documents</label>
                    <input type="file" name="documents[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    <p class="form-help-text">Lettre d'invitation, réservation d'hôtel, etc.</p>
                </div>
                
                <div class="form-navigation">
                    <button type="button" class="prev-btn">Précédent</button>
                    <button type="submit" class="submit-btn">Envoyer la demande</button>
                </div>
            </div>
            <div id="mandat-section" style="display: none; margin: 30px 0; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);">
                <h3 style="text-align: center; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 15px; margin-bottom: 25px;">Approbation du mandat</h3>
                <div id="mandat-preview" style="height: 400px; overflow-y: auto; margin-bottom: 25px; padding: 20px; background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 5px; line-height: 1.6;"></div>
                
                <div class="form-field" style="margin-bottom: 25px;">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" name="approve_mandat" required style="margin-right: 10px; width: auto;"> 
                        <span>Je reconnais avoir pris connaissance du mandat ci-dessus et l'approuve sans réserve</span>
                    </label>
                </div>

                <div class="form-navigation">
                    <button type="button" id="cancel-mandat" class="prev-btn">Retour</button>
                    <button type="submit" id="confirm-submit" class="submit-btn">Confirmer et envoyer</button>
                </div>
            </div>

        <?php else : ?>
            <!-- Champs spécifiques Court Séjour -->
            <!-- Section 1: Informations personnelles -->
            <div id="section-1" class="form-section active">
                <h2 class="form-section-title">Informations personnelles</h2>
                
                <div class="form-field">
                    <label>Nom <span class="required">*</span></label>
                    <input type="text" name="nom" required placeholder="Nom de famille">
                </div>
                
                <div class="form-field">
                    <label>Nom(s) de naissance <span class="required">*</span></label>
                    <input type="text" name="nom_famille" required placeholder="Nom(s) de famille antérieur(s)">
                </div>
                
                <div class="form-field">
                    <label>Prénom(s) <span class="required">*</span></label>
                    <input type="text" name="prenom" required placeholder="Prénom(s) usuel(s)">
                </div>
                
                <div class="form-field">
                    <label>Date de naissance <span class="required">*</span></label>
                    <input type="date" name="date_naissance" required>
                </div>
                
                <div class="form-field">
                    <label>Lieu de naissance <span class="required">*</span></label>
                    <input type="text" name="lieu_naissance" required>
                </div>
                
                <div class="form-field">
                    <label>Pays de naissance <span class="required">*</span></label>
                    <input type="text" name="pays_naissance" required>
                </div>
                
                <div class="form-field">
                    <label>Nationalité actuelle <span class="required">*</span></label>
                    <input type="text" name="Nationalite_actuelle" required>
                </div>
                
                <div class="form-field">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-field">
                    <label>Téléphone <span class="required">*</span></label>
                    <input type="text" name="telephone" required>
                </div>
                
                <h3 class="form-subtitle">État civil</h3>
                
                <div class="form-field">
                    <label>Sexe <span class="required">*</span></label>
                    <select name="sexe" required>
                        <option value="">-- Sélectionnez --</option>
                        <option value="Homme">Homme</option>
                        <option value="Femme">Femme</option>
                    </select>
                </div>
                
                <div class="form-field">
                    <label>État civil <span class="required">*</span></label>
                    <select name="etat_civil" required>
                        <option value="">-- Sélectionnez --</option>
                        <option value="Marié">Marié</option>
                        <option value="Célibataire">Célibataire</option>
                        <option value="Divorcé">Divorcé</option>
                    </select>
                </div>
                
                <h3 class="form-subtitle">Gestion des dossiers groupés</h3>
                
                <div class="form-field">
                    <label>Avez-vous une autre demande en cours de traitement ?</label>
                    <select name="dossier_groupe" id="dossier-groupe-select">
                        <option value="non">Non</option>
                        <option value="oui">Oui</option>
                    </select>
                </div>
                
                <div id="dossier-groupe-info" class="conditional-field">
                    <div class="form-field">
                        <label>Nom inscrit sur la demande principale</label>
                        <input type="text" name="nom_demande_principale" placeholder="Exactement comme inscrit sur l'autre demande">
                    </div>
                    
                    <div class="form-field">
                        <label>Numéro de référence de la demande principale (si connu)</label>
                        <input type="text" name="ref_demande_principale" placeholder="Référence ou numéro de dossier">
                    </div>
                    
                    <p class="form-help-text">Ces informations nous aideront à lier votre demande actuelle à votre dossier principal.</p>
                </div>
                
                <h3 class="form-subtitle">Autorité parentale</h3>
                
                <div class="form-field">
                    <label>Autorité parentale pour les mineurs</label>
                    <input type="text" name="autorite_parietale" placeholder="Père / Mère / Tuteur légal - Nom, Prénom, Adresse">
                </div>
                
                <div class="form-field">
                    <label>Numéro national d'identité (le cas échéant)</label>
                    <input type="text" name="Numero_national_echeant" placeholder="Cas échéant">
                </div>
                
                <div class="form-navigation">
                    <button type="button" class="next-btn">Suivant</button>
                </div>
            </div>

            <!-- Section 2: Document de voyage et informations du demandeur -->
            <div id="section-2" class="form-section">
                <h2 class="form-section-title">Document de voyage et informations professionnelles</h2>
                
                <h3 class="form-subtitle">Document de voyage</h3>
                
                <div class="form-field">
                    <label>Type de document <span class="required">*</span></label>
                    <select name="type_doc_voyage" required>
                        <option value="">-- Sélectionnez --</option>
                        <option value="Passeport ordinaire">Passeport ordinaire</option>
                        <option value="Passeport diplomatique">Passeport diplomatique</option>
                        <option value="Autre document">Autre document</option>
                    </select>
                </div>
                
                <div class="form-field">
                    <label>Numéro du document <span class="required">*</span></label>
                    <input type="text" name="numero_doc_voyage" required>
                </div>
                
                <div class="form-field">
                    <label>Date de délivrance <span class="required">*</span></label>
                    <input type="date" name="date_delivrance_doc_voyage" required>
                </div>
                
                <div class="form-field">
                    <label>Délivré par <span class="required">*</span></label>
                    <input type="text" name="delivre_doc_voyage" required>
                </div>
                
                <h3 class="form-subtitle">Informations professionnelles</h3>
                
                <div class="form-field">
                    <label>Adresse du domicile <span class="required">*</span></label>
                    <input type="text" name="adresse_domicile" required>
                </div>
                
                <div class="form-field">
                    <label>Résidence dans un pays autre que celui de la nationalité actuelle</label>
                    <input type="text" name="residence_etrangere" placeholder="+ numéro autorisation de séjour et date d'expiration">
                </div>
                
                <div class="form-field">
                    <label>Profession actuelle <span class="required">*</span></label>
                    <input type="text" name="profession" required>
                </div>
                
                <div class="form-field">
                    <label>Nom de l'employeur <span class="required">*</span></label>
                    <input type="text" name="nom_employeur" required>
                </div>
                
                <div class="form-field">
                    <label>Adresse de l'employeur <span class="required">*</span></label>
                    <input type="text" name="adresse_employeur" required>
                </div>
                
                <div class="form-field">
                    <label>Téléphone de l'employeur <span class="required">*</span></label>
                    <input type="text" name="telephone_employeur" required>
                </div>
                
                <div class="form-navigation">
                    <button type="button" class="prev-btn">Précédent</button>
                    <button type="button" class="next-btn">Suivant</button>
                </div>
            </div>

            <!-- Section 3: Informations sur le voyage -->
            <div id="section-3" class="form-section">
                <h2 class="form-section-title">Informations sur le voyage</h2>
                
                <div class="form-field">
                    <label>Objet principal du voyage <span class="required">*</span></label>
                    <select name="visa_type" required>
                        <option value="">-- Sélectionnez --</option>
                        <option value="Tourisme">Tourisme</option>
                        <option value="Visite familiale ou amicale">Visite familiale ou amicale</option>
                        <option value="Affaires">Affaires</option>
                        <option value="Formation / Stage de courte durée">Formation / Stage de courte durée</option>
                        <option value="Événement culturel, sportif ou artistique">Événement culturel, sportif ou artistique</option>
                        <option value="Transit aéroportuaire">Transit aéroportuaire (Type A)</option>
                        <option value="Études">Études</option>
                        <option value="Travail">Travail</option>
                        <option value="Regroupement familial">Regroupement familial</option>
                        <option value="Recherche scientifique">Recherche scientifique</option>
                        <option value="Tourisme prolongé">Tourisme prolongé</option>
                        <option value="Soins médicaux">Soins médicaux</option>
                        <option value="Mariage">Mariage avec un ressortissant français</option>
                        <option value="vvt">Visa Vacances-Travail (VVT)</option>
                    </select>
                </div>
                
                <div class="form-field">
                    <label>Informations complémentaires sur l'objet du voyage <span class="required">*</span></label>
                    <textarea name="motif" required></textarea>
                </div>
                
                <div class="form-field">
                    <label>État(s) membre(s) de destination <span class="required">*</span></label>
                    <input type="text" name="etat_membre" placeholder="France pour DROM/CTOM" required>
                </div>
                
                <div class="form-field">
                    <label>Nombre d'entrées demandées <span class="required">*</span></label>
                    <select name="entrees" required>
                        <option value="">-- Sélectionnez --</option>
                        <option value="1">Entrée unique</option>
                        <option value="2">Double entrée</option>
                        <option value="Multiple">Multiples entrées</option>
                    </select>
                </div>
                
                <div class="form-field">
                    <label>Date d'arrivée <span class="required">*</span></label>
                    <input type="date" name="date_arrivee" required>
                </div>
                
                <div class="form-field">
                    <label>Date de départ <span class="required">*</span></label>
                    <input type="date" name="date_depart" required>
                </div>
                
                <div class="form-field">
                    <label>Empreintes digitales relevées précédemment</label>
                    <input type="text" name="empreinte" placeholder="Date + numéro du visa si oui">
                </div>
                
                <div class="form-field">
                    <label>Autorisation d'entrée dans le pays de destination finale</label>
                    <input type="text" name="autorisation_entree" placeholder="Numéro et dates de validité si hors Schengen">
                </div>
                
                <h3 class="form-subtitle">Coordonnées en France</h3>
                
                <div class="form-field">
                    <label>Personne invitante</label>
                    <input type="text" name="nom_invitant" placeholder="Nom complet ou coordonnées d'hôtel">
                </div>
                
                <div class="form-field">
                    <label>Adresse de la personne invitante</label>
                    <textarea name="adresse_invitant" placeholder="Adresse complète"></textarea>
                </div>
                
                <div class="form-field">
                    <label>Organisation/entreprise hôte</label>
                    <textarea name="organisation_hote" placeholder="Nom et adresse complète"></textarea>
                </div>
                
                <div class="form-navigation">
                    <button type="button" class="prev-btn">Précédent</button>
                    <button type="button" class="next-btn">Suivant</button>
                </div>
            </div>

            <!-- Section 4: Financement et documents -->
            <div id="section-4" class="form-section">
                <h2 class="form-section-title">Financement et documents</h2>
                
                <h3 class="form-subtitle">Financement du séjour</h3>
                
                <div class="form-field">
                    <label>Moyen de financement <span class="required">*</span></label>
                    <select name="recouvrement" required>
                        <option value="">-- Sélectionnez --</option>
                        <optgroup label="Par le demandeur">
                            <option value="liquide">Liquide</option>
                            <option value="Chèque voyage">Chèque voyage</option>
                            <option value="Carte crédit">Carte crédit</option>
                            <option value="Hébergement prépayé">Hébergement prépayé</option>
                            <option value="Transport prépayé">Transport prépayé</option>
                            <option value="Autres">Autres</option>
                        </optgroup>
                        <optgroup label="Par un garant">
                            <option value="Entreprise">Entreprise</option>
                            <option value="Organisation">Organisation</option>
                            <option value="Particulier">Particulier</option>
                            <option value="Hébergement fourni">Hébergement fourni</option>
                            <option value="Tous frais financés">Tous frais financés</option>
                            <option value="Transport prépayé">Transport prépayé</option>
                            <option value="Autres">Autres</option>
                        </optgroup>
                    </select>
                </div>
                
                <div class="form-field">
                    <label>Nom et prénom de la personne qui remplit le formulaire</label>
                    <input type="text" name="remplisseur_nom">
                </div>
                
                <h3 class="form-subtitle">Documents obligatoires</h3>
                
                <div class="form-field">
                    <label>Photos d'identité récentes <span class="required">*</span></label>
                    <input type="file" name="identity[]" multiple accept=".jpg,.jpeg,.png" required>
                    <p class="form-help-text">2 photos conformes aux normes OACI (35x45mm, fond clair)</p>
                </div>
                
                <div class="form-field">
                    <label>Copie de la carte d'identité/passeport <span class="required">*</span></label>
                    <input type="file" name="CIN[]" multiple accept=".pdf,.jpg,.jpeg,.png" required>
                    <p class="form-help-text">Recto et verso (sélectionnez les deux fichiers)</p>
                </div>
                
                <div class="form-field">
                    <label>Preuve de ressources financières <span class="required">*</span></label>
                    <input type="file" name="documentsFinanciere[]" multiple accept=".pdf,.jpg,.jpeg,.png" required>
                    <p class="form-help-text">Relevés bancaires, attestation de prise en charge, etc.</p>
                </div>
                
                <div class="form-field">
                    <label>Preuve de paiement <span class="required">*</span></label>
                    <input type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                
                <h3 class="form-subtitle">Documents supplémentaires</h3>
                
                <div class="form-field">
                    <label>Autres documents</label>
                    <input type="file" name="documents[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    <p class="form-help-text">Lettre d'invitation, réservation d'hôtel, etc.</p>
                </div>
                
                <div class="form-navigation">
                    <button type="button" class="prev-btn">Précédent</button>
                    <button type="submit" class="submit-btn">Envoyer la demande</button>
                </div>
            </div>
            <div id="mandat-section" style="display: none; margin: 30px 0; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);">
                <h3 style="text-align: center; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 15px; margin-bottom: 25px;">Approbation du mandat</h3>
                <div id="mandat-preview" style="height: 400px; overflow-y: auto; margin-bottom: 25px; padding: 20px; background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 5px; line-height: 1.6;"></div>
                
                <div class="form-field" style="margin-bottom: 25px;">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" name="approve_mandat" required style="margin-right: 10px; width: auto;"> 
                        <span>Je reconnais avoir pris connaissance du mandat ci-dessus et l'approuve sans réserve</span>
                    </label>
                </div>

                <div class="form-navigation">
                    <button type="button" id="cancel-mandat" class="prev-btn">Retour</button>
                    <button type="submit" id="confirm-submit" class="submit-btn">Confirmer et envoyer</button>
                </div>
            </div>
        <?php endif; ?>
    </form>
    

    
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('visa_request_form', 'visa_form_shortcode');
