<?php

namespace App\Livewire;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    public string $locale;

    public function mount()
    {
        $this->locale = Session::get('locale', App::getLocale()); // Valeur par défaut
    }

    public function switchLanguage()
    {
        $this->locale = $this->locale === 'fr' ? 'en' : 'fr';
        Session::put('locale', $this->locale);
        $this->redirect(request()->header('Referer') ?? 'dashboard'); // Redirige vers la page précédente ou le tableau de bord
    }

    public function render()
    {
        return view('livewire.language-switcher');
    }
}
