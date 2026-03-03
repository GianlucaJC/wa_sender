<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crea Nuovo Template - FilleaOFFICE WhatsApp</title>

    <meta name="jwt-token" content="{{ $token }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container my-5" style="max-width: 800px;">
        <header class="mb-5 text-center">
            <h1 class="display-5 fw-bold"><i class="bi bi-plus-square-dotted"></i> Crea Nuovo Template</h1>
            <p class="lead text-secondary">Invia un nuovo template a Meta per l'approvazione.</p>
        </header>

        <main class="card shadow-sm">
            <div class="card-body p-4 p-md-5">
                <form action="{{ route('templates.store', ['token' => $token]) }}" method="POST">
                    @csrf

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <h5 class="alert-heading">Sono stati riscontrati degli errori:</h5>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-4">
                        <label for="whatsapp_account_id" class="form-label">Account di Invio</label>
                        <select name="whatsapp_account_id" id="whatsapp_account_id" class="form-select bg-light" readonly disabled>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" selected>{{ $account->name }} ({{ $account->business_name }})</option>
                            @endforeach
                        </select>
                        {{-- Hidden input to actually send the value --}}
                        <input type="hidden" name="whatsapp_account_id" value="{{ $accounts[0]->id }}">
                    </div>

                    <div class="mb-4">
                        <label for="name" class="form-label">Nome Template</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required placeholder="es. promo_giugno_24">
                        <div class="form-text">Solo lettere minuscole, numeri e underscore (<code>_</code>).</div>
                    </div>

                    <div class="mb-4">
                        <label for="category" class="form-label">Categoria</label>
                        <select name="category" id="category" class="form-select" required>
                            <option value="MARKETING" @if(old('category') == 'MARKETING') selected @endif>Marketing (Promozioni, offerte)</option>
                            <option value="UTILITY" @if(old('category') == 'UTILITY') selected @endif>Utility (Notifiche, aggiornamenti)</option>
                            <option value="AUTHENTICATION" @if(old('category') == 'AUTHENTICATION') selected @endif>Autenticazione (Codici OTP)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="language_code" class="form-label">Codice Lingua</label>
                        <input type="text" name="language_code" id="language_code" class="form-control" value="{{ old('language_code', 'it') }}" required>
                        <div class="form-text">Usa 'it' per l'italiano.</div>
                    </div>

                    <div class="mb-4">
                        <label for="body_text" class="form-label">Testo del Messaggio</label>
                        <textarea name="body_text" id="body_text" class="form-control" rows="5" required placeholder="Ciao {{1}}, ti scriviamo per informarti che...">{{ old('body_text') }}</textarea>
                        <div class="form-text">Usa <code>@{{1}}</code>, <code>@{{2}}</code>, etc. per inserire le variabili che verranno sostituite in fase di invio.</div>
                    </div>

                    <div class="d-flex justify-content-between mt-5">
                        <a href="{{ route('templates.index', ['token' => $token]) }}" class="btn btn-secondary">Annulla</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Invia per Approvazione</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>