<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SystemActivityEmailNotification;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class AdminNotifier
{
    public static function notifyBulkAction(
        mixed $action,
        string $operation,
        string|array $displayFields = 'name',
        string $resourceName = 'registros',
    ): mixed {
        return $action->after(fn ($records) => self::sendBulk(
            $records,
            $operation,
            $displayFields,
            $resourceName,
        ));
    }

    public static function notifyAction(
        mixed $action,
        ?Page $pageInstance,
        string $operation,
        string|array $displayFields = 'name',
        ?string $customResourceName = null,
        bool $sendEmail = true,
    ): mixed {
        return $action->after(fn (Model $record) => self::send(
            $pageInstance,
            $record,
            $operation,
            $displayFields,
            $customResourceName,
            $sendEmail,
        ));
    }

    /**
     * @param  Page|null  $pageInstance  Instancia de la página (opcional para recursos relacionados)
     * @param  Model  $record  El modelo afectado
     * @param  string  $operation  Acción realizada
     * @param  string|array  $displayFields  Campo(s) a mostrar del registro (soporta relación.campo)
     * @param  string|null  $customResourceName  Nombre manual del recurso (ej: 'el torneo')
     */
    public static function send(
        ?Page $pageInstance,
        Model $record,
        string $operation,
        string|array $displayFields = 'name',
        ?string $customResourceName = null,
        bool $sendEmail = true,
    ): void {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        // 1. Obtener el nombre del recurso: Prioridad al nombre manual, luego al del panel [2]
        $resourceLabel = $customResourceName;
        if (! $resourceLabel && $pageInstance) {
            $resourceLabel = $pageInstance::getResource()::getNavigationLabel();
        }
        $resourceLabel = $resourceLabel ?? 'registro';

        // 2. Resolver los campos del registro (Soporta relaciones como 'player.last_name')
        if (is_array($displayFields)) {
            $recordName = collect($displayFields)
                ->map(fn ($field) => data_get($record, $field))
                ->filter()
                ->implode(', ');
        } else {
            $recordName = data_get($record, $displayFields) ?? "ID: {$record->id}";
        }

        // 3. Construir el mensaje dinámico
        $message = "El usuario {$user->name} {$operation} a {$recordName} en {$resourceLabel}";

        $title = 'Operación: '.ucfirst($operation);
        $recipients = self::recipients();

        FilamentNotification::make()
            ->title($title)
            ->body($message)
            ->info()
            ->sendToDatabase($recipients);

        if ($sendEmail) {
            self::sendEmail($recipients, new SystemActivityEmailNotification($title, $message));
        }
    }

    public static function recipients(): Collection
    {
        $user = Auth::user();
        $admins = User::query()
            ->where(function ($query): void {
                $query->where('name', 'super-admin')
                    ->orWhereHas('roles', fn ($roles) => $roles->where('name', 'super-admin'));
            })
            ->get();

        return collect([$user])
            ->merge($admins)
            ->filter()
            ->unique(fn (User $recipient): string => $recipient->getMorphClass().':'.$recipient->getKey())
            ->values();
    }

    public static function recipientEmails(): array
    {
        return self::recipients()
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function sendBulk(
        iterable $records,
        string $operation,
        string|array $displayFields = 'name',
        string $resourceName = 'registros',
    ): void {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $records = collect($records);
        $names = $records
            ->map(function (Model $record) use ($displayFields): string {
                $fields = is_array($displayFields) ? $displayFields : [$displayFields];

                return collect($fields)
                    ->map(fn (string $field) => data_get($record, $field))
                    ->filter()
                    ->implode(', ') ?: "ID: {$record->getKey()}";
            })
            ->take(10)
            ->implode('; ');

        $message = "El usuario {$user->name} {$operation} {$records->count()} registros en {$resourceName}";

        if ($names !== '') {
            $message .= ": {$names}";
        }

        $title = 'Operación masiva: '.ucfirst($operation);
        $recipients = self::recipients();

        FilamentNotification::make()
            ->title($title)
            ->body($message)
            ->info()
            ->sendToDatabase($recipients);

        self::sendEmail($recipients, new SystemActivityEmailNotification($title, $message));
    }

    public static function sendException(Throwable $e): void
    {
        // Usuario que generó el error (si existe)
        $user = Auth::user();
        $userName = $user?->name ?? 'Usuario no autenticado';

        // Mensaje institucional
        $message = "Se produjo una excepción en el sistema.\n".
                "Usuario: {$userName}\n".
                "Mensaje: {$e->getMessage()}\n".
                "Archivo: {$e->getFile()}\n".
                "Línea: {$e->getLine()}";

        $title = '⚠️ Error en el sistema';
        $recipients = self::recipients();

        FilamentNotification::make()
            ->title($title)
            ->body(nl2br($message))
            ->danger()
            ->sendToDatabase($recipients);

        self::sendEmail($recipients, new SystemActivityEmailNotification($title, $message));
    }

    private static function sendEmail(Collection $recipients, SystemActivityEmailNotification $notification): void
    {
        try {
            Notification::send(
                $recipients->filter(fn (User $recipient): bool => filled($recipient->email)),
                $notification,
            );
        } catch (Throwable $exception) {
            Log::error('No se pudo enviar una notificación del sistema por correo.', [
                'message' => $exception->getMessage(),
                'recipients' => $recipients->pluck('email')->filter()->values()->all(),
            ]);
        }
    }
}
