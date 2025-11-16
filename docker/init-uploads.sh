#!/bin/bash
# Script to ensure uploads directory has correct permissions

mkdir -p /var/www/html/public/uploads/articles
chown -R www-data:www-data /var/www/html/public/uploads
chmod -R 775 /var/www/html/public/uploads

echo "Uploads directory permissions set"

