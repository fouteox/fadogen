{{-- Interactive configuration setup --}}
@env('local')
    info "Downloading questions script..."
@endenv
if ! curl -fsSL -o "$TMP_DIR/questions.php" "{{ str_replace('_LANG_CODE_', '$LANG_CODE', route('localized.prompts.questions', ['locale' => '_LANG_CODE_', 'template' => $template])) }}"; then
    error "Failed to download questions script"
fi

@env('local')
    info "Fetching latest version of configuration tool..."
@endenv
if ! docker pull fouteox/laravel-prompts:latest >/dev/null 2>&1; then
    error "Failed to pull latest version of configuration tool. Please check your internet connection."
fi

@env('local')
    info "Running configuration tool..."
@endenv
if ! docker run -ti --rm \
    -v "$(pwd):/app/dir" \
    -v "$TMP_DIR:/app/output" \
    -v "$TMP_DIR/questions.php:/app/questions.php" \
    fouteox/laravel-prompts:latest -f /app/questions.php; then
    error "Configuration tool failed to complete successfully"
fi

if [ ! -f "$TMP_DIR/result.json" ]; then
    error "result.json was not created by the Docker container"
fi

@env('local')
    debug "Generated result file (result.json):"
    debug "$(cat "$TMP_DIR/result.json" | sed 's/^/  /')"
@endenv
PROJECT_NAME=$(cat "$TMP_DIR/result.json" | grep -o '"project_name": *"[^"]*"' | cut -d'"' -f4)
if [ -z "$PROJECT_NAME" ]; then
    error "Could not extract project name from result.json"
fi

@env('local')
    debug "Project name: $PROJECT_NAME"
    info "Submitting configuration to server..."
@endenv
DOWNLOAD_URL=$(curl -fsSL -X POST \
    -H "Content-Type: application/json" \
    -H "Accept: text/plain" \
    -d @"$TMP_DIR/result.json" \
    "{{ route('api.templates.store') }}")

if [ -z "$DOWNLOAD_URL" ]; then
    error "Invalid download URL"
fi

@env('local')
    success "Configuration submitted successfully (URL: $DOWNLOAD_URL)"
    info "Processing configuration..."
@endenv
TIMEOUT=300
START_TIME=$(date +%s)

while true; do
    if [ $(($(date +%s) - START_TIME)) -gt $TIMEOUT ]; then
        error "Processing timeout after $TIMEOUT seconds"
    fi

    STATUS=$(curl -fsSL -D "$TMP_DIR/headers" -o "$TMP_DIR/template.tar" "$DOWNLOAD_URL")
    STATUS_CODE=$(grep -i "^HTTP" "$TMP_DIR/headers" | tail -n1 | awk '{print $2}')

    case "$STATUS_CODE" in
        200)
            break
            ;;
        202)
            printf "."
            sleep 5
            ;;
        422)
            error "Template generation failed"
            ;;
        404)
            error "Template not found"
            ;;
        *)
            error "Unexpected status ($STATUS_CODE)"
            ;;
    esac
done

mkdir -p "$PROJECT_NAME"

@include('scripts.partials.extract-archive')

cd "$PROJECT_NAME" || error "Failed to enter project directory"
