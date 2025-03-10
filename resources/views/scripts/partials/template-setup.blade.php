PROJECT_NAME="{{ $template['project_name'] }}"

if [ -z "$PROJECT_NAME" ]; then
    error "Failed to extract project name from template"
fi

@env('local')
    debug "Project name: $PROJECT_NAME"
@endenv

mkdir -p "$PROJECT_NAME"

@env('local')
    info "Downloading template..."
@endenv

if ! curl -fsSL -o "$TMP_DIR/template.tar" "{!! $downloadUrl !!}"; then
    error "Failed to download template"
fi

@include('scripts.partials.extract-archive')

cd "$PROJECT_NAME" || error "Failed to enter project directory"
