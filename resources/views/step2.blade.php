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
                            <p><strong>Nome Campagna:</strong> {{ $campaignData['name'] }}</p>
                            <p><strong>Template:</strong> <code>{{ $campaignData['message_template'] }}</code></p>
                            <p class="mb-0"><strong>Origine Destinatari:</strong> {{ $campaignData['recipient_source'] }}</p>
                        </div>
                    </div>

                    @if($campaignData['recipient_source'] === 'file_upload')
                        {{-- SEZIONE MAPPING FILE --}}
                        <div id="file-mapping-section">
                            <h3>Mappatura Campi da File</h3>
                            <p>Associa le colonne del tuo file ai campi richiesti per l'invio. Per ora questa è una simulazione, la logica di lettura del file e mappatura verrà implementata.</p>

                            <div class="alert alert-info">
                                <strong>File caricato:</strong> {{ $campaignData['recipient_file_path'] ?? 'N/D' }}
                                <br>
                                In questa sezione verranno mostrate le colonne del file (es. "Nome", "Cognome", "Numero") e potrai associarle ai campi "Nominativo" e "Numero Cellulare".
                            </div>

                            {{-- Form di mapping (simulato) --}}
                            <form action="#" method="POST">
                                @csrf
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="map_name" class="form-label">Campo "Nominativo"</label>
                                        <select id="map_name" name="map_name" class="form-select">
                                            <option selected>Scegli colonna...</option>
                                            <option value="col_1">Colonna A (Simulazione)</option>
                                            <option value="col_2">Colonna B (Simulazione)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="map_phone" class="form-label">Campo "Numero Cellulare"</label>
                                        <select id="map_phone" name="map_phone" class="form-select">
                                            <option selected>Scegli colonna...</option>
                                            <option value="col_1">Colonna A (Simulazione)</option>
                                            <option value="col_2">Colonna B (Simulazione)</option>
                                        </select>
                                    </div>
                                </div>
                                <p class="form-text">Dopo aver mappato i campi, potrai vedere un'anteprima dei dati e confermare l'invio.</p>
                            </form>
                        </div>
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
                        </div>
                    @endif

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
                @endif
            </div>
        </main>

        <footer class="mt-5 text-center">
            WA Sender v1.0
        </footer>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>