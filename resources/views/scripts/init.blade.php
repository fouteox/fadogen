@include('scripts.partials.header')

@include('scripts.partials.functions')

@include('scripts.partials.ddev-check')

@include('scripts.partials.init-common')

@if(isset($useTemplate) && $useTemplate)
    @include('scripts.partials.template-setup')
@else
    @include('scripts.partials.interactive-setup')
@endif

@include('scripts.partials.execute-commands')
