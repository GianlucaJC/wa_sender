<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Informativa Privacy - FilleaOFFICE WhatsApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 800px; }
        h1, h2 { color: #8d0c10; }
        .card { border: none; }
    </style>
</head>
<body>
    <div class="container my-4 my-md-5">
        <div class="card shadow-sm">
            <div class="card-body p-4 p-md-5">
                <h1 class="display-6 fw-bold mb-4">Informativa sulla Privacy</h1>
                <p class="text-muted">Ultimo aggiornamento: 26 Febbraio 2026</p>

                <p>La presente Informativa sulla Privacy descrive come le tue informazioni personali vengono raccolte, utilizzate e condivise quando utilizzi il nostro servizio di invio campagne WhatsApp (il "Servizio").</p>

                <h2 class="h4 mt-5 mb-3">1. Titolare del Trattamento dei Dati</h2>
                <p>
                    Il Titolare del Trattamento dei Dati è <strong>[NOME DELLA TUA AZIENDA/ORGANIZZAZIONE]</strong>, con sede in <strong>[Il tuo indirizzo]</strong>.
                    <br>
                    Email di contatto del Titolare: <strong>[La tua email di contatto per la privacy]</strong>
                </p>

                <h2 class="h4 mt-5 mb-3">2. Dati Personali Raccolti</h2>
                <p>Raccogliamo le seguenti categorie di dati personali per fornire e migliorare il Servizio:</p>
                <ul>
                    <li>
                        <strong>Dati di Connessione all'Account WhatsApp:</strong> Quando colleghi il tuo account WhatsApp Business tramite il processo di "Embedded Signup" di Facebook, riceviamo da Meta Platforms, Inc. le seguenti informazioni necessarie per operare:
                        <ul>
                            <li>Il tuo nome account (da te fornito sulla nostra piattaforma).</li>
                            <li>Il nome della tua azienda (fornito da Meta).</li>
                            <li>L'ID del tuo WhatsApp Business Account (WABA ID).</li>
                            <li>L'ID del tuo numero di telefono collegato.</li>
                            <li>Il numero di telefono in formato visualizzabile.</li>
                            <li>Un token di accesso con scadenza, necessario per autorizzare le chiamate API per tuo conto.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Dati delle Campagne:</strong> Quando crei una campagna, raccogliamo:
                        <ul>
                            <li>Il nome che assegni alla campagna.</li>
                            <li>Il template di messaggio selezionato.</li>
                            <li>L'elenco dei destinatari (numeri di telefono e nomi) che carichi o selezioni.</li>
                        </ul>
                    </li>
                </ul>

                <h2 class="h4 mt-5 mb-3">3. Finalità del Trattamento</h2>
                <p>Utilizziamo i dati raccolti per le seguenti finalità:</p>
                <ul>
                    <li><strong>Fornitura del Servizio:</strong> Per permetterti di collegare il tuo account WhatsApp e inviare campagne di messaggistica ai destinatari da te specificati.</li>
                    <li><strong>Sicurezza e Manutenzione:</strong> Per garantire la sicurezza della piattaforma e per la manutenzione tecnica. Il token di accesso viene memorizzato in formato crittografato per proteggerlo da accessi non autorizzati.</li>
                    <li><strong>Supporto Utente:</strong> Per assisterti in caso di problemi tecnici o domande relative al Servizio.</li>
                </ul>
                <p>La base giuridica del trattamento è l'esecuzione di un contratto di cui sei parte (la fornitura del Servizio richiesto).</p>

                <h2 class="h4 mt-5 mb-3">4. Condivisione dei Dati</h2>
                <p>Non vendiamo né condividiamo i tuoi dati personali con terze parti per scopi di marketing.</p>
                <p>I tuoi dati vengono condivisi solo con <strong>Meta Platforms, Inc.</strong> (Facebook/WhatsApp) come parte integrante del funzionamento del Servizio. Ogni invio di messaggio richiede una comunicazione con le loro API, utilizzando il tuo token di accesso e gli ID associati al tuo account.</p>

                <h2 class="h4 mt-5 mb-3">5. Sicurezza e Conservazione dei Dati</h2>
                <p>Adottiamo misure di sicurezza tecniche e organizzative per proteggere i tuoi dati. In particolare, i token di accesso, che sono credenziali sensibili, vengono conservati nel nostro database in formato <strong>crittografato</strong>.</p>
                <p>I dati relativi al tuo account e alle tue campagne vengono conservati per tutto il tempo in cui il tuo account è attivo sulla nostra piattaforma. Puoi richiedere la rimozione del tuo account in qualsiasi momento.</p>

                <h2 class="h4 mt-5 mb-3">6. I Tuoi Diritti</h2>
                <p>In qualità di interessato, hai il diritto di:</p>
                <ul>
                    <li><strong>Accedere</strong> ai tuoi dati personali che conserviamo.</li>
                    <li>Chiedere la <strong>rettifica</strong> di dati inesatti.</li>
                    <li>Chiedere la <strong>cancellazione</strong> del tuo account e dei dati associati. Puoi farlo autonomamente dalla sezione "Account WhatsApp" della piattaforma.</li>
                    <li><strong>Opporti</strong> al trattamento o chiederne la limitazione, nei casi previsti dalla legge.</li>
                </ul>
                <p>Per esercitare i tuoi diritti, puoi contattarci all'indirizzo email fornito al punto 1.</p>

                <h2 class="h4 mt-5 mb-3">7. Modifiche a questa Informativa</h2>
                <p>Potremmo aggiornare questa informativa sulla privacy di tanto in tanto per riflettere, ad esempio, modifiche alle nostre pratiche o per altre ragioni operative, legali o normative. Ti invitiamo a consultare periodicamente questa pagina.</p>

                <div class="text-center mt-5">
                    <a href="{{ url()->previous(route('campaigns.create')) }}" class="btn btn-secondary">Torna alla pagina precedente</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

