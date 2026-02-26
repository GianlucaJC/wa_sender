<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Guida Utente - FilleaOFFICE WhatsApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .accordion-button:not(.collapsed) {
            background-color: #fdf0f1;
            color: #8d0c10;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <header class="mb-5 text-center">
            <h1 class="display-5 fw-bold"><i class="bi bi-question-circle-fill"></i> Guida Utente</h1>
            <p class="lead text-secondary">Come utilizzare lo strumento di invio massivo WhatsApp.</p>
        </header>

        <main>
            <div class="accordion" id="docsAccordion">

                <!-- Introduzione -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingIntro">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseIntro" aria-expanded="true" aria-controls="collapseIntro">
                            <i class="bi bi-rocket-takeoff-fill me-2"></i> A cosa serve questo strumento?
                        </button>
                    </h2>
                    <div id="collapseIntro" class="accordion-collapse collapse show" aria-labelledby="headingIntro" data-bs-parent="#docsAccordion">
                        <div class="accordion-body">
                            <p>Questo strumento permette di inviare <strong>messaggi massivi su WhatsApp</strong> a una lista di contatti, utilizzando dei "template" di messaggio pre-approvati da Meta (Facebook/WhatsApp).</p>
                            <p>Il processo è diviso in 3 fasi principali:</p>
                            <ol>
                                <li><strong>Creazione della Campagna:</strong> Si definisce il nome, si scelgono i destinatari e il messaggio da inviare.</li>
                                <li><strong>Conferma e Avvio:</strong> Si controllano i dati e si avvia l'invio.</li>
                                <li><strong>Monitoraggio:</strong> Si controlla lo stato di avanzamento della campagna.</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Step 1: Creazione Campagna -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingStep1">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStep1" aria-expanded="false" aria-controls="collapseStep1">
                            <i class="bi bi-1-circle-fill me-2"></i> Step 1: Creare una Nuova Campagna
                        </button>
                    </h2>
                    <div id="collapseStep1" class="accordion-collapse collapse" aria-labelledby="headingStep1" data-bs-parent="#docsAccordion">
                        <div class="accordion-body">
                            <p>Nella pagina principale, compila i seguenti campi:</p>
                            <ul>
                                <li><strong>Nome Campagna:</strong> Un nome a tua scelta per riconoscere l'invio (es. "Invito Assemblea Giugno 2024"). Non verrà visto dai destinatari.</li>
                                <li>
                                    <strong>Modalità di Invio Destinatari:</strong> Scegli da dove prendere i numeri di telefono. La modalità più comune è <strong>"Da file Excel/CSV"</strong>.
                                </li>
                                <li>
                                    <strong>Carica File Destinatari:</strong> Se hai scelto "Da file", qui devi caricare il tuo file.
                                    <div class="alert alert-warning mt-2">
                                        <strong>Attenzione:</strong> Il file deve essere in formato <strong>CSV</strong> con il <strong>punto e virgola (;)</strong> come separatore. La prima riga deve contenere i nomi delle colonne (es. <code>Nome;Cognome;Cellulare</code>).
                                    </div>
                                </li>
                                <li>
                                    <strong>Template Messaggio Approvato:</strong> Scegli dall'elenco il messaggio che vuoi inviare. Questi sono messaggi pre-approvati da WhatsApp. Se il template contiene delle variabili (es. <code>Ciao {{1}}...</code>), il sistema le sostituirà automaticamente con i dati presi dal tuo file (es. il nome della persona).
                                </li>
                                <li>
                                    <strong>Invio Test Singolo:</strong> Prima di avviare la campagna, puoi inviare un messaggio di prova a un tuo numero per verificare che tutto sia corretto.
                                </li>
                            </ul>
                            <p>Una volta compilato tutto, clicca su <strong>"Crea e Visualizza Destinatari"</strong> per passare allo step successivo.</p>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Mappatura -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingStep2">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStep2" aria-expanded="false" aria-controls="collapseStep2">
                            <i class="bi bi-2-circle-fill me-2"></i> Step 2: Mappatura e Avvio
                        </button>
                    </h2>
                    <div id="collapseStep2" class="accordion-collapse collapse" aria-labelledby="headingStep2" data-bs-parent="#docsAccordion">
                        <div class="accordion-body">
                            <p>In questa schermata devi "mappare" i campi, cioè dire al sistema quale colonna del tuo file corrisponde a quale informazione.</p>
                            <ul>
                                <li>
                                    <strong>Campo "Nominativo":</strong> Seleziona la colonna del tuo file che contiene il nome della persona (es. "Nome" o "Nominativo"). Questo verrà usato per personalizzare il messaggio se il template lo prevede.
                                </li>
                                <li>
                                    <strong>Campo "Numero Cellulare":</strong> <span class="text-danger fw-bold">Questo è il campo più importante.</span> Seleziona la colonna che contiene i numeri di cellulare.
                                </li>
                            </ul>
                            <p>Dopo aver associato le colonne, clicca su <strong>"Prosegui e Avvia Campagna"</strong>. L'invio partirà in background.</p>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Monitoraggio -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingStep3">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStep3" aria-expanded="false" aria-controls="collapseStep3">
                            <i class="bi bi-3-circle-fill me-2"></i> Step 3: Monitorare l'Avanzamento
                        </button>
                    </h2>
                    <div id="collapseStep3" class="accordion-collapse collapse" aria-labelledby="headingStep3" data-bs-parent="#docsAccordion">
                        <div class="accordion-body">
                            <p>Dopo aver avviato la campagna, verrai reindirizzato alla pagina di avanzamento. Qui puoi vedere in tempo reale:</p>
                            <ul>
                                <li><strong>Stato Campagna:</strong> Indica se la campagna è "In Elaborazione", "Completata" o "Fallita".</li>
                                <li><strong>Barra di Progresso:</strong> Mostra visivamente quanti messaggi sono stati inviati (in verde) e quanti sono falliti (in rosso).</li>
                                <li><strong>Contatori:</strong> Indicano il numero esatto di messaggi inviati, falliti e il totale.</li>
                            </ul>
                            <p>Puoi accedere a questa pagina in qualsiasi momento anche dallo <strong>"Storico Campagne"</strong>, accessibile dalla pagina principale.</p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="text-center mt-5">
                <a href="{{ route('campaigns.create') }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-arrow-left"></i> Torna alla Creazione Campagna
                </a>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>