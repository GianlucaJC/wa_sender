<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WA Sender - Lista Template</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-body-tertiary">
    <div class="container my-4 my-md-5">

        @if(isset($is_admin) && $is_admin)
            <header class="mb-5 text-center">
                <h1 class="display-5 fw-bold">Template Messaggio WhatsApp</h1>
                <p class="lead text-body-secondary">Lista dei template associati al tuo account e il loro stato di approvazione.</p>
            </header>

            <main class="card shadow-sm">
                <div class="card-body p-4">

                    @if(isset($error))
                        <div class="alert alert-danger" role="alert">
                            {{ $error }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Nome Template</th>
                                    <th scope="col">Categoria</th>
                                    <th scope="col">Lingua</th>
                                    <th scope="col" class="text-center">Stato</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($templates as $template)
                                    @php
                                        $status_class = match($template['status']) {
                                            'APPROVED' => 'bg-success',
                                            'PENDING' => 'bg-warning text-dark',
                                            'REJECTED' => 'bg-danger',
                                            'PAUSED' => 'bg-secondary',
                                            'DISABLED' => 'bg-dark',
                                            default => 'bg-info',
                                        };
                                    @endphp
                                    <tr>
                                        <td><code>{{ $template['name'] }}</code></td>
                                        <td>{{ $template['category'] }}</td>
                                        <td>{{ $template['language'] }}</td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill {{ $status_class }}">{{ $template['status'] }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">Nessun template trovato o impossibile recuperare i dati.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="{{ route('campaigns.create') }}" class="btn btn-secondary">Torna alle Campagne</a>
                        <a href="{{ route('templates.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Crea Nuovo Template
                        </a>
                    </div>
                </div>
            </main>
        @else
            <div class="alert alert-danger text-center">
                <h4 class="alert-heading">Accesso Negato</h4>
                <p>Non hai i permessi per accedere a questa sezione.</p>
            </div>
        @endif

    </div>
</body>
</html>