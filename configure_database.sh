#!/bin/bash
# Configure database credentials from Railway environment variables
# This mimics what the web installer does - writes directly to Database.php

CONFIG_FILE="app/Config/Database.php"

echo "Configuring database credentials..."

# Read the current Database.php and replace the default values with environment variables
# This is what the original installer does

if [ -f "$CONFIG_FILE" ]; then
    # Backup original
    cp "$CONFIG_FILE" "${CONFIG_FILE}.backup"
    
    # Replace hostname
    if [ ! -z "$DB_HOST" ]; then
        sed -i "s/'hostname' => 'localhost'/'hostname' => '${DB_HOST}'/" "$CONFIG_FILE"
        echo "✓ Set DB_HOST"
    fi
    
    # Replace username
    if [ ! -z "$DB_USER" ]; then
        sed -i "s/'username' => 'root'/'username' => '${DB_USER}'/" "$CONFIG_FILE"
        echo "✓ Set DB_USER"
    fi
    
    # Replace password
    if [ ! -z "$DB_PASSWORD" ]; then
        # Escape special characters in password
        ESCAPED_PASSWORD=$(echo "$DB_PASSWORD" | sed 's/[\/&]/\\&/g')
        sed -i "s/'password' => ''/'password' => '${ESCAPED_PASSWORD}'/" "$CONFIG_FILE"
        echo "✓ Set DB_PASSWORD"
    fi
    
    # Replace database name
    if [ ! -z "$DB_NAME" ]; then
        sed -i "s/'database' => 'servana'/'database' => '${DB_NAME}'/" "$CONFIG_FILE"
        echo "✓ Set DB_NAME"
    fi
    
    # Replace port
    if [ ! -z "$DB_PORT" ]; then
        sed -i "s/'port'     => 3306/'port'     => ${DB_PORT}/" "$CONFIG_FILE"
        echo "✓ Set DB_PORT"
    fi
    
    echo "Database configuration complete!"
else
    echo "ERROR: Database.php not found!"
    exit 1
fi
