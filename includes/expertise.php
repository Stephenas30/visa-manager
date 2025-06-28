<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expertise - Dossier Visa</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 24px;
            color: #002395; /* Bleu France */
            margin-bottom: 5px;
        }
        .header p {
            font-size: 14px;
            color: #666;
        }
        .section {
            margin-bottom: 25px;
        }
        .section h2 {
            font-size: 18px;
            color: #002395;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
        }
        .documents-list {
            list-style-type: none;
            padding-left: 0;
        }
        .documents-list li {
            margin-bottom: 8px;
            padding-left: 20px;
            position: relative;
        }
        .documents-list li:before {
            content: counter(item) ".";
            counter-increment: item;
            position: absolute;
            left: 0;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            font-style: italic;
        }
        .signature {
            margin-top: 50px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Expertise</h1>
        <p>Consulat Général de France à <span id="depot-ville">Alger</span></p>
        <p>Adresse du Consulat <span id="depot-ville-2">Alger</span>, Algérie</p>
    </div>

    <div class="section">
        <p><strong>Objet :</strong> Demande de visa de <span id="visa-type">visite professionnelle</span></p>
        
        <p>Monsieur le Consul Général,</p>
        
        <p>Je soussigné, <span id="nom-complet">Prénom NOM</span>, sollicite par la présente un visa de type "<span id="visa-type-2">visite professionnelle</span>" pour me rendre en France, à <span id="ville-destination">Lons-le-Saulnier (Doubs)</span>, dans le cadre d'une <span id="motif-voyage">mission professionnelle</span>. Ci-après, les informations détaillées relatives à ma demande de visa, ainsi que les pièces justificatives correspondantes.</p>
    </div>

    <div class="section">
        <h2>Informations personnelles</h2>
        
        <div class="info-grid">
            <div class="info-label">Nom :</div>
            <div id="nom">DUPONT</div>
            
            <div class="info-label">Prénom :</div>
            <div id="prenom">Éric</div>
            
            <div class="info-label">Date de naissance :</div>
            <div id="date-naissance">12 février 2001</div>
            
            <div class="info-label">Lieu de naissance :</div>
            <div id="lieu-naissance">Alger</div>
            
            <div class="info-label">Numéro de passeport :</div>
            <div id="numero-passeport">1/2024</div>
            
            <div class="info-label">Adresse :</div>
            <div id="adresse">Cité Garidi, n° 55, appartement 25, Alger</div>
            
            <div class="info-label">Téléphone :</div>
            <div id="telephone">123456789</div>
            
            <div class="info-label">Email :</div>
            <div id="email">eric.dupontpersonnel@gmail.com</div>
        </div>
    </div>

    <div class="section">
        <h2>Informations professionnelles</h2>
        
        <div class="info-grid">
            <div class="info-label">Entreprise :</div>
            <div id="entreprise">Condor SA</div>
            
            <div class="info-label">Profession :</div>
            <div id="profession">Technicien supérieur en électronique</div>
            
            <div class="info-label">Revenu mensuel :</div>
            <div id="revenu">80 000 DZD</div>
            
            <div class="info-label">Référent professionnel :</div>
            <div id="referent">M. Mohamed MARTIN, Directeur des Ressources Humaines</div>
        </div>
        
        <p>Documents justificatifs :</p>
        <ul class="documents-list">
            <li>Registre de commerce de l'entreprise <span id="entreprise-2">Condor SA</span></li>
            <li>Fiche de paie récente</li>
            <li>Déclaration de revenus</li>
        </ul>
    </div>

    <div class="section">
        <h2>Détails du voyage</h2>
        
        <div class="info-grid">
            <div class="info-label">Nature du voyage :</div>
            <div id="nature-voyage">Mission professionnelle à <span id="ville-destination-2">Lons-le-Saulnier (Doubs)</span>, France</div>
            
            <div class="info-label">Dates du voyage :</div>
            <div id="dates-voyage">Du 15 janvier 2025 au 10 février 2025</div>
            
            <div class="info-label">Destination :</div>
            <div id="destination">Lons-le-Saulnier, Doubs, France</div>
            
            <div class="info-label">Référence vol :</div>
            <div id="reference-vol">Vol aller-retour du 15 janvier 2025 au 10 février 2025</div>
            
            <div class="info-label">Hébergement :</div>
            <div id="hebergement">Invitation officielle de la société Daikin</div>
            
            <div class="info-label">Prise en charge :</div>
            <div id="prise-en-charge">La société Daikin prendra en charge l'intégralité des frais liés au séjour</div>
            
            <div class="info-label">Budget personnel :</div>
            <div id="budget">2 000 euros en numéraire</div>
        </div>
    </div>

    <div class="page-break"></div> <!-- Saut de page pour impression -->

    <div class="section">
        <h2>Assurance voyage</h2>
        <p>Couverture de l'assurance pour la période du <span id="dates-assurance">15 janvier 2025 au 10 février 2025</span></p>
    </div>

    <div class="section">
        <h2>Engagement de l'entreprise</h2>
        <p>Je joins également à ce dossier un courrier officiel de la société <span id="entreprise-invitante">Daikin</span>, qui engage sa responsabilité pour l'ensemble de la prise en charge des frais relatifs à ce voyage professionnel. Cette prise en charge inclut non seulement l'hébergement et les frais de transport, mais aussi tout autre coût lié à la mission.</p>
    </div>

    <div class="section">
        <h2>Historique des visas</h2>
        <p>Je souhaite également attirer votre attention sur le fait que j'ai précédemment obtenu plusieurs visas pour la France. Ces séjours se sont déroulés dans le cadre de voyages professionnels, et j'ai toujours respecté les conditions de séjour prévues par les autorités françaises.</p>
    </div>

    <div class="section">
        <h2>Documents joints à cette demande</h2>
        <ol class="documents-list" style="counter-reset: item;">
            <li>Passeport (numéro <span id="numero-passeport-2">1/2024</span>)</li>
            <li>Registre de commerce de l'entreprise <span id="entreprise-3">Condor SA</span></li>
            <li>Fiche de paie (justificatif de revenu)</li>
            <li>Déclaration de revenus</li>
            <li>Réservation de vol aller-retour du <span id="dates-vol">15 janvier 2025 au 10 février 2025</span></li>
            <li>Invitation de la société <span id="entreprise-invitante-2">Daikin</span></li>
            <li>Justificatif de l'assurance voyage couvrant la période du <span id="dates-assurance-2">15 janvier 2025 au 10 février 2025</span></li>
            <li>Preuve de fonds personnels : <span id="budget-2">2 000 euros</span> en numéraire</li>
            <li>Copies des anciens visas pour la France (si applicable)</li>
        </ol>
    </div>

    <div class="footer">
        <p>Je vous remercie de bien vouloir examiner ma demande et reste à votre disposition pour toute information complémentaire ou pour fournir d'autres documents si nécessaire.</p>
        
        <p>Dans l'attente de votre réponse favorable, je vous prie d'agréer, Monsieur le Consul Général, l'expression de mes salutations distinguées.</p>
    </div>

    <div class="signature">
        <p><strong><span id="nom-complet-2">Prénom NOM</span></strong></p>
        <p>Téléphone : <span id="telephone-2">123456789</span></p>
        <p>Email : <span id="email-2">eric.dupontpersonnel@gmail.com</span></p>
    </div>

    <script>
        // Fonction pour remplir automatiquement les données depuis le formulaire
        function fillDataFromForm(formData) {
            // Informations personnelles
            document.getElementById('nom-complet').textContent = formData.nom + ' ' + formData.prenom;
            document.getElementById('nom-complet-2').textContent = formData.nom + ' ' + formData.prenom;
            document.getElementById('nom').textContent = formData.nom;
            document.getElementById('prenom').textContent = formData.prenom;
            document.getElementById('date-naissance').textContent = formData.date_naissance;
            document.getElementById('lieu-naissance').textContent = formData.lieu_naissance;
            document.getElementById('numero-passeport').textContent = formData.numero_doc_voyage;
            document.getElementById('numero-passeport-2').textContent = formData.numero_doc_voyage;
            document.getElementById('adresse').textContent = formData.numero_rue + ', ' + formData.ville;
            document.getElementById('telephone').textContent = formData.telephone;
            document.getElementById('telephone-2').textContent = formData.telephone;
            document.getElementById('email').textContent = formData.email;
            document.getElementById('email-2').textContent = formData.email;
            
            // Informations professionnelles
            document.getElementById('entreprise').textContent = formData.nom_employeur;
            document.getElementById('entreprise-2').textContent = formData.nom_employeur;
            document.getElementById('entreprise-3').textContent = formData.nom_employeur;
            document.getElementById('profession').textContent = formData.profession;
            
            // Détails du voyage
            document.getElementById('visa-type').textContent = formData.motif_visa_type;
            document.getElementById('visa-type-2').textContent = formData.motif_visa_type;
            document.getElementById('motif-voyage').textContent = formData.motif;
            document.getElementById('dates-voyage').textContent = 'Du ' + formData.date_arrivee + ' au ' + formData.date_depart;
            document.getElementById('dates-vol').textContent = 'Du ' + formData.date_arrivee + ' au ' + formData.date_depart;
            document.getElementById('ville-destination').textContent = formData.adresse_sejour;
            document.getElementById('ville-destination-2').textContent = formData.adresse_sejour;
            document.getElementById('destination').textContent = formData.adresse_sejour;
            document.getElementById('nature-voyage').textContent = formData.motif_visa_type + ' à ' + formData.adresse_sejour;
            document.getElementById('prise-en-charge').textContent = formData.nom_prise_en_charge ? 
                'Prise en charge par ' + formData.nom_prise_en_charge : 
                'Prise en charge personnelle';
            
            // Ville de dépôt
            if(formData.depot_ville) {
                document.getElementById('depot-ville').textContent = formData.depot_ville;
                document.getElementById('depot-ville-2').textContent = formData.depot_ville;
            }
        }

        // Exemple d'utilisation avec des données de test
        const sampleData = {
            nom: 'DUPONT',
            prenom: 'Éric',
            date_naissance: '12 février 2001',
            lieu_naissance: 'Alger',
            numero_doc_voyage: '1/2024',
            numero_rue: 'Cité Garidi, n° 55, appartement 25',
            ville: 'Alger',
            telephone: '123456789',
            email: 'eric.dupontpersonnel@gmail.com',
            nom_employeur: 'Condor SA',
            profession: 'Technicien supérieur en électronique',
            motif_visa_type: 'visite professionnelle',
            motif: 'mission professionnelle',
            date_arrivee: '15 janvier 2025',
            date_depart: '10 février 2025',
            adresse_sejour: 'Lons-le-Saulnier (Doubs)',
            nom_prise_en_charge: 'société Daikin',
            depot_ville: 'Alger'
        };

        // Remplir avec les données d'exemple (à remplacer par les données réelles du formulaire)
        fillDataFromForm(sampleData);
    </script>
</body>
</html>