# Attachment plugin for CakePHP 5.x

Attachment plugin solves common problems with media, files and embed data.
The goal is to store files where you want (Dropbox, AWS S3, local, etc.) and keep a record of it in a database table.

Attachment offers both storage layer and database layer as well as frontend and backend solutions for common needs.

It uses [CakePHP 5](https://cakephp.org/), [Flysystem](https://flysystem.thephpleague.com/) and [Intervention Image](http://image.intervention.io/)

## Requirements

- PHP >= 8.1
- CakePHP ^5.0
- friendsofcake/crud ^7.0
- friendsofcake/search ^7.0
- firebase/php-jwt ^7.0

## Installation

### Installation.composer

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```bash
composer require 3xw/cakephp-attachment
```

### Installation.load

In src/Application.php

```php
$this->addPlugin(\Trois\Attachment\Plugin::class, ['bootstrap' => true, 'routes' => true]);
```

Alternatively you can overload with your own settings (config/attachment.php):

```php
Configure::write('Trois/Attachment.config', ['attachment']);
$this->addPlugin(\Trois\Attachment\Plugin::class, ['bootstrap' => true, 'routes' => true]);
```

### Installation.db

```bash
bin/cake migrations migrate -p Trois/Attachment
```

A SQL file can be found at:

```bash
vendor/3xw/attachment/config/Schema/attachment.sql
```

### Installation.folders

Create a thumbnails folder with appropriate permissions:

```bash
mkdir webroot/thumbnails
chmod 777 webroot/thumbnails
```

If you store your files locally, create a folder according to default settings or your own:

```bash
mkdir webroot/files
chmod 777 webroot/files
```

## Backend Dependencies

### BackendDependencies.libs

In order to use backend tools you need to have following libs installed:

JavaScript:

```bash
jquery >= 1.x
vuejs = 2.x
vue-resource = 1.x
```

CSS:

```bash
bootstrap = 4.x
```

### BackendDependencies.html

Vue.js components are nested to a top parent you need to setup.
It requires one extra block (template). Following is easy to achieve.

In your layout.php:

```php
<head>
    ...
    <!-- CSS -->
    <?= $this->Html->css([
        'bootstrap.min.css',
        'app.css'
    ]) ?>
    <?= $this->fetch('css') ?>
    ...
</head>
<body>
    <div id="admin-app" class="wrapper">
        ...flash, content goes here...
    </div>

    <!-- TEMPLATES -->
    <?= $this->fetch('template') ?>

    <!-- SCRIPTS -->
    <?= $this->Html->script([
        'jquery.min.js',
        'vue.min.js',
        'vue-resource.min.js',
        'app.js'
    ]) ?>
    <?= $this->fetch('script') ?>

</body>
```

### BackendDependencies.js

In your app.js

```js
(function(scope, $, Vue){
    // bootstrap
    $(document).ready(function(){ var adminApp = new Vue({el: "#admin-app"}) })
})(window, jQuery, Vue)
```

## Settings

Default settings are present at: `vendor/3xw/attachment/config/attachment.php`

Feel free to write your own at: `config/attachment.php`

### Settings.profiles

You can set up your profiles according to [Flysystem](https://flysystem.thephpleague.com/) docs. Just add `baseUrl` to retrieve full URLs. Profiles are stored by name so you can split your files across several systems.

Attachment comes with these default profiles:

- `default` - Local file system stored in webroot/files
- `external` - Used for external URLs
- `thumbnails` - For thumbnail creation
- `img` - Local file system stored in webroot/img

#### Secure Download

Profiles support a `secureDownload` option for token-based file access via the DownloadController. This is useful for private files that should not have direct URL access:

```php
'profiles' => [
    'default' => [
        'client' => new League\Flysystem\Adapter\Local(WWW_ROOT.'files'),
        'baseUrl' => '/files/',
        'secureDownload' => false, // Direct URL download (default)
    ],
    'private' => [
        'client' => new League\Flysystem\Adapter\Local(ROOT.DS.'private_files'),
        'baseUrl' => null,
        'secureDownload' => true, // Token-based download via DownloadController
    ],
],
```

Following is the default adapter for local storage:

```php
'default' => [
    'client' => new League\Flysystem\Adapter\Local(WWW_ROOT.'files'),
    'baseUrl' => '/files/',
    'secureDownload' => false,
],
```

### Settings.upload

The upload is made before saving a related record. Global settings are setup under `Attachment.upload`. You can set global behaviors and then override them locally in add.php or edit.php:

```php
'upload' => [
    'dir' => false,
    'maxsize' => 30, // 30MB
    'maxquantity' => -1, // -1 = unlimited
    'minwidth' => 0, // Minimum image width in px, 0 = no constraint
    'types' => ['image/jpeg', 'image/png', 'embed/soundcloud', ...], // mime types and embed/:service
    'atags' => [], // Tags to associate with uploads
    'atagsDisplay' => false, // false | 'select' | 'input'
    'restrictions' => [], // See Restriction Constants below
    'cols' => 'col-12 col-md-6',
    'relation' => 'belongsToMany', // Model relation type
    'profile' => 'default', // Storage profile to use
    'visibility' => 'public', // public or private
    'speech' => false,
    'mandatory_tag' => false, // true = at least one tag required on upload
    'pagination' => [
        'offset' => 9, // = 10 pages
        'start' => true,
        'end' => true,
    ],
],
```

### Settings.browse

Configure the file browser UI:

```php
'browse' => [
    'show_sidebar' => true, // Set to false to hide tags sidebar
    'search' => [
        'dateField' => 'Attachments.created'
    ],
    'download' => [
        'filename' => [], // 'date' for date entered by user, 'created' for date added
        'multiple' => false,
        'client' => '',
        'url' => '',
        'token' => '',
    ],
    'filter_tags' => false, // true = only show tags that have files
    'only_used_tags' => true,
    'user_filter_tag_types' => [], // IDs of tag types to filter by user
    'show_private_links' => false,
    'types' => [
        'all' => [
            'label' => __('All'),
            'mime' => ['']
        ],
        'image' => [
            'label' => __('Images'),
            'mime' => ['image/*']
        ],
        'video' => [
            'label' => __('Videos'),
            'mime' => ['video/*', 'embed/youtube', 'embed/vimeo']
        ],
        'pdf' => [
            'label' => __('PDF'),
            'mime' => ['application/pdf']
        ],
        'other' => [
            'label' => __('Other'),
            'mime' => ['!image/*', '!video/*', '!embed/youtube', '!embed/vimeo', '!application/pdf']
        ]
    ],
    'filters' => [
        [
            'label' => __('Orientation'),
            'slug' => 'orientation',
            'options' => [
                ['label' => __('Vertical'), 'slug' => 'vertical'],
                ['label' => __('Horizontal'), 'slug' => 'horizontal'],
                ['label' => __('Square'), 'slug' => 'square']
            ]
        ]
    ]
],
```

### Settings.thumbnails

Configure thumbnail generation with Intervention Image:

```php
'thumbnails' => [
    'driver' => 'Imagick', // or 'GD' if Imagick not installed
    'compression' => [
        'jpegoptim' => '/usr/local/bin/jpegoptim', // Path or false
        'pngquant' => '/usr/local/bin/pngquant', // Path or false
        'convert' => '/usr/local/bin/convert', // ImageMagick convert for WebP
        'cwebp' => '/usr/local/bin/cwebp', // WebP encoder
        'quality' => 25 // Default encoding quality 0-100
    ],
    'breakpoints' => [
        'lg' => '(min-width: 1200px)',
        'md' => '(max-width: 1199px)',
        'sm' => '(max-width: 991px)',
        'xs' => '(max-width: 767px)',
    ],
    'widths' => [600, 1200], // Allowed widths
    'heights' => [], // Allowed heights
    'aligns' => [], // Allowed alignments [0-8]: 0=center, 1=top, 2=right, 3=bottom, 4=left, 5=top-right, 6=bottom-right, 7=bottom-left, 8=top-left
    'crops' => ['4:3', '16:9', '1:1'] // Allowed crop ratios
]
```

### Settings.listeners

Listeners are Event Handlers executed when their relative event is triggered. You can set handlers globally in the config file, or add the `listeners` key to settings arrays of any Attachment Helper function with CRUD ability:

```php
'listeners' => [
    'beforePaginate' => [
        'App\Listener\MyListener',
        'App\Listener\MyOtherListener' => [
            'key' => 'value'
        ]
    ],
],
```

Events triggered:

- `beforeFilter`, `startup`
- `beforeDelete`, `afterDelete`
- `beforeFind`, `afterFind`
- `beforeSave`, `afterSave`
- `beforePaginate`, `afterPaginate`
- `beforeRedirect`, `beforeRender`
- `recordNotFound`, `setFlash`

Listeners should extend the BaseListener class:

```php
namespace App\Listener;

use Trois\Attachment\Listener\BaseListener;
use Cake\Event\EventInterface;

class MyListener extends BaseListener
{
    public function respond(EventInterface $event)
    {
        // $event->getSubject() returns an object with minimum a request variable
        // All model events wrap: https://crud.readthedocs.io/en/latest/events.html
    }
}
```

### Settings.other

```php
// Enforce unique files by MD5 hash
'md5Unique' => true,

// Enable i18n for title/description fields
'translate' => false,
```

## Restriction Constants

Use these constants to filter and restrict attachments:

```php
use Trois\Attachment\View\Helper\AttachmentHelper;

AttachmentHelper::OPEN                    // No restrictions
AttachmentHelper::TAG_RESTRICTED          // Filter by tags with AND strategy
AttachmentHelper::TAG_OR_RESTRICTED       // Filter by tags with OR strategy
AttachmentHelper::TYPES_RESTRICTED        // Filter by mime types with OR strategy
AttachmentHelper::USER_RESTRICTED         // Filter by current user only
AttachmentHelper::USER_OR_NO_ONE_RESTRICTED // Filter by current user or unassigned
AttachmentHelper::PROFILE_RESTRICTED      // Filter by storage profile
```

## CLI Commands

### Profile Testing

Test a storage profile by uploading and deleting a file:

```bash
bin/cake at_profile <profile> <localPath> <path> <mime>
```

Example:

```bash
bin/cake at_profile default /tmp/test.jpg uploads/test.jpg image/jpeg
```

### Extract Image Dimensions

Retrieve and store width/height for all images:

```bash
bin/cake at_get_image_sizes
```

### Create Missing Translations

Create missing i18n translations for specified locales:

```bash
bin/cake at_create_missing_translations <locale1> <locale2> ...
```

Example:

```bash
bin/cake at_create_missing_translations en_GB de_CH fr_FR
```

## Controllers & Endpoints

### DownloadController (Secure Downloads)

For files with `secureDownload: true` or when you need controlled access:

**Get file token:**

```
POST /attachment/download/get-file-token
Body: { "file": "<attachment_id>" }
Response: { "token": "<jwt_token>" }
```

**Download file (forces download):**

```
GET /attachment/download/file?token={token}
```

**Stream file inline (for video/PDF preview):**

```
GET /attachment/download/stream?token={token}
```

**Get ZIP token for multiple files:**

```
POST /attachment/download/get-zip-token
Body: { "files": ["<id1>", "<id2>", ...] }
Response: { "token": "<jwt_token>" }
```

**Download ZIP archive:**

```
POST /attachment/download/files
Body: { "token": "<jwt_token>" }
```

### ResizeController (Thumbnails)

Thumbnails are generated on-demand via the `/thumbnails/*` route.

**URL Format:**

```
GET /thumbnails/{profile}/{params}/{path}
```

**Parameters:**

- `w{width}` - Width in pixels
- `h{height}` - Height in pixels
- `c{crop}` - Crop ratio (e.g., `c16-9` for 16:9)
- `a{align}` - Alignment (0-8)
- `q{quality}` - Quality 0-100

**Examples:**

```
/thumbnails/default/w600h400c16-9/uploads/image.jpg
/thumbnails/default/w1200q90/photos/landscape.jpg
/thumbnails/default/w600h600c1-1a0/avatars/user.png
```

**WebP Support:**

Request `.webp` extension and the controller will convert from the original format:

```
/thumbnails/default/w600/uploads/image.webp
```

Requires `cwebp` and `convert` (ImageMagick) to be configured.

### AttachmentsController

CRUD API using FriendsOfCake/Crud with JSON responses:

```
GET    /attachment/attachments.json       # List attachments
GET    /attachment/attachments/:id.json   # View attachment
POST   /attachment/attachments.json       # Create attachment
PUT    /attachment/attachments/:id.json   # Update attachment
DELETE /attachment/attachments/:id.json   # Delete attachment
```

## Behaviors

The AttachmentsTable uses these behaviors in sequence:

| Behavior | Description |
|----------|-------------|
| **UserIDBehavior** | Associates uploads with the authenticated user from session |
| **ExternalBehavior** | Handles external URL attachments (non-uploaded files) |
| **EmbedBehavior** | Processes embedded content from YouTube, Vimeo, Soundcloud |
| **AarchiveBehavior** | Manages ZIP archive operations (runs before FlyBehavior) |
| **FlyBehavior** | Core file upload/storage handling via Flysystem |
| **ATagBehavior** | Manages attachment tag associations |

## Usage

### Usage.model

Attachment is two tables: Attachments and Atags. You can bind any of your models with them; all relation types are supported:

```php
// In your Table class
$this->belongsToMany('Attachments', [
    'foreignKey' => 'post_id',
    'targetForeignKey' => 'attachment_id',
    'joinTable' => 'attachments_posts',
    'className' => 'Trois/Attachment.Attachments'
]);

// OR

$this->belongsTo('Attachments', [
    'foreignKey' => 'attachment_id',
    'joinType' => 'LEFT',
    'className' => 'Trois/Attachment.Attachments'
]);
```

Attachment handles an `order` field. Feel free to add such a field in your HABTM join tables.

### Usage.controller

Simply use contain or any join you need:

```php
public function index()
{
    $this->paginate = [
        'contain' => ['Attachments'] // Add 'sort' => 'order' if using order field
    ];
    $posts = $this->paginate($this->Posts);
    $this->set(compact('posts'));
}
```

### Usage.view

All functionality is in the AttachmentHelper. Add it to your AppView:

In src/View/AppView.php

```php
public function initialize(): void
{
    $this->loadHelper('Trois/Attachment.Attachment');
}
```

#### Usage.view.backend

**File picker input (add.php):**

```php
<?= $this->Attachment->input('Attachments', [ // 'Attachments' = HABTM, singular = belongsTo
    'label' => 'Image',
    'types' => ['image/jpeg', 'image/png'],
    'atags' => ['Restricted Tag 1', 'Restricted Tag 2'],
    'profile' => 's3',
    'cols' => 'col-xs-6 col-md-6 col-lg-4',
    'maxquantity' => -1,
    'restrictions' => [
        Trois\Attachment\View\Helper\AttachmentHelper::TAG_RESTRICTED,
        Trois\Attachment\View\Helper\AttachmentHelper::TYPES_RESTRICTED
    ],
    'attachments' => [] // Array of existing Attachment entities
]) ?>
```

**File picker input (edit.php):**

```php
<?= $this->Attachment->input('Attachments', [
    'label' => 'Image',
    'types' => ['image/jpeg', 'image/png'],
    'atags' => ['Restricted Tag 1', 'Restricted Tag 2'],
    'profile' => 's3',
    'cols' => 'col-xs-6 col-md-6 col-lg-4',
    'maxquantity' => -1,
    'restrictions' => [
        Trois\Attachment\View\Helper\AttachmentHelper::TAG_RESTRICTED,
        Trois\Attachment\View\Helper\AttachmentHelper::TYPES_RESTRICTED
    ],
    'attachments' => $post->attachments // Existing attachments
]) ?>
```

**Global attachments browser:**

```php
<?= $this->Attachment->index([
    'actions' => ['add', 'edit', 'delete', 'view', 'download'],
    'types' => ['image/jpeg', 'image/png', 'embed/youtube', 'embed/vimeo'],
    'atags' => ['Restricted Tag 1', 'Restricted Tag 2'],
    'listStyle' => true,
    'profile' => 's3',
    'restrictions' => [
        Trois\Attachment\View\Helper\AttachmentHelper::TAG_RESTRICTED,
        Trois\Attachment\View\Helper\AttachmentHelper::TYPES_RESTRICTED
    ]
]) ?>
```

#### Usage.view.frontend

**Display a responsive image with srcset and WebP:**

```php
<?= $this->Attachment->image([
    'image' => $post->attachments[0]->path,
    'profile' => $post->attachments[0]->profile,
    'width' => '600',
    'cropratio' => '16:9',
    'quality' => 50,
    'version' => $post->attachments[0]->modified->getTimestamp(), // Cache busting
    'srcset' => [
        'lg' => [360, 720],
        'md' => [293, 586],
        'sm' => [283, 566],
        'xs' => [767, 1534],
    ]
], ['class' => 'img-fluid']) ?>
```

This generates a `<picture>` element with WebP sources and fallback.

**Display an embed video:**

```php
<?= $post->attachments[0]->embed ?>
```

**Get thumbnail URL only:**

```php
<?= $this->Attachment->thumbSrc([
    'image' => $post->attachments[0]->path,
    'profile' => $post->attachments[0]->profile,
    'width' => '600',
    'cropratio' => '16:9',
    'quality' => 50,
    'version' => $post->attachments[0]->modified->getTimestamp() // Cache busting
]) ?>
```

**Get full path URL:**

```php
<?= $this->Attachment->fullPath($attachment) ?>
```

This handles `secureDownload` profiles by returning the appropriate URL.

### TinyMCE Plugin

Attachment comes with a TinyMCE plugin. Works with [cakephp-tinymce](https://github.com/3xw/cakephp-tinymce):

```php
echo $this->element('Trois/Tinymce.tinymce', [
    'field' => 'content',
    'value' => $post->content,
    'init' => [
        'external_plugins' => [
            'attachment' => $this->Url->build('/attachment/js/Plugins/tinymce/plugin.min.js', true),
        ],
        'attachment_settings' => $this->Attachment->jsSetup('content', [
            'types' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/jpeg',
                'image/png',
                'embed/youtube',
                'embed/vimeo'
            ],
            'thumbBaseUrl' => '', // If not $this->Url->build('/')
            'atags' => [],
            'restrictions' => [
                Trois\Attachment\View\Helper\AttachmentHelper::TAG_OR_RESTRICTED,
                Trois\Attachment\View\Helper\AttachmentHelper::TYPES_RESTRICTED
            ],
        ])
    ]
]);
```

## License

MIT License
