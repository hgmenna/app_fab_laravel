<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionClass(Permission::class)
            ->setRoleClass(Role::class);

        /**
         * PUENTE DE PERMISOS: INVITADOS -> ROL JUGADOR
         * Esta lógica permite que los invitados hereden los permisos de Shield
         * sin tocar Recursos ni Policies.
         */
        
        Gate::before(function (?User $user, $ability) {
            
            // Solo actuamos si el usuario navega en el panel 'guest'
            if (Filament::getCurrentPanel()?->getId() === 'guest') {
                
                // Buscamos el rol 'Jugador' con TODOS sus permisos cargados (estándar y custom)
                $role = Role::where('name', 'Jugador')
                    ->where('guard_name', 'web')
                    ->with('permissions')
                    ->first();

                if ($role) {
                    /**
                     * 1. COMPROBACIÓN UNIVERSAL DE PERMISOS
                     * Si Shield pregunta por CUALQUIER permiso (personalizado o de recurso),
                     * buscamos si el nombre existe en la tabla de permisos del rol Jugador.
                     * Esto cubre: 'PayMembership', 'view_any_torneo', 'export_pdf_inscripcion', etc.
                     */
                    
                    if ($role->permissions->contains('name', $ability)) {
                        return true;
                    }
                     $policyMethods = ['viewAny', 'view', 'create', 'update', 'delete', 'reorder'];
                    if (in_array($ability, $policyMethods)) {
                        return null; 
                    } 
                }
                // Si no es un método de policy y no está en la DB del Jugador, denegamos.
                return false; 
            }
            return null;
        });
        
    }
}
