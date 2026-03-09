<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Avanzamento Campagna - FilleaOFFICE WhatsApp</title>
    <meta name="jwt-token" content="{{ $token }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .progress { height: 2rem; font-size: 1rem; }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h1 class="h4 mb-0">Avanzamento: <span class="fw-normal">{{ $campaign->name }}</span></h1>
                <div id="status-container">
                    @include('campaigns.partials.status-badge', ['status' => $campaign->status])
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row text-center mb-4">
                    <div class="col-md-4">
                        <div class="stat-box">
                            <div class="fs-1 fw-bold" id="total-recipients">{{ $campaign->total_recipients }}</div>
                            <div class="text-muted">Destinatari Totali</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-box">
                            <div class="fs-1 fw-bold text-success" id="processed-count">{{ $campaign->processed_count }}</div>
                            <div class="text-muted">Inviati con Successo</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-box">
                            <div class="fs-1 fw-bold text-danger" id="failed-count">{{ $campaign->failed_count }}</div>
                            <div class="text-muted">Falliti / Annullati</div>
                        </div>
                    </div>
                </div>

                <div class="progress mb-3" id="progress-bar-container">
                    <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                </div>
                <div id="progress-text" class="text-center text-muted">Inizializzazione...</div>

                <div class="d-flex justify-content-between mt-5">
                    <a href="{{ route('campaigns.index', ['token' => $token]) }}" class="btn btn-secondary"><i class="bi bi-archive"></i> Torna allo Storico</a>

                    @if(in_array($campaign->status, ['pending', 'processing']))
                        <form action="{{ route('campaigns.stop', ['campaign' => $campaign->id, 'token' => $token]) }}" method="POST" onsubmit="return confirm('Sei sicuro di voler interrompere questa campagna?');" id="stop-campaign-form">
                            @csrf
                            <button type="submit" class="btn btn-danger" id="stop-button" {{ $campaign->status !== 'processing' ? 'disabled' : '' }}><i class="bi bi-stop-circle"></i> Interrompi Campagna</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sezione Dettaglio Destinatari --}}
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Dettaglio Destinatari</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 20%;">Nome</th>
                                <th style="width: 20%;">Numero Telefono</th>
                                <th class="text-center" style="width: 15%;">Stato Messaggio</th>
                                <th style="width: 20%;">Ultimo Aggiornamento</th>
                                <th>Dettagli</th>
                            </tr>
                        </thead>
                        <tbody id="recipients-table-body">
                            @forelse($recipients as $recipient)
                                @php
                                    $status_info = match($recipient->status) {
                                        'sent' => ['class' => 'bg-secondary', 'label' => 'Inviato', 'icon' => 'bi-check'],
                                        'delivered' => ['class' => 'bg-info text-dark', 'label' => 'Consegnato', 'icon' => 'bi-check2-all'],
                                        'read' => ['class' => 'bg-success', 'label' => 'Letto', 'icon' => 'bi-check2-all text-primary'],
                                        'failed' => ['class' => 'bg-danger', 'label' => 'Fallito', 'icon' => 'bi-x-circle'],
                                        'cancelled' => ['class' => 'bg-warning text-dark', 'label' => 'Annullato', 'icon' => 'bi-slash-circle'],
                                        'processing' => ['class' => 'bg-light text-dark border', 'label' => 'In elaborazione', 'icon' => 'bi-arrow-repeat'],
                                        'queued' => ['class' => 'bg-light text-dark border', 'label' => 'In coda', 'icon' => 'bi-clock-history'],
                                        default => ['class' => 'bg-light text-dark border', 'label' => ucfirst($recipient->status), 'icon' => 'bi-question-circle'],
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $recipient->name ?? 'N/D' }}</td>
                                    <td>{{ $recipient->phone_number }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $status_info['class'] }}"><i class="bi {{ $status_info['icon'] }}"></i> {{ $status_info['label'] }}</span>
                                    </td>
                                    <td>{{ $recipient->updated_at->format('d/m/Y H:i:s') }}</td>
                                    <td class="text-truncate" style="max-width: 200px;" title="{{ $recipient->status === 'failed' ? $recipient->error_message : $recipient->message_id }}">
                                        @if($recipient->status === 'failed')
                                            <span class="text-danger">{{ $recipient->error_message }}</span>
                                        @else
                                            <small class="text-muted">{{ $recipient->message_id }}</small>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Nessun destinatario da mostrare.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($recipients->hasPages())
                <div class="card-footer d-flex justify-content-center">
                    {{-- Preserva i parametri della query string (come il token) durante la paginazione --}}
                    {{ $recipients->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // LA SOLUZIONE È QUI: Usiamo l'helper route() per generare l'URL completo e corretto.
            const statusUrl = "{{ route('campaigns.status', ['campaign' => $campaign->id, 'token' => $token]) }}";
            const jwtToken = document.querySelector('meta[name="jwt-token"]').getAttribute('content');

            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const processedCountEl = document.getElementById('processed-count');
            const failedCountEl = document.getElementById('failed-count');
            const statusContainer = document.getElementById('status-container');
            const stopButton = document.getElementById('stop-button');
            const stopForm = document.getElementById('stop-campaign-form');

            let intervalId;

            function updateProgress(data) {
                const total = data.total_recipients;
                const processed = data.processed_count;
                const failed = data.failed_count;
                const completedJobs = processed + failed;
                
                const percentage = total > 0 ? Math.round((completedJobs / total) * 100) : 0;

                progressBar.style.width = percentage + '%';
                progressBar.textContent = percentage + '%';
                progressBar.setAttribute('aria-valuenow', percentage);

                processedCountEl.textContent = processed;
                failedCountEl.textContent = failed;
                progressText.textContent = `${completedJobs} di ${total} destinatari elaborati.`;

                if (data.status === 'completed' || data.status === 'cancelled' || data.status === 'failed') {
                    clearInterval(intervalId);
                    progressBar.classList.remove('progress-bar-animated');
                    if (stopForm) {
                        stopForm.style.display = 'none';
                    }
                    if (data.status === 'completed' && percentage === 100) {
                        progressBar.classList.add('bg-success');
                    } else {
                        progressBar.classList.add('bg-danger');
                    }
                } else if (data.status === 'processing' && stopButton) {
                    stopButton.disabled = false;
                }
            }

            async function fetchStatus() {
                try {
                    const response = await fetch(statusUrl, {
                        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${jwtToken}` }
                    });

                    if (!response.ok) {
                        console.error('Error fetching campaign status:', response.status, response.statusText);
                        progressText.textContent = 'Errore durante l\'aggiornamento dello stato (Codice: ' + response.status + '). La pagina non si aggiornerà automaticamente.';
                        progressText.classList.add('text-danger');
                        clearInterval(intervalId);
                        return;
                    }
                    const data = await response.json();
                    updateProgress(data);
                } catch (error) {
                    console.error('Network error while fetching status:', error);
                    clearInterval(intervalId);
                }
            }

            updateProgress({!! json_encode($campaign->only(['id', 'status', 'total_recipients', 'processed_count', 'failed_count'])) !!});

            if (['pending', 'processing'].includes('{{ $campaign->status }}')) {
                intervalId = setInterval(fetchStatus, 3000);
            }
        });
    </script>
</body>
</html>
