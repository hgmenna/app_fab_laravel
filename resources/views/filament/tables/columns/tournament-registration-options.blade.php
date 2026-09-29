@php
    $record = $getRecord();
    $payment = (bool) $record->is_payment_enabled;
    $registration = (bool) $record->registration_enabled;
@endphp

<div class="flex flex-col items-start gap-1.5 text-xs leading-none">
    <div class="flex items-center gap-1.5" title="{{ $payment ? 'Pago habilitado' : 'Sin pago' }}">
        <span class="w-8 font-semibold">$</span>
        <x-filament::icon
            :icon="$payment ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'"
            @class([
                'h-5 w-5 shrink-0',
                'text-success-500' => $payment,
                'text-danger-500' => ! $payment,
            ])
        />
    </div>

    <div class="flex items-center gap-1.5" title="{{ $registration ? 'Inscripción habilitada' : 'Inscripción cerrada' }}">
        <span class="w-8 font-semibold">INSC</span>
        <x-filament::icon
            :icon="$registration ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'"
            @class([
                'h-5 w-5 shrink-0',
                'text-success-500' => $registration,
                'text-danger-500' => ! $registration,
            ])
        />
    </div>
</div>
