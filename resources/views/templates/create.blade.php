<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WA Sender - Crea Template</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-body-tertiary">
    <div class="container my-4 my-md-5">

        @if(isset($is_admin) && $is_admin)
            <header class="mb-5 text-center">
                <h1 class="display-5 fw-bold">Crea un Nuovo Template WhatsApp</h1>
                <p class="lead text-body-secondary">Sottometti un nuovo template a Meta per l'approvazione.</p>
            </header>

            <main class="card shadow-sm">
                <div class="card-body p-4 p-md-5">

                    @if(session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('templates.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="name" class="form-label">Nome Template</label>
                            <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required>
                            <div class="form-text">Solo lettere minuscole, numeri e underscore (es. `promozione_pasqua_24`).</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="category" class="form-label">Categoria</label>
                                <select id="category" name="category" class="form-select" required>
                                    <option value="MARKETING" @if(old('category') == 'MARKETING') selected @endif>Marketing</option>
                                    <option value="UTILITY" @if(old('category') == 'UTILITY') selected @endif>Utility</option>
                                    <option value="AUTHENTICATION" @if(old('category') == 'AUTHENTICATION') selected @endif>Autenticazione</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label for="language_code" class="form-label">Codice Lingua</label>
                                <input type="text" id="language_code" name="language_code" class="form-control" value="{{ old('language_code', 'it') }}" required>
                                <div class="form-text">Esempi: `it`, `en_US`, `fr`.</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="body_text" class="form-label">Corpo del Messaggio</e-label>
                            <textarea id="body_text" name="body_text" rows="6" class="form-control" required>{{ old('body_text') }}</textarea>
                            <div class="form-text">Usa le variabili con le doppie parentesi graffe numerate, es. `Ciao {{1}}, il tuo codice è {{2}}`.</div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <a href="{{ route('campaigns.create') }}" class="btn btn-secondary">Torna alle Campagne</a>
                                <a href="{{ route('templates.index') }}" class="btn btn-outline-secondary">Vedi Lista Template</a>
                            </div>
                            <button type="submit" class="btn btn-primary">Invia per Approvazione</button>
                        </div>
                    </form>
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