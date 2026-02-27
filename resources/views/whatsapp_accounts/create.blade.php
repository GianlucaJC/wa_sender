{{-- Assumendo che tu abbia un layout base come layouts.app --}}
{{-- @extends('layouts.app') --}}
{{-- @section('content') --}}
<div class="container" style="font-family: sans-serif; padding: 2rem;">
    <h1>Collega un Nuovo Account WhatsApp</h1>
    <p>Usa il processo guidato di Facebook per collegare in modo sicuro il tuo WhatsApp Business Account (WABA).</p>
    <p>Dovrai accedere a Facebook con un account che ha i permessi di amministratore sul Business Manager che gestisce il WABA.</p>

    <a href="{{ route('whatsapp-accounts.index') }}" style="display: inline-block; margin-bottom: 1.5rem; color: #6c757d; text-decoration: none;">Annulla e torna alla lista</a>

    @if (isset($error))
        <div style="background-color: #f8d7da; color: #842029; padding: 1rem; border: 1px solid #f5c2c7; border-radius: 0.25rem;">
            <strong>Errore di Configurazione:</strong> {{ $error }}
        </div>
        
    @else
        <div id="form-container">
            <div style="margin-bottom: 1rem;">
                <label for="accountName" style="display: block; margin-bottom: 0.5rem;">Dai un nome a questo account</label>
                <input type="text" id="accountName" placeholder="Es. Account Aziendale Principale" style="width: 100%; padding: 0.375rem 0.75rem; border: 1px solid #ced4da; border-radius: 0.25rem;">
                <div style="font-size: 0.875em; color: #6c757d; margin-top: 0.25rem;">Questo nome ti aiuterà a riconoscere l'account all'interno della piattaforma.</div>
            </div>

            <!-- Bottone di login di Facebook per l'Embedded Signup.
                 - data-config_id: L'ID di configurazione del tuo setup sulla tua App Facebook.
                 - onlogin: La funzione Javascript da chiamare al successo. DEVE essere una funzione globale. -->
            <fb:login-button
                config_id="{{ config('services.meta_whatsapp.config_id', 'YOUR_CONFIG_ID') }}"
                onlogin="onWhatsAppSignupSuccess">
                Collega il tuo account WhatsApp
            </fb:login-button>
            <p style="font-size: 0.875em; color: #6c757d; margin-top: 0.5rem;">Assicurati di aver inserito un nome per l'account nel campo di testo sopra prima di cliccare.</p>

            <div id="status" style="margin-top: 1.5rem;"></div>
        </div>

        <!-- Root per Facebook SDK -->
        <div id="fb-root"></div>

        <!-- Script per Facebook SDK -->
        <script>
            // Funzione di callback che viene chiamata dal flusso di Embedded Signup al successo
            function onWhatsAppSignupSuccess(response) {
                const statusDiv = document.getElementById('status');
                if (!response.authResponse) {
                    statusDiv.innerHTML = `<div style="background-color: #fff3cd; color: #664d03; padding: 1rem; border: 1px solid #ffecb5; border-radius: 0.25rem;">Processo annullato o permessi non concessi.</div>`;
                    return;
                }

                console.log('Embedded Signup Success:', response);
                const accountName = document.getElementById('accountName').value;

                if (!accountName) {
                    alert('Per favore, inserisci un nome per l\'account nel campo di testo.');
                    // Idealmente, ricarica il bottone o permette un nuovo tentativo
                    return;
                }

                statusDiv.innerHTML = `<div style="background-color: #cff4fc; color: #055160; padding: 1rem; border: 1px solid #b6effb; border-radius: 0.25rem;">Flusso completato. Salvataggio dell'account in corso...</div>`;

                // I dati necessari vengono passati nell'oggetto 'shared_config'
                const wabaData = response.authResponse.shared_config;

                const payload = {
                    _token: '{{ csrf_token() }}',
                    name: accountName,
                    accessToken: response.authResponse.accessToken,
                    wabaId: wabaData.waba_id,
                    phoneNumberId: wabaData.phone_number_id,
                    businessName: wabaData.business_name,
                    phoneNumber: wabaData.phone_number,
                };

                fetch('{{ route("whatsapp-accounts.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => {
                    if (!res.ok) {
                        // Gestisce errori HTTP come 409, 422, 500
                        return res.json().then(err => { throw new Error(err.message || `Errore del server: ${res.status}`) });
                    }
                    return res.json();
                })
                .then(data => {
                    if (data.success) {
                        statusDiv.innerHTML = `<div style="background-color: #d1e7dd; color: #0f5132; padding: 1rem; border: 1px solid #badbcc; border-radius: 0.25rem;">${data.message} Reindirizzamento...</div>`;
                        window.location.href = data.redirect_url;
                    } else {
                        // Questo blocco potrebbe non essere raggiunto se l'errore è gestito nel .catch
                        throw new Error(data.message || 'Errore sconosciuto.');
                    }
                })
                .catch(error => {
                    console.error('Error saving account:', error);
                    statusDiv.innerHTML = `<div style="background-color: #f8d7da; color: #842029; padding: 1rem; border: 1px solid #f5c2c7; border-radius: 0.25rem;">Errore durante il salvataggio: ${error.message}</div>`;
                });
            }

            // Inizializzazione asincrona del Facebook SDK
            window.fbAsyncInit = function() {
                FB.init({
                    appId: '{{ $facebook_client_id }}',
                    cookie: true,
                    xfbml: true,
                    version: 'v19.0'
                });
            };

            (function(d, s, id){
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) {return;}
                js = d.createElement(s); js.id = id;
                js.src = "https://connect.facebook.net/it_IT/sdk.js";
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));
        </script>
    @endif
</div>
{{-- @endsection --}}