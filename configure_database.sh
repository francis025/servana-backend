#!/bin/bash
# Configure database credentials from Railway environment variables AT RUNTIME
# This mimics what the web installer does - writes directly to Database.php

CONFIG_FILE="app/Config/Database.php"

# Railway provides MySQL credentials with MYSQL_ prefix
# Use those if DB_ variables aren't set
DB_HOST="${DB_HOST:-${MYSQLHOST}}"
DB_USER="${DB_USER:-${MYSQLUSER}}"
DB_PASSWORD="${DB_PASSWORD:-${MYSQLPASSWORD}}"
DB_NAME="${DB_NAME:-${MYSQLDATABASE}}"
DB_PORT="${DB_PORT:-${MYSQLPORT}}"

echo "=== Configuring Database Credentials ==="
echo "DB_HOST: ${DB_HOST:-NOT SET}"
echo "DB_USER: ${DB_USER:-NOT SET}"
echo "DB_NAME: ${DB_NAME:-NOT SET}"
echo "DB_PORT: ${DB_PORT:-NOT SET}"

if [ -f "$CONFIG_FILE" ]; then
    # Create backup if it doesn't exist
    if [ ! -f "${CONFIG_FILE}.original" ]; then
        cp "$CONFIG_FILE" "${CONFIG_FILE}.original"
        echo "✓ Created original backup"
    fi
    
    # Restore from original for clean replacement
    cp "${CONFIG_FILE}.original" "$CONFIG_FILE"
    
    # Replace hostname
    if [ ! -z "$DB_HOST" ]; then
        sed -i "s/'hostname' => 'localhost'/'hostname' => '${DB_HOST}'/" "$CONFIG_FILE"
        echo "✓ Set DB_HOST to: $DB_HOST"
    else
        echo "❌ DB_HOST not set!"
    fi
    
    # Replace username
    if [ ! -z "$DB_USER" ]; then
        sed -i "s/'username' => 'root'/'username' => '${DB_USER}'/" "$CONFIG_FILE"
        echo "✓ Set DB_USER to: $DB_USER"
    else
        echo "❌ DB_USER not set!"
    fi
    
    # Replace password
    if [ ! -z "$DB_PASSWORD" ]; then
        # Escape special characters in password
        ESCAPED_PASSWORD=$(echo "$DB_PASSWORD" | sed 's/[\/&]/\\&/g')
        sed -i "s/'password' => ''/'password' => '${ESCAPED_PASSWORD}'/" "$CONFIG_FILE"
        echo "✓ Set DB_PASSWORD (hidden)"
    else
        echo "❌ DB_PASSWORD not set!"
    fi
    
    # Replace database name
    if [ ! -z "$DB_NAME" ]; then
        sed -i "s/'database' => 'servana'/'database' => '${DB_NAME}'/" "$CONFIG_FILE"
        echo "✓ Set DB_NAME to: $DB_NAME"
    else
        echo "❌ DB_NAME not set!"
    fi
    
    # Replace port
    if [ ! -z "$DB_PORT" ]; then
        sed -i "s/'port'     => 3306/'port'     => ${DB_PORT}/" "$CONFIG_FILE"
        echo "✓ Set DB_PORT to: $DB_PORT"
    else
        echo "❌ DB_PORT not set!"
    fi
    
    echo "=== Database Configuration Complete ==="
else
    echo "❌ ERROR: Database.php not found at: $CONFIG_FILE"
    exit 1
fi
