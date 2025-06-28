<?php

function visa_form_shortcode()
{
    // Récupérer les données du cookie si elles existent
    $form_data = isset($_COOKIE['visa_form_data']) ? json_decode(stripslashes($_COOKIE['visa_form_data']), true) : array();

    ob_start();

    // pour horloge et date
    visa_render_datetime_block();


    // Étape 1: Email et mot de passe
    if (!isset($_POST['email'])) {
        render_email_password_form($form_data);
        return ob_get_clean();
    }
    // Sauvegarder les données après la première étape
    $form_data = array_merge($form_data, $_POST);
    save_form_data_to_cookie($form_data);

    // Étape 2: Sélection du type de visa et ville de dépôt
    if (!isset($_POST['visa_type'])) {
        render_visa_type_and_city_selection();
        return ob_get_clean();
    }
    // Sauvegarder les données après la deuxième étape
    $form_data = array_merge($form_data, $_POST);
    save_form_data_to_cookie($form_data);

    // Étape 3: Formulaire spécifique au type de visa
    echo '<div class="visa-form-wrapper">';
    render_visa_form($_POST['visa_type']);
    render_form_styles();
    render_form_scripts();
    echo '</div>';

    return ob_get_clean();
}
// Fonction pour entrer le formulaire d'email et mot de passe
add_shortcode('visa_request_form', 'visa_form_shortcode');

/**
 * Petit bloc d’affichage date + horloge.
 * Appelé au début de visa_form_shortcode().
 */
function visa_render_datetime_block()
{
    $max_daily_requests = get_option('vm_max_daily_requests', 50);
    $today_requests = get_option('vm_today_requests', 0);
    $remaining_requests = max(0, $max_daily_requests - $today_requests);
    ?>
    <div id="visa-datetime-container">
        <span id="visa-date"></span>
        <span id="visa-clock"></span>
        <div id="visa-requests-counter" style="margin-top: 8px; font-size: 14px; color: <?php echo ($remaining_requests > 0) ? '#21555e' : '#e74c3c'; ?>">
            <?php
            if ($remaining_requests > 0) {
                echo "Il reste $remaining_requests demandes pouvant être acceptées aujourd'hui";
            } else {
                echo "Le quota de demandes pour aujourd'hui est atteint";
            }
    ?>
        </div>
    </div>

    <style>
        /* Style minimal - modifie à ton goût */
        #visa-datetime-container{
            font-family:Arial, sans-serif;
            text-align:center;
            padding:10px;
            background:#f3f3f3;
            border:1px solid #ccc;
            width:max-content;
            margin:20px auto 25px;   /* 25 px de marge en bas pour respirer */
            border-radius:8px;
            box-shadow:0 0 10px rgba(0,0,0,.1);
        }
        #visa-date{
            font-size:18px;
            color:#333;
        }
        #visa-clock{
            font-size:24px;
            font-weight:bold;
            color:#007BFF;
            margin-left:6px;
        }
        #visa-requests-counter {
            font-weight: bold;
        }
    </style>

    <script>
        /*   Met à jour toutes les secondes   */
        (function(){
            function updateDateTime(){
                const now = new Date();
                const options = {weekday:'long',year:'numeric',month:'long',day:'numeric'};
                document.getElementById('visa-date').textContent  = now.toLocaleDateString('fr-FR', options);
                document.getElementById('visa-clock').textContent = now.toLocaleTimeString('fr-FR');
            }
            document.addEventListener('DOMContentLoaded', () => {
                updateDateTime();
                setInterval(updateDateTime, 1000);
            });
        })();
    </script>
<?php }

