#!/bin/bash
# Script to ensure uploads directory has correct permissions

mkdir -p /var/www/public/uploads/articles
chown -R www-data:www-data /var/www/public/uploads
chmod -R 775 /var/www/public/uploads

echo "Uploads directory permissions set"

