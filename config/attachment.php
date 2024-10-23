<?php

return [
  'Trois/Attachment' => [

    'listeners' => [],

    // set profiles
    'profiles' => [

      // packed profiles
      'default' => [
        'client' => new League\Flysystem\Local\LocalFilesystemAdapter(WWW_ROOT . 'files'),
        'baseUrl' => '/files/'
      ],
      'img' => [
        'client' => new League\Flysystem\Local\LocalFilesystemAdapter(WWW_ROOT . 'img'),
        'baseUrl' => '/img/'
      ],
      // 'external' => [
      // 	'client' => new Trois\Attachment\Filesystem\Adapter\External(),
      //   'baseUrl' =>  null,
      // ],
      'thumbnails' => [
        'client' => new League\Flysystem\Local\LocalFilesystemAdapter(WWW_ROOT . 'thumbnails'),
        'baseUrl' => '/thumbnails/',
        'thumbnails' => false
      ],
      'sys_temp' => [
        'client' => new League\Flysystem\Local\LocalFilesystemAdapter(sys_get_temp_dir()),
        'baseUrl' => null,
      ],
    ],

    'archives' => new Trois\Attachment\Filesystem\Compressor\ZipCompressor([
      'profile' => 'default'
    ]),

    'multiUpload' => [
      'enabled' => false,
    ],

    // unique
    'md5Unique' => true,

    // translate
    'translate' => false,

    'options' => [
      'visibility' => [
        [
          'visible' => false, // Caché ou pas
          'model' => 'Filters', // QUI est caché ??? Atags | AtagTypes | Filters
          'slug' => 'orientation', //SLUG | SLUGS ['']
          // searchManager params du model PHP
          'atags' => '*',
          'types' => '*',
          'filters' => '*'
        ],
        [
          'visible' => true,
          'model' => 'Filters',
          'slug' => 'orientation',
          'types' => ['image/*'],
        ]
      ]
    ],

    // upload settings
    'upload' => [
      'dir' => false,
      'maxsize' => 30, // 30MB,
      'maxquantity' => -1,
      'minwidth' => 0, // taille de l'image en px | 0 = pas de contrainte
      'types' => [],
      'atags' => [],
      'atagsDisplay' => false, // false | 'select' | 'input'
      'restrictions' => [], // or Trois\Attachment\View\Helper\AttachmentHelper::TAG_RESTRICTED
      'cols' => 'col-12 col-md-6',
      'relation' => 'belongsToMany',
      'profile' => 'default',
      'visibility' => 'public',
      'speech' => false,
      'mandatory_tag' => false, // true = au moins un tag à l'upload
      'pagination' => [
        'offset' => 9, // = 10 pages
        'start' => true,
        'end' => true,
      ],
    ],

    'browse' => [
      'search' => [
        'dateField' => 'Attachments.created'
      ],
      'download' => [
        'filename' => [ // 'date' for date entered by user, 'created' for date added to db or other params, can be cumulated
        ],
        'multiple' => false,
        'client' => '',
        'url' => '',
        'token' => '',
      ],
      'filter_tags' => false, // true = n'affiche que les tags qui ont des fichiers
      'types' => [
        'all' => [
          'label' => __('Tout'),
          'mime' => ['']
        ],
        'image' => [
          'label' => __('Images'),
          'mime' => ['image/*']
        ],
        'video' => [
          'label' => __('Vidéos'),
          'mime' => ['video/*', 'embed/youtube', 'embed/vimeo']
        ],
        'pdf' => [
          'label' => __('PDF'),
          'mime' => ['application/pdf']
        ],
        'other' => [
          'label' => __('Autres'),
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
            ['label' => __('Carré'), 'slug' => 'square']
          ]
        ]
      ]
    ],

    // thumbnails settings
    'thumbnails' => [
      'driver' => 'Imagick', // or Imagick if installed,
      'compression' => [
        'jpegoptim' => '/usr/local/bin/jpegoptim',
        'pngquant' => '/usr/local/bin/pngquant',
        'convert' => '/usr/local/bin/convert',
        'cwebp' => '/usr/local/bin/cwebp',
        'quality' => 25
      ],
      'breakpoints' => [
        'lg' => '(min-width: 1200px)',
        'md' => '(max-width: 1199px)',
        'sm' => '(max-width: 991px)',
        'xs' => '(max-width: 767px)',
      ],
      'widths' => [],
      'heights' => [],
      'aligns' => [], // or some of following [0,1,2,3,4,5,6,7,8] with 0 center, 1 top, 4 left, 5 right top corner, 8 left top corner ....
      'crops' => []
    ]
  ]
];
