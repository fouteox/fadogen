@env('local')
    echo ""
    info "Commands that will be executed:"
    echo "-----------------------------"
    cat "$TMP_DIR/commands.sh"
    echo "-----------------------------"
    echo ""
@endenv
exec 3< "$TMP_DIR/commands.sh"
while IFS= read -r cmd <&3 || [ -n "$cmd" ]; do
    if [ -n "$cmd" ]; then
        @env('local')
            info "Executing: $cmd"
        @endenv
        if ! eval "$cmd"; then
            exec 3<&-
            rm "$TMP_DIR/commands.sh"
            rm "$TMP_DIR/template.tar"
            error "Command failed: $cmd"
        fi
    fi
    done
exec 3<&-

rm "$TMP_DIR/commands.sh"
rm "$TMP_DIR/template.tar"

echo ""
success "{{ __("Installation completed successfully!") }} 🎉"
