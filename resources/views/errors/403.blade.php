<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accesso Negato</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
            font-family: sans-serif;
        }
        .error-container {
            max-width: 500px;
        }
        .error-icon {
            font-size: 5rem;
            color: #8d0c10;
        }
    </style>
</head>
<body>
    <div class="error-container p-4">
        <div class="error-icon mb-4">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h1 class="display-5 fw-bold">Accesso Scaduto o Non Valido</h1>
        <p class="lead text-secondary mb-5">Il tuo link di accesso non è più valido o la sessione è scaduta. Per continuare, per favore, accedi nuovamente dal tuo gestionale.</p>
        <a href="{{ config('app.management_home_url', '#') }}" class="btn btn-primary btn-lg">
            <i class="bi bi-arrow-left-circle"></i> Torna al Gestionale
        </a>
    </div>
</body>
</html>