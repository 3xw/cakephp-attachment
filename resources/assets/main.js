import ElementUI from 'element-ui'
import AttachmentLoader from '@/components/AttachmentLoader.vue'
import VuePackeryPlugin from 'vue-packery-plugin'

import locale from 'element-ui/lib/locale/lang/fr'

// utils
import '@/utils/directives'
import '@/utils/filters'
import '@/utils/utils'

// plugins
import '@/plugins/tinymce.js'

// init
Vue.component('attachment-loader', AttachmentLoader)
Vue.use(VuePackeryPlugin)
Vue.use(ElementUI, { locale })
