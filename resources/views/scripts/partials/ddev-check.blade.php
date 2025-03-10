@env('local')
    info "Vérification de l'installation de ddev..."
@endenv

@php
    $minVersion = '1.24.3';
@endphp

if ! command_exists ddev; then
error "{{ __('ddev is not installed. Please install it:') }}
macOS:     brew install ddev/ddev/ddev
Windows:   https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/#ddev-installation-windows
Linux:     https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/#ddev-installation-linux"
fi

CURRENT_VERSION=$(ddev --version | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')

# Utilisation de la commande 'sort -V -C' pour comparer les versions
if ! printf '%s\n%s\n' "{{ $minVersion }}" "$CURRENT_VERSION" | sort -V -C; then
    error "{{ __('Your DDEV version (:current) is outdated. Minimum required version is :min', ['current' => '$CURRENT_VERSION', 'min' => $minVersion]) }}
    To upgrade:
    macOS:     brew upgrade ddev/ddev/ddev
    Windows:   https://ddev.readthedocs.io/en/stable/users/install/ddev-upgrade/#upgrading-ddev-windows
    Linux:     https://ddev.readthedocs.io/en/stable/users/install/ddev-upgrade/#upgrading-ddev-linux"
fi
