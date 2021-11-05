import Browse from '../components/Browse.vue'

export default class TinyPlugin
{
  #editor = null
  #store = null
  #settings = null

  aid = null

  constructor(editor, store)
  {
    this.editor = editor
    this.store = store

    this.settings = this.editor.settings.attachment_settings
    this.aid = this.getName()

    this.init()
    this.addEventListener()
    this.addButton()
  }

  getName()
  {
    let sections = this.settings.field.split('.'), name = ''
    for(let i = 0;i < sections.length;i++) name += i > 0? '['+sections[i]+']': sections[i]
    return name
  }

  init()
  {
    let BrowseCompoClass = Vue.component(Browse.name)

    //FORCE OVERLAY
    this.settings.overlay = 1
    this.settings.groupActions = ['tinymce']
    this.settings.actions = ['add']
    this.editor.attachment = new BrowseCompoClass({store: this.store, propsData: {settings: this.settings, aid: this.aid}})
    this.editor.attachment.mode = 'hidden'
    this.editor.attachment.tinymce = true
    this.editor.attachment.$mount()

    // classy way : )
    window.vueTinymce[this.editor.settings.id].appendPlugin(this.editor.attachment)
  }

  addEventListener()
  {
    //this.editor.attachment.$on('options-success', this.addAttachment)

    this.editor.attachment.$on('options-success', (file, options) => {
      if(options.displayAs == 'Link') this.editor.insertContent('<a href="'+this.settings.baseUrls[file.profile].profile+file.path+'" target="'+options.target+'">'+options.title+'</a>')
      else this.editor.insertContent(this.createImageNode(file, options))
    })
  }

  createImageNode(file, options)
  {
    let html = '<img'
    let classes = 'img-responsive img-fluid '
    classes += (options.classes)? options.classes+' ': ''
    classes += (options.align)? options.align+' ': ''
    html += ' class=\'' + classes + '\''
    if(options.alt) html += ' alt=\'' + options.alt.replace(/['"]+/g, '') + '\''
    html += ' src=\'' + this.getImagePath(file, options) + '\''
    html += ' />'
    return html
  }

  getImagePath(file, options)
  {
    let path = this.editor.attachment.settings.baseUrls.thumbnails.profile+file.profile+'/'
    path += (options.width)? 'w'+options.width: 'w1200'
    path += (options.crop)? 'c'+options.cropWidth+'-'+options.cropHeight: '';
    path += '/'+file.path;
    return path;
  }

  addButton()
  {
    let this2 = this
    this.editor.ui.registry
    .addMenuButton('attachment', {
      text: 'Média',
      icon: 'image',
      fetch: function (callback)
      {
        let items = [
          {
            type: 'menuitem',
            text: 'Télécharger',
            onAction: () => this2.upload()
          },
          {
            type: 'menuitem',
            text: 'Parcourir',
            onAction: () => this2.browse()
          },
        ];
        callback(items);
      }
    })
  }

  browse()
  {
    this.editor.attachment.overlay = true
    this.editor.attachment.mode = 'browse'
  }

  upload()
  {
    console.log('upload');
    this.editor.attachment.overlay = true
    this.editor.attachment.mode = 'upload'
  }
}
