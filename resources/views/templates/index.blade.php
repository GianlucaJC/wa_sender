<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestione Template - FilleaOFFICE WhatsApp</title>

    <!-- Token di autenticazione JWT per le chiamate API -->
    <meta name="jwt-token" content="{{ $token }}">
    <!-- Token CSRF per la protezione contro Cross-Site Request Forgery -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .badge { font-size: 0.9em; }
        .status-APPROVED { background-color: #198754 !important; color: white; }
        .status-PENDING { background-color: #ffc107 !important; color: #000; }
        .status-REJECTED { background-color: #dc3545 !important; color: white; }
        .status-IN_APPEAL { background-color: #0dcaf0 !important; color: #000; }
        .status-PAUSED, .status-DISABLED { background-color: #6c757d !important; color: white; }
    </style>
</head>
<body>
    <div class="container my-5">
        <header class="mb-5 text-center">
            <h1 class="display-5 fw-bold"><i class="bi bi-card-list"></i> Gestione Template</h1>
            <p class="lead text-secondary">Visualizza i template esistenti e crea nuove proposte da inviare a Meta per l'approvazione.</p>
        </header>

        <main class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Template Esistenti</h5>
                <a href="{{ route('templates.create', ['token' => $token]) }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Crea Nuovo Template
                </a>
            </div>
            <div class="card-body p-4">

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if(isset($error))
                    <div class="alert alert-danger">{{ $error }}</div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nome Template</th>
                                <th class="text-center">Stato</th>
                                <th>Categoria</th>
                                <th>Lingua</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($templates as $template)
                                <tr>
                                    <td><strong>{{ $template['name'] }}</strong></td>
                                    <td class="text-center">
                                        <span class="badge status-{{ $template['status'] }}">{{ $template['status'] }}</span>
                                    </td>
                                    <td>{{ $template['category'] ?? 'N/D' }}</td>
                                    <td>{{ $template['language'] ?? 'N/D' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Nessun template trovato.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="{{ route('campaigns.create', ['token' => $token]) }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Torna alla Creazione Campagna
                </a>
            </div>
        </main>
    </div>
</body>
</html>