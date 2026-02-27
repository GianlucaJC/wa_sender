<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php($is_admin = true) {{-- SIMULAZIONE ADMIN --}}
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>FilleaOFFICE WhatsApp - Nuova Campagna</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background-color: #8d0c10;
        }
        header h1, header p, footer {
            color: white;
        }
        header p {
            color: rgba(255, 255, 255, 0.85);
        }
        .btn-outline-white {
            --bs-btn-color: #fff;
            --bs-btn-border-color: #fff;
            --bs-btn-hover-color: #8d0c10;
            --bs-btn-hover-bg: #fff;
            --bs-btn-hover-border-color: #fff;
        }
    </style>
</head>
<body>
    <div class="container my-4 my-md-5">
        <header class="mb-5 text-center">
            <h1 class="display-5 fw-bold"><i class="bi bi-whatsapp"></i> FilleaOFFICE WhatsApp</h1>
            <p class="lead">Configura i dettagli della tua campagna di messaggistica massiva.</p>
        </header>

        <div class="text-center mb-4">
            <a href="{{ route('docs.index') }}" class="btn btn-outline-white"><i class="bi bi-question-circle"></i> Guida Utente</a>
            <a href="{{ route('campaigns.index') }}" class="btn btn-outline-white"><i class="bi bi-archive"></i> Storico Campagne</a>
            @if($is_admin)
            <a href="{{ route('templates.index') }}" class="btn btn-outline-white"><i class="bi bi-card-list"></i> Gestisci Template</a>
            @endif
        </div>

        <main class="card shadow-sm">
            <div class="card-body p-4 p-md-5">
                <form id="campaignForm" action="{{ route('campaigns.launch.unified') }}" method="POST">
                    @csrf

                    {{-- Session and validation errors will be handled by Swal at the end of the body --}}

                    <!-- Selezione Account WhatsApp -->
                    <div class="mb-4">
                        <label for="whatsapp_account_id" class="form-label">Account di Invio</label>
                        <select id="whatsapp_account_id" name="whatsapp_account_id" class="form-select form-select-lg" required @if($whatsappAccounts->isEmpty()) disabled @endif>
                            <option value="" selected>Scegli un account WhatsApp...</option>
                            @forelse($whatsappAccounts as $account)
                                <option value="{{ $account->id }}" @if(old('whatsapp_account_id', $campaignData['whatsapp_account_id'] ?? null) == $account->id) selected @endif>
                                    {{ $account->name }} ({{ $account->phone_number_display }})
                                </option>
                            @empty
                                <option value="" disabled>Nessun account collegato. <a href="{{ route('whatsapp-accounts.create') }}">Collegalo ora</a>.</option>
                            @endforelse
                        </select>
                        @if($whatsappAccounts->isEmpty())
                            <div class="form-text text-danger mt-2">Devi prima collegare un account WhatsApp per poter creare una campagna. <a href="{{ route('whatsapp-accounts.create') }}">Vai alla gestione account</a>.</div>
                        @endif
                    </div>

                    <!-- Nome Campagna -->
                    <div class="mb-4">
                        <label for="campaign_name" class="form-label">Nome Campagna</label>
                        <input type="text" id="campaign_name" name="campaign_name" class="form-control form-control-lg" placeholder="Es: Promozione Estiva" value="{{ old('campaign_name', $campaignData['campaign_name'] ?? '') }}" required>
                    </div>

                    <!-- Tipologia di Invio -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Modalità di Invio Destinatari</label>
                        <div class="card card-body bg-body">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="recipient_source" id="source_fillea" value="fillea_tabulato" {{ old('recipient_source', $campaignData['recipient_source'] ?? 'fillea_tabulato') == 'fillea_tabulato' ? 'checked' : '' }}>
                                <label class="form-check-label" for="source_fillea">
                                    Attivi iscritti FILLEA da tabulato
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="recipient_source" id="source_assemblea" value="assemblea_generale" {{ old('recipient_source', $campaignData['recipient_source'] ?? null) == 'assemblea_generale' ? 'checked' : '' }}>
                                <label class="form-check-label" for="source_assemblea">
                                    Assemblea generale / Comitato direttivo
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="recipient_source" id="source_organismi" value="organismi_dirigenti" {{ old('recipient_source', $campaignData['recipient_source'] ?? null) == 'organismi_dirigenti' ? 'checked' : '' }}>
                                <label class="form-check-label" for="source_organismi">
                                    Organismi dirigenti della tua struttura
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="recipient_source" id="source_file" value="file_upload" {{ old('recipient_source', $campaignData['recipient_source'] ?? null) == 'file_upload' ? 'checked' : '' }}>
                                <label class="form-check-label" for="source_file">
                                    Da file Excel/CSV
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Sezione Upload File (visibile solo se si sceglie l'opzione file) -->
                    <div id="file_upload_section" class="mb-4" style="display: none;">
                        <label for="recipient_file" class="form-label">Carica File Destinatari</label>
                        <input class="form-control form-control-lg" type="file" id="recipient_file" name="recipient_file" accept=".csv">
                        <div class="form-text mt-2">
                            Il file verrà caricato automaticamente. Sono ammessi solo file CSV con separatore punto e virgola (;).
                        </div>
                        <!-- Progress Bar -->
                        <div id="upload-progress-container" class="mt-3" style="display: none;">
                            <div class="progress" style="height: 20px;">
                                <div id="upload-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                            </div>
                        </div>
                        <!-- Hidden input for file path -->
                        <input type="hidden" id="recipient_file_path" name="recipient_file_path">
                    </div>

                    <!-- Selezione Template -->
                    <div class="mb-4">
                        <label for="message_template_name" class="form-label">Template Messaggio Approvato</label>
                        <select id="message_template_name" name="message_template" class="form-select form-select-lg" required>
                            <option value="" selected>Scegli un template...</option>
                            @forelse($templates as $template)
                                <option value="{{ $template['name'] }}" @if(old('message_template', $campaignData['message_template'] ?? null) == $template['name']) selected @endif>{{ $template['name'] }}</option>
                            @empty
                                <option value="" disabled>Nessun template approvato trovato.</option>
                            @endforelse
                        </select>
                        @if(isset($templates_error) && $templates_error)
                            <div class="form-text text-danger mt-2">{{ $templates_error }}</div>
                        @endif
                    </div>

                    <!-- Allegati -->
                    <div class="mb-4" style='display:none'>
                        <label for="attachment_link" class="form-label">Link da allegare (opzionale)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                            <input type="url" id="attachment_link" name="attachment_link" class="form-control form-control-lg" placeholder="https://esempio.com/documento" value="{{ old('attachment_link', $campaignData['attachment_link'] ?? '') }}">
                        </div>
                        <div class="form-text mt-2">Il link verrà usato se il template lo prevede (es. in un pulsante o come variabile).</div>
                    </div>

                    <div class="mb-4" style='display:none'>
                        <label for="attachment_pdf" class="form-label">PDF da allegare (opzionale)</label>
                        <input class="form-control form-control-lg" type="file" id="attachment_pdf" name="attachment_pdf" accept="application/pdf">
                        <div class="form-text mt-2">Il PDF verrà inviato come documento se il template selezionato ha un header di tipo "Documento".</div>
                    </div>

                    <!-- Anteprima -->
                    <div class="mb-5">
                        <h3 class="h5 fw-semibold mb-2">Anteprima</h3>
                        <div class="card bg-body-secondary">
                            <div class="card-body" id="message_preview_container">
                                <p class="text-body-secondary" id="message_preview">L'anteprima del messaggio apparirà qui...</p>
                            </div>
                        </div>
                    </div>

                    <hr class="my-5">

                    <!-- Invio Test -->
                    <div class="mb-5">
                        <h3 class="h5 fw-semibold mb-3">Invio Test Singolo</h3>
                        <div class="d-flex align-items-center mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="test_send_method" id="test_method_api" value="api" checked>
                                <label class="form-check-label" for="test_method_api">Tramite API (Consigliato)</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="test_send_method" id="test_method_web" value="web">
                                <label class="form-check-label" for="test_method_web">Tramite WhatsApp Web</label>
                            </div>
                            <button type="button" class="btn btn-sm btn-link p-0" data-bs-toggle="popover" data-bs-html="true" data-bs-title="Differenza tra le modalità" data-bs-content="<b>Tramite API:</b> Simula un invio reale attraverso il sistema. È il test più affidabile.<br><br><b>Tramite WhatsApp Web:</b> Apre una chat in WhatsApp Web/Desktop con il messaggio pre-compilato. Utile per vedere l'anteprima del testo, ma non testa il sistema di invio.">
                                <i class="bi bi-info-circle"></i>
                            </button>
                        </div>
                        <div class="row g-2 align-items-center">
                            <div class="col-sm">
                                <label for="test_recipient" class="visually-hidden">Numero di telefono</label>
                                <input type="tel" class="form-control" id="test_recipient" placeholder="Numero di telefono per il test (es. 393331234567)">
                            </div>
                            <div class="col-sm-auto">
                                <button type="button" id="send_test_button" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-whatsapp"></i> Invia Messaggio di Prova
                                </button>
                            </div>
                        </div>
                        <div id="test-send-feedback" class="form-text mt-2"></div>
                    </div>

                    <!-- Pulsante di avvio -->
                    <div class="d-flex justify-content-end mt-5">
                        <button type="submit" id="main-launch-button" class="btn btn-primary btn-lg">
                            <i class="bi bi-send-check"></i> Avvia Campagna
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <!-- Mapping Modal -->
        <div class="modal fade" id="mappingModal" tabindex="-1" aria-labelledby="mappingModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="mappingModalLabel"><i class="bi bi-diagram-3"></i> Mappatura Colonne File</h5>
                    </div>
                    <div class="modal-body">
                        <p>Associa le colonne del tuo file ai campi richiesti per l'invio.</p>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="map_name" class="form-label">Campo "Nominativo"</label>
                                <select id="map_name" name="map_name" class="form-select" required></select>
                                <div class="form-text">Questo campo verrà usato per le variabili come <code>{{1}}</code>.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="map_phone" class="form-label fw-bold">Campo "Numero Cellulare" <span class="text-danger">*</span></label>
                                <select id="map_phone" name="map_phone" class="form-select" required></select>
                                <div class="form-text">Questo campo è obbligatorio per l'invio.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="document.getElementById('recipient_file').value = ''">Annulla</button>
                        <button type="button" id="validate-button" class="btn btn-primary">
                            <i class="bi bi-shield-check"></i> Valida File
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Validation Report Modal -->
        <div class="modal fade" id="validationReportModal" tabindex="-1" aria-labelledby="validationReportModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="validationReportModalLabel"><i class="bi bi-check2-circle"></i> Report di Validazione</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="validationReportModalBody">
                        {{-- Content will be injected by JS --}}
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                        <span class="form-text">Puoi avviare la campagna usando il pulsante in fondo alla pagina.</span>
                    </div>
                </div>
            </div>
        </div>

        <footer class="mt-5 text-center">
            WA Sender v1.0 | <a href="{{ route('privacy.policy') }}" class="text-white" target="_blank">Informativa Privacy</a>
        </footer>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // General elements
            const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
            const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
            const templateSelect = document.getElementById('message_template_name');
            const previewBox = document.getElementById('message_preview');
            const defaultPreviewText = previewBox.textContent;
            const templatesData = @json($templates);
            const csrfToken = document.querySelector('input[name="_token"]').value;

            // AJAX Flow elements
            const fileInput = document.getElementById('recipient_file');
            const filePathInput = document.getElementById('recipient_file_path');
            const progressContainer = document.getElementById('upload-progress-container');
            const progressBar = document.getElementById('upload-progress-bar');
            const mappingModalEl = document.getElementById('mappingModal');
            const mappingModal = new bootstrap.Modal(mappingModalEl);
            const validationModalEl = document.getElementById('validationReportModal');
            const validationModal = new bootstrap.Modal(validationModalEl);
            const mainLaunchButton = document.getElementById('main-launch-button');

            // Helper to show alerts using SweetAlert2
            function showSwalAlert(title, text, icon = 'error') {
                Swal.fire({
                    icon: icon,
                    title: title,
                    html: text,
                });
            }
            // --- Logica per la selezione della fonte dei destinatari ---
            const recipientSourceRadios = document.querySelectorAll('input[name="recipient_source"]');
            const fileUploadSection = document.getElementById('file_upload_section');

            const toggleFileUploadSection = () => {
                const selectedSource = document.querySelector('input[name="recipient_source"]:checked').value;
                if (selectedSource === 'file_upload') {
                    fileUploadSection.style.display = 'block';
                    mainLaunchButton.disabled = true;
                    mainLaunchButton.innerHTML = '<i class="bi bi-shield-check"></i> Prima Valida il File';
                } else {
                    fileUploadSection.style.display = 'none';
                    mainLaunchButton.disabled = false;
                    mainLaunchButton.innerHTML = '<i class="bi bi-send-check"></i> Avvia Campagna';
                }
            };

            recipientSourceRadios.forEach(radio => {
                radio.addEventListener('change', toggleFileUploadSection);
            });
            toggleFileUploadSection(); // Esegui al caricamento per impostare lo stato iniziale
            // Trigger change on template select to show preview for the pre-selected item
            if (templateSelect.value) templateSelect.dispatchEvent(new Event('change'));


            templateSelect.addEventListener('change', (event) => {
                const selectedTemplateName = event.target.value;
                if (!selectedTemplateName) {
                    previewBox.textContent = defaultPreviewText;
                    previewBox.className = 'text-body-secondary';
                    return;
                }

                const template = templatesData.find(t => t.name === selectedTemplateName);

                if (template) {
                    const bodyComponent = template.components.find(c => c.type === 'BODY');
                    if (bodyComponent) {
                        previewBox.className = 'text-body';
                        let previewText = bodyComponent.text.replace(/\{\{(\d+)\}\}/g, (match, p1) => {
                            return `<strong class="text-primary">[Variabile ${p1}]</strong>`;
                        });
                        previewBox.innerHTML = previewText;
                    }
                } else {
                     previewBox.textContent = defaultPreviewText;
                     previewBox.className = 'text-body-secondary';
                }
            });

            // --- Logica per Invio Test ---
            const sendTestBtn = document.getElementById('send_test_button');
            const testRecipientInput = document.getElementById('test_recipient');
            const testFeedback = document.getElementById('test-send-feedback');
            const accountSelect = document.getElementById('whatsapp_account_id');

            sendTestBtn.addEventListener('click', async () => {
                const recipient = testRecipientInput.value;
                const templateName = templateSelect.value;

                // Validazione input
                if (!recipient || !templateName) {
                    testFeedback.textContent = 'Per favore, seleziona un account, un template e inserisci un numero di telefono.';
                    testFeedback.className = 'form-text mt-2 text-danger';
                    return;
                }

                // UI feedback
                const selectedMethod = document.querySelector('input[name="test_send_method"]:checked').value;

                if (selectedMethod === 'web') {
                    const template = templatesData.find(t => t.name === templateName);
                    if (template) {
                        const bodyComponent = template.components.find(c => c.type === 'BODY');
                        if (bodyComponent) {
                            let messageText = bodyComponent.text.replace(/\{\{(\d+)\}\}/g, '[Variabile $1]');
                            const encodedMessage = encodeURIComponent(messageText);
                            // Pulisce il numero da caratteri non numerici per il link wa.me
                            const cleanRecipient = recipient.replace(/[^0-9]/g, '');
                            const url = `https://wa.me/${cleanRecipient}?text=${encodedMessage}`;
                            
                            window.open(url, '_blank');

                            testFeedback.textContent = `È stata aperta una nuova scheda per inviare il messaggio tramite WhatsApp Web/Desktop.`;
                            testFeedback.className = 'form-text mt-2 text-info';
                        }
                    }
                    return; // Termina qui per la modalità web
                }

                // Logica per la modalità API
                sendTestBtn.disabled = true;
                sendTestBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Invio in corso...`;
                testFeedback.textContent = '';
                testFeedback.className = 'form-text mt-2';

                try {
                    const response = await fetch('/campaigns/send-test', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            whatsapp_account_id: accountSelect.value,
                            recipient: recipient,
                            message_template: templateName,
                        })
                    });

                    const result = await response.json();

                    if (response.ok) {
                        testFeedback.textContent = `Messaggio di prova per ${recipient} inviato con successo. (Rif: ${result.message_id})`;
                        testFeedback.className = 'form-text mt-2 text-success';
                        testRecipientInput.value = ''; // Pulisce l'input
                    } else {
                        throw new Error(result.message || 'Si è verificato un errore durante l\'invio.');
                    }

                } catch (error) {
                    testFeedback.textContent = `Errore: ${error.message}`;
                    testFeedback.className = 'form-text mt-2 text-danger';
                } finally {
                    // Ripristina il pulsante
                    sendTestBtn.disabled = false;
                    sendTestBtn.innerHTML = `<i class="bi bi-whatsapp"></i> Invia Messaggio di Prova`;
                }
            });

            // --- Nuovo Flusso AJAX per Upload File ---

            fileInput.addEventListener('change', (event) => {
                const file = event.target.files[0];
                if (!file) return;

                // Reset launch button state
                mainLaunchButton.disabled = true;
                mainLaunchButton.innerHTML = '<i class="bi bi-shield-check"></i> Prima Valida il File';

                const formData = new FormData();
                formData.append('recipient_file', file);
                formData.append('_token', csrfToken);

                progressContainer.style.display = 'block';
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';

                const xhr = new XMLHttpRequest();
                xhr.open('POST', '{{ route("campaigns.ajax.upload") }}', true);

                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) {
                        const percentComplete = Math.round((e.loaded / e.total) * 100);
                        progressBar.style.width = percentComplete + '%';
                        progressBar.textContent = percentComplete + '%';
                    }
                };

                xhr.onload = () => {
                    setTimeout(() => { progressContainer.style.display = 'none'; }, 1000);
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        filePathInput.value = response.file_path;
                        populateMappingModal(response.headers);
                        mappingModal.show();
                    } else {
                        const error = JSON.parse(xhr.responseText);
                        showSwalAlert('Errore Caricamento', error.message);
                        fileInput.value = '';
                    }
                };

                xhr.onerror = () => {
                    showSwalAlert('Errore di Rete', 'Si è verificato un errore di rete durante il caricamento del file.');
                    progressContainer.style.display = 'none';
                };

                xhr.send(formData);
            });

            function populateMappingModal(headers) {
                const nameSelect = document.getElementById('map_name');
                const phoneSelect = document.getElementById('map_phone');
                const defaultOption = '<option value="" selected disabled>Scegli colonna...</option>';
                nameSelect.innerHTML = defaultOption;
                phoneSelect.innerHTML = defaultOption;
                headers.forEach(header => {
                    const option = `<option value="${header}">${header}</option>`;
                    nameSelect.innerHTML += option;
                    phoneSelect.innerHTML += option;
                });
            }

            document.getElementById('validate-button').addEventListener('click', async function() {
                const button = this;
                const mapName = document.getElementById('map_name').value;
                const mapPhone = document.getElementById('map_phone').value;
                const filePath = filePathInput.value;

                if (!mapName || !mapPhone) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Campi Mancanti',
                        text: 'Seleziona entrambe le colonne per il mapping prima di procedere.',
                    });
                    return;
                }

                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Validazione...';

                try {
                    const response = await fetch('{{ route("campaigns.ajax.validate") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: JSON.stringify({ file_path: filePath, map_name: mapName, map_phone: mapPhone })
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.message);

                    mappingModal.hide();
                    populateValidationModal(result.report);
                    validationModal.show();
                } catch (error) {
                    showSwalAlert('Errore Validazione', error.message);
                } finally {
                    button.disabled = false;
                    button.innerHTML = '<i class="bi bi-shield-check"></i> Valida File';
                }
            });

            function populateValidationModal(report) {
                const body = document.getElementById('validationReportModalBody');
                let invalidTable = '';
                if (report.invalid_count > 0) {
                    invalidTable = `<hr><h4 class="h5">Dettaglio Contatti Scartati</h4>
                        <div class="table-responsive" style="max-height: 200px;">
                            <table class="table table-sm table-striped">
                                <thead class="table-light"><tr><th>Riga</th><th>Nome</th><th>Numero</th><th>Motivo</th></tr></thead>
                                <tbody>${report.invalid_entries.map(e => `<tr><td>${e.line}</td><td>${e.name || '-'}</td><td><code>${e.phone || '(vuoto)'}</code></td><td><small>${e.reason}</small></td></tr>`).join('')}</tbody>
                            </table>
                        </div>`;
                }
                body.innerHTML = `<h4 class="h5">Riepilogo Scansione</h4>
                    <p>Sono stati analizzati <strong>${report.total_rows}</strong> contatti.</p>
                    <ul class="list-group mb-4">
                        <li class="list-group-item d-flex justify-content-between align-items-center"><div><i class="bi bi-person-check-fill text-success"></i> Contatti validi</div><span class="badge bg-success rounded-pill">${report.valid_count}</span></li>
                        <li class="list-group-item d-flex justify-content-between align-items-center"><div><i class="bi bi-magic text-info"></i> Contatti corretti</div><span class="badge bg-info rounded-pill">${report.normalized_count}</span></li>
                        <li class="list-group-item d-flex justify-content-between align-items-center"><div><i class="bi bi-person-x-fill text-danger"></i> Contatti scartati</div><span class="badge bg-danger rounded-pill">${report.invalid_count}</span></li>
                    </ul>
                    ${invalidTable}`;
                
                mainLaunchButton.disabled = report.valid_count === 0;
                mainLaunchButton.innerHTML = `<i class="bi bi-send-check"></i> Avvia Campagna per ${report.valid_count} Contatti`;
            }

            // --- Gestione Avvio Campagna con Conferma ---
            const campaignForm = document.getElementById('campaignForm');
            campaignForm.addEventListener('submit', function(event) {
                event.preventDefault(); // Blocca l'invio standard del form

                const launchButton = document.getElementById('main-launch-button');
                const recipientCountText = launchButton.textContent.match(/\d+/);
                const recipientCount = recipientCountText ? parseInt(recipientCountText[0], 10) : 'diversi';

                Swal.fire({
                    title: 'Sei sicuro?',
                    text: `Stai per avviare una campagna per ${recipientCount} destinatari. L'azione non è reversibile.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sì, avvia campagna!',
                    cancelButtonText: 'Annulla'
                }).then((result) => {
                    if (result.isConfirmed) {
                        launchButton.disabled = true;
                        launchButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Avvio in corso...`;
                        campaignForm.submit(); // Invia il form
                    }
                });
            });

            @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Attenzione!',
                text: "{{ session('error') }}"
            });
            @endif

            @if ($errors->any())
                (function() {
                    let errorHtml = '<ul class="list-unstyled text-start">';
                    @foreach ($errors->all() as $error)
                        errorHtml += '<li>- {{ $error }}</li>';
                    @endforeach
                    errorHtml += '</ul>';

                    Swal.fire({
                        icon: 'error',
                        title: 'Errore di Validazione',
                        html: errorHtml,
                    });
                })();
            @endif
        });
    </script>
</body>
</html>
