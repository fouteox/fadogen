TMP_DIR=$(mktemp -d)
trap cleanup EXIT INT TERM

@env('local')
    debug "Created temporary directory: $TMP_DIR"
@endenv
