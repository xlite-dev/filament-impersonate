<?php

namespace XliteDev\FilamentImpersonate\Controllers;

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Lab404\Impersonate\Services\ImpersonateManager;
use Livewire\Redirector;

class ImpersonateController
{
    public static function allowed($current, $target): bool
    {
        return $current->isNot($target)
            && ! app(ImpersonateManager::class)->isImpersonating()
            && (! method_exists($current, 'canImpersonate') || $current->canImpersonate())
            && (! method_exists($target, 'canBeImpersonated') || $target->canBeImpersonated());
    }

    public static function impersonate($record): bool|Redirector|RedirectResponse
    {
        if (! static::allowed(Filament::auth()->user(), $record)) {
            return false;
        }

        app(ImpersonateManager::class)->take(
            Filament::auth()->user(),
            $record,
            config('filament-impersonate.guard')
        );

        session()->forget(array_unique([
            'password_hash_'.config('filament-impersonate.guard'),
            'password_hash_'.config('filament.auth.guard'),
        ]));
        session()->put('impersonate.back_to', request('fingerprint.path'));

        return redirect(config('filament-impersonate.redirect_to'));
    }

    public static function leave(): bool|Redirector|RedirectResponse
    {
        if (! app(ImpersonateManager::class)->isImpersonating()) {
            return redirect('/');
        }

        app(ImpersonateManager::class)->leave();

        session()->forget(array_unique([
            'password_hash_'.config('filament-impersonate.guard'),
            'password_hash_'.config('filament.auth.guard'),
        ]));

        return redirect(
            session()->pull('impersonate.back_to') ?? config('filament.path')
        );
    }
}
