@php
    $theme = auth()->user()->theme ?? [];
    $footerClasses = ['main-footer', 'footer', 'footer-fix'];
    if(!empty($theme['footer_text_sm']) && filter_var($theme['footer_text_sm'], FILTER_VALIDATE_BOOLEAN)) $footerClasses[] = 'text-sm';
    $footerClassString = implode(' ', $footerClasses);
    
    // Dynamic Version
    $version = file_exists(base_path('version.txt')) ? file_get_contents(base_path('version.txt')) : 'v1.0';
@endphp
<footer class="{{ $footerClassString }}">
    <div class="container-fluid">
        <div class="row">
            <div class="text-center col-md-12 footer-copyright">
                <p class="mb-0">
                    Copyright {{ date('Y') }} © <a href="https://github.com/jhosagid7" target="_blank">jhonnypirela.dev</a> 
                    <span class="ml-2">{{ $version }}</span>
                </p>
            </div>
        </div>
    </div>
</footer>
