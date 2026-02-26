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
                <form action="{{ route('campaigns.store') }}" method="POST" id="campaignForm" enctype="multipart/form-data">
                    @csrf

                    @if(session('error'))
                        <div class="alert alert-danger mb-4">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger mb-4">
                            <strong>Attenzione!</strong> Correggi i seguenti errori:
                            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif

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
                        <input class="form-control form-control-lg" type="file" id="recipient_file" name="recipient_file" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
                        <div class="form-text mt-2">
                            Carica un file con i contatti. Verrà richiesto di mappare le colonne (es. nominativo, cellulare) nel prossimo step.
                        </div>
                    </div>

                    <!-- Selezione Template -->
                    <div class="mb-4">
                        <label for="message_template_name" class="form-label">Template Messaggio Approvato</label>
                        <select id="message_template_name" name="message_template" class="form-select form-select-lg" required>
                            <option value="" @if(!old('message_template') && empty($campaignData['message_template'])) selected @endif disabled>Scegli un template...</option>
                            @forelse($templates as $template)
                                <option value="{{ $template['name'] }}" {{ old('message_template', $campaignData['message_template'] ?? null) == $template['name'] ? 'selected' : '' }}>{{ $template['name'] }}</option>
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
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-whatsapp"></i> Crea e Visualizza Destinatari
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <footer class="mt-5 text-center">
            WA Sender v1.0
        </footer>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
            const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));

            const templateSelect = document.getElementById('message_template_name');
            const previewBox = document.getElementById('message_preview');
            const defaultPreviewText = previewBox.textContent;
            const templatesData = @json($templates);

            // --- Logica per la selezione della fonte dei destinatari ---
            const recipientSourceRadios = document.querySelectorAll('input[name="recipient_source"]');
            const fileUploadSection = document.getElementById('file_upload_section');

            const toggleFileUploadSection = () => {
                const selectedSource = document.querySelector('input[name="recipient_source"]:checked').value;
                if (selectedSource === 'file_upload') {
                    fileUploadSection.style.display = 'block';
                } else {
                    fileUploadSection.style.display = 'none';
                }
            };

            recipientSourceRadios.forEach(radio => {
                radio.addEventListener('change', toggleFileUploadSection);
            });
            toggleFileUploadSection(); // Esegui al caricamento per impostare lo stato iniziale

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

            sendTestBtn.addEventListener('click', async () => {
                const recipient = testRecipientInput.value;
                const templateName = templateSelect.value;
                const csrfToken = document.querySelector('input[name="_token"]').value;

                // Validazione input
                if (!recipient || !templateName) {
                    testFeedback.textContent = 'Per favore, inserisci un numero di telefono e seleziona un template.';
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
        });
    </script>
</body>
</html>
