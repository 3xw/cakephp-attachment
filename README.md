# Attachment plugin for CakePHP ^3.7
Attachment plugin solves common problems with media, files and embed data.
The goal is to store files where you want ( Dropbox, AWS S3, ... ) and keep a record of it in a table.

Attachment offers both storage layer and database layer as well as frontend and backend solutions for common needs.

It uses [CakePHP 3](https://cakephp.org/), [Flysystem](https://flysystem.thephpleague.com/) and [Intervention Image](http://image.intervention.io/)

## Installation

### Installation.composer

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```bash
composer require 3xw/attachment
```

### Installation.load
In src/Application.php

```php
$this->addPlugin(\ Attachment\Plugin::class, ['bootstrap' => true, 'routes' => true]);
```

Alternatively you can overload with your own settings (config/attachment.php):

```php
Configure::write('Attachment.config', ['attachment']);
$this->addPlugin(\ Attachment\Plugin::class, ['bootstrap' => true, 'routes' => true]);
```

### Installation.db

```bash
bin/cake Migrations migrate -p Attachment
```

a sql file can found at path:

```bash
vendor/3xw/attachment/config/Schema/attachment.sql
```

### Installation.folders
Create a thumbnails folder with appropriate chmod to enable php to write in it...

```bash
mkdir webroot/thumbnails
chmod 777 webroot/thumbnails
```


If you store your files locally, then create a folder according to default settings or your own. For default set as follow:

```bash
mkdir webroot/files
chmod 777 webroot/files
```

## BackendDependencies
### BackendDependencies.libs
In order to use backend tools you need to have following libs installed:

javascript:

```bash
jquery >= 1.x
vuejs = 2.x
vue-resource = 1.x
```

css:

```bash
bootstrap = 4.x
```

### BackendDependencies.html
Vuejs components are nested to a top parent you need to setup.
It requires one extra block (template). Following is easy to achieve.

in your layout.ctp:

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
	    'jquery.min.js'
	    'vue.min.js',
	    'vue-resource.min.js',
	    'app.js'
	 ]) ?>
	<?= $this->fetch('script') ?>

</body>
```

### BackendDependencies.js
in your app.js

```js
(function(scope, $, Vue){

	// boostrap
	$(document).ready(function(){ var adminApp = new Vue({el: "#admin-app"}) })


})(window, jQuery, Vue)
```

## Settings
Default settings are present at following path: vendor/3xw/attachment/config/attachment.php

feel free to write your own at following path: config/attachment.php

Exemple of settings:

```php
return [
  'Attachment' => [

    // set profiles
    'profiles' => [
      's3' => [
		'replace' => false,
		'afterReplace' => null // null | callback fct($entity),
		'delete' => true,
		'adapter' => 'League\Flysystem\AwsS3v3\AwsS3Adapter',
		'client' => new League\Flysystem\AwsS3v3\AwsS3Adapter(Aws\S3\S3Client::factory([
			'credentials' => [
				'key'    => '***',
				'secret' => '***',
			],
			'region' => 'eu-central-1',
			'version' => 'latest',
		]),'s3.example.com',''),
		'baseUrl' =>  's3.example.com'
      ],
    ],

    // lsiteners
    lsiteners => [],

    // upload settings
    'upload' => [
      'maxsize' => 30, // 30MB
      'types' =>['image/jpeg','image/png','image/gif'],
      'atags' => [],
      'atagsDisplay' => false, // false | 'select' | 'input'
      'profile' => 's3',

      // pagination setting in browse views
      'pagination' => [
        'offset' => 9, // = 10 pages
        'start' => true,
        'end' => true,
      ],
    ],

    // thumbnails settings
    'thumbnails' => [
      'driver' => 'Imagick', // or Imagick if installed,
      'compression' => [
     		'jpegoptim' => '/usr/local/bin/jpegoptim', // path or false ( default /usr/local/bin/jpegoptim )
    		'pngquant' => '/usr/local/bin/pngquant', // path or false ( default /usr/local/bin/pngquant )
    		'quality' => 25 // encoding quality level from 0 to 100 ( default 25 )
  		],
  		'breakpoints' => [
	        'lg' => '(min-width: 1200px)',
	        'md' => '(max-width: 1199px)',
	        'sm' => '(max-width: 991px)',
	        'xs' => '(max-width: 767px)',
      ],
      'widths' => ['678','1200'],
      'heights' => false,
      'aligns' => false, // or some of following [0,1,2,3,4,5,6,7,8] with 0 center, 1 top, 4 left, 5 right top corner, 8 left top corner ....
      'crops' => ['16:9','4:3','1:1']
    ]
]];
```

### Settings.profiles
You can set up your profiles according to [Flysystem](https://flysystem.thephpleague.com/) doc just add baseUrl in order to retrieve full urls. Profiles are stored by name. So you can split your file in sevral systems.

Attachment comes prepact with three default settings:


	default // Local file system stored in webroot/files
	external // used for external urls
	cache // for thumbs creations


following is the default adptater for local storage:

```php
'default' => [
	'adapter' => 'League\Flysystem\Adapter\Local',
	'client' => new League\Flysystem\Adapter\Local('files'),
	'baseUrl' =>  '/files/'
],
```

So you can use your own or install new one with composer.

### Settings.upload
The upload is made before saving a realted records. global settings are setup under Attachment.upload. You can set global behaviors and then override them local in add.ctp or edit.ctp. Sevral options are avaliable here:

```php
'upload' => [
  'maxsize' => 30, // 30MB
  'types' =>['image/jpeg','embed/soundcloud',...], // mime types and embed/:service for embed stuff
  'atags' => [], // atags are use to store attachemnts with
  'relation' => 'belongsToMany', // model relation
  'profile' => 'default', // profile to use (where you store files)
  'visibility' => 'public', // public or private
  'speech' => false, // french goody
  'restrictions' => [] // or Attachment\View\Helper\AttachmentHelper::TAG_RESTRICTED
],
```

Restrictions are behaviors used in backend to sort files.

```php
AttachmentHelper::TAG_RESTRICTED // enforce attachments to associted with given tags in save and retieve with a AND strategy
AttachmentHelper::TAG_OR_RESTRICTED // enforce attachments to associted with given tags in save and retieve with a OR strategy
AttachmentHelper::types_restricted // enforce attachments to saved and retrieve with a OR strategy according given mime types
```

### Settings.listeners
Listeners are Events Handlers that are executed once their relative event is triggered. You can set handlers for a genral purpose in the attachment config file, or you can add the 'lsiteners' key to setting arrays of any Attachment Helper functions with CRUD ability.

```php
'listeners' => [
  'beforePaginate' => [
    'App\Listener\MyListener',
    'App\Listener\MayOtherListener' => [
      'momo' => 'toto'
    ]
  ],
]
```
Events triggered list is:

```bash
beforeFilter
startup
beforeDelete
afterDelete
beforeFind
afterFind
beforeSave
afterSave
beforePaginate
afterPaginate
beforeRedirect
beforeRender
recordNotFound
setFlash
```

Listners should extend the BaseListener class:

```php
namespace App\Listener;

