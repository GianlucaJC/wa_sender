{{-- Assumendo che tu abbia un layout base come layouts.app --}}
{{-- @extends('layouts.app') --}}
{{-- @section('content') --}}
<div class="container" style="font-family: sans-serif; padding: 2rem;">
    <h1>Account WhatsApp Collegati</h1>
    <p>Questi sono gli account WhatsApp Business che hai collegato alla piattaforma. Puoi usarli per creare e inviare campagne.</p>

    <div style="margin: 1.5rem 0;">
        <a href="{{ route('whatsapp-accounts.create') }}" style="padding: 0.5rem 1rem; background-color: #0d6efd; color: white; text-decoration: none; border-radius: 0.25rem;">Collega Nuovo Account</a>
    </div>

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

    @if($accounts->isEmpty())
        <div style="background-color: #cff4fc; color: #055160; padding: 1rem; border: 1px solid #b6effb; border-radius: 0.25rem;">
            Nessun account WhatsApp è stato ancora collegato.
        </div>
    @else
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="text-align: left; border-bottom: 2px solid #dee2e6;">
                <tr>
                    <th style="padding: 0.5rem;">Nome Account</th>
                    <th style="padding: 0.5rem;">Nome Azienda (Meta)</th>
                    <th style="padding: 0.5rem;">Numero di Telefono</th>
                    <th style="padding: 0.5rem;">Data Collegamento</th>
                    <th style="padding: 0.5rem;">Azioni</th>
                </tr>
            </thead>
            <tbody>
                @foreach($accounts as $account)
                    <tr style="border-bottom: 1px solid #dee2e6;">
                        <td style="padding: 0.5rem;">{{ $account->name }}</td>
                        <td style="padding: 0.5rem;">{{ $account->business_name }}</td>
                        <td style="padding: 0.5rem;">{{ $account->phone_number_display }}</td>
                        <td style="padding: 0.5rem;">{{ $account->created_at->format('d/m/Y H:i') }}</td>
                        <td style="padding: 0.5rem;">
                            <form action="{{ route('whatsapp-accounts.destroy', $account) }}" method="POST" onsubmit="return confirm('Sei sicuro di voler rimuovere questo account?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="padding: 0.25rem 0.5rem; background-color: #dc3545; color: white; border: none; border-radius: 0.25rem; cursor: pointer;">Rimuovi</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
{{-- @endsection --}}