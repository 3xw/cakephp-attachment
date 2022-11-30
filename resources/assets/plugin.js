// CSS
import './assets/scss/theme.scss'

// COMPONENTS
import Browse from './components/Browse.vue'

// UTILS
import './utils/directives'
import './utils/filters'
import './utils/getCsrfToken'
import './utils/utils'

// WORK
const
components = [Browse],
install = function(Vue, { store })
{
  // add components
  components.forEach(component => Vue.component(component.name, component))
}

// EXPORT
export default
{
  version: '4.0.0',
  install,
}
