#!/bin/bash

echo "Creating middleware files..."

# Array of middleware names
middleware=(
    "EnsureOrganizationExists"
    "EnsureUserBelongsToOrganization" 
    "CheckUserPermissions"
    "EnforceMultiFactorAuth"
    "CheckUserStatus"
    "LogUserActivity"
)

# Create each middleware file
for mw in "${middleware[@]}"; do
    echo "Creating middleware: $mw"
    php artisan make:middleware $mw
done

echo ""
echo "All middleware files created in app/Http/Middleware/"
echo "Now replace their content with the code from the artifact."
echo ""
echo "Files created:"
for mw in "${middleware[@]}"; do
    echo "- app/Http/Middleware/$mw.php"
done
