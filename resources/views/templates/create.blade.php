{{-- Assumendo un layout base --}}
<div class="container" style="font-family: sans-serif; padding: 2rem;">
    <h1>Crea Nuovo Template</h1>
    <p>Invia un nuovo template a Meta per l'approvazione. Ricorda di usare le variabili come <code>{{1}}</code>, <code>{{2}}</code>, etc. per i contenuti dinamici.</p>
    <a href="{{ route('templates.index') }}" style="display: inline-block; margin-bottom: 1.5rem; color: #6c757d; text-decoration: none;">Annulla e torna alla lista</a>

    @if (session('success'))
        <div style="background-color: #d1e7dd; color: #0f5132; padding: 1rem; border: 1px solid #badbcc; border-radius: 0.25rem; margin-bottom: 1rem;">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div style="background-color: #f8d7da; color: #842029; padding: 1rem; border: 1px solid #f5c2c7; border-radius: 0.25rem; margin-bottom: 1rem;">
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div style="background-color: #f8d7da; color: #842029; padding: 1rem; border: 1px solid #f5c2c7; border-radius: 0.25rem; margin-bottom: 1rem;">
            <strong>Sono presenti errori di validazione:</strong>
            <ul style="margin-top: 0.5rem; margin-bottom: 0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('templates.store') }}" method="POST">
        @csrf

        <div style="margin-bottom: 1rem;">
            <label for="whatsapp_account_id" style="display: block; margin-bottom: 0.5rem;">Account WhatsApp*</label>
            <select id="whatsapp_account_id" name="whatsapp_account_id" style="width: 100%; padding: 0.375rem 0.75rem; border: 1px solid #ced4da; border-radius: 0.25rem;" required @if($accounts->isEmpty()) disabled @endif>
                <option value="">Scegli per quale account creare il template...</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}" {{ old('whatsapp_account_id') == $account->id ? 'selected' : '' }}>
                        {{ $account->name }} ({{ $account->phone_number_display }})
                    </option>
                @endforeach
            </select>
            @if($accounts->isEmpty())
                <div style="font-size: 0.875em; color: #dc3545; margin-top: 0.25rem;">Nessun account collegato. Impossibile creare template.</div>
            @endif
        </div>

        <div style="margin-bottom: 1rem;">
            <label for="name" style="display: block; margin-bottom: 0.5rem;">Nome Template*</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required style="width: 100%; padding: 0.375rem 0.75rem; border: 1px solid #ced4da; border-radius: 0.25rem;">
            <div style="font-size: 0.875em; color: #6c757d; margin-top: 0.25rem;">Solo lettere minuscole, numeri e underscore (es. <code>promo_pasqua_24</code>).</div>
        </div>

        <div style="margin-bottom: 1rem;">
            <label for="category" style="display: block; margin-bottom: 0.5rem;">Categoria*</label>
            <select id="category" name="category" required style="width: 100%; padding: 0.375rem 0.75rem; border: 1px solid #ced4da; border-radius: 0.25rem;">
                <option value="MARKETING" {{ old('category') == 'MARKETING' ? 'selected' : '' }}>Marketing</option>
                <option value="UTILITY" {{ old('category') == 'UTILITY' ? 'selected' : '' }}>Utility</option>
                <option value="AUTHENTICATION" {{ old('category') == 'AUTHENTICATION' ? 'selected' : '' }}>Autenticazione</option>
            </select>
        </div>

        <div style="margin-bottom: 1rem;">
            <label for="language_code" style="display: block; margin-bottom: 0.5rem;">Lingua*</label>
            <input type="text" id="language_code" name="language_code" value="{{ old('language_code', 'it') }}" required style="width: 100%; padding: 0.375rem 0.75rem; border: 1px solid #ced4da; border-radius: 0.25rem;">
            <div style="font-size: 0.875em; color: #6c757d; margin-top: 0.25rem;">Codice lingua, es. <code>it</code>, <code>en_US</code>.</div>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label for="body_text" style="display: block; margin-bottom: 0.5rem;">Testo del Messaggio (Body)*</label>
            <textarea id="body_text" name="body_text" rows="5" required style="width: 100%; padding: 0.375rem 0.75rem; border: 1px solid #ced4da; border-radius: 0.25rem;">{{ old('body_text') }}</textarea>
            <div style="font-size: 0.875em; color: #6c757d; margin-top: 0.25rem;">Esempio: <code>Ciao {{1}}, il tuo codice di verifica è {{2}}.</code></div>
        </div>

        <button type="submit" style="padding: 0.5rem 1rem; background-color: #0d6efd; color: white; border: none; border-radius: 0.25rem; cursor: pointer;">Invia per Approvazione</button>
    </form>
</div>