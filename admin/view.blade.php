{{-- Views from requests.views are exposed under this prefix. If the partials do not render, adjust only this line. --}}
@php($partials = 'blueprint.extensions.desec.partials')

@include($partials . '.flash')
@include($partials . '.' . $page, $data)