use Attachment\Listener\BaseListener;
use Cake\Event\Event;

class ExtranetMoveFileListener extends BaseListener
{
  // $event->getSubject() returns an object with minimum a request variable
  // all model events are wrapped on top of:
  // https://crud.readthedocs.io/en/latest/events.html#crud-beforesave
  public function respond(Event $event)  
  {

  }
}
```

### Settings.thumbnails
Attachment.thumbnails is the settings for thumbs generation.

```php
'thumbnails' => [
  'driver' => 'Imagick', // or Imagick if installed,
  'widths' => [600, 1200],
  'heights' => [],
  'aligns' => [], // or some of following [0,1,2,3,4,5,6,7,8] with 0 center, 1 top, 4 left, 5 right top corner, 8 left top corner ....
  'crops' => ['4:3','16:9']
]
```

This settings are global and restirct local changes in order to keep logic of thumb in one file and limit extra formats.
each table are possibility you allow. So only 600px and 1200px thumbs are allowed. Only crop of 4:3 and 16:9 are allowed.

## Usage
### Usage.model
Attachment is two tables: Attachments and Atags. So you can bind any of your models with, all relations types are supported.

```bash
$this->belongsToMany('Attachments', [
  'foreignKey' => 'post_id',
  'targetForeignKey' => 'attachment_id',
  'joinTable' => 'attachments_posts'
]);

// OR

