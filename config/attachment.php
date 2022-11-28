<?php

return [
  'Trois/Attachment' => [

    'listeners' => [],

    // set profiles
    'profiles' => [

      // packed profiles
      'default' => [
    		'client' => new League\Flysystem\Adapter\Local(WWW_ROOT.'files'),
        'baseUrl' =>  '/files/'
    	],
      'img' => [
    		'client' => new League\Flysystem\Adapter\Local(WWW_ROOT.'img'),
        'baseUrl' =>  '/img/'
    	],
      'external' => [
    		'client' => new Trois\Attachment\Filesystem\Adapter\External(),
        'baseUrl' =>  null,
    	],
      'thumbnails' => [
    		'client' => new League\Flysystem\Adapter\Local(WWW_ROOT.'thumbnails'),
        'baseUrl' =>  '/thumbnails/',
        'thumbnails' => false
    	],
      'sys_temp' => [
    		'client' => new League\Flysystem\Adapter\Local(sys_get_temp_dir()),
        'baseUrl' =>  null,
    	],
    ],

    'archives' => new Trois\Attachment\Filesystem\Compressor\ZipCompressor([
      'profile' => 'default'
    ]),

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
      'types' =>[],
      'atags' => [],
      'atagsDisplay' => false, // false | 'select' | 'input'
      'restrictions' => [], // or Trois\Attachment\View\Helper\AttachmentHelper::TAG_RESTRICTED
      'cols' => 'col-12 col-md-6',
      'relation' => 'belongsToMany',
      'profile' => 'default',
      'visibility' => 'public',
      'speech' => false,
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
]];
