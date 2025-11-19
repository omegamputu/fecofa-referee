@component('mail::message')
# Bonjour, {{ $user->name }}

Vous avez été invité à rejoindre la plateforme **FECOFA Arbitrage**.

Cliquez sur le bouton ci-dessous pour définir votre mot de passe et activer votre accès.

@component('mail::button', ['url' => $url, 'color' => 'primary'])
Définir mon mot de passe
@endcomponent

Ce lien expirera dans **{{ $expires }} minutes**.  
Si vous n’êtes pas à l’origine de cette invitation,vous pouvez ignorer cet email.

Besoin d’aide ?  Contactez le support : **support@fecofa.cd**

Merci,  
**L'équipe Support**

@slot('subcopy')
Si le bouton ne fonctionne pas, copiez-collez  ce lien dans votre navigateur :  
{{ $url }}
@endslot

@slot('subcopy')
© {{ date('Y') }} **FECOFA Arbitrage**.  
Tous droits réservés.  
Fédération Congolaise de Football Association
@endslot
@endcomponent


