<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>FilleaOFFICE WhatsApp - Step 2: Destinatari</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
    </style>
</head>
<body>
    <div class="container my-4 my-md-5">
        <header class="mb-5 text-center">
            <h1 class="display-5 fw-bold"><i class="bi bi-whatsapp"></i> FilleaOFFICE WhatsApp</h1>
            <p class="lead">Step 2: Conferma Destinatari e Avvia Campagna</p>
        </header>

        <main class="card shadow-sm">
            <div class="card-body p-4 p-md-5">

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @if(!isset($campaignData))
                    <div class="alert alert-warning">
                        Dati della campagna non trovati. <a href="{{ route('campaigns.create') }}">Ricomincia da qui</a>.
                    </div>
                @else
                    {{-- Sezione Riepilogo Campagna --}}
                    <div class="mb-5">
                        <h3 class="mb-3">Riepilogo Campagna</h3>
                        <div class="card card-body bg-light">
                            <p><strong>Nome Campagna:</strong> {{ $campaignData['campaign_name'] }}</p>
                            <p><strong>Template:</strong> <code>{{ $campaignData['message_template'] }}</code></p>
                            <p class="mb-0"><strong>Origine Destinatari:</strong> {{ $campaignData['recipient_source'] }}</p>
                        </div>
                    </div>

                    @if($campaignData['recipient_source'] === 'file_upload')
                        <form action="{{ route('campaigns.validate') }}" method="POST">
                            @csrf
                            {{-- SEZIONE MAPPING FILE --}}
                            <div id="file-mapping-section">
                                <h3>Mappatura Campi da File</h3>
                                <p>Associa le colonne del tuo file ai campi richiesti per l'invio.</p>

                                <div class="alert alert-info">
                                    <strong>File caricato:</strong> {{ basename($campaignData['recipient_file_path'] ?? 'N/D') }}
                                </div>

                                @if(isset($file_headers) && !empty($file_headers))
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="map_name" class="form-label">Campo "Nominativo"</label>
                                            <select id="map_name" name="map_name" class="form-select" required>
                                                <option value="" selected disabled>Scegli colonna per il Nominativo...</option>
                                                @foreach($file_headers as $header)
                                                    <option value="{{ $header }}">{{ $header }}</option>
                                                @endforeach
                                            </select>
                                            <div class="form-text">Questo campo verrà usato per le variabili come <code>@{{1}}</code> nel messaggio.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="map_phone" class="form-label fw-bold">Campo "Numero Cellulare" <span class="text-danger">*</span></label>
                                            <select id="map_phone" name="map_phone" class="form-select" required>
                                                <option value="" selected disabled>Scegli colonna per il Cellulare...</option>
                                                @foreach($file_headers as $header)
                                                    <option value="{{ $header }}">{{ $header }}</option>
                                                @endforeach
                                            </select>
                                            <div class="form-text">Questo campo è obbligatorio per l'invio.</div>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-warning">Impossibile leggere le colonne dal file. Assicurati che la prima riga contenga le intestazioni.</div>
                                @endif
                            </div>
                            <hr class="my-5">
                            {{-- Pulsanti Azione --}}
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('campaigns.create') }}" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Modifica Campagna
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg" @if(!isset($file_headers) || empty($file_headers)) disabled @endif>
                                    <i class="bi bi-shield-check"></i> Valida Destinatari
                                </button>
                            </div>
                        </form>
                    @else
                        {{-- SEZIONE ANTEPRIMA DB --}}
                        <div id="db-preview-section">
                            <h3>Anteprima Destinatari</h3>
                            <p>Di seguito un'anteprima dei destinatari recuperati in base alla modalità di invio selezionata. Vengono mostrati solo i primi record a scopo di verifica.</p>

                             <div class="alert alert-info">
                                Questa è una simulazione. La query al database per recuperare i dati da <strong>{{ $campaignData['recipient_source'] }}</strong> non è ancora implementata.
                            </div>

                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Nominativo (Simulato)</th>
                                            <th>Numero Cellulare (Simulato)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Mario Rossi</td>
                                            <td>+393331234567</td>
                                        </tr>
                                        <tr>
                                            <td>Luigi Bianchi</td>
                                            <td>+393337654321</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="form-text">Se i dati sono corretti, procedi con l'invio.</p>

                            <hr class="my-5">

                            {{-- Pulsanti Azione --}}
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('campaigns.create') }}" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Modifica Campagna
                                </a>
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-send-check"></i> Avvia Campagna
                                </button>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </main>

        {{-- Modal per il Report di Validazione --}}
        @if(session('validation_report'))
            @php($report = session('validation_report'))
            <div class="modal fade" id="validationReportModal" tabindex="-1" aria-labelledby="validationReportModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="validationReportModalLabel"><i class="bi bi-check2-circle"></i> Report di Validazione</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <h4 class="h5">Riepilogo Scansione File</h4>
                            <p>Sono stati analizzati <strong>{{ $report['total_rows'] }}</strong> contatti dal tuo file.</p>
                            <ul class="list-group mb-4">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div><i class="bi bi-person-check-fill text-success"></i> Contatti validi pronti per l'invio</div>
                                    <span class="badge bg-success rounded-pill">{{ $report['valid_count'] }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div><i class="bi bi-magic text-info"></i> Contatti corretti automaticamente</div>
                                    <span class="badge bg-info rounded-pill">{{ $report['normalized_count'] }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div><i class="bi bi-person-x-fill text-danger"></i> Contatti scartati</div>
                                    <span class="badge bg-danger rounded-pill">{{ $report['invalid_count'] }}</span>
                                </li>
                            </ul>

                            @if($report['invalid_count'] > 0)
                                <hr>
                                <h4 class="h5">Dettaglio Contatti Scartati</h4>
                                <div class="table-responsive" style="max-height: 200px;">
                                    <table class="table table-sm table-striped">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Riga n.</th>
                                                <th>Nominativo</th>
                                                <th>Numero Fornito</th>
                                                <th>Motivo Scarto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($report['invalid_entries'] as $entry)
                                            <tr>
                                                <td>{{ $entry['line'] }}</td>
                                                <td>{{ $entry['name'] ?: '-' }}</td>
                                                <td><code>{{ $entry['phone'] ?: '(vuoto)' }}</code></td>
                                                <td><small>{{ $entry['reason'] }}</small></td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                            <form action="{{ route('campaigns.launch') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success" @if($report['valid_count'] == 0) disabled @endif>
                                    <i class="bi bi-send-check"></i> Conferma e Avvia Invio a {{ $report['valid_count'] }} Contatti
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <footer class="mt-5 text-center">
            WA Sender v1.0
        </footer>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    @if(session('validation_report'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalEl = document.getElementById('validationReportModal');
            if (modalEl) {
                const validationModal = new bootstrap.Modal(modalEl);
                validationModal.show();
            }
        });
    </script>
    @endif
</body>
</html>