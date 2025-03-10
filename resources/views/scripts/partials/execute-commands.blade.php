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

if [ -d "$TMP_DIR/deployment" ]; then
    @env('local')
        info "Moving deployment directory from TMP_DIR to project root..."
    @endenv

    mv "$TMP_DIR/deployment" ./
    @env('local')
        info "Deployment directory moved successfully"
    @endenv

    if [ -f "./deployment/Dockerfile" ]; then
        mv "./deployment/Dockerfile" ./
    fi

    if [ -f "./deployment/compose.prod.yaml" ]; then
        mv "./deployment/compose.prod.yaml" ./
    fi

    if [ -f "./deployment/deploy.yml" ]; then
        mkdir -p ./.github/workflows
        mv "./deployment/deploy.yml" ./.github/workflows/
    fi

    if [ -f "./deployment/.env.production" ]; then
        mv "./deployment/.env.production" ./
    fi

    if git rev-parse --is-inside-work-tree > /dev/null 2>&1; then
        git add .
        git commit -q -m "Add deployment files"
    fi
fi

rm "$TMP_DIR/commands.sh"
rm "$TMP_DIR/template.tar"

echo ""
success "{{ __("Installation completed successfully!") }} 🎉"
