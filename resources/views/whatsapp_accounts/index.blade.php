<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Account WhatsApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
<div class="container py-5">
    <h1 class="mb-3">Account WhatsApp Collegati</h1>
    <p class="lead mb-4">Questi sono gli account WhatsApp Business che hai collegato alla piattaforma. Puoi usarli per creare e inviare campagne.</p>

    <div class="my-4 d-flex gap-2">
        <a href="{{ route('whatsapp-accounts.create', ['token' => $token]) }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Collega Nuovo Account</a>
        <a href="{{ route('campaigns.create', ['token' => $token]) }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Torna alla Home</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success mb-3">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mb-3">
            {{ session('error') }}
        </div>
    @endif

    @if($accounts->where('name', '!=', 'SIMULATE')->isEmpty())
        <div class="alert alert-info">
            Nessun account WhatsApp è stato ancora collegato.
        </div>
    @else
        <table class="table table-striped table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 20%;">Nome Account</th>
                    <th>Numero di Telefono</th>
                    <th>WABA ID</th>
                    <th>Phone Number ID</th>
                    <th style="width: 10%;">Azioni</th>
                </tr>
            </thead>
            <tbody>
                @foreach($accounts as $account)
                    <tr>
                        <td>
                            <strong>{{ $account->name }}</strong>
                            <small class="d-block text-muted">{{ $account->business_name }}</small>
                        </td>
                        <td>{{ $account->phone_number_display }}</td>
                        <td><code>{{ $account->waba_id }}</code></td>
                        <td><code>{{ $account->phone_number_id }}</code></td>
                        <td>
                            {{-- L'account di simulazione non può essere modificato o rimosso --}}
                            @if ($account->name !== 'SIMULATE')
                                <a href="{{ route('whatsapp-accounts.edit', ['whatsapp_account' => $account, 'token' => $token]) }}" class="btn btn-sm btn-secondary me-1" title="Modifica"><i class="bi bi-pencil-fill"></i></a>
                                <form action="{{ route('whatsapp-accounts.destroy', ['whatsapp_account' => $account, 'token' => $token]) }}" method="POST" onsubmit="return confirm('Sei sicuro di voler rimuovere questo account? L\'azione non è reversibile.');" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Rimuovi"><i class="bi bi-trash-fill"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
</body>
</html>