PROJECT_NAME={!! escapeshellarg($template['project_name']) !!}

if [ -z "$PROJECT_NAME" ]; then
    error "Failed to extract project name from template"
fi

if [ -e "$PROJECT_NAME" ]; then
    error "Directory '$PROJECT_NAME' already exists"
fi

@env('local')
    debug "Project name: $PROJECT_NAME"
@endenv

mkdir "$PROJECT_NAME"

@env('local')
    info "Downloading template..."
@endenv

if ! curl -fsSL -o "$TMP_DIR/template.tar" "{!! $downloadUrl !!}"; then
    error "Failed to download template"
fi

@include('scripts.partials.extract-archive')

cd "$PROJECT_NAME" || error "Failed to enter project directory"
