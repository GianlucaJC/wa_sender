<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Storico Campagne - FilleaOFFICE WhatsApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .badge { font-size: 0.9em; }
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
                    <table class="table table-hover align-middle">
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
                            @forelse($campaigns as $campaign)
                                @php
                                    $status_info = match($campaign->status) {
                                        'completed' => ['class' => 'bg-success', 'label' => 'Completata'],
                                        'processing' => ['class' => 'bg-info text-dark', 'label' => 'In corso'],
                                        'pending' => ['class' => 'bg-secondary', 'label' => 'In attesa'],
                                        'failed' => ['class' => 'bg-danger', 'label' => 'Fallita'],
                                        default => ['class' => 'bg-light text-dark', 'label' => ucfirst($campaign->status)],
                                    };
                                @endphp
                                <tr>
                                    <td><strong>{{ $campaign->name }}</strong></td>
                                    <td class="text-center"><span class="badge {{ $status_info['class'] }}">{{ $status_info['label'] }}</span></td>
                                    <td class="text-center">{{ $campaign->total_recipients }}</td>
                                    <td class="text-center">
                                        <span class="text-success fw-bold">{{ $campaign->processed_count }}</span> / <span class="text-danger fw-bold">{{ $campaign->failed_count }}</span>
                                    </td>
                                    <td>{{ $campaign->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('campaigns.progress', $campaign) }}" class="btn btn-sm btn-outline-primary" title="Visualizza dettagli">
                                            <i class="bi bi-eye"></i> Dettagli
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Nessuna campagna trovata.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($campaigns->hasPages())
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $campaigns->links() }}
                    </div>
                @endif
            </div>
            <div class="card-footer text-center">
                <a href="{{ route('campaigns.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Crea Nuova Campagna
                </a>
            </div>
        </main>
    </div>
</body>
</html>