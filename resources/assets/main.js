import ElementUI from 'element-ui'
import AttachmentLoader from '@/components/AttachmentLoader.vue'
import Browse from '@/components/Browse.vue'
import VuePackeryPlugin from 'vue-packery-plugin'

import locale from 'element-ui/lib/locale/lang/fr'

// utils
import '@/utils/directives'
import '@/utils/filters'
import '@/utils/getCsrfToken'
import '@/utils/utils'

// plugins
import '@/plugins/tinymce.js'

// use
Vue.use(VuePackeryPlugin)

// components
Vue.component(AttachmentLoader.name, AttachmentLoader)
Vue.component(Browse.name, Browse)
