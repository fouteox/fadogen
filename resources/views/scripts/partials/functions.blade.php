@env('local')
debug() {
    echo "${YELLOW}[DEBUG] $1${RESET}" >&2
}
@endenv

error() {
    echo "${RED}Error: $1${RESET}" >&2
        cleanup
    exit 1
}

success() {
    echo "${GREEN}✓ $1${RESET}"
}

info() {
    echo "${YELLOW}$1${RESET}"
}

command_exists() {
    command -v "$1" >/dev/null 2>&1
}

cleanup() {
@env('local')
    debug "Running cleanup..."
@endenv
    if [ -d "$TMP_DIR" ]; then
@env('local')
        debug "Removing temporary directory: $TMP_DIR"
@endenv
    rm -rf "$TMP_DIR"
    fi
}

version_gt() {
    test "$(printf '%s\n' "$@" | sort -V | head -n 1)" != "$1"
}

detect_language() {
    if [ -n "$LANG" ]; then
        DETECTED_LANG=$(echo "$LANG" | cut -d'_' -f1)
        else
        if [ -n "$LC_ALL" ]; then
            DETECTED_LANG=$(echo "$LC_ALL" | cut -d'_' -f1)
        else
            if [ -n "$LANGUAGE" ]; then
                DETECTED_LANG=$(echo "$LANGUAGE" | cut -d':' -f1 | cut -d'_' -f1)
            else
                DETECTED_LANG="en"
            fi
        fi
    fi

    case "$DETECTED_LANG" in
        fr|en|de|es)
            echo "$DETECTED_LANG"
            ;;
        *)
            echo "en"
        ;;
    esac
}

LANG_CODE=$(detect_language)
@env('local')
debug "Langue détectée : $LANG_CODE"
@endenv
