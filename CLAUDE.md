# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a CakePHP 5 plugin (`3xw/cakephp-attachment`) for file and media attachment management. It provides a storage abstraction layer using Flysystem, database persistence for attachment metadata, and frontend/backend Vue.js components for file browsing and uploading.

**Namespace:** `Trois\Attachment`

## Common Commands

```bash
# Install dependencies
composer install

# Run database migrations
bin/cake migrations migrate -p Trois/Attachment

# Run tests
vendor/bin/phpunit

# Run a specific test file
vendor/bin/phpunit tests/TestCase/Model/Table/AttachmentsTableTest.php

# CLI commands provided by the plugin
bin/cake at_profile              # Profile management
bin/cake at_get_image_sizes      # Retrieve width/height of images
bin/cake at_create_missing_translations <locale>  # Create missing translations
```

## Architecture

### Storage Layer (Flysystem-based)

- **ProfileRegistry** (`src/Filesystem/ProfileRegistry.php`): Singleton registry for storage profiles
- **Profile** (`src/Filesystem/Profile.php`): Represents a storage profile configuration
- **FilesystemRegistry** (`src/Filesystem/FilesystemRegistry.php`): Creates Flysystem instances per profile

Storage profiles are configured in `config/attachment.php` under `Trois/Attachment.profiles`. Default profiles:
- `default`: Local storage in `webroot/files`
- `external`: External URLs
- `thumbnails`: Local storage in `webroot/thumbnails`

Custom adapters in `src/Filesystem/Adapter/`: OpenStack, FtpWindows, WebDAV, External

### Core Behaviors (src/ORM/Behavior/)

The `AttachmentsTable` uses these behaviors in sequence:
1. **UserIDBehavior**: Associates uploads with the authenticated user
2. **ExternalBehavior**: Handles external URL attachments
3. **EmbedBehavior**: Processes embedded content (YouTube, Vimeo)
4. **AarchiveBehavior**: Manages archive operations (must run before FlyBehavior)
5. **FlyBehavior**: Core file upload/storage handling via Flysystem
6. **ATagBehavior**: Manages attachment tags

### Models

- **AttachmentsTable**: Main table for file metadata (profile, type, subtype, path, dimensions, etc.)
- **AtagsTable**: Tags for categorizing attachments
- **AtagTypesTable**: Tag type categories
- **AarchivesTable**: Archive/compression records

### Controllers

- **AttachmentsController**: CRUD API using FriendsOfCake/Crud with JSON API responses
- **ResizeController**: Thumbnail generation on-the-fly
- **DownloadController**: Secure file downloads
- **AarchivesController**: Archive operations

### View Helper

**AttachmentHelper** (`src/View/Helper/AttachmentHelper.php`): Main helper for frontend integration
- `input()`: Renders Vue.js file picker for forms
- `index()`: Renders file browser component
- `image()`: Generates responsive images with srcset
- `thumbSrc()`: Returns thumbnail URL with transformation parameters

Restriction constants for filtering: `TAG_RESTRICTED`, `TAG_OR_RESTRICTED`, `TYPES_RESTRICTED`, `USER_RESTRICTED`, `PROFILE_RESTRICTED`

### Event System

Listeners extend `BaseListener` (`src/Listener/BaseListener.php`). Configure in `config/attachment.php` under `Trois/Attachment.listeners`. Available events follow CRUD patterns: `beforePaginate`, `afterPaginate`, `beforeSave`, `afterSave`, `beforeDelete`, `afterDelete`, etc.

### Thumbnail Generation

Thumbnails are generated on-demand via `/thumbnails/*` route. URL format: `/thumbnails/{profile}/w{width}h{height}c{crop-ratio}/{path}`

Configuration in `config/attachment.php` under `Trois/Attachment.thumbnails`:
- `driver`: `Imagick` or `GD`
- `compression`: Paths to jpegoptim, pngquant, cwebp
- `widths`, `heights`, `crops`: Allowed dimensions and crop ratios

## Configuration

Main config file: `config/attachment.php`

Key settings:
- `profiles`: Storage adapters (S3, local, etc.)
- `upload`: Default upload constraints (maxsize, types, profile)
- `browse`: File browser settings
- `thumbnails`: Image processing settings
- `md5Unique`: Enforce unique files by MD5 hash (default: true)
- `translate`: Enable i18n for title/description fields

## Frontend Dependencies

The plugin includes Vue.js 2 components. Host app must provide:
- jQuery >= 1.x
- Vue.js 2.x with vue-resource
- Bootstrap 4.x CSS

Layout requires a `#admin-app` container for Vue mounting and a `template` block for Vue templates.
