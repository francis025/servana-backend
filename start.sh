#!/bin/bash
# Railway startup script for Servana backend
# This script ensures environment variables are properly passed to PHP

# Set permissions
chmod -R 777 writable

# Export all environment variables to make them available to PHP
export DB_HOST="${DB_HOST}"
export DB_USER="${DB_USER}"
export DB_PASSWORD="${DB_PASSWORD}"
export DB_NAME="${DB_NAME}"
export DB_PORT="${DB_PORT}"
export CI_ENVIRONMENT="${CI_ENVIRONMENT}"
export DECRYPTION_KEY="${DECRYPTION_KEY}"
export DECRYPTION_IV="${DECRYPTION_IV}"
export PAYTM_ENCRYPTION_IV="${PAYTM_ENCRYPTION_IV}"

# Start PHP built-in server with environment variables
exec php -d display_errors=1 \
     -d error_reporting=E_ALL \
     -d variables_order=EGPCS \
     -S 0.0.0.0:${PORT} \
     -t public
