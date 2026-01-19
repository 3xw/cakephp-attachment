/* DIRECTIVES
*******************************/
export function registerDirectives(Vue) {
  Vue.directive('sortable', function (el, binding) {
    var options = binding.value || {}
    window.Sortable.create(el, options);
  });
}
