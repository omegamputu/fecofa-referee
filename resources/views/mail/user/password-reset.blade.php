@component('mail::message')
# Bonjour, {{ $user->name }},

Vous avez demandé à réinitialiser votre mot de passe.

@component('mail::button', ['url' => $url, 'color' => 'primary'])
Réinitialiser mon mot de passe
@endcomponent

Si vous n’êtes pas à l’origine de cette demande, ignorez cet email.

Merci,  
**L'équipe Support**
@slot('subcopy')
Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :  
{{ $url }}
@endslot
@endcomponent
