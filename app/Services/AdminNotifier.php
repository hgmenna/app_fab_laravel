<?php

namespace App\Services;

use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AdminNotifier
{
    /**
     * @param Page|null $pageInstance Instancia de la página (opcional para recursos relacionados)
     * @param Model $record El modelo afectado
     * @param string $operation Acción realizada
     * @param string|array $displayFields Campo(s) a mostrar del registro (soporta relación.campo)
     * @param string|null $customResourceName Nombre manual del recurso (ej: 'el torneo')
     */
    public static function send(
        ?Page $pageInstance, 
        Model $record, 
        string $operation, 
        string|array $displayFields = 'name',
        ?string $customResourceName = null
    ): void {
        $user = Auth::user(); 

        // 1. Obtener el nombre del recurso: Prioridad al nombre manual, luego al del panel [2]
        $resourceLabel = $customResourceName;
        if (!$resourceLabel && $pageInstance) {
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

        Notification::make()
            ->title("Operación: " . ucfirst($operation))
            ->body($message)
            ->info()
            ->sendToDatabase([$user, ...User::where('name', 'super-admin')->get()]); 
    }

    public static function sendException(Throwable $e): void
    {
        // Usuario que generó el error (si existe)
        $user = Auth::user();
        $userName = $user?->name ?? 'Usuario no autenticado';

        // Administradores a notificar
        $admins = User::where('name', 'super-admin')->get();

        // Mensaje institucional
        $message = "Se produjo una excepción en el sistema.\n".
                "Usuario: {$userName}\n".
                "Mensaje: {$e->getMessage()}\n".
                "Archivo: {$e->getFile()}\n".
                "Línea: {$e->getLine()}";

        Notification::make()
            ->title('⚠️ Error en el sistema')
            ->body(nl2br($message))
            ->danger()
            ->sendToDatabase($admins);
    }


}