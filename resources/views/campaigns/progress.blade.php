<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Avanzamento Campagna - FilleaOFFICE WhatsApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .status-badge { font-size: 1rem; }
    </style>
</head>
<body>
    <div class="container my-5">
        <header class="mb-5 text-center">
            <h1 class="display-5 fw-bold"><i class="bi bi-whatsapp"></i> Avanzamento Campagna</h1>
            <p class="lead text-secondary">Monitora lo stato di invio della tua campagna in tempo reale.</p>
        </header>

        <main class="card shadow-sm" id="progress-panel" data-campaign-id="{{ $campaign->id }}">
            <div class="card-header fs-5">
                Riepilogo Campagna: <strong>{{ $campaign->name }}</strong>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="fs-1 me-3"><i class="bi bi-card-checklist text-primary"></i></div>
                            <div>
                                <div class="text-muted">Stato Campagna</div>
                                <div id="campaign-status" class="fw-bold fs-4">
                                    <span class="spinner-border spinner-border-sm"></span> Caricamento...
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="fs-1 me-3"><i class="bi bi-people-fill text-secondary"></i></div>
                            <div>
                                <div class="text-muted">Destinatari Totali</div>
                                <div id="total-recipients" class="fw-bold fs-4">{{ $campaign->total_recipients ?? 'N/D' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="fs-1 me-3"><i class="bi bi-clock-history text-info"></i></div>
                            <div>
                                <div class="text-muted">Template</div>
                                <div class="fw-bold fs-5"><code>{{ $campaign->message_template }}</code></div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <h4 class="mb-3">Progresso Invii</h4>
                <div class="progress" style="height: 30px;">
                    <div id="progress-bar-sent" class="progress-bar bg-success" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    <div id="progress-bar-failed" class="progress-bar bg-danger" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <div>
                        <span class="badge bg-success me-1">&nbsp;</span>
                        <span id="sent-count">0</span> Inviati
                    </div>
                    <div>
                        <span class="badge bg-danger me-1">&nbsp;</span>
                        <span id="failed-count">0</span> Falliti
                    </div>
                </div>
                <div class="mt-3 text-center text-muted">
                    <span id="processed-count">0</span> di <span id="total-count">{{ $campaign->total_recipients ?? 'N/D' }}</span> elaborati.
                </div>

            </div>
            <div class="card-footer text-center">
                <a href="{{ route('campaigns.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nuova Campagna
                </a>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const panel = document.getElementById('progress-panel');
            const campaignId = panel.dataset.campaignId;
            const statusUrl = `/campaigns/${campaignId}/status`;

            const statusEl = document.getElementById('campaign-status');
            const sentCountEl = document.getElementById('sent-count');
            const failedCountEl = document.getElementById('failed-count');
            const processedCountEl = document.getElementById('processed-count');
            const sentBar = document.getElementById('progress-bar-sent');
            const failedBar = document.getElementById('progress-bar-failed');

            const statusMap = {
                pending: '<span class="badge bg-secondary status-badge">In Attesa</span>',
                processing: '<span class="badge bg-info text-dark status-badge">In Elaborazione...</span>',
                completed: '<span class="badge bg-success status-badge">Completata</span>',
                failed: '<span class="badge bg-danger status-badge">Fallita</span>',
            };

            let intervalId;

            async function fetchStatus() {
                try {
                    const response = await fetch(statusUrl);
                    const data = await response.json();
                    
                    const total = data.total_recipients || 0;
                    const sent = data.processed_count || 0;
                    const failed = data.failed_count || 0;

                    statusEl.innerHTML = statusMap[data.status] || data.status;
                    sentCountEl.textContent = sent;
                    failedCountEl.textContent = failed;
                    processedCountEl.textContent = sent + failed;

                    sentBar.style.width = total > 0 ? `${(sent / total) * 100}%` : '0%';
                    failedBar.style.width = total > 0 ? `${(failed / total) * 100}%` : '0%';

                    if (data.status === 'completed' || data.status === 'failed') clearInterval(intervalId);
                } catch (error) {
                    statusEl.innerHTML = '<span class="badge bg-danger status-badge">Errore</span>';
                    clearInterval(intervalId);
                }
            }

            fetchStatus();
            intervalId = setInterval(fetchStatus, 3000);
        });
    </script>
</body>
</html>