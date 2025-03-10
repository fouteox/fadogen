TMP_DIR=$(mktemp -d)
trap cleanup EXIT INT TERM

@env('local')
    debug "Created temporary directory: $TMP_DIR"
@endenv

if [ -n "$PROJECT_NAME" ] && [ -d "$PROJECT_NAME" ]; then
    error "Directory '$PROJECT_NAME' already exists"
fi
