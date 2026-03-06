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
                    
                    <form action="{{ route('campaigns.stop', ['campaign' => $campaign->id, 'token' => $token]) }}" method="POST" onsubmit="return confirm('Sei sicuro di voler interrompere questa campagna?');">
                        @csrf
                        <button type="submit" class="btn btn-danger" id="stop-button" @if(!in_array($campaign->status, ['pending', 'processing'])) disabled @endif>
                            <i class="bi bi-stop-circle"></i> Interrompi Campagna
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // LA SOLUZIONE È QUI: Usiamo l'helper route() per generare l'URL completo e corretto.
            const statusUrl = "{{ route('campaigns.status', ['campaign' => $campaign->id, 'token' => $token]) }}";
            const jwtToken = document.querySelector('meta[name="jwt-token"]').getAttribute('content');

            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const processedCountEl = document.getElementById('processed-count');
            const failedCountEl = document.getElementById('failed-count');
            const statusContainer = document.getElementById('status-container');
            const stopButton = document.getElementById('stop-button');

            let intervalId = null;

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
                    stopButton.disabled = true;
                    if (data.status === 'completed' && percentage === 100) {
                        progressBar.classList.add('bg-success');
                    } else {
                        progressBar.classList.add('bg-danger');
                    }
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

            updateProgress(@json($campaign->only(['id', 'status', 'total_recipients', 'processed_count', 'failed_count'])));

            if (['pending', 'processing'].includes('{{ $campaign->status }}')) {
                intervalId = setInterval(fetchStatus, 3000);
            }
        });
    </script>
</body>
</html>