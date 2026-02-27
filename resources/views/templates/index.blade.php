{{-- Assumendo un layout base --}}
<div class="container" style="font-family: sans-serif; padding: 2rem;">
    <h1>Gestione Template WhatsApp</h1>
    <p>Elenco dei template di messaggi associati ai tuoi account.</p>

    <div style="margin: 1.5rem 0;">
        <a href="{{ route('templates.create') }}" style="padding: 0.5rem 1rem; background-color: #198754; color: white; text-decoration: none; border-radius: 0.25rem;">Crea Nuovo Template</a>
        <a href="{{ route('campaigns.create') }}" style="margin-left: 1rem; color: #6c757d; text-decoration: none;">Torna alle Campagne</a>
    </div>

    @if (session('success'))
        <div style="background-color: #d1e7dd; color: #0f5132; padding: 1rem; border: 1px solid #badbcc; border-radius: 0.25rem; margin-bottom: 1rem;">
            {{ session('success') }}
        </div>
    @endif
    @if (isset($error))
        <div style="background-color: #f8d7da; color: #842029; padding: 1rem; border: 1px solid #f5c2c7; border-radius: 0.25rem; margin-bottom: 1rem;">
            {!! $error !!}
        </div>
    @endif

    @if(empty($templates))
        <div style="background-color: #cff4fc; color: #055160; padding: 1rem; border: 1px solid #b6effb; border-radius: 0.25rem;">
            Nessun template trovato per gli account collegati.
        </div>
    @else
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="text-align: left; border-bottom: 2px solid #dee2e6;">
                <tr>
                    <th style="padding: 0.5rem;">Nome Template</th>
                    <th style="padding: 0.5rem;">Account</th>
                    <th style="padding: 0.5rem;">Categoria</th>
                    <th style="padding: 0.5rem;">Lingua</th>
                    <th style="padding: 0.5rem;">Stato</th>
                </tr>
            </thead>
            <tbody>
                @foreach($templates as $template)
                    <tr style="border-bottom: 1px solid #dee2e6;">
                        <td style="padding: 0.5rem;"><code>{{ $template['name'] }}</code></td>
                        <td style="padding: 0.5rem;">{{ $template['account_name'] }}</td>
                        <td style="padding: 0.5rem;">{{ $template['category'] }}</td>
                        <td style="padding: 0.5rem;">{{ $template['language'] }}</td>
                        <td style="padding: 0.5rem;">
                            <span style="padding: 0.2em 0.6em; border-radius: 0.25rem; color: white; background-color: {{ $template['status'] == 'APPROVED' ? '#198754' : ($template['status'] == 'PENDING' ? '#ffc107' : '#dc3545') }};">
                                {{ $template['status'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>