$this->belongsTo('Attachments', [
  'foreignKey' => 'attachment_id',
  'joinType' => 'INNER' // OR LEFT ...
]);
```

Attachment handles an 'order' field as well. So feel free to add such a field in your HABTM join tables...

### Usage.controller
Simply use contain or any join you need...

```php
public function index()
{
	$this->paginate = [
	  'contain' => ['Attachments' /* => ['sort' => 'order'] */ ] // if HABTM with an order field
	];
	$posts = $this->paginate($this->Posts);
	$this->set(compact('posts'));
	$this->set('_serialize', ['posts']);
}
```

### Usage.view
All skills are in the Helper Attachment. So first of All add it into your AppView.

in src/View/AppView.php

```php
public function initialize()
{
	$this->loadHelper('Attachment.Attachment');
}
```

#### Usage.view.backend
In add.ctp

```php
<!-- Attachment -->
<?= $this->Attachment->input('Attachments', // if Attachments => HABTM else if !Attachments => belongsTo
  	[
	  	'label' => 'Image',
	  	'types' =>['image/jpeg','image/png'],
	  	'atags' => ['Restricted Tag 1', 'Restricted Tag 2'],
	  	'profile' => 's3', // optional as it was set in config/attachment.php
	  	'cols' => 'col-xs-6 col-md-6 col-lg-4', // optional as it was set in config/attachment.php,
	  	'maxquantity' => -1,
	  	'restrictions' => [
	    	Attachment\View\Helper\AttachmentHelper::TAG_RESTRICTED,
	    	Attachment\View\Helper\AttachmentHelper::TYPES_RESTRICTED
	  	],
	  	'attachments' => [] // array of exisiting Attachment entities ( HABTM ) or [entity] ( belongsTo )
	]
) ?>
```

In edit.ctp

```php
<!-- Attachment -->
<?= $this->Attachment->input('Attachments', // if Attachments => HABTM else if !Attachments => belongsTo
  	[
	  	'label' => 'Image',
	  	'types' =>['image/jpeg','image/png'],
	  	'atags' => ['Restricted Tag 1', 'Restricted Tag 2'],
	  	'profile' => 's3', // optional as it was set in config/attachment.php
	  	'cols' => 'col-xs-6 col-md-6 col-lg-4', // optional as it was set in config/attachment.php,
	  	'maxquantity' => -1,
	  	'restrictions' => [
	    	Attachment\View\Helper\AttachmentHelper::TAG_RESTRICTED,
	    	Attachment\View\Helper\AttachmentHelper::TYPES_RESTRICTED
	  	],
	  	'attachments' => $posts->attachments // array of exisiting Attachment entities ( HABTM ) or entity ( belongsTo )
	]
) ?>
```

##### Global Attachments index :

```php
<!-- Attachments element -->
<?= $this->Attachment->buildIndex([
  'actions' => ['add','edit','delete','view','download'],
  'types' =>['image/jpeg','image/png','embed/youtube','embed/vimeo'],
  'atags' => ['Restricted Tag 1', 'Restricted Tag 2'],
		'listStyle' => true,
		'profile' => 's3', // optional as it was set in config/attachment.php
  'restrictions' => [
    Attachment\View\Helper\AttachmentHelper::TAG_RESTRICTED,
    Attachment\View\Helper\AttachmentHelper::TYPES_RESTRICTED
  ]
]) ?>
```

##### TinyMCE Plugin
Attachment comes with a TinyMCE plugin. Working with package [cakephp-tinymce](https://github.com/3xw/cakephp-tinymce)

```php
echo $this->element('Trois/Tinymce.tinymce',[
  'field' => 'content',
  'value' => $post->content,
  'init' => [
    'external_plugins' => [
      'attachment' => $this->Url->build('/attachment/js/Plugins/tinymce/plugin.min.js', true),
    ],
    'attachment_settings' => $this->Attachment->jsSetup('content',[

      // overrides config/attachment.php settings
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
			'thumbBaseUrl' => '', //IF NOT $this->Url->build('/')
      'atags' => [],
      'restrictions' => [
        Attachment\View\Helper\AttachmentHelper::TAG_OR_RESTRICTED,
        Attachment\View\Helper\AttachmentHelper::TYPES_RESTRICTED
      ],
    ])
  ]
]);
```

This will let you insert image right into trumbowyg textarea !!! heepee!

Exemple in locale.ctp:

```php
$this->element('locale',['fields' => ['meta',
	'header' => [
		'Trois/Tinymce.tinymce' => [
			'value' => $post,
			'init' => []
		]
	],
	'body' => [
		'Trois/Tinymce.tinymce' => [
			'value' => $post,
			'init' => [
				'external_plugins' => [
					'attachment' => $this->Url->build('/attachment/js/Plugins/tinymce/plugin.min.js', true),
				],
				'attachment_settings' => [
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
					'atags' => [],
					'restrictions' => [
						Attachment\View\Helper\AttachmentHelper::TAG_OR_RESTRICTED,
						Attachment\View\Helper\AttachmentHelper::TYPES_RESTRICTED
					],
				]
			]
		]
	]
], 'labels' => ['meta (description google, facebook)', 'lead', 'text']]);
```

#### Usage.view.frontend
in file

```php
<!-- Display a 16:9 croped image  -->
<?= $this->Attachment->image([
	'image' => $post->attachments[0]->path,
	'profile' => $post->attachments[0]->profile,
	'width' => '600',
	'cropratio' => '16:9,
	'quality' => 50, // from 0 to 100 ( default 25 in plugin's config file attachment.php )
	'srcset' => [
      'lg' => [360,720],
      'md' => [293, 586],
      'sm' => [283, 566],
      'xs' => [767,1534],
    ]
],['class' => 'img-responsive']) ?>

<!-- Display an embed video  -->
<?= $post->attachments[0]->embed ?>
```

Url Only

```php
<?= $this->Attachment->thumbSrc([
	'image' => $post->attachments[0]->path,
	'profile' => $post->attachments[0]->profile,
	'width' => '600',
	'cropratio' => '16:9,
	'quality' => 50, // from 0 to 100 ( default 25 in plugin's config file attachment.php )
	'srcset' => [
      'lg' => [360,720],
      'md' => [293, 586],
      'sm' => [283, 566],
      'xs' => [767,1534],
    ]
]) ?>
```


#### Usage.view.download

```php
echo $this->Attachment->downloadLink($attachment ); // Attachment $attachment
// return the full download url for THIS SESSION ONLY
```

#### Usage.shell
1) Attachment plugin provides a usefull shell script to retrieve width and height of images

```bash
bin/cake Attachment.GetImageSizes
```

2) CReate missing attachment transaltions for loacle:

```bash
bin/cake CreateMissingTranslations en_GB de_CH ...
```
