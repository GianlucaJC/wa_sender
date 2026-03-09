<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Avanzamento Campagna - FilleaOFFICE WhatsApp</title>
    <meta name="jwt-token" content="{{ $token }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .progress { height: 2rem; font-size: 1rem; }
        /* Sposta leggermente in basso i bottoni di DataTables per un migliore allineamento visivo con il selettore delle righe */
        .dt-buttons {
            position: relative;
            top: 3px;
        }
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
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="recipients-table" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Numero Telefono</th>
                                <th class="text-center">Stato Messaggio</th>
                                <th>Ultimo Aggiornamento</th>
                                <th>Dettagli</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Il contenuto verrà caricato dinamicamente da DataTables --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
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

            // Inizializzazione di DataTables
            $('#recipients-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('campaigns.recipients.data', ['campaign' => $campaign->id, 'token' => $token]) }}",
                dom: "<'row'<'col-sm-12 col-md-6'lB><'col-sm-12 col-md-6'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel"></i> Esporta Excel',
                        className: 'btn btn-success ms-2',
                        titleAttr: 'Esporta in formato Excel',
                        title: 'Dettaglio Destinatari - Campagna: {{ addslashes($campaign->name) }}',
                        exportOptions: {
                            // Esporta tutte le colonne visibili
                            columns: [0, 1, 2, 3, 4]
                        }
                    }
                ],
                columns: [
                    { data: 'name', name: 'name', defaultContent: 'N/D' },
                    { data: 'phone_number', name: 'phone_number' },
                    { 
                        data: 'status', 
                        name: 'status',
                        className: 'text-center',
                        render: function(data, type, row) {
                            let info = {};
                            switch(data) {
                                case 'sent': info = {class: 'bg-secondary', label: 'Inviato', icon: 'bi-check'}; break;
                                case 'delivered': info = {class: 'bg-info text-dark', label: 'Consegnato', icon: 'bi-check2-all'}; break;
                                case 'read': info = {class: 'bg-success', label: 'Letto', icon: 'bi-check2-all text-primary'}; break;
                                case 'failed': info = {class: 'bg-danger', label: 'Fallito', icon: 'bi-x-circle'}; break;
                                case 'cancelled': info = {class: 'bg-warning text-dark', label: 'Annullato', icon: 'bi-slash-circle'}; break;
                                case 'processing': info = {class: 'bg-light text-dark border', label: 'In elaborazione', icon: 'bi-arrow-repeat'}; break;
                                case 'opted-out': info = {class: 'bg-dark', label: 'Bloccato', icon: 'bi-hand-thumbs-down-fill'}; break;
                                case 'queued': info = {class: 'bg-light text-dark border', label: 'In coda', icon: 'bi-clock-history'}; break;
                                default: info = {class: 'bg-light text-dark border', label: (data || '').charAt(0).toUpperCase() + (data || '').slice(1), icon: 'bi-question-circle'}; break;
                            }
                            return `<span class="badge ${info.class}"><i class="bi ${info.icon}"></i> ${info.label}</span>`;
                        }
                    },
                    { 
                        data: 'updated_at', 
                        name: 'processed_at', // Ordiniamo per 'processed_at' (come da controller) ma mostriamo 'updated_at'
                        render: function(data, type, row) {
                            if (!data) return '';
                            const date = new Date(data);
                            return date.toLocaleString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        }
                    },
                    {
                        data: 'message_id',
                        name: 'message_id',
                        orderable: false, // Disabilitiamo l'ordinamento su questa colonna composita
                        render: function(data, type, row) {
                            const detail = row.status === 'failed' ? (row.error_message || 'Errore non specificato') : (row.message_id || 'N/A');
                            const cssClass = row.status === 'failed' ? 'text-danger' : 'text-muted';
                            const content = row.status === 'failed' ? detail : `<small>${detail}</small>`;
                            return `<div class="${cssClass} text-truncate" style="display: inline-block; max-width: 200px;" title="${detail}">${content}</div>`;
                        }
                    }
                ],
                order: [[ 3, "desc" ]], // Ordina per data di aggiornamento (più recenti prima)
                language: {
                    "sEmptyTable": "Nessun dato presente nella tabella",
                    "sInfo": "Vista da _START_ a _END_ di _TOTAL_ elementi",
                    "sInfoEmpty": "Vista da 0 a 0 di 0 elementi",
                    "sInfoFiltered": "(filtrati da _MAX_ elementi totali)",
                    "sLengthMenu": "Visualizza _MENU_ elementi",
                    "sLoadingRecords": "Caricamento...",
                    "sProcessing": "Elaborazione...",
                    "sSearch": "Cerca:",
                    "sZeroRecords": "La ricerca non ha portato alcun risultato.",
                    "oPaginate": { "sFirst": "Inizio", "sPrevious": "Precedente", "sNext": "Successivo", "sLast": "Fine" }
                }
            });
        });
    </script>
</body>
</html>
