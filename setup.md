# Local Package Setup Guide

This guide explains how to install the `azeemade/bulk-upload` package into another Laravel application on your local machine using Composer's "repositories" feature.

## 1. Locate Paths

Assume your projects are organized like this:

- **Package Path**: `/Users/azeem/Documents/Projects/open source/bulk-upload`
- **Your Application Path**: `/path/to/your/laravel-app`

## 2. Configure Your Application

Open the `composer.json` file **of the application** where you want to install the package (NOT the package's composer.json).

Add the `repositories` key (or append to it if it exists):

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "/Users/azeem/Documents/Projects/open source/bulk-upload",
      "options": {
        "symlink": true
      }
    }
  ]
}
```

## 3. Require the Package

Run the following command in your **application's** terminal:

```bash
composer require azeemade/bulk-upload @dev
```

If you encounter stability issues, ensure your application's `minimum-stability` is set to `dev` or allow it in the require command.

## 4. Install Assets

After successful installation, publish the configuration and migrations:

```bash
php artisan vendor:publish --provider="Azeemade\BulkUpload\BulkUploadServiceProvider"
```

## 5. Run Migrations

```bash
php artisan migrate
```

## 6. Development Workflow

Since `symlink` is set to `true`, any changes you make in `/Users/azeem/Documents/Projects/open source/bulk-upload` will immediately be reflected in your application without needing `composer update`.
