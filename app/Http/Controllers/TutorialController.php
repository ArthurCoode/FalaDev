<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * "Não exibir novamente" on the tutorial: it leaves the menu and the
 * dashboard. Settings > Aparência brings it back.
 */
class TutorialController extends Controller
{
    public function dismiss(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['tutorial_dismissed_at' => now()])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Tutorial escondido. Para ver de novo, vá em Configurações > Aparência.',
        ]);

        return to_route('dashboard');
    }

    public function restore(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['tutorial_dismissed_at' => null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'O tutorial voltou para o menu.']);

        return back();
    }
}