function render_days_calculator()
{
    $max_days_allowed = get_option('vm_max_schengendays', 90);

    ?>
    <div class="form-field" id="days-calculator">
        <label><span class="numero">34. </span>Calculateur de jours autorisés</label>
        <div style="display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <label for="entry-date" style="display: block; margin-bottom: 5px;">Date d'entrée</label>
                <input type="date" id="entry-date" class="regular-text" required>
            </div>
            <div style="flex: 1; min-width: 200px;">
                <label for="exit-date" style="display: block; margin-bottom: 5px;">Date de sortie</label>
                <input type="date" id="exit-date" class="regular-text" required>
            </div>
            <div style="display: flex; align-items: flex-end; min-width: 120px;">
                <button type="button" id="calculate-days" class="button button-primary">Calculer</button>
            </div>
        </div>
        <div id="days-result" style="padding: 10px; background: #f5f5f5; border-radius: 4px; display: none; margin-top: 10px;">
            <strong>Résultat :</strong> <span id="calculated-days">0</span> jours (maximum autorisé: <?php echo $max_days_allowed; ?> jours)
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const calculateBtn = document.getElementById('calculate-days');
        const entryDateInput = document.getElementById('entry-date');
        const exitDateInput = document.getElementById('exit-date');
        const resultDiv = document.getElementById('days-result');
        const calculatedDaysSpan = document.getElementById('calculated-days');
        const maxDaysAllowed = <?php echo $max_days_allowed; ?>;
        
        // Set minimum date to today for entry date
        entryDateInput.min = new Date().toISOString().split('T')[0];
        
        // When entry date changes, update exit date min
        entryDateInput.addEventListener('change', function() {
            exitDateInput.min = this.value;
        });
        
        calculateBtn.addEventListener('click', function() {
            const entryDate = new Date(entryDateInput.value);
            const exitDate = new Date(exitDateInput.value);

            if (!entryDateInput.value || isNaN(entryDate.getTime())) {
                alert('Veuillez entrer une date d\'entrée valide');
                return;
            }
            
            if (!exitDateInput.value || isNaN(exitDate.getTime())) {
                alert('Veuillez entrer une date de sortie valide');
                return;
            }
            
            if (exitDate <= entryDate) {
                alert('La date de sortie doit être après la date d\'entrée');
                return;
            }
            
            const diffTime = Math.abs(exitDate - entryDate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
            
            calculatedDaysSpan.textContent = diffDays;
            resultDiv.style.display = 'block';
            
            if (diffDays > maxDaysAllowed) {
                resultDiv.style.color = 'red';
                resultDiv.style.fontWeight = 'bold';
                alert('Attention : Vous dépassez la durée maximale autorisée de ' + maxDaysAllowed + ' jours pour un visa court séjour Schengen');
            } else {
                resultDiv.style.color = 'green';
                resultDiv.style.fontWeight = 'normal';
            }
        });
    });
    </script>
    <?php
}
// Fonction pour afficher le formulaire d'email et mot de passe
function render_email_password_form($form_data = array())
{
    $email = $form_data['email'] ?? '';
    $password = $form_data['password'] ?? '';
    ?>
    <style>
        .visa-form-wrapper {
            max-width: 500px;
            margin: 30px auto;
            padding: 25px;
            background: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            font-family: Arial, sans-serif;
        }
        .visa-form-wrapper form {
            display: flex;
            flex-direction: column;
        }
        .form-field {
            margin-bottom: 20px;
        }
        .form-field label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        .form-field .required {
            color: red;
        }
        .form-field input {
            width: 100%;
            padding: 10px 12px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 6px;
            transition: border-color 0.3s;
        }
        .form-field input:focus {
            border-color: #0073aa;
            outline: none;
        }
        .form-navigation {
            text-align: right;
        }
        .next-btn {
            background-color: #0073aa;
            color: #fff;
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .next-btn:hover {
            background-color: #005d8f;
        }
    </style>

    <div class="visa-form-wrapper">
        <form method="post">
            <div class="form-field">
                <h2 style="font-size: 20px;">Veillez remplir les informations suivant</h2>
                <label>Adresse email de la demande <span class="required">*</span></label>
                <input type="email" name="email" required>
            </div>
            <div class="form-field">
                <label>Mot de passe <span class="required">*</span></label>
                <input type="password" name="password" required>
            </div>
            <div class="form-navigation">
                <button type="submit" class="next-btn">Continuer</button>
            </div>
        </form>
    </div>
    <?php
}
// Ajouter cette fonction pour sauvegarder les données dans un cookie
function save_form_data_to_cookie($data)
{
    if (!headers_sent()) {
        $json_data = json_encode($data);
        setcookie('visa_form_data', $json_data, time() + 3600 * 24 * 1, '/', '', false, true);
        $_COOKIE['visa_form_data'] = $json_data; // Pour accès immédiat
    } else {
        error_log('Impossible de définir le cookie - headers déjà envoyés');
    }
}

// Fonction pour afficher la sélection du type de visa
function render_visa_type_and_city_selection()
{
    ?>
    <style>
        .visa-form-wrapper {
            max-width: 500px;
            margin: 30px auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            font-family: Arial, sans-serif;
        }

        .visa-form-wrapper .form-field {
            margin-bottom: 20px;
        }

        .visa-form-wrapper label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .visa-form-wrapper input,
        .visa-form-wrapper select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        .visa-form-wrapper .required {
            color: red;
        }

        .visa-form-wrapper .form-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .visa-form-wrapper .next-btn,
        .visa-form-wrapper .prev-btn {
            padding: 10px 20px;
            background-color: #0066cc;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .visa-form-wrapper .prev-btn {
            background-color: #777;
        }

        .visa-form-wrapper .next-btn:hover,
        .visa-form-wrapper .prev-btn:hover {
            opacity: 0.9;
        }

        .numero {
            color: gray;
            font-size: small;
        }
    </style>

    <div class="visa-form-wrapper">
        <form method="post">
            <input type="hidden" name="email" value="<?= esc_attr($_POST['email']) ?>">
            <input type="hidden" name="password" value="<?= esc_attr($_POST['password']) ?>">
            <div class="form-field">

                <label>Choisissez un type de visa : <span class="required">*</span></label>
                <select name="visa_type" required>
                    <option value="">-- Sélectionner --</option>
                    <option value="long_sejour">Visa Long Séjour (+ de 90 jours maximum)</option>
                    <option value="court_sejour">Visa Court Séjour / VTA (90 jours maximum)</option>
                </select>
            </div>
            
            <div class="form-field">
                <label>Ville de dépôt du dossier : <span class="required">*</span></label>
                <select name="depot_ville" required>
                    <option value="">-- Sélectionner votre Wilaya --</option>

                    <optgroup label="Alger">
                        <option value="Alger - 01 - Adrar">01 - Adrar</option>
                        <option value="Alger - 05 - Batna">05 - Batna</option>
                        <option value="Alger - 06 - Béjaïa">06 - Béjaïa</option>
                        <option value="Alger - 07 - Biskra">07 - Biskra</option>
                        <option value="Alger - 08 - Béchar">08 - Béchar</option>
                        <option value="Alger - 09 - Blida">09 - Blida</option>
                        <option value="Alger - 10 - Bouira">10 - Bouira</option>
                        <option value="Alger - 11 - Tamanrasset">11 - Tamanrasset</option>
                        <option value="Alger - 15 - Tizi Ouzou">15 - Tizi Ouzou</option>
                        <option value="Alger - 16 - Alger">16 - Alger</option>
                        <option value="Alger - 17 - Djelfa">17 - Djelfa</option>
                        <option value="Alger - 26 - Médéa">26 - Médéa</option>
                        <option value="Alger - 28 - M’Sila">28 - M’Sila</option>
                        <option value="Alger - 30 - Ouargla">30 - Ouargla</option>
                        <option value="Alger - 32 - El Bayadh">32 - El Bayadh</option>
                        <option value="Alger - 33 - Illizi">33 - Illizi</option>
                        <option value="Alger - 35 - Boumerdès">35 - Boumerdès</option>
                        <option value="Alger - 42 - Tipaza">42 - Tipaza</option>
                        <option value="Alger - 47 - Ghardaïa">47 - Ghardaïa</option>
                        <option value="Alger - 49 - El Oued">49 - El Oued</option>
                        <option value="Alger - 50 - El M’Ghaier">50 - El M’Ghaier</option>
                        <option value="Alger - 51 - El Meniaa">51 - El Meniaa</option>
                        <option value="Alger - 52 - Ouled Djellal">52 - Ouled Djellal</option>
                        <option value="Alger - 53 - Béni Abbès">53 - Béni Abbès</option>
                        <option value="Alger - 54 - Timimoun">54 - Timimoun</option>
                        <option value="Alger - 55 - Bordj Badji Mokhtar">55 - Bordj Badji Mokhtar</option>
                        <option value="Alger - 56 - Djanet">56 - Djanet</option>
                        <option value="Alger - 57 - In Salah">57 - In Salah</option>
                        <option value="Alger - 58 - In Guezzam">58 - In Guezzam</option>
                    </optgroup>

                    <optgroup label="Annaba">
                        <option value="Annaba - 12 - Tébessa">12 - Tébessa</option>
                        <option value="Annaba - 21 - Skikda">21 - Skikda</option>
                        <option value="Annaba - 23 - Annaba">23 - Annaba</option>
                        <option value="Annaba - 24 - Guelma">24 - Guelma</option>
                        <option value="Annaba - 36 - El Tarf">36 - El Tarf</option>
                        <option value="Annaba - 41 - Souk Ahras">41 - Souk Ahras</option>
                    </optgroup>

                    <optgroup label="Constantine">
                        <option value="Constantine - 04 - Oum El Bouaghi">04 - Oum El Bouaghi</option>
                        <option value="Constantine - 07 - Biskra">07 - Biskra</option>
                        <option value="Constantine - 18 - Jijel">18 - Jijel</option>
                        <option value="Constantine - 19 - Sétif">19 - Sétif</option>
                        <option value="Constantine - 25 - Constantine">25 - Constantine</option>
                        <option value="Constantine - 40 - Khenchela">40 - Khenchela</option>
                        <option value="Constantine - 43 - Mila">43 - Mila</option>
                        <option value="Constantine - 52 - Ouled Djellal">52 - Ouled Djellal</option>
                    </optgroup>

                    <optgroup label="Oran">
                        <option value="Oran - 02 - Chlef">02 - Chlef</option>
                        <option value="Oran - 03 - Laghouat">03 - Laghouat</option>
                        <option value="Oran - 13 - Tlemcen">13 - Tlemcen</option>
                        <option value="Oran - 14 - Tiaret">14 - Tiaret</option>
                        <option value="Oran - 20 - Saïda">20 - Saïda</option>
                        <option value="Oran - 22 - Sidi Bel Abbès">22 - Sidi Bel Abbès</option>
                        <option value="Oran - 27 - Mostaganem">27 - Mostaganem</option>
                        <option value="Oran - 29 - Mascara">29 - Mascara</option>
                        <option value="Oran - 31 - Oran">31 - Oran</option>
                        <option value="Oran - 34 - Relizane">34 - Relizane</option>
                        <option value="Oran - 39 - Tindouf">39 - Tindouf</option>
                        <option value="Oran - 44 - Naâma">44 - Naâma</option>
                        <option value="Oran - 45 - Aïn Defla">45 - Aïn Defla</option>
                        <option value="Oran - 46 - Aïn Témouchent">46 - Aïn Témouchent</option>
                        <option value="Oran - 53 - Béni Abbès">53 - Béni Abbès</option>
                        <option value="Oran - 54 - Timimoun">54 - Timimoun</option>
                        <option value="Oran - 55 - Bordj Badji Mokhtar">55 - Bordj Badji Mokhtar</option>
                    </optgroup>

                </select>
            </div>
            
            <div class="form-navigation">
                <button type="button" class="prev-btn" onclick="history.back()">Retour</button>
                <button type="submit" class="next-btn">Continuer</button>
            </div>
        </form>
    </div>
    <?php
}


// Fonction pour afficher le formulaire en fonction du type de visa
function render_visa_form($visa_type)
{
    ?>
    <form action="<?= esc_url(admin_url('admin-post.php')) ?>" method="post" enctype="multipart/form-data" id="visa-request-form">
        <?php wp_nonce_field('vm_visa_form', 'vm_nonce'); ?>
        <input type="hidden" name="action" value="submit_visa_request">
        <input type="hidden" name="visa_type" value="<?= esc_attr($visa_type); ?>">
        <input type="hidden" name="email" value="<?= esc_attr($_POST['email']) ?>">
        <input type="hidden" name="password" value="<?= esc_attr($_POST['password']) ?>">
        <input type="hidden" name="depot_ville" value="<?= esc_attr($_POST['depot_ville']) ?>">

        <?php
        if ($visa_type === 'long_sejour') {
            render_long_sejour_form();
        } elseif ($visa_type === 'court_sejour') {
            render_court_sejour_form();
        } else {
            echo '<p>Type de visa non valide.</p>';
        }
    ?>
    </form>
    <?php
}

// Fonction pour le formulaire de visa long séjour
function render_long_sejour_form()
{
    // Section 1: Informations personnelles
    render_form_section('section-1', 'Informations personnelles', true, function () {
        render_personal_info_fields_long();
        render_marital_status_fields_long();
        render_grouped_files_fields();
        render_parental_authority_fields_long();
        render_travel_document_fields();
    });

    // Section 2: Document de voyage et informations professionnelles
    render_form_section('section-2', 'Document de voyage et informations professionnelles', false, function () {
        render_professional_adresse_fields_long();
        render_professional_info_fields_long();
        render_travel_info_fields();
        render_france_contact_fields();
    });

    // Section 3: Documents
    render_form_section('section-3', 'Documents', false, function () {
        render_required_documents_fields();
        render_additional_documents_fields();
        render_acceptation();
    });

    // Section Mandat
    render_mandat_section();
}

// Fonction pour le formulaire de visa court séjour

function render_court_sejour_form()
{
    // Section 1: Informations personnelles
    render_form_section('section-1', 'Informations personnelles', true, function () {
        render_personal_info_fields_court();
        render_marital_status_fields_court();
        render_parental_authority_fields_court();
        render_travel_document_fields();
    });

    // Section 2: Document de voyage et informations professionnelles
    render_form_section('section-2', 'Document de voyage et informations professionnelles', false, function () {
        render_parental_resortisants_fields();
        render_professional_adresse_fields_court();
        render_professional_info_fields_court();
        render_voyage_information();
        render_form_information_cours_sejour();
        render_notifications_section();
    });

    // Section 3: Financement et documents
    render_form_section('section-3', 'Documents', false, function () {
        render_required_documents_fields();
        render_additional_documents_fields();
    });
    // Section Mandat
    render_mandat_section();
}


// numero 1 : Fonction pour les champs d'informations personnelles
function render_personal_info_fields_long()
{
    ?>
    <div class="form-field">
        <label><span class="numero">1. </span>Nom <span class="required">*</span></label>
        <input type="text" name="nom" required placeholder="Nom de famille">
    </div>
    <div class="form-field">
        <label><span class="numero">2. </span>Nom à la naissance</label>
        <input type="text" name="nom_famille" required placeholder="Nom(s) de famille antérieur(s)">
    </div>
    <div class="form-field">
        <label><span class="numero">3. </span>Prénom(s) <span class="required">*</span></label>
        <input type="text" name="prenom" required placeholder="Nom(s) usuel(s)">
    </div>
    <div class="form-field">
        <label><span class="numero">4. </span>Date de naissance <span class="required">*</span></label>
        <input type="date" name="date_naissance" required>
    </div>
    <div class="form-field">
        <label><span class="numero">5. </span>Lieu de naissance <span class="required">*</span></label>
        <input type="text" name="lieu_naissance" required>
    </div>
    <div class="form-field">
        <label><span class="numero">6. </span>Pays de naissance <span class="required">*</span></label>
        <select name="pays_naissance" class="select-pays" required>
            <option value="">-- Sélectionnez un pays --</option>
        </select>
    </div>

    <div class="form-field">
        <label><span class="numero">7. </span>Nationalité actuelle <span class="required">*</span></label>
        <select name="Nationalite_actuelle" class="select-nationalite" required>
           <option value="">-- Sélectionnez un pays --</option>
        </select>
    </div>
    <div class="form-field">
        <label>Nationalité à la naissance si différente</label>
        <input type="text" name="nationalite_naissance">
    </div>
    
    <?php
}

function render_personal_info_fields_court()
{
    ?>
    <div class="form-field">
        <label><span class="numero">1. </span>Nom <span class="required">*</span></label>
        <input type="text" name="nom" required placeholder="Nom de famille">
    </div>
    <div class="form-field">
        <label><span class="numero">2. </span>Nom à la naissance</label>
        <input type="text" name="nom_famille" required placeholder="Nom(s) de famille antérieur(s)">
    </div>
    <div class="form-field">
        <label><span class="numero">3. </span>Prénom(s) <span class="required">*</span></label>
        <input type="text" name="prenom" required placeholder="Nom(s) usuel(s)">
    </div>
    <div class="form-field">
        <label><span class="numero">4. </span>Date de naissance <span class="required">*</span></label>
        <input type="date" name="date_naissance" required>
    </div>
    <div class="form-field">
        <label><span class="numero">5. </span>Lieu de naissance <span class="required">*</span></label>
        <input type="text" name="lieu_naissance" required>
    </div>
    <div class="form-field">
        <label><span class="numero">6. </span>Pays de naissance <span class="required">*</span></label>
        <select name="pays_naissance" class="select-pays" required>
            <option value="">-- Sélectionnez un pays --</option>
        </select>
    </div>
    <div class="form-field">
        <label><span class="numero">7. </span>Nationalité actuelle <span class="required">*</span></label>
        <select name="Nationalite_actuelle" class="select-nationalite" required>
            <option value="">-- Sélectionnez une nationalité --</option>
        </select>
    </div>
    <div class="form-field">
        <label>Nationalité à la naissance si différente</label>
        <input type="text" name="nationalite_naissance">
    </div>
    <div class="form-field">
        <label>Autre(s) nationalité(s) :</label>
        <input type="text" name="autre_nationalite">
    </div>
    
    <?php
}

// numero 2  Fonction pour les champs d'état civil
function render_marital_status_fields_long()
{
    ?>
    <div class="form-field">
        <label><span class="numero">8. </span>Sexe <span class="required">*</span></label>
        <select name="sexe" required>
            <option value="">-- Sélectionnez --</option>
            <option value="Masculin">Homme</option>
            <option value="Féminin">Féminin</option>
            <option value="Autre">Autre</option>
        </select>
    </div>
    
    <div class="form-field">
        <label><span class="numero">9. </span>État civil <span class="required">*</span></label>
        <select name="etat_civil" required>
            <option value="">-- Sélectionnez --</option>
            <option value="Marié(e)"> Marié(e) </option>
            <option value="Célibataire">Célibataire</option>
            <option value="Divorcé(e)">Divorcé(e)</option>
            <option value="Séparé(e)">Séparé(e) </option>
            <option value="Veuf(ve)">Veuf(ve) </option>
            <option value="Autre">Autre (veuillez préciser)</option>
        </select>
    </div>
    <?php
}

function render_marital_status_fields_court()
{
    ?>
    <div class="form-field">
        <label><span class="numero">8. </span>Sexe <span class="required">*</span></label>
        <select name="sexe" required>
            <option value="">-- Sélectionnez --</option>
            <option value="Homme">Homme</option>
            <option value="Femme">Femme</option>
            <option value="Autre">Autre</option>
        </select>
        <input type="text" name="sexe_autre" placeholder="Précisez votre sexe" style="display: none; margin-top: 5px; width: 100%;">
    </div>
        
    <div class="form-field">
        <label><span class="numero">9. </span>État civil <span class="required">*</span></label>
        <select name="etat_civil" required>
            <option value="">-- Sélectionnez --</option>
            <option value="Marié(e)"> Marié(e) </option>
            <option value="Célibataire">Célibataire</option>
            <option value="Divorcé(e)">Divorcé(e)</option>
            <option value="Séparé(e)">Séparé(e) </option>
            <option value="Veuf(ve)">Veuf(ve) </option>
            <option value="Partenariat enregistré">Partenariat enregistré</option>
            <option value="Autre">Autre (veuillez préciser)</option>
        </select>
        <input type="text" name="etat_civil_autre" placeholder="Précisez votre état civil" style="display: none; margin-top: 5px; width: 100%;">
    </div>
    <?php
}

// numero 3 long s Fonction pour les champs de fichiers groupés
function render_grouped_files_fields()
{
    ?>
    <div class="form-field">
        <label>Faites-vous partie d'un dossier groupé ? <span class="required">*</span></label>
        <select name="dossier_groupe" id="dossier-groupe-select" required>
            <option value="">-- Sélectionnez --</option>
            <option value="oui">Oui</option>
            <option value="non">Non</option>
        </select>
    </div>
    <div id="dossier-groupe-info" style="display: none;">
        <div class="form-field">
            <label>Nom du groupe <span class="required">*</span></label>
            <input type="text" name="nom_groupe">
        </div>
        <div class="form-field">
            <label>Nombre de personnes dans le groupe <span class="required">*</span></label>
            <input type="number" name="nombre_personnes_groupe" min="1">
        </div>
    </div>
    <?php
}

// numero 4 longS Fonction pour les champs d'autorité parentale
function render_parental_authority_fields_long()
{
    ?>
    <div class="form-field">
        <label for="autorite_parentale"><span class="numero">10. </span>Pour les mineurs : Nom, prénom, adresse (si différente de celle du demandeur) et nationalité de l'autorité parentale/du tuteur légal</label>
        <textarea name="nationalite_autorite_parentale"></textarea>
    </div>
    
    <?php
}

function render_parental_authority_fields_court()
{
    ?>
    <div class="form-field">
        <label for="autorite_parentale"><span class="numero">10. </span>Autorité parentale (pour les mineurs)/tuteur légal ( nom, prénom, adresse (si différente de celle du demandeur), numéro de téléphone, adresse électronique et nationalité) :</label>
        <textarea name="nationalite_autorite_parentale"></textarea>
    </div>
    
    <?php
}

// numero 5 longS Fonction pour les champs de document de voyage
function render_travel_document_fields()
{
    ?>
    <div class="form-field">
        <label><span class="numero">11. </span>Numéro national d'identité (le cas échéant)</label>
        <input type="text" name="Numero_national_echeant" placeholder="Cas échéant">
    </div>
    <div class="form-field">
        <label><span class="numero">12. </span>Type du document de voyage <span class="required">*</span></label>
        <select name="type_doc_voyage" required>
            <option value="">-- Sélectionnez --</option>
            <option value="Passeport diplomatique">Passeport diplomatique</option>
            <option value="Passeport ordinaire">Passeport ordinaire</option>
            <option value="Passeport officiel">Passeport officiel</option>
            <option value=" Passeport de service"> Passeport de service</option>
            <option value="Passeport spécial">Passeport spécial</option>
            <option value="Autre document de voyage">Autre document de voyage (à préciser) :</option>
        </select>
        <input type="text" name="type_doc_voyage_autre" placeholder="Précisez si autre" style="display:none; margin-top:5px;">
    </div>
    <div class="form-field">
        <label><span class="numero">13. </span>Numéro du document de voyage <span class="required">*</span></label>
        <input type="text" name="numero_doc_voyage" required>
    </div>
    <div class="form-field">
        <label><span class="numero">14. </span>Date de délivrance <span class="required">*</span></label>
        <input type="date" name="date_delivrance_doc_voyage" required>
    </div>
    <div class="form-field">
        <label><span class="numero">15. </span>Date de d'expiration <span class="required">*</span></label>
        <input type="date" name="date_expiration_doc_voyage" required>
    </div>
    
    <div class="form-field">
        <label><span class="numero">16. </span>Délivré par (pays) : <span class="required">*</span></label>
        <select name="delivre_doc_voyage" class="select-pays" required>
            <option value="">-- Sélectionnez un pays --</option>
        </select>
    </div>
    <?php
}

// numero 6 longSFonction pour les champs d'informations professionnelles
function render_professional_adresse_fields_long()
{
    ?>
    <div class="form-field">
        <label for="adresse"><span class="numero">17. </span>Adresse du domicile (n°, rue, ville, code postal, pays)</label>
        <textarea name="adresse"></textarea>
    </div>
    <div class="form-field">
        <label><span class="numero">18. </span>Adresse électronique<span class="required">*</span></label>
        <input type="email" name="email" required value="<?= esc_attr($_POST['email'] ?? '') ?>">
    </div>
    <div class="form-field">
        <label><span class="numero">19. </span>Numéro(s) de téléphone <span class="required">*</span></label>
        <input type="tel" name="telephone" required pattern="00\.213\.\d{3}\.\d{2}\.\d{2}\.\d{2}" placeholder="00.213.xxx.xx.xx.xx">
        <small>Format attendu : 00.213.xxx.xx.xx.xx</small>
    </div>

    <div class="form-field">
        <label for=""><span class="numero">20. </span>En cas de résidence dans un pays autre que celui de la nationalité actuelle, veuillez indiquer :</label>
    </div>
    <div class="form-field">
        <label> Numéro du titre de séjour </label>
        <input type="text" name="numero_titre_sejour" >
    </div>
    <div class="form-field">
        <label> Date de delivrance </label>
        <input type="date" name="date_delivrance_titre_sejour">
    </div>
    <div class="form-field">
        <label> Date d'expiration </label>
        <input type="date" name="date_expiration_titre_sejour" >
    </div>
    <?php
}

function render_professional_adresse_fields_court()
{
    ?>
    <div class="form-field">
        <label for=""><span class="numero">19. </span>Adresse du domicile et adresse électronique du demandeur :</label>
        <textarea name="adresse_demandeur"></textarea>
    </div>
    <div class="form-field">
        <label>Numéro de téléphone</label>
        <input type="text" name="telephone_demandeur">
    </div>

    <?php
}

// numero 7 longSFonction pour les champs d'informations professionnelles
function render_professional_info_fields_long()
{
    ?>
    <div class="form-field">
        <label><span class="numero">21. </span>Activité professionnelle actuelle <span class="required">*</span></label>
        <input type="text" name="profession" required>
    </div>
    <div class="form-field">
        <label for=""><span class="numero">22. </span>Employeur (Nom, adresse, courriel, n° téléphone) - Pour les étudiants, nom et adresse de l'établissement d'enseignement</label>
        <input type="text" name="employeur" required>
    </div>
    <?php
}

function render_professional_info_fields_court()
{
    ?>
    <div class="form-field">
        <label><span class="numero">20. </span>Résidence dans un pays autre que celui de la nationalité actuelle :</label>
        <label style="display: flex;"><input type="radio" name="residence_autre_pays" value="non" style=" width: 10%;"> Non</label>
        <label style="display: flex;"><input type="radio" name="residence_autre_pays" value="oui" style=" width: 10%;"> Oui : Titre de séjour ou équivalent</label>
        <div>
            <label>N°</label>
            <input type="text" name="residence_autre_pays_numero">
            <label>Valide jusqu’au</label>
            <input type="date" name="residence_autre_pays_validité">
        </div>
    </div>
    <div class="form-field">
        <label><span class="numero">21. </span>Activité professionnelle actuelle <span class="required">*</span></label>
        <input type="text" name="profession" required>
    </div>
    <div class="form-field">
        <label for=""><span class="numero">22. </span>Nom, adresse et numéro de téléphone de l’employeur. Pour les étudiants, adresse de l’établissement d’enseignement :<span class="required">*</span></label>
        <textarea name="employeur" required></textarea>
    </div>
    <?php
}

//numero 8 longS  Fonction pour les champs d'informations sur le voyage
function render_travel_info_fields()
{
    ?>
    <div class="form-field">
        <label><span class="numero">23. </span>Je sollicite un visa pour le motif suivant : <span class="required">*</span></label>
        <select name="motif_visa_type" required>
            <option value="">-- Sélectionnez --</option>
            <option value="Activité professionnelle ">Activité professionnelle </option> 
            <option value="Etablissement familial">Etablissement familial</option>
            <option value="Prise de fonctions officielles">Prise de fonctions officielles</option>
            <option value="Etudes">Etudes</option>
            <option value="Etablissement privé/Visiteur">Etablissement privé/Visiteur</option>
            <option value="Stage/formation">Stage/formation</option>
            <option value="Mariage">Mariage</option>
            <option value="Raison médicale">Raison médicale</option>
            <option value="Visa de retour">Visa de retour</option>
            <option value="Autre">Autre (à préciser)</option>
        </select>
        <input type="text" name="motif_visa_type_autre" placeholder="Si autre">
    </div>
    <?php
}

// numero 9 LongS Fonction pour les champs de coordonnées en France
function render_france_contact_fields()
{
    ?>
    <div class="form-field">
        <label><span class="numero">24. </span>Nom, adresse, courriel et n° téléphone en France de l'employeur / de l'établissement d'accueil / du membre de famille invitant, ...etc</label>
        <textarea name="nom_invitant"></textarea>
    </div>
    <div class="form-field">
        <label><span class="numero">25. </span>Quelle sera votre adresse en France pendant votre séjour ?</label>
        <textarea name="adresse_france" placeholder="Adresse complète"></textarea>
    </div>
    <div class="form-field">
        <label><span class="numero">26. </span>Date d'entrée prévue sur le territoire de la France, ou dans l'espace Schengen en cas de transit (jour-mois-année)</label>
        <input type="date" name="date_entree_prevue" >
    </div>
    <div class="form-field">
        <label><span class="numero">27. </span>Durée prévue du séjour sur le territoire de la France</label>
        <select name="duree_prevue_sejour" required>
            <option value="">-- Sélectionnez --</option>
            <option value="Entre 3 et 6 mois">Entre 3 et 6 mois</option>
            <option value="Entre 6 mois et un an">Entre 6 mois et un an</option>
            <option value="Supérieure à un an">Supérieure à un an</option>
        </select>
    </div>
    <div class="form-field">
        <label><span class="numero">28. </span>Si vous comptez effectuer ce séjour avec des membres de votre famille, veuillez indiquer :</label>
    </div>

    <table id="famille-table">
        <thead>
            <tr>
                <th>Lien de parenté</th>
                <th>Nom(s), prénom(s)</th>
                <th>Date de naissance</th>
                <th>Nationalité</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="famille-body">
            <tr>
                <td><input type="text" name="Lien_parente[]"></td>
                <td><input type="text" name="nom_prenoms_lien_parente[]"></td>
                <td><input type="date" name="date_naissance_lien_parente[]"></td>
                <td><input type="text" name="Nationalite_lien_parente[]"></td>
                <td><button type="button" onclick="removeRow(this)">Supprimer</button></td>
            </tr>
        </tbody>
    </table>

    <button type="button" onclick="addRow()">Ajouter un membre</button>

    <div class="form-field">
        <label><span class="numero">29. </span>Quels seront vos moyens d'existence en France ?</label>
        <textarea name="moyens_dexistence"></textarea>
    </div>
    <div class="form-field">
        <label>Serez-vous titulaire d'une bourse ?</label>
        <label style="display: flex;"><input type="radio" name="bourse" value="oui" style="width: 10%;"> Oui</label>
        <label style="display: flex;"><input type="radio" name="bourse" value="non" style="width: 10%;"> Non</label>
    </div>
    <div class="form-field" id="bourse_oui">
        <label>Si oui, indiquez le nom, l'adresse, le courriel, le téléphone de l'organisme et le montant de la bourse :</label>
        <textarea name="bourse_oui"></textarea>
    </div>

    <div class="form-field">
        <label><span class="numero">30. </span>Serez-vous pris(e) en charge par une ou plusieurs personne(s) en France ?</label>
        <label style="display: flex;"><input type="radio" name="prise_en_charge" value="oui" style="width: 10%;"> Oui</label>
        <label style="display: flex;"><input type="radio" name="prise_en_charge" value="non" style="width: 10%;"> Non</label>
    </div>
    <div class="form-field" id="prise_en_charge_oui">
        <label>Si oui, indiquez leur nom, nationalité, qualité, adresse, courriel et téléphone :</label>
        <textarea name="prise_en_charge_oui"></textarea>
    </div>

    <div class="form-field">
        <label><span class="numero">31. </span>Des membres de votre famille résident-ils en France ?</label>
        <label style="display: flex;"><input type="radio" name="famille_resident" value="oui" style="width: 10%;"> Oui</label>
        <label style="display: flex;"><input type="radio" name="famille_resident" value="non" style="width: 10%;"> Non</label>
    </div>
    <div class="form-field" id="famille_resident_oui">
        <label>Si oui, indiquez leur nom, nationalité, lien de parenté, adresse, courriel et téléphone :</label>
        <textarea name="famille_resident_oui"></textarea>
    </div>

    <div class="form-field">
        <label><span class="numero">32. </span>Avez-vous déjà résidé plus de trois mois consécutifs en France ?</label>
        <label style="display: flex;"><input type="radio" name="resident_plus_de_trois_mois" value="oui" style="width: 10%;"> Oui</label>
        <label style="display: flex;"><input type="radio" name="resident_plus_de_trois_mois" value="non" style="width: 10%;"> Non</label>
    </div>
    <div id="resident_plus_de_trois_mois_oui">
        <div class="form-field">
            <label>Si oui, précisez à quelle(s) date(s) et pour quel(s) motif(s) :</label>
            <textarea name="resident_plus_de_trois_mois_oui"></textarea>
        </div>
        <div class="form-field">
            <label>A quelle(s) adresse(s) ?</label>
            <textarea name="resident_plus_de_trois_mois_adresse"></textarea>
        </div>
    </div>
    <?php
}

// numero 10 longS  Fonction pour les champs de financement
function render_funding_fields()
{
    ?>
    <div class="form-field">
        <label>Quels seront vos moyens d'existence en France ? <span class="required">*</span></label>
        <select name="recouvrement" required>
            <option value="">-- Sélectionnez --</option>
            <option value="liquide">Liquide</option>
            <option value="carte_credit">Carte de crédit</option>
        </select>
    </div>
    <div class="form-field">
        <label>Serez-vous titulaire d'une bourse ? </label>
        <select name="bourse" required>
            <option value="">-- Sélectionnez --</option>
            <option value="Oui">Oui</option>
            <option value="Non">Non</option>
        </select>
    </div>
    <div class="form-field">
        <label>Si oui, indiquez le nom, l'adresse, le courriel, le téléphone de l'organisme et le montant de la bourse </label>
        <textarea name="titulaire_bourse" placeholder="Indiquer ici les informations concernant la bourse"></textarea>
    </div>
    <div class="form-field">
        <label>Votre séjour sera-t-il financé par une ou plusieurs personnes ?</label>
        <select name="financement_personnes" required>
            <option value="">-- Sélectionnez --</option>
            <option value="Oui">Oui</option>
            <option value="Non">Non</option>
        </select>
    </div>
    <div class="form-field">
        <label>Serez-vous pris(e) en charge par une ou plusieurs personne(s) en France ?</label>
        <select name="prise_charge_personnes" required>
            <option value="">-- Sélectionnez --</option>
            <option value="Oui">Oui</option>
            <option value="Non">Non</option>
        </select>
    </div>
    <div class="form-field">
        <label>Si oui, indiquer le nom et prénom(s) personne qui prendra en charge vos frais de séjour en France</label>
        <input type="text" name="nom_prise_en_charge" placeholder="Nom et prénom(s)">
    </div>
    <div class="form-field">
        <label>Adresse de la personne qui prendra en charge vos frais de séjour en France</label>
        <input type="text" name="adresse_prise_en_charge" placeholder="Adresse complète">
    </div>
    <div class="form-field">
        <label>Téléphone de la personne qui prendra en charge vos frais de séjour en France</label>
        <input type="text" name="telephone_prise_en_charge" placeholder="Numéro de téléphone">
    </div>
    <div class="form-field">
        <label>Des membres de votre famille résident-ils en France ?</label>
        <textarea name="membre_famille_prise_en_charge" id=""placeholder=" Si oui, indiquez leur nom, nationalité, lien de parenté, adresse, courriel et téléphone :"></textarea>
    </div>
    <div class="form-field">
        <label>Avez-vous déjà résidé plus de trois mois consécutifs  en France ?</label>
        <textarea name="residance_france_deja" id=""placeholder=" Si oui, précisez à quelle(s) date(s) et pour quel(s) motif(s) :"></textarea>
        <label>A quelle(s) adresse(s) ?</label>
        <input type="text" name="adresse_deja_reside_france" placeholder="Numéro de téléphone">
    </div>
    <span> En connaissance de cause, j'accepte ce qui suit : aux fins de l'examen de ma demande de visa, il y a lieu de recueillir les données requises dans ce formulaire, de me photographier et, le cas échéant, de prendre mes
 empreintes digitales. Les données à caractère personnel me concernant qui figurent dans le présent formulaire de demande de visa, ainsi que mes empreintes digitales et ma photo, seront communiquées aux autorités
 françaises compétentes et traitées par elles, aux fins de la décision relative à ma demande de visa. 
Ces données ainsi que celles concernant la décision relative à ma demande de visa, ou toute décision d'annulation ou d'abrogation du visa, seront saisies et conservées dans la base française des données biométriques
 VISABIO pendant une période maximale de cinq ans, durant laquelle elles seront accessibles aux autorités chargées des visas, aux autorités compétentes chargées de contrôler les visas aux frontières, aux autorités
 nationales compétentes en matière d'immigration et d'asile aux fins de la vérification du respect des conditions d'entrée et de séjour réguliers sur le territoire de la France, aux fins de l'identification des personnes qui ne
 remplissent pas ou plus ces conditions. Dans certaines conditions, ces données seront aussi accessibles aux autorités françaises désignées et à Europol aux fins de la prévention et de la détection des infractions
 terroristes et des autres infractions pénales graves, ainsi que dans la conduite des enquêtes s'y rapportant. L'autorité française est compétente pour le traitement des données [(...)] 
En application de la loi n° 78-17 du 6 janvier 1978 relative à l’informatique et aux libertés je suis informé(e) de mon droit d'obtenir auprès de l'État français communication des informations me concernant qui sont
 enregistrées dans la base VISABIO et de mon droit de demander que ces données soient rectifiées si elles sont erronées, ou éventuellement effacées seulement si elles ont été traitées de façon illicite. Ce droit d’accès et
 de rectification éventuelle s’exerce auprès du chef de poste. La Commission nationale de l'Informatique et des Libertés (CNIL) - 3 Place de Fontenoy - TSA 80715 - 75334 PARIS CEDEX 07 -peut éventuellement être
 saisie si j'entends contester les conditions de protection des données à caractère personnel me concernant.
 Je suis informé que tout dossier incomplet accroît le risque de refus de ma demande de visa par l'autorité consulaire et que celle-ci peut être amenée à conserver mon passeport pendant le délai de traitement de ma
 demande
 Je déclare qu'à ma connaissance, toutes les indications que j'ai fournies sont correctes et complètes. Je suis informé(e) que toute fausse déclaration entraînera le rejet de ma demande ou l'annulation du visa s'il a déjà été
 délivré, et sera susceptible d'entraîner des poursuites pénales à mon égard en application du droit français.
 « Je suis informé(e) que le silence gardé par l’administration plus de deux mois après le dépôt de ma demande attesté par la remise d’une quittance vaut décision implicite de rejet . Cette décision pourra être contestée
 auprès de la Commission des recours contre les décisions de refus de visa, BP 83.609, 44036 Nantes CEDEX 1, dans un délai de deux mois suivant la naissance de la décision implicite
 Je m'engage à quitter le territoire français avant l'expiration du visa, si celui-ci m'a été délivré, et si je n'ai pas obtenu le droit de séjourner en France au delà de cette durée. 
Je suis informé(e) que le livret d’informations « Venir vivre en France » est disponible à l’adresse www.immigration.interieur.gouv.fr et www.ofii.fr</span>

    <div class="form-field">
        <label>Lieu et date</label>
        <input type="text" name="lieu_et_date" placeholder=" ">
    </div>


    <?php
}
//numero 11 longS Fonction pour les champs de documents requis
function render_required_documents_fields()
{
    ?>
    <h3 class="form-subtitle">Documents obligatoires</h3>
                
    <div class="form-field">
        <label>Photos d'identité récentes <span class="required">*</span></label>
        <input type="file" name="identity[]" multiple accept=".pdf,.jpg,.jpeg,.png" required>
        <p class="form-help-text">2 photos conformes aux normes OACI (35x45mm, fond clair)</p>
    </div>
    <!-- Carte d'identité retirée de la liste 
    <div class="form-field">
        <label>Copie de la carte d'identité/passeport <span class="required">*</span></label>
        <input type="file" name="CIN[]" multiple accept=".pdf,.jpg,.jpeg,.png" required>
        <p class="form-help-text">Recto et verso (sélectionnez les deux fichiers)</p>
    </div>-->

    <div class="form-field">
        <label>Preuve de ressources financières <span class="required">*</span></label>
        <input type="file" name="documentsFinanciere[]" multiple accept=".pdf,.jpg,.jpeg,.png" required>
        <p class="form-help-text">Relevés bancaires, attestation de prise en charge, etc.</p>
    </div>
    
    <div class="form-field">
        <label>Preuve de paiement <span class="required">*</span></label>
        <input type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png" required>
    </div>
    <?php
}

// numero 12 longS Fonction pour les champs de documents supplémentaires
function render_additional_documents_fields()
{
    ?>
    <h3 class="form-subtitle">Documents supplémentaires</h3>
    
    <div class="form-field">
        <label>Autres documents</label>
        <input type="file" name="documents[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
        <p class="form-help-text">Lettre d'invitation, réservation d'hôtel, etc.</p>
    </div>
    <?php
}
// cours sejours pqrent infor,qtion
function render_parental_resortisants_fields()
{
    ?>
    <label><span class="numero">17. </span>Données à caractère personnel du membre de la famille qui est un ressortissant de l’UE, de l’EEE ou de la Confédération suisse ou un ressortissant du Royaume-Uni bénéficiaire de l’accord sur le retrait du Royaume-Uni de l’UE, selon le cas</label>
    <div class="form-field">
        <label>Nom  (nom de famille) :</label>
        <input type="text" name="nom_resortisant_EE" placeholder="Nom">
    </div>
    <div class="form-field">
        <label>Prénom(s) [nom(s) usuel(s)] :</label>
        <input type="text" name="prenom_resortisant_EE" placeholder="prénom(s)">
    </div>
    <div class="form-field">
        <label>Date de naissance (jour-mois-année) : </label>
        <input type="date" name="date_naissance_resortisant_EE" >
    </div>
    <div class="form-field">
        <label>Nationalité  : </label>
        <select name="nationalite_resortisant_EE" class="select-nationalite" required>
            <option value="">-- Sélectionnez une nationalité --</option>
            <option value="algerie">Algérie</option>
            <option value="tunisie">Tunisie</option>
            <option value="maroc">Maroc</option>
            <option value="senegal">Sénégal</option>
            <option value="mali">Mali</option>
            <option value="cote_ivoire">Côte d'Ivoire</option>
            <option value="niger">Niger</option>
            <option value="burkina_faso">Burkina Faso</option>
            <option value="tchad">Tchad</option>
            <option value="guinee">Guinée</option>
            <option value="rdc">République Démocratique du Congo</option>
            <option value="cameroon">Cameroun</option>
        </select>
    </div>
    <div class="form-field">
        <label>Numéro du document de voyage ou de la carte d’identité :</label>
        <input type="text" name="numero_doc_resortisant_EE" >
    </div>
    <div class="form-field">
        <label><span class="numero">18. </span>Lien de parenté avec un ressortissant de l’UE, de l’EEE</label>
        <select name="lien_parente_resortissant" required>
            <option value="">-- Sélectionnez --</option>
            <option value=" Conjoint "> Conjoint </option>
            <option value="Enfant">Enfant</option>
            <option value="Petit-fils ou petite-fille">Petit-fils ou petite-fille</option>
            <option value="Ascendant dépendant">Ascendant dépendant</option>
            <option value="Partenariat enregistré">Partenariat enregistré</option>
            <option value="Autre">Autre</option>
        </select>
        <input type="text" name="lien_parente_resortissant_autre" placeholder="Si autre">
    </div>
    
    <?php
}
// fonction information voyage
function render_voyage_information()
{
    ?>
    <div class="form-field">
       <label><span class="numero">23. </span>Objet(s) du voyage : </label>
        <select name="motif_visa_type" required>
            <option value="">-- Sélectionnez --</option>
            <option value="Tourisme">Tourisme</option>
            <option value="Affaires">Affaires</option> 
            <option value="Visite à la famille ou à des amis">Visite à la famille ou à des amis</option>
            <option value="Culture">Culture</option>
            <option value="Sports">Sports</option>
            <option value="Visite officielle">Visite officielle</option>
            <option value="Raisons médicales">Raisons médicales</option>
            <option value="Études">Études</option>
            <option value="Transit aéroportuaire">Transit aéroportuaire</option>
            <option value="Autre">Autre (à préciser) :</option>
        </select>
        <input type="text" name="motif_visa_type_autre" placeholder="A remplir si autre">
    </div>
    <div class="form-field">
        <label><span class="numero">24. </span>Informations complémentaires sur l'objet du voyage</label>
        <textarea name="motif"></textarea>
    </div>
    <?php
    $schengen_countries = [
        "Allemagne", "Autriche", "Belgique", "Danemark", "Espagne", "Estonie", "Finlande", "France", "Grèce", "Hongrie",
        "Islande", "Italie", "Lettonie", "Liechtenstein", "Lituanie", "Luxembourg", "Malte", "Norvège", "Pays-Bas",
        "Pologne", "Portugal", "République tchèque", "Slovaquie", "Slovénie", "Suède", "Suisse"
    ];
    ?>
    <div class="form-field">
        <label><span class="numero">25. </span>État membre de destination principale :</label>
        <select name="etat_membre" required>
            <option value="">-- Sélectionnez --</option>
            <?php foreach ($schengen_countries as $country): ?>
                <option value="<?= $country ?>"><?= $country ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-field">
        <label><span class="numero">26. </span>État membre de première entrée :</label>
        <select name="etat_membre_premiere_entree" required>
            <option value="">-- Sélectionnez --</option>
            <?php foreach ($schengen_countries as $country): ?>
                <option value="<?= $country ?>"><?= $country ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-field">
        <label><span class="numero">27. </span>Nombre d'entrées demandées</label>
        <select name="entrees" required>
            <option value="">-- Sélectionnez --</option>
            <option value="1">Une entrée</option>
            <option value="2"> Deux entrées</option>
            <option value="Multiple">Entrées multiples</option>
        </select>
    </div>
     <?php
    // Ajouter le calculateur de jours
    render_days_calculator();
    ?>
    
    <div class="form-field">
        <label>Date d’arrivée prévue pour le 1er séjour envisagé dans l’espace Schengen : <span class="required">*</span></label>
        <input type="date" name="date_arrivee" required>
    </div>
    
    <div class="form-field">
        <label>Date de départ prévue de l’espace Schengen après le 1er séjour envisagé :</label>
        <input type="date" name="date_depart" required>
    </div>
    
    <div class="form-field">
        <label><span class="numero">28. </span>Empreintes digitales relevées précédemment aux fins d’une demande de visa Schengen :</label>
        <label style="display: flex;"><input type="radio" name="empreinte" value="non" style=" width: 10%;"> Non</label>
        <label style="display: flex;"><input type="radio" name="empreinte" value="oui" style=" width: 10%;"> Oui : Titre de séjour ou équivalent</label>
        <div id="empreinte_date_connue" style="display:none;">
            <label>Date, si elle est connue :</label>
            <input type="date" name="empreinte_date_connue[]">
            <button type="button" onclick="addVisaDate()">Ajouter une date</button>
            <div id="visa-dates-list"></div>
            <label>Numéro du visa, s’il est connu :</label>
            <input type="text" name="empreinte_num_visa_connu[]">
            <button type="button" onclick="addVisaNum()">Ajouter un numéro</button>
            <div id="visa-nums-list"></div>
        </div>
    </div>
    <script>
    function addVisaDate() {
        const container = document.getElementById('visa-dates-list');
        const input = document.createElement('input');
        input.type = 'date';
        input.name = 'empreinte_date_connue[]';
        input.style.marginTop = '5px';
        container.appendChild(input);
    }
    function addVisaNum() {
        const container = document.getElementById('visa-nums-list');
        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'empreinte_num_visa_connu[]';
        input.placeholder = 'Numéro de visa';
        input.style.marginTop = '5px';
        container.appendChild(input);
    }
    </script>
    <div class="form-field">
        <label><span class="numero">29. </span>Autorisation d’entrée dans le pays de destination finale, le cas échéant :</label>
        <label> Délivrée par : </label>
        <select name="empreinte_autorisation" class="select-pays">
            <option value="">-- Sélectionnez un pays --</option>
        </select>
    </div>
    <div class="form-field">
        <label>  valable du: </label>
        <input type="date" name="valabilite_debut" >
    </div>
    <div class="form-field">
        <label>  au </label>
        <input type="date" name="valabilite_fin" >
    </div>   
    <?php

}

// fonction cours sejours pour inforqtion do,icile et entreprise
function render_form_information_cours_sejour()
{
    ?>
    <div class="form-field">
        <label>Information sur la personne invitante</label>
    </div>
    <div class="form-field">
        <label><span class="numero">30. </span>Nom et prénom de la ou des personnes qui invitent dans le ou les États membres. A défaut, nom d’un ou des hôtels ou lieux d’hébergement temporaire dans le ou les États membres : <span class="required">*</span></label>
        <input type="text" name="nom_invitant" placeholder="Nom complet " required>
    </div>
    <div class="form-field">
        <label>Adresse et adresse électronique de la ou des personnes qui invitent /du ou des hôtels /du ou des lieux d’hébergement temporaire :</label>
        <textarea name="adresse_invitant" placeholder="Adresse complète"></textarea>
    </div>
    <div class="form-field">
        <label>Téléphone</label>
        <textarea name="telephone_invitant"></textarea>
    </div>
    <div class="form-field">
        <label><span class="numero">31. </span>Nom et adresse de l’entreprise /l’organisation hôte :<span class="required">*</span></label>
        <textarea name="organisation_hote" placeholder="Nom et adresse complète" required></textarea>
    </div>
    <div class="form-field">
        <label>Numéro de téléphone de l’entreprise / l’organisation :</label>
        <input type="number" name="telephone_organisation"></textarea>
    </div>
    <div class="form-field">
        <label>Nom, prénom, adresse, numéro de téléphone, et adresse électronique de la personne de contact dans l’entreprise/organisation :</label>
        <textarea name="nom_adresse_invitant" ></textarea>
    </div>
    

    <div class="form-field">
        <label><span class="numero">32. </span>Les frais de voyage et de subsistance durant le séjour du demandeur sont financés :<span class="required">*</span></label>
        <label style="display: flex;"><input type="radio" name="frais_subsistance" value="Par le demandeur" style="width: 10%;"> Par le demandeur</label>
        <label style="display: flex;"><input type="radio" name="frais_subsistance" value="Par un garant" style="width: 10%;"> Par un garant (hôte, entreprise, organisation), veuillez préciser :</label>

        <div id="garant">
            <label style="display: flex;"><input type="radio" name="frais_subsistance_garant" value="vise" style="width: 10%;"> Visé dans la case 30 ou 31</label>
            <label style="display: flex;"><input type="radio" name="frais_subsistance_garant" value="autre" style="width: 10%;"> Autre (à préciser) :</label>
            <textarea name="frais_subsistance_garant_autre"></textarea>
            
            <div>
                <p>Moyens de subsistance :</p>
                <label style="display: flex;"><input type="checkbox" name="subsistance_garant[]" value="argent_liquide" style="width: 10%;"> Argent liquide</label>
                <label style="display: flex;"><input type="checkbox" name="subsistance_garant[]" value="hebergement_fourni" style="width: 10%;"> Hébergement fourni</label>
                <label style="display: flex;"><input type="checkbox" name="subsistance_garant[]" value="tous_frais_finances" style="width: 10%;"> Tous les frais sont financés pendant le séjour</label>
                <label style="display: flex;"><input type="checkbox" name="subsistance_garant[]" value="transport_prepaye" style="width: 10%;"> Transport prépayé</label>
                <label style="display: flex;">
                    <input type="checkbox" name="subsistance_garant[]" value="autre" style="width: 10%;"> Autre (à préciser) :
                </label>
                <textarea name="subsistance_garant_autre" rows="2"></textarea>
            </div>
        </div>

        <div id="demandeur">
            <p>Moyens de subsistance :</p>
            <label style="display: flex;"><input type="checkbox" name="subsistance_demandeur[]" value="argent_liquide" style="width: 10%;"> Argent liquide</label>
            <label style="display: flex;"><input type="checkbox" name="subsistance_demandeur[]" value="cheques" style="width: 10%;"> Chèques de voyage</label>
            <label style="display: flex;"><input type="checkbox" name="subsistance_demandeur[]" value="carte_credit" style="width: 10%;"> Carte de crédit</label>
            <label style="display: flex;"><input type="checkbox" name="subsistance_demandeur[]" value="hebergement_prepaye" style="width: 10%;"> Hébergement prépayé</label>
            <label style="display: flex;"><input type="checkbox" name="subsistance_demandeur[]" value="transport_prepaye" style="width: 10%;"> Transport prépayé</label>
            <label style="display: flex;">
                <input type="checkbox" name="subsistance_demandeur[]" value="autre" style="width: 10%;"> Autre (à préciser) :
            </label>
            <textarea name="subsistance_demandeur_autre" rows="2"></textarea>
        </div>
    </div>

    <div class="form-field">
        <label><span class="numero">33. </span>Nom et prénom de la personne qui remplit le formulaire de demande, si elle n’est pas le demandeur :</label>
        <textarea name="nom_remplisseur" ></textarea>
    </div>

    <div class="form-field">
        <label>Adresse et adresse électronique de la personne qui remplit le formulaire de demande :</label>
        <textarea name="adresse_electronique_remplisseur" ></textarea>
    </div>

    <div class="form-field">
        <label>Numéro de téléphone :</label>
        <input type="number" name="telephone_remplisseur"></textarea>
    </div>

    <?php
}

// fonction cours sejours pour inforation paiement
function render_funding_fields_cours()
{
    ?>
    <h3 class="form-subtitle">Financement du séjour</h3>
                
    <div class="form-field">
        <label>Les frais de voyage et de subsistance durant le séjour du demandeur sont financés : <span class="required">*</span></label>
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
                <option value="Particulier">  Visé dans la case 30 ou 31 </option>
                <option value="Autres">Autres</option>
            </optgroup>
            <optgroup label=" Moyens de subsistance :">
                <option value="Argent liquide">Argent liquide</option>
                <option value=" Hébergement fourni"> Hébergement fourni</option>
                <option value="Tous les frais sont financés pendant le sejour">  Tous les frais sont financés pendant le séjour </option>
                <option value=" Transport prépaye"> Transport prépayé</option>
                <option value="Autres">Autres</option>
            </optgroup>
        </select>
    </div>

    <div class="form-field">
        <label>Nom et prénom de la personne qui remplit le formulaire</label>
        <input type="text" name="remplisseur_nom">
    </div>
    <div class="form-field">
        <label> Adresse et adresse électronique de remplisseur </label>
        <input type="text" name="remplisseur_mail">
    </div><div class="form-field">
        <label>Numéro de téléphone : </label>
        <input type="text" name="remplisseur_telephone">
    </div>

    <?php
}

// fonction pour notificqtion
function render_notifications_section()
{
    ?>
    <div class="notifications-section" style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-bottom: 20px; font-size: 24px;">
            Informations importantes
        </h2>

        <h2 style="font-size: 18px; margin-bottom: 15px;">
            Je suis informé(e) que les droits de visa ne sont pas remboursés si le visa est refusé.
        </h2>

        <h2 style="font-size: 18px; margin-bottom: 15px;">
            Applicable en cas de délivrance d'un visa à entrées multiples :<br>
            Je suis informé(e) de la nécessité de disposer d’une assurance maladie en voyage adéquate pour mon premier séjour et lors de voyages ultérieurs sur le territoire des États membres.
        </h2>

        <h2 style="font-size: 18px; margin-bottom: 10px;">
            En connaissance de cause, j'accepte ce qui suit :
        </h2>

        <h2 style="font-size: 16px; font-weight: normal; margin: 10px 0; line-height: 1.6;">
            Aux fins de l'examen de ma demande, il y a lieu de recueillir les données requises dans ce formulaire de demande, de me photographier et, le cas échéant, de prendre mes empreintes digitales. Les données à caractère personnel me concernant qui figurent dans le présent formulaire de demande, ainsi que mes empreintes digitales et ma photo, seront communiquées aux autorités compétentes des États membres et traitées par elles, aux fins de la décision relative à ma demande de visa.
        </h2>

        <h2 style="font-size: 16px; font-weight: normal; margin: 10px 0; line-height: 1.6;">
            Ces données ainsi que celles concernant la décision relative à ma demande, ou toute décision d'annulation, d'abrogation ou de prolongation de visa, seront saisies et conservées dans le système d'information sur les visas (VIS) pendant une période maximale de cinq ans durant laquelle elles seront accessibles aux autorités compétentes.
        </h2>

        <h2 style="font-size: 16px; font-weight: normal; margin: 10px 0; line-height: 1.6;">
            Je déclare qu'à ma connaissance, toutes les indications que j'ai fournies sont correctes et complètes. Je suis informé(e) que toute fausse déclaration entraînera le rejet de ma demande ou l'annulation du visa s'il a déjà été délivré, et peut également entraîner des poursuites pénales à mon égard en application du droit de l'État membre qui traite la demande.
        </h2>

        <h2 style="font-size: 16px; font-weight: normal; margin: 10px 0; line-height: 1.6;">
            Je m'engage à quitter le territoire des États membres avant l'expiration du visa, si celui-ci m'est accordé. J'ai été informé(e) que la possession d'un visa n'est que l'une des conditions préalables d'entrée sur le territoire européen des États membres. Le simple fait qu'un visa m'ait été accordé ne signifie pas que j'aurai droit à une indemnisation si je ne respecte pas les dispositions pertinentes à l'article 6, paragraphe 1, du règlement UE 2016/399 ( code frontières Schengen) et que l'entrée m'est par conséquent refusée. Le respect des conditions préalables d'entrée sera contrôlé à nouveau au moment de l'entrée sur le territoire européen des États membres.
        </h2>
    </div>
    <?php
}

// Fonction pour afficher l'acceptation lon séjour

function render_acceptation()
{
    ?>
    <p>En connaissance de cause, j'accepte ce qui suit : aux fins de l'examen de ma demande de visa, il y a lieu de recueillir les données requises dans ce formulaire, de me photographier et, le cas échéant, de prendre mes empreintes digitales. Les données à caractère personnel me concernant qui figurent dans le présent formulaire de demande de visa, ainsi que mes empreintes digitales et ma photo, seront communiquées aux autorités françaises compétentes et traitées par elles, aux fins de la décision relative à ma demande de visa.</p>
    <p>Ces données ainsi que celles concernant la décision relative à ma demande de visa, ou toute décision d'annulation ou d'abrogation du visa, seront saisies et conservées dans la base française des données biométriques VISABIO pendant une période maximale de cinq ans, durant laquelle elles seront accessibles aux autorités chargées des visas, aux autorités compétentes chargées de contrôler les visas aux frontières, aux autorités nationales compétentes en matière d'immigration et d'asile aux fins de la vérification du respect des conditions d'entrée et de séjour réguliers sur le territoire de la France, aux fins de l'identification des personnes qui ne remplissent pas ou plus ces conditions. Dans certaines conditions, ces données seront aussi accessibles aux autorités françaises désignées et à Europol aux fins de la prévention et de la détection des infractions terroristes et des autres infractions pénales graves, ainsi que dans la conduite des enquêtes s'y rapportant. L'autorité française est compétente pour le traitement des données [(...)]</p>
    <p>En application de la loi n° 78-17 du 6 janvier 1978 relative à l’informatique et aux libertés je suis informé(e) de mon droit d'obtenir auprès de l'État français communication des informations me concernant qui sont enregistrées dans la base VISABIO et de mon droit de demander que ces données soient rectifiées si elles sont erronées, ou éventuellement effacées seulement si elles ont été traitées de façon illicite. Ce droit d’accès et de rectification éventuelle s’exerce auprès du chef de poste. La Commission nationale de l'Informatique et des Libertés (CNIL) - 3 Place de Fontenoy - TSA 80715 - 75334 PARIS CEDEX 07 -peut éventuellement être saisie si j'entends contester les conditions de protection des données à caractère personnel me concernant.</p>
    <p>Je suis informé que tout dossier incomplet accroît le risque de refus de ma demande de visa par l'autorité consulaire et que celle-ci peut être amenée à conserver mon passeport pendant le délai de traitement de ma demande</p>
    <p>Je déclare qu'à ma connaissance, toutes les indications que j'ai fournies sont correctes et complètes. Je suis informé(e) que toute fausse déclaration entraînera le rejet de ma demande ou l'annulation du visa s'il a déjà été délivré, et sera susceptible d'entraîner des poursuites pénales à mon égard en application du droit français</p>
    <p>« Je suis informé(e) que le silence gardé par l’administration plus de deux mois après le dépôt de ma demande attesté par la remise d’une quittance vaut décision implicite de rejet . Cette décision pourra être contestée auprès de la Commission des recours contre les décisions de refus de visa, BP 83.609, 44036 Nantes CEDEX 1, dans un délai de deux mois suivant la naissance de la décision implicite</p>
    <p>Je m'engage à quitter le territoire français avant l'expiration du visa, si celui-ci m'a été délivré, et si je n'ai pas obtenu le droit de séjourner en France au delà de cette durée. </p>
    <p>Je suis informé(e) que le livret d’informations « Venir vivre en France » est disponible à l’adresse www.immigration.interieur.gouv.fr et www.ofii.fr</p>
    <?php
}

// Fonction pour afficher une section du formulaire

function render_form_section($id, $title, $is_active = false, $content_callback)
{
    ?>
    <div id="<?= $id ?>" class="form-section <?= $is_active ? 'active' : '' ?>">
        <h2 class="form-section-title"><?= $title ?></h2>
        <?php $content_callback(); ?>

        <div class="form-navigation">
            <?php if ($id !== 'section-1'): // Afficher "Précédent" sauf pour la première section?>
                <button type="button" class="prev-btn">Précédent</button>
            <?php endif; ?>

            <?php if ($id === 'section-3'): //  Afficher "Envoyer la demande" pour la dernière section?>
                <button type="button" class="submit-btn" >Envoyer la demande</button>
            <?php else: // Afficher "Suivant" pour les autres sections?>
                <button type="button" class="next-btn">Suivant</button>
            <?php endif; ?>
        </div>
    </div>
    <?php
}


function render_form_scripts()
{
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Gestion des sections
            const sections = document.querySelectorAll('.form-section');
            const prevBtns = document.querySelectorAll('.prev-btn');
            const nextBtns = document.querySelectorAll('.next-btn');
            const submitBtns = document.querySelectorAll('.submit-btn'); // Modifié pour sélectionner tous les boutons submit
            let currentSection = 0;

            // Formulaire et éléments liés au mandat
            const form = document.getElementById('visa-request-form');
            const mandatSection = document.getElementById('mandat-section');

            // Affichage conditionnel dossier groupé
            const dossierSelect = document.getElementById('dossier-groupe-select');
            if (dossierSelect) {
                dossierSelect.addEventListener('change', function () {
                    const dossierInfo = document.getElementById('dossier-groupe-info');
                    dossierInfo.style.display = this.value === 'oui' ? 'block' : 'none';
                });
            }

            // Afficher la première section
            showSection(currentSection);

            // Bouton Suivant
            nextBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    if (validateSection(currentSection)) {
                        currentSection++;
                        showSection(currentSection);
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                });
            });

            // Bouton Précédent
            prevBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    if (currentSection > 0) {
                        currentSection--;
                        showSection(currentSection);
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                });
            });

            // Gestion de l'affichage du mandat - Pour tous les boutons submit
            submitBtns.forEach(submitBtn => {
                submitBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Valider la dernière section
                    if (!validateSection(currentSection)) return;
                    
                    // Collecter les données du formulaire pour le mandat
                    const formData = {
                        nom: form.querySelector('[name="nom"]').value,
                        prenom: form.querySelector('[name="prenom"]').value,
                        date_naissance: form.querySelector('[name="date_naissance"]').value,
                        lieu_naissance: form.querySelector('[name="lieu_naissance"]').value,
                        Nationalite_actuelle: form.querySelector('[name="Nationalite_actuelle"]').value,
                        numero_doc_voyage: form.querySelector('[name="numero_doc_voyage"]').value,
                        adresse_domicile: form.querySelector('[name="adresse"]') ? form.querySelector('[name="adresse"]').value : 
                                        form.querySelector('[name="adresse_demandeur"]') ? form.querySelector('[name="adresse_demandeur"]').value : '',
                        visa_type: document.querySelector('[name="visa_type"]').value,
                        profession: form.querySelector('[name="profession"]').value,
                        /*nom_employeur: form.querySelector('[name="employeur"]') ? form.querySelector('[name="employeur"]').value : 
                                    form.querySelector('[name="organisation_hote"]') ? form.querySelector('[name="organisation_hote"]').value : ''
                            */
                        };

                    // Générer le contenu du mandat
                    document.getElementById('mandat-preview').innerHTML = generateMandatContent(formData);

                    // Afficher la section mandat
                    mandatSection.style.display = 'block';
                    window.scrollTo({ top: 0, behavior: 'smooth' });

                    // Cacher toutes les autres sections
                    sections.forEach(section => {
                        section.style.display = 'none';
                    });
                });
            });

            // Bouton Annuler du mandat
            const cancelBtn = document.getElementById('cancel-mandat');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function () {
                    mandatSection.style.display = 'none';
                    showSection(currentSection); // Revenir à la dernière section visible
                });
            }
        
            // Gestion de la soumission du formulaire
            const confirmSubmitBtn = document.getElementById('confirm-submit');
            if (confirmSubmitBtn) {
                confirmSubmitBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Vérifier que la case est cochée
                    const approveCheckbox = document.querySelector('input[name="approve_mandat"]');
                    if (!approveCheckbox.checked) {
                        alert('Veuillez approuver le mandat avant de soumettre.');
                        return;
                    }
                    
                    // Soumettre le formulaire
                    const form = document.getElementById('visa-request-form');
                    if (form) {
                        form.submit();
                    }
                });
            }

            function showSection(index) {
                sections.forEach((section, i) => {
                    section.classList.toggle('active', i === index);
                    section.style.display = i === index ? 'block' : 'none';
                });
            }

            function validateSection(index) {
                let isValid = true;
                const currentSec = sections[index];
                const requiredFields = currentSec.querySelectorAll('[required]');

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.style.borderColor = '#e74c3c';
                        if (isValid) { // Scroll seulement vers le premier champ invalide
                            field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            isValid = false;
                        }
                    } else {
                        field.style.borderColor = '';
                    }
                });

                // Validation spécifique pour les champs radio required
                const requiredRadios = currentSec.querySelectorAll('input[type="radio"][required]');
                if (requiredRadios.length > 0) {
                    let radioChecked = false;
                    const radioName = requiredRadios[0].name;
                    const radios = currentSec.querySelectorAll(`input[type="radio"][name="${radioName}"]`);
                    
                    radios.forEach(radio => {
                        if (radio.checked) radioChecked = true;
                    });

                    if (!radioChecked) {
                        isValid = false;
                        requiredRadios[0].closest('.form-field').style.borderLeft = '3px solid #e74c3c';
                        requiredRadios[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                        alert('Veuillez sélectionner une option pour les champs obligatoires.');
                    } else {
                        requiredRadios[0].closest('.form-field').style.borderLeft = '';
                    }
                }

                if (!isValid) {
                    alert('Veuillez remplir tous les champs obligatoires avant de continuer.');
                }

                return isValid;
            }

            function generateMandatContent(formData) {
                const today = new Date();
                const dateStr = today.toLocaleDateString('fr-FR', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });

                return `
                    <h2 style="text-align: center; color: #2c3e50; margin-bottom: 20px;">LETTRE DE MANDAT</h2>
                    
                    <p style="margin-bottom: 15px;">
                    Je soussigné(e),<br>
                    <strong>${formData.nom} ${formData.prenom}</strong>, né(e) le <strong>${formData.date_naissance}</strong> à <strong>${formData.lieu_naissance}</strong>,<br>
                    de nationalité <strong>${formData.Nationalite_actuelle}</strong>, exerçant la profession de <strong>${formData.profession}</strong>,<br>
                    titulaire du passeport n° <strong>${formData.numero_doc_voyage}</strong>,<br>
                    résidant à <strong>${formData.adresse_domicile}</strong>,
                    </p>

                    <p style="margin-bottom: 15px;">
                    <strong>DONNE POUVOIR</strong> à l'entreprise <strong>Visa Logistics</strong>,<br>
                    pour effectuer en mon nom et pour mon compte toutes les démarches nécessaires<br>
                    relatives à ma demande de visa de type <strong>${formData.visa_type}</strong> pour la France.
                    </p>

                    <p style="margin-bottom: 15px;">
                    Ce mandat inclut notamment :<br>
                    - Le dépôt et le suivi de ma demande de visa<br>
                    - La fourniture de tous documents nécessaires<br>
                    - La réception des informations relatives à ma demande<br>
                    - Toute autre démarche administrative connexe
                    </p>

                    <p style="margin-bottom: 15px;">
                    Je déclare avoir fourni toutes les informations exactes et complètes,<br>
                    et être pleinement responsable de leur exactitude.
                    </p>

                    <p style="margin-bottom: 30px;">
                    Fait à <strong>[LIEU]</strong>, le <strong>${dateStr}</strong>
                    </p>

                    <div style="margin-top: 50px; text-align: center;">
                        <div style="display: inline-block; text-align: left; border-top: 1px solid #000; width: 300px; padding-top: 10px;">
                            <p style="margin: 0;">Signature du mandant :<br>
                            <strong>${formData.nom} ${formData.prenom}</strong></p>
                        </div>
                    </div>
                `;
            }

            // Gestion des champs conditionnels
            // 12. Type du document de voyage - court séjour
            const select = document.querySelector('select[name="type_doc_voyage"]');
            if (select) {
                const textarea = document.querySelector('textarea[name="type_doc_voyage_autre"]');
                textarea.style.display = 'none';

                select.addEventListener('change', function () {
                    if (select.value.trim() === 'Autre document de voyage') {
                        textarea.style.display = 'block';
                        textarea.required = true;
                    } else {
                        textarea.style.display = 'none';
                        textarea.required = false;
                        textarea.value = '';
                    }
                });
            }
            
            // ...existing code inside <script> in render_form_scripts()...
            document.addEventListener('DOMContentLoaded', function() {
                // Fonction générique pour gérer les champs conditionnels
                function setupConditionalField(selectName, inputName, triggerValue, placeholder) {
                    document.querySelectorAll(`select[name="${selectName}"]`).forEach(function(select) {
                        const container = select.closest('.form-field');
                        if (!container) return;

                        let input = container.querySelector(`input[name="${inputName}"]`);
                        
                        if (!input) {
                            input = document.createElement('input');
                            input.type = 'text';
                            input.name = inputName;
                            input.placeholder = placeholder;
                            Object.assign(input.style, {
                                marginTop: '5px',
                                width: '100%',
                                padding: '8px',
                                display: 'none'
                            });
                            container.appendChild(input);
                        }
                        
                        select.addEventListener('change', function() {
                            if (this.value === triggerValue) {
                                input.style.display = 'block';
                                input.required = true;
                            } else {
                                input.style.display = 'none';
                                input.required = false;
                                input.value = '';
                            }
                        });
                        
                        // Déclencheur initial au cas où la valeur serait déjà sélectionnée
                        if (select.value === triggerValue) {
                            input.style.display = 'block';
                            input.required = true;
                        }
                    });
                }

                // Configuration des champs conditionnels
                setupConditionalField('sexe', 'sexe_autre', 'Autre', 'Précisez votre sexe');
                setupConditionalField('etat_civil', 'etat_civil_autre', 'Autre', 'Précisez votre état civil');
                setupConditionalField('type_doc_voyage', 'type_doc_voyage_autre', 'Autre document de voyage', 'Précisez le type de document');
                setupConditionalField('lien_parente_resortissant', 'lien_parente_resortissant_autre', 'Autre', 'Précisez le lien de parenté');
            });
            // Lien de parenté autre
            document.querySelectorAll('select[name="lien_parente_resortissant"]').forEach(function(select) {
                const input = select.parentElement.querySelector('input[name="lien_parente_resortissant_autre"]');
                select.addEventListener('change', function() {
                    if (this.value === 'Autre') {
                        input.style.display = 'block';
                        input.required = true;
                    } else {
                        input.style.display = 'none';
                        input.required = false;
                        input.value = '';
                    }
                });
            });
            // 18. Lien de parenté avec un ressortissant de l'UE, de l'EEE
            const selectParente = document.querySelector('select[name="lien_parente_resortissant"]');
            if (selectParente) {
                const inputParente = document.querySelector('input[name="lien_parente_resortissant_autre"]');
                inputParente.style.display = 'none';

                selectParente.addEventListener('change', function () {
                    if (selectParente.value.trim() === 'Autre') {
                        inputParente.style.display = 'block';
                        inputParente.required = true;
                    } else {
                        inputParente.style.display = 'none';
                        inputParente.required = false;
                        inputParente.value = '';
                    }
                });
            }

            // 23. Je sollicite un visa pour le motif suivant - court séjour
            const motif_visa_type = document.querySelector('select[name="motif_visa_type"]');
            if (motif_visa_type) {
                const motif_visa_type_autre = document.querySelector('input[name="motif_visa_type_autre"]');
                motif_visa_type_autre.style.display = 'none';

                motif_visa_type.addEventListener('change', function () {
                    if (motif_visa_type.value.trim() === 'Autre') {
                        motif_visa_type_autre.style.display = 'block';
                        motif_visa_type_autre.required = true;
                    } else {
                        motif_visa_type_autre.style.display = 'none';
                        motif_visa_type_autre.required = false;
                        motif_visa_type_autre.value = '';
                    }
                });
            }

            // 20. Résidence dans un pays autre que celui de la nationalité actuelle :
            const radios = document.querySelectorAll('input[name="residence_autre_pays"]');
            if (radios.length > 0) {
                const detailsDiv = document.querySelector('input[name="residence_autre_pays_numero"]').closest('div');
                detailsDiv.style.display = 'none';

                radios.forEach(radio => {
                    radio.addEventListener('change', function () {
                        if (this.value === 'oui') {
                            detailsDiv.style.display = 'block';
                            detailsDiv.querySelectorAll('input').forEach(input => input.required = true);
                        } else {
                            detailsDiv.style.display = 'none';
                            detailsDiv.querySelectorAll('input').forEach(input => {
                                input.value = '';
                                input.required = false;
                            });
                        }
                    });
                });
            }

            // Gestion des champs conditionnels (bourse, prise en charge, etc.)
            const config = [
                { radioName: 'bourse', divId: 'bourse_oui' },
                { radioName: 'prise_en_charge', divId: 'prise_en_charge_oui' },
                { radioName: 'famille_resident', divId: 'famille_resident_oui' },
                { radioName: 'resident_plus_de_trois_mois', divId: 'resident_plus_de_trois_mois_oui' },
                { radioName: 'empreinte', divId: 'empreinte_date_connue' }
            ];

            config.forEach(({ radioName, divId }) => {
                const radioss = document.querySelectorAll(`input[name="${radioName}"]`);
                const targetDiv = document.getElementById(divId);
                
                if (!targetDiv || radioss.length === 0) return;

                // Masquer au chargement
                targetDiv.style.display = 'none';

                radioss.forEach(radio => {
                    radio.addEventListener('change', function () {
                        if (this.value === 'oui') {
                            targetDiv.style.display = 'block';
                            targetDiv.querySelectorAll('input, textarea').forEach(input => input.required = true);
                        } else {
                            targetDiv.style.display = 'none';
                            // Réinitialiser les champs contenus
                            targetDiv.querySelectorAll('input, textarea').forEach(input => {
                                input.value = '';
                                input.required = false;
                            });
                        }
                    });
                });
            });

            // 32. Les frais de voyage / Court séjour
            const radiosss = document.querySelectorAll('input[name="frais_subsistance"]');
            if (radiosss.length > 0) {
                const garantDiv = document.getElementById('garant');
                const demandeurDiv = document.getElementById('demandeur');

                // Masquer les deux divs au chargement
                if (garantDiv) garantDiv.style.display = 'none';
                if (demandeurDiv) demandeurDiv.style.display = 'none';

                radiosss.forEach(radio => {
                    radio.addEventListener('change', function () {
                        if (this.value === 'Par un garant' && garantDiv) {
                            garantDiv.style.display = 'block';
                            if (demandeurDiv) demandeurDiv.style.display = 'none';
                            // Réinitialise les champs de #demandeur
                            if (demandeurDiv) {
                                demandeurDiv.querySelectorAll('input, textarea').forEach(input => {
                                    if (input.type === 'checkbox' || input.type === 'radio') input.checked = false;
                                    else input.value = '';
                                    input.required = false;
                                });
                            }
                            // Rendre obligatoire les champs de garant
                            garantDiv.querySelectorAll('input, textarea').forEach(input => input.required = true);
                        } else if (this.value === 'Par le demandeur' && demandeurDiv) {
                            if (garantDiv) garantDiv.style.display = 'none';
                            demandeurDiv.style.display = 'block';
                            // Réinitialise les champs de #garant
                            if (garantDiv) {
                                garantDiv.querySelectorAll('input, textarea').forEach(input => {
                                    if (input.type === 'checkbox' || input.type === 'radio') input.checked = false;
                                    else input.value = '';
                                    input.required = false;
                                });
                            }
                            // Rendre obligatoire les champs de demandeur
                            demandeurDiv.querySelectorAll('input, textarea').forEach(input => input.required = true);
                        }
                    });
                });
            }

            // Nationalité
            const listeNationalites = [
                "Afghane", "Albanaise", "Algérienne", "Allemande", "Andorrane", "Angolaise", "Antiguaise-et-barbudienne",
                "Saoudienne", "Argentine", "Arménienne", "Australienne", "Autrichienne", "Azerbaïdjanaise", "Bahamienne",
                "Bahreïnienne", "Bangladaise", "Barbadienne", "Belge", "Bélizienne", "Béninoise", "Bhoutanaise",
                "Biélorusse", "Birmane", "Bolivienne", "Bosnienne", "Botswanaise", "Brésilienne", "Brunéienne",
                "Bulgare", "Burkinabè", "Burundaise", "Cambodgienne", "Camerounaise", "Canadienne", "Cap-verdienne",
                "Centrafricaine", "Chilienne", "Chinoise", "Chypriote", "Colombienne", "Comorienne", "Congolaise",
                "Nord-coréenne", "Sud-coréenne", "Costaricaine", "Croate", "Cubaine", "Danoise", "Djiboutienne",
                "Dominicaine", "Égyptienne", "Émiratie", "Équatorienne", "Érythréenne", "Espagnole", "Estonienne",
                "Éthiopienne", "Finlandaise", "Française", "Gabonaise", "Gambienne", "Géorgienne", "Ghanéenne",
                "Grecque", "Grenadienne", "Guatémaltèque", "Guinéenne", "Guinéenne-bissau", "Guinéenne équatoriale",
                "Guyanienne", "Haïtienne", "Hondurienne", "Hongroise", "Indienne", "Indonésienne", "Irakienne",
                "Iranienne", "Irlandaise", "Islandaise", "Israélienne", "Italienne", "Ivoirienne", "Jamaïcaine",
                "Japonaise", "Jordanienne", "Kazakhstanaise", "Kényane", "Kirghize", "Kiribatienne", "Koweïtienne",
                "Laotienne", "Lesothienne", "Lettone", "Libanaise", "Libérienne", "Libyenne", "Liechtensteinoise",
                "Lituanienne", "Luxembourgeoise", "Macédonienne", "Malaisienne", "Malawienne", "Maldivienne", "Malgache", 
                "Malienne", "Maltaise", "Marocaine", "Marshallaise", "Mauricienne", "Mauritanienne", "Mexicaine",
                "Micronésienne", "Moldave", "Monegasque", "Mongole", "Monténégrine", "Mozambicaine", "Namibienne",
                "Nauruane", "Népalaise", "Nicaraguayenne", "Nigérienne", "Nigériane", "Norvégienne",
                "Néo-Zélandaise", "Omanaise", "Ougandaise", "Ouzbèke", "Pakistanaise", "Palaosienne", "Palestinienne",
                "Panaméenne", "Papou", "Paraguayenne", "Néerlandaise", "Péruvienne", "Philippine", "Polonaise",
                "Portugaise", "Qatari", "Roumaine", "Britannique", "Russe", "Rwandaise", "Saint-Lucienne",
                "Saint-marinaise", "Saint-Vincentaise", "Salomonaise", "Salvadorienne", "Samoane", "Santoméenne",
                "Sénégalaise", "Serbe", "Seychelloise", "Sierra-léonaise", "Singapourienne", "Slovaque", "Slovène",
                "Somalienne", "Soudanaise", "Sud-soudanaise", "Sri-lankaise", "Suédoise", "Suisse", "Surinamaise",
                "Syrienne", "Tadjike", "Tanzanienne", "Tchadienne", "Tchèque", "Thaïlandaise", "Timoraise",
                "Togolaise", "Tongienne", "Trinidadienne", "Tunisienne", "Turkmène", "Turque", "Tuvaluane",
                "Ukrainienne", "Uruguayenne", "Vanuataise", "Vaticane", "Vénézuélienne", "Vietnamienne", "Yéménite",
                "Zambienne", "Zimbabwéenne"
            ];

            const selectNationalite = document.querySelectorAll('.select-nationalite');

            selectNationalite.forEach(select => {
                // Vider le select avant d'ajouter les options
                while (select.options.length > 1) {
                    select.remove(1);
                }
                
                listeNationalites.forEach(nat => {
                    const option = document.createElement('option');
                    option.value = nat;
                    option.textContent = nat;
                    select.appendChild(option);
                });
            });

            // Liste pays
            const paysSelect = document.querySelectorAll('.select-pays');

            const pays = [
                "Afghanistan", "Afrique du Sud", "Albanie", "Algérie", "Allemagne", "Andorre", "Angola", "Arabie Saoudite",
                "Argentine", "Arménie", "Australie", "Autriche", "Azerbaïdjan", "Bahamas", "Bahreïn", "Bangladesh",
                "Belgique", "Bénin", "Bhoutan", "Biélorussie", "Birmanie", "Bolivie", "Bosnie-Herzégovine", "Botswana",
                "Brésil", "Brunei", "Bulgarie", "Burkina Faso", "Burundi", "Cambodge", "Cameroun", "Canada", "Cap-Vert",
                "Chili", "Chine", "Chypre", "Colombie", "Comores", "Congo", "Corée du Nord", "Corée du Sud", "Costa Rica",
                "Côte d'Ivoire", "Croatie", "Cuba", "Danemark", "Djibouti", "Dominique", "Égypte", "Émirats arabes unis",
                "Équateur", "Érythrée", "Espagne", "Estonie", "États-Unis", "Éthiopie", "Fidji", "Finlande", "France",
                "Gabon", "Gambie", "Géorgie", "Ghana", "Grèce", "Guatemala", "Guinée", "Guinée équatoriale", "Haïti",
                "Honduras", "Hongrie", "Inde", "Indonésie", "Irak", "Iran", "Irlande", "Islande", "Israël", "Italie",
                "Jamaïque", "Japon", "Jordanie", "Kazakhstan", "Kenya", "Kirghizistan", "Koweït", "Laos", "Lettonie",
                "Liban", "Libéria", "Libye", "Lituanie", "Luxembourg", "Macédoine", "Madagascar", "Malaisie", "Malawi",
                "Maldives", "Mali", "Malte", "Maroc", "Maurice", "Mauritanie", "Mexique", "Moldavie", "Monaco", "Mongolie",
                "Monténégro", "Mozambique", "Namibie", "Népal", "Nicaragua", "Niger", "Nigéria", "Norvège", "Nouvelle-Zélande",
                "Oman", "Ouganda", "Ouzbékistan", "Pakistan", "Palestine", "Panama", "Papouasie-Nouvelle-Guinée", "Paraguay",
                "Pays-Bas", "Pérou", "Philippines", "Pologne", "Portugal", "Qatar", "République centrafricaine", "République tchèque",
                "Roumanie", "Royaume-Uni", "Russie", "Rwanda", "Saint-Kitts-et-Nevis", "Saint-Marin", "Saint-Siège",
                "Saint-Vincent-et-les-Grenadines", "Sainte-Lucie", "Salvador", "Samoa", "Sénégal", "Serbie", "Seychelles",
                "Sierra Leone", "Singapour", "Slovaquie", "Slovénie", "Somalie", "Soudan", "Soudan du Sud", "Sri Lanka",
                "Suède", "Suisse", "Suriname", "Syrie", "Tadjikistan", "Tanzanie", "Tchad", "Thaïlande", "Timor oriental",
                "Togo", "Trinité-et-Tobago", "Tunisie", "Turkménistan", "Turquie", "Ukraine", "Uruguay", "Vanuatu", "Vatican",
                "Venezuela", "Vietnam", "Yémen", "Zambie", "Zimbabwe"
            ];

            paysSelect.forEach(select => {
                // Vider le select avant d'ajouter les options
                while (select.options.length > 1) {
                    select.remove(1);
                }
                
                pays.forEach(p => {
                    const option = document.createElement('option');
                    option.value = p;
                    option.textContent = p;
                    select.appendChild(option);
                });
            });

            // Gestion de l'ajout/suppression de lignes dans le tableau
            window.addRow = function() {
                const tbody = document.getElementById('famille-body');
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type="text" name="Lien_parente[]"></td>
                    <td><input type="text" name="nom_prenoms_lien_parente[]"></td>
                    <td><input type="date" name="date_naissance_lien_parente[]"></td>
                    <td><input type="text" name="Nationalite_lien_parente[]"></td>
                    <td><button type="button" onclick="removeRow(this)">Supprimer</button></td>
                `;
                tbody.appendChild(row);
            };

            window.removeRow = function(button) {
                const row = button.closest('tr');
                row.remove();
            };
        });
    </script>
    <?php
}

function render_form_styles()
{
    ?>
    <style>
        .visa-form-wrapper {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.9375rem rgba(0, 0, 0, 0.1);
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #333;
            line-height: 1.6;
        }

        .form-section {
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        .form-section.active {
            display: block;
        }

        .form-section-title {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 0.75rem;
            margin: 0 0 1.5rem 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .form-subtitle {
            color: #2c3e50;
            margin: 1.5rem 0 1rem;
            font-size: 1.2rem;
            font-weight: 500;
            border-left: 4px solid #3498db;
            padding-left: 0.75rem;
        }

        .form-field {
            margin-bottom: 1.25rem;
            position: relative;
        }

        .form-field label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #34495e;
            font-size: 0.95rem;
        }

        .form-field input[type="text"],
        .form-field input[type="email"],
        .form-field input[type="date"],
        .form-field input[type="number"],
        .form-field input[type="tel"],
        .form-field select,
        .form-field textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 1rem;
            transition: all 0.2s ease;
            background-color: #f9fafb;
        }

        .form-field input:focus,
        .form-field select:focus,
        .form-field textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            background-color: #fff;
        }

        .form-field textarea {
            min-height: 6rem;
            resize: vertical;
        }

        .form-field input[type="file"] {
            width: 100%;
            padding: 0.75rem;
            background: #f8f9fa;
            border: 1px dashed #3498db;
            border-radius: 0.375rem;
            font-size: 0.9rem;
        }

        .form-field input[type="file"]:focus {
            border-style: solid;
        }

        .form-help-text {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 0.25rem;
            line-height: 1.4;
            font-style: italic;
        }

        .required {
            color: #e74c3c;
            font-weight: bold;
        }

        .form-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .form-navigation button {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 1rem;
            flex: 1;
            min-width: 120px;
            text-align: center;
        }

        .prev-btn {
            background: #6b7280;
            color: white;
        }

        .next-btn, .submit-btn {
            background: #3498db;
            color: white;
        }

        .prev-btn:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }

        .next-btn:hover, .submit-btn:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }

        optgroup {
            font-weight: 600;
            font-style: normal;
            color: #374151;
        }

        optgroup option {
            font-weight: normal;
            padding-left: 1.25rem;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Améliorations responsive */
        @media (max-width: 768px) {
            .visa-form-wrapper {
                padding: 1.25rem;
                margin: 0 1rem;
                width: auto;
                max-width: none;
                box-shadow: none;
                border-radius: 0;
            }
            
            .form-section-title {
                font-size: 1.3rem;
            }
            
            .form-subtitle {
                font-size: 1.1rem;
            }
            
            .form-field input[type="text"],
            .form-field input[type="email"],
            .form-field input[type="date"],
            .form-field select,
            .form-field textarea {
                padding: 0.65rem;
                font-size: 0.95rem;
            }
            
            .form-navigation {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .form-navigation button {
                width: 100%;
                padding: 0.85rem;
            }
        }

        @media (max-width: 480px) {
            .visa-form-wrapper {
                padding: 1rem;
            }
            
            .form-section-title {
                font-size: 1.25rem;
            }
            
            .form-subtitle {
                font-size: 1.05rem;
            }
        }

        /* Amélioration pour les petits écrans en mode portrait */
        @media (max-width: 400px) and (orientation: portrait) {
            .form-field label {
                font-size: 0.9rem;
            }
            
            .form-field input[type="text"],
            .form-field input[type="email"],
            .form-field input[type="date"],
            .form-field select,
            .form-field textarea {
                padding: 0.6rem;
                font-size: 0.9rem;
            }
        }

        /* Style pour les erreurs de validation */
        .form-field input:invalid,
        .form-field select:invalid,
        .form-field textarea:invalid {
            border-color: #e74c3c;
        }

        .form-field input:invalid:focus,
        .form-field select:invalid:focus,
        .form-field textarea:invalid:focus {
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.2);
        }

        /* Style pour le tableau*/
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1em;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 8px;
        }
        input {
            width: 100%;
            box-sizing: border-box;
        }
    </style>
    <?php
}

function render_mandat_section()
{
    ?>
    <div id="mandat-section" style="display: none; margin: 30px 0; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);">
        <h3 style="text-align: center; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 15px; margin-bottom: 25px;">Approbation du mandat</h3>
        <div id="mandat-preview" style="height: 400px; overflow-y: auto; margin-bottom: 25px; padding: 20px; background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 5px; line-height: 1.6;"></div>
        
        <div style="margin: 30px 0; padding: 20px; background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 5px;">
            <h4 style="color: #2c3e50; margin-bottom: 15px;">Autorisation de traitement des données par intelligence artificielle</h4>
            <p style="margin-bottom: 15px;">
                Je soussigné(e) autorise expressément Visa Logistics à utiliser des systèmes d'intelligence artificielle pour le traitement des données personnelles fournies dans le cadre de ma demande de visa, et ce dans les limites suivantes :
            </p>
            <ul style="margin-bottom: 15px; padding-left: 20px;">
                <li>Analyse et vérification des documents fournis</li>
                <li>Vérification de la cohérence et de l'exhaustivité du dossier</li>
                <li>Pré-remplissage de formulaires administratifs</li>
                <li>Optimisation du processus de demande de visa</li>
                <li>Détection de potentielles erreurs ou omissions</li>
            </ul>
            <p style="margin-bottom: 15px;">
                Je suis informé(e) que ces traitements automatisés sont réalisés dans le strict respect des réglementations en vigueur sur la protection des données personnelles (RGPD et loi informatique et libertés).
            </p>
            <p>
                Cette autorisation est valable pour la durée nécessaire au traitement de ma demande de visa et jusqu'à la finalisation complète de celle-ci.
            </p>
        </div>

        <div class="form-field" style="margin-bottom: 25px;">
            <label style="display: flex; align-items: center; cursor: pointer;">
                <input type="checkbox" name="approve_mandat" required style="margin-right: 10px; width: auto;"> 
                <span>Je reconnais avoir pris connaissance du mandat et de l'autorisation de traitement par IA ci-dessus et les approuve sans réserve</span>
            </label>
        </div>

        <div class="form-navigation">
            <button type="button" id="cancel-mandat" class="prev-btn">Retour</button>
            <button type="submit" id="confirm-submit" class="submit-btn">Confirmer et envoyer</button>
        </div>
    </div>
    <?php
}
function render_dossier_final_section()
{
    ?>
    <div id="dossier-final-section" style="display: none; margin: 30px 0; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);">
        <h3 style="text-align: center; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 15px; margin-bottom: 25px;">Dossier Final - Visa Professionnel</h3>
        <div id="dossier-final-content" style="height: 400px; overflow-y: auto; margin-bottom: 25px; padding: 20px; background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 5px; line-height: 1.6; font-family: Arial, sans-serif;"></div>
        
        <div class="form-field" style="margin-bottom: 25px;">
            <label style="display: flex; align-items: center; cursor: pointer;">
                <input type="checkbox" name="approve_dossier" required style="margin-right: 10px; width: auto;"> 
                <span>Je certifie que les informations de ce dossier sont exactes et complètes</span>
            </label>
        </div>

        <div class="form-navigation">
            <button type="button" id="cancel-dossier" class="prev-btn">Retour</button>
            <button type="submit" id="confirm-dossier" class="submit-btn">Soumettre la demande</button>
        </div>
    </div>
    <?php
}
