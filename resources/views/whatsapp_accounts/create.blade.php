<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea Nuovo Account WhatsApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
<div class="container py-5">
    <h1 class="mb-3">Crea Nuovo Account WhatsApp</h1>

    <div class="card bg-light mb-4">
        <div class="card-body d-flex justify-content-start align-items-center gap-3">
            <h6 class="card-title mb-0">Link Utili Meta:</h6>
            <a href="https://business.facebook.com/latest/settings/business_info?business_id=1614730662901360" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-person-badge"></i> Gestione Account Business</a>
            <a href="https://developers.facebook.com/apps/" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-window-desktop"></i> Dashboard App</a>
        </div>
    </div>

    <p class="lead mb-4">Inserisci manualmente i dettagli del tuo WhatsApp Business Account (WABA).</p>

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Sono presenti degli errori:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('whatsapp-accounts.store', ['token' => $token]) }}" method="POST" class="card card-body">
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label">Nome Account (per uso interno)</label>
            <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required>
        </div>

        <div class="mb-3">
            <label for="business_name" class="form-label">Nome Azienda (da Meta)</label>
            <input type="text" id="business_name" name="business_name" class="form-control" value="{{ old('business_name') }}" required>
            <div class="form-text">Questo è il nome visualizzato che i tuoi contatti vedranno. Dopo la registrazione, questo nome deve essere approvato da Meta. Controlla lo stato di approvazione nel WhatsApp Manager: deve essere "Approvato". <strong>Non potrai inviare messaggi finché il nome non sarà approvato.</strong></div>
        </div>

        <div class="mb-3">
            <label for="phone_number_display" class="form-label">Numero di Telefono (visualizzato)</label>
            <input type="text" id="phone_number_display" name="phone_number_display" class="form-control" value="{{ old('phone_number_display') }}" required>
        </div>

        <div class="mb-3">
            <label for="waba_id" class="form-label">WABA ID</label>
            <input type="text" id="waba_id" name="waba_id" class="form-control" value="{{ old('waba_id') }}" required>
        </div>

        <div class="mb-3">
            <label for="phone_number_id" class="form-label">Phone Number ID</label>
            <input type="text" id="phone_number_id" name="phone_number_id" class="form-control" value="{{ old('phone_number_id') }}" required>
            <div class="form-text">Per personalizzare il nome e l'immagine del profilo pubblico di questo numero, devi farlo dal <strong>WhatsApp Manager</strong> di Meta. I numeri di prova forniti da Meta non possono essere personalizzati.</div>
        </div>

        <div class="mb-4">
            <label for="access_token" class="form-label">Access Token</label>
            <input type="password" id="access_token" name="access_token" class="form-control" required>
            <div class="form-text">
                Il token verrà salvato in modo sicuro e cifrato. Per un uso stabile, si consiglia di generare un <strong>token di accesso utente di sistema permanente</strong> dal proprio Meta Business Manager, non il token temporaneo dalla dashboard degli sviluppatori.
                <a href="https://developers.facebook.com/docs/whatsapp/business-management-api/get-started#system-user-access-token" target="_blank" class="d-block mt-1">Guida alla creazione di un token permanente <i class="bi bi-box-arrow-up-right"></i></a>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('whatsapp-accounts.index', ['token' => $token]) }}" class="btn btn-secondary">Annulla</a>
            <button type="submit" class="btn btn-primary">Crea Account</button>
        </div>
    </form>
</div>
</body>
</html>