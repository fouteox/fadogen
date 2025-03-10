@env('local')
    info "Extracting template archive directly to TMP_DIR..."
@endenv

if ! tar -xf "$TMP_DIR/template.tar" -C "$TMP_DIR"; then
    error "Failed to extract template archive"
fi

@env('local')
    debug "Checking extracted content:"
    ls -la "$TMP_DIR"
@endenv

if [ ! -f "$TMP_DIR/commands.sh" ]; then
    error "commands.sh not found in archive"
fi

@env('local')
    debug "Archive extraction completed successfully"
@endenv
