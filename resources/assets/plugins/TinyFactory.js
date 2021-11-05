import TinyPlugin from './TinyPlugin.js'

export default class TinyFactory
{
  static store = null
  static pluginManager = null

  static init(store)
  {
    if(!window.tinymce) return console.error('TinyMCE - Attachment: No tinymce found!');
    if(!window.vueTinymce) return console.error('TinyMCE - Attachment: No VUE tinymce found!');

    this.store = store
    this.pluginManager = window.tinymce.util.Tools.resolve('tinymce.PluginManager')

    //this.pluginManager.add('attachment', this.createPlugin)
    this.pluginManager.add('attachment', function(editor, url){
      new TinyPlugin(editor, TinyFactory.store)
    })
  }
}
