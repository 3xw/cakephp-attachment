// vue components & store
import Browse from '@/components/Browse.vue'
import store from '@host-assets/admin/store/store.js'

// Plugin TinyMCE
if(typeof tinymce !== 'undefined'){
  const
  global = tinymce.util.Tools.resolve('tinymce.PluginManager'),
  Plugin =
  {
    createVueEl: function(editor)
    {
      let conf = editor.settings.attachment_settings,
      aid = conf.field,
      BrowseCompoClass = Vue.component('browse', Browse)

      //FORCE OVERLAY
      conf.overlay = 1
      conf.groupActions = ['tinymce']
      conf.actions = ['add']

      editor.attachment = new BrowseCompoClass({store, propsData: {settings: conf, aid: aid}})
      editor.attachment.mode = 'hidden'
      editor.attachment.tinymce = true
      editor.attachment.$mount()
      window.vueTinymce[aid].$el.appendChild(editor.attachment.$el)
    },
    addEventListener: function(editor)
    {
      editor.attachment.$on('options-success', function(options, file){ Plugin.addAttachment(editor, file, options) })
    },
    addAttachment: function(editor, file, options)
    {
      let conf = editor.settings.attachment_settings,
      aid = conf.field

      if(options.displayAs == 'Link') editor.insertContent('<a href="'+file.fullpath+'" target="'+options.target+'">'+options.title+'</a>')
      else editor.insertContent(Plugin.createImageNode(editor, conf, file, options))
    },
    createImageNode: function(editor, conf, file, options)
    {
      let html = '<img'
      let classes = 'img-responsive img-fluid '
      classes += (options.classes)? options.classes+' ': ''
      classes += (options.align)? options.align+' ': ''
      html += ' class=\'' + classes + '\''
      if(options.alt) html += ' alt=\'' + options.alt.replace(/['"]+/g, '') + '\''
      html += ' src=\'' + Plugin.getImagePath(editor, conf, file, options) + '\''
      html += ' />'
      return html
    },
    getImagePath: function(editor, conf, file, options)
    {
      let path = editor.attachment.settings.baseUrls.thumbnails.profile+file.profile+'/'
      path += (options.width)? 'w'+options.width: 'w1200'
      path += (options.crop)? 'c'+options.cropWidth+'-'+options.cropHeight: '';
      path += '/'+file.path;
      return path;
    },
    addButton: function(editor)
    {

      let conf = editor.settings.attachment_settings,
      aid = conf.field,
      storeAid = conf.field.replace(/\./g, '')

      editor.ui.registry.addMenuButton('attachment', {
        text: 'Média',
        icon: 'image',
        fetch: function (callback) {
          let items = [
            {
              type: 'menuitem',
              text: 'Télécharger',
              onAction: function (_) {
                editor.attachment.overlay = true
                editor.attachment.mode = 'upload'
              }
            },
            {
              type: 'menuitem',
              text: 'Parcourir',
              onAction: function (_) {
                editor.attachment.overlay = true
                editor.attachment.mode = 'browse'
              }
            },

          ];
          callback(items);
        }
      });
    },
  }

  // init
  global.add('attachment', function (editor)
  {
    Plugin.createVueEl(editor)
    Plugin.addEventListener(editor)
    Plugin.addButton(editor)
  })

}
