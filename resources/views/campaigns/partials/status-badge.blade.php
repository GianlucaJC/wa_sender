@php
    $statusClasses = [
        'pending' => 'bg-secondary',
        'processing' => 'bg-primary',
        'completed' => 'bg-success',
        'cancelled' => 'bg-warning text-dark',
        'failed' => 'bg-danger',
    ];
    $statusIcons = [
        'pending' => 'bi-clock',
        'processing' => 'bi-gear-fill',
        'completed' => 'bi-check2-circle',
        'cancelled' => 'bi-exclamation-octagon',
        'failed' => 'bi-x-circle',
    ];
@endphp

<span class="badge fs-6 rounded-pill {{ $statusClasses[$status] ?? 'bg-dark' }}">
    <i class="bi {{ $statusIcons[$status] ?? 'bi-question-circle' }}"></i>
    {{ ucfirst($status) }}
</span>