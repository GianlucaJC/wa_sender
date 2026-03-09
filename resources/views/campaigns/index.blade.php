<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Storico Campagne - FilleaOFFICE WhatsApp</title>

    <!-- Token di autenticazione JWT per le chiamate API -->
    <meta name="jwt-token" content="{{ $token }}">
    <!-- Token CSRF per la protezione contro Cross-Site Request Forgery -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .badge { font-size: 0.9em; }
        /* Sposta leggermente in basso i bottoni di DataTables per un migliore allineamento visivo con il selettore delle righe */
        .dt-buttons {
            position: relative;
            top: 3px;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <header class="mb-5 text-center">
            <h1 class="display-5 fw-bold"><i class="bi bi-archive-fill"></i> Storico Campagne</h1>
            <p class="lead text-secondary">Visualizza tutte le campagne inviate e il loro stato di avanzamento.</p>
        </header>

        <main class="card shadow-sm">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="campaigns-table" class="table table-hover align-middle" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>Nome Campagna</th>
                                <th class="text-center">Stato</th>
                                <th class="text-center">Destinatari</th>
                                <th class="text-center">Inviati / Falliti</th>
                                <th>Data Creazione</th>
                                <th class="text-end">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Il contenuto verrà caricato dinamicamente da DataTables --}}
                        </tbody>
                    </table>
                </div>

            </div>
            <div class="card-footer text-center">
                <a href="{{ route('campaigns.create', ['token' => $token]) }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Crea Nuova Campagna
                </a>
            </div>
        </main>
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
        $(document).ready(function() {
            $('#campaigns-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('campaigns.data', ['token' => $token]) }}",
                columns: [
                    { data: 'name', name: 'name' },
                    { 
                        data: 'status', 
                        name: 'status',
                        className: 'text-center',
                        render: function(data, type, row) {
                            let info = {};
                            switch(data) {
                                case 'completed': info = {class: 'bg-success', label: 'Completata'}; break;
                                case 'processing': info = {class: 'bg-info text-dark', label: 'In corso'}; break;
                                case 'pending': info = {class: 'bg-secondary', label: 'In attesa'}; break;
                                case 'cancelled': info = {class: 'bg-warning text-dark', label: 'Annullata'}; break;
                                case 'failed': info = {class: 'bg-danger', label: 'Fallita'}; break;
                                default: info = {class: 'bg-light text-dark', label: (data || '').charAt(0).toUpperCase() + (data || '').slice(1)}; break;
                            }
                            return `<span class="badge ${info.class}">${info.label}</span>`;
                        }
                    },
                    { data: 'total_recipients', name: 'total_recipients', className: 'text-center' },
                    { 
                        data: null, // Colonna combinata
                        className: 'text-center',
                        orderable: false,
                        render: function(data, type, row) {
                            return `<span class="text-success fw-bold">${row.processed_count}</span> / <span class="text-danger fw-bold">${row.failed_count}</span>`;
                        }
                    },
                    { 
                        data: 'created_at', 
                        name: 'created_at',
                        render: function(data, type, row) {
                            if (!data) return '';
                            const date = new Date(data);
                            return date.toLocaleString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                        }
                    },
                    {
                        data: null, // Colonna Azioni
                        className: 'text-end',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            let detailsUrl = "{{ route('campaigns.progress', ['campaign' => ':id', 'token' => $token]) }}".replace(':id', row.id);
                            let stopForm = '';
                            if (row.status === 'processing') {
                                let stopUrl = "{{ route('campaigns.stop', ['campaign' => ':id', 'token' => $token]) }}".replace(':id', row.id);
                                let csrfToken = $('meta[name="csrf-token"]').attr('content');
                                stopForm = `
                                    <form action="${stopUrl}" method="POST" class="d-inline-block" onsubmit="return confirm('Sei sicuro di voler interrompere questa campagna?');">
                                        <input type="hidden" name="_token" value="${csrfToken}">
                                        <button type="submit" class="btn btn-sm btn-danger">Interrompi</button>
                                    </form>
                                `;
                            }
                            return `<a href="${detailsUrl}" class="btn btn-sm btn-info">Dettagli</a> ${stopForm}`;
                        }
                    }
                ],
                order: [[ 4, "desc" ]], // Ordina per data di creazione (più recenti prima)
                dom: "<'row'<'col-sm-12 col-md-6'lB><'col-sm-12 col-md-6'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel"></i> Esporta Excel',
                        className: 'btn btn-success ms-2',
                        titleAttr: 'Esporta in formato Excel',
                        title: 'Storico Campagne',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4] // Esporta le colonne visibili tranne "Azioni"
                        }
                    }
                ],
                language: { "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/it-IT.json" }
            });
        });
    </script>
</body>
</html>