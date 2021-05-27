import createCrudModule from 'vuex-crud';

import { client, parseResponse, parseResponseWithPaginate, parseTags} from '../http/client.js'
import module from '../store/module.js'

export default
{
  props:
  {
    aid: String,
    settings: Object
  },
  data: () => ({
    mode: 'browse',
    overlay: false,
    loading: false,
  }),
  created()
  {
    //Remove Char who breaks store
    this.aid = this.aid.replace(/\./g, '')

    // create new module and store settings
    this.$store.registerModule(this.aid, Object.assign({}, module))
    this.$store.registerModule(this.aid+'/attachments', createCrudModule({
      resource: 'attachments',
      urlRoot: this.settings.url+'attachment/attachments',
      client,
      parseSingle: parseResponse,
      parseList: parseResponseWithPaginate,
      onFetchListStart: () => {
        this.loading = true
      },
      onFetchListSuccess: (o, response) => {
        this.loading = false
        this.$store.set(this.aid + '/pagination', response.pagination)
      },
    }))
    this.$store.registerModule(this.aid+'/atags', createCrudModule({
      resource: 'atags',
      urlRoot: this.settings.url+'attachment/atags',
      client,
      parseSingle: parseResponse,
      parseList: parseTags
    }))
    this.$store.registerModule(this.aid+'/token', createCrudModule({
      resource: 'token',
      only: ['CREATE'],
      urlRoot: this.settings.url+'attachment/download/get-zip-token',
      client,
      idAttribute: 'token',
      onCreateSuccess: (o, response) => {
        this.$store.set(this.aid + '/selection.token', response.data.token)
      },
    }))
    this.$store.registerModule(this.aid+'/size', createCrudModule({
      resource: 'size',
      urlRoot: this.settings.url+'attachment/attachments/get-size',
      client,
      parseList: parseResponse,
      idAttribute: 'uuid',
    }))
    this.$store.registerModule(this.aid+'/aarchives', createCrudModule({
      resource: 'aarchives',
      only: ['CREATE'],
      urlRoot: this.settings.url+'attachment/aarchives',
      client,
      parseSingle: parseResponse,
    }))

    // init
    this.overlay = this.settings.overlay
    this.mode = this.settings.mode
    this.$store.set(this.aid + '/settings', this.settings)
    if(this.mode == 'input' && this.settings.attachments.length > 0) this.$store.set(this.aid + '/selection.files', this.settings.attachments)

    // CRUD
    client.baseURL = this.settings.url

  },
  beforeDestroy()
  {
    this.$store.unregisterModule(this.aid)
    this.$store.unregisterModule(this.aid+'/attachments')
    this.$store.unregisterModule(this.aid+'/atags')
    this.$store.unregisterModule(this.aid+'/token')
    this.$store.unregisterModule(this.aid+'/aarchives')
  }
}
