/*
* copy of : https://github.com/JiriChara/vuex-crud
*/

import defaultClient from './client';
import createActions from './createActions';
import createGetters from './createGetters';
import createMutations from './createMutations';
import createState from './createState';

const createCrud = ({
  idAttribute = 'id',
  resource,
  urlRoot,
  customUrlFn = null,
  state = {},
  actions = {},
  mutations = {},
  getters = {},
  client = defaultClient,
  onFetchListStart = () => {},
  onFetchListSuccess = () => {},
  onFetchListError = () => {},
  onFetchSingleStart = () => {},
  onFetchSingleSuccess = () => {},
  onFetchSingleError = () => {},
  onCreateStart = () => {},
  onCreateSuccess = () => {},
  onCreateError = () => {},
  onUpdateStart = () => {},
  onUpdateSuccess = () => {},
  onUpdateError = () => {},
  onReplaceStart = () => {},
  onReplaceSuccess = () => {},
  onReplaceError = () => {},
  onDestroyStart = () => {},
  onDestroySuccess = () => {},
  onDestroyError = () => {},
  only = ['FETCH_LIST', 'FETCH_SINGLE', 'CREATE', 'UPDATE', 'REPLACE', 'DESTROY'],
  parseList = res => res,
  parseSingle = res => res,
  parseError = res => res
} = {}) => {
  if (!resource) {
    throw new Error('Resource name must be specified');
  }

  let rootUrl;

  /**
   * Create root url for API requests. By default it is: /api/<resource>.
   * Use custom url getter if given.
   */
  if (typeof customUrlFn === 'function') {
    rootUrl = customUrlFn;
  } else if (typeof urlRoot === 'string') {
    rootUrl = ((url) => {
      const lastCharacter = url.substr(-1);

      return lastCharacter === '/' ? url.slice(0, -1) : url;
    })(urlRoot);
  } else {
    rootUrl = `/api/${resource}`;
  }

  return {
    namespaced: true,

    state: createState({ state, only }),

    actions: createActions({
      actions,
      rootUrl,
      only,
      client,
      parseList,
      parseSingle,
      parseError
    }),

    mutations: createMutations({
      mutations,
      idAttribute,
      only,
      onFetchListStart,
      onFetchListSuccess,
      onFetchListError,
      onFetchSingleStart,
      onFetchSingleSuccess,
      onFetchSingleError,
      onCreateStart,
      onCreateSuccess,
      onCreateError,
      onUpdateStart,
      onUpdateSuccess,
      onUpdateError,
      onReplaceStart,
      onReplaceSuccess,
      onReplaceError,
      onDestroyStart,
      onDestroySuccess,
      onDestroyError
    }),

    getters: createGetters({ getters })
  };
};

export default createCrud;

/**
 * Export client in case user want's to add interceptors.
 */
export { defaultClient as client };
