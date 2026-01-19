// CSS
import './assets/scss/theme.scss'

// COMPONENTS
import Browse from './components/Browse.vue'

// UTILS
import { registerDirectives } from './utils/directives'
import { registerFilters } from './utils/filters'
import './utils/getCsrfToken'
import './utils/utils'

// WORK
const
components = [Browse],
install = function(Vue, { store })
{
  // register directives
  registerDirectives(Vue)
  // register filters
  registerFilters(Vue)
  // add components
  components.forEach(component => Vue.component(component.name, component))
}

// EXPORT
export default
{
  version: '4.0.0',
  install,
}
