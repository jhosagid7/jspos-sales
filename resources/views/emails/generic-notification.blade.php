@component('mail::message')
# Hola!

{{ $body }}

Gracias por preferirnos,<br>
{{ config('app.name') }}
@endcomponent
