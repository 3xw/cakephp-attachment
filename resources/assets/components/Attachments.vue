<template>
  <section class="section-attachment--index">
    <!-- uploaded files -->
    <div v-if="aParams.date && aParams.date.split(',').length === 1" class="alert alert-info">
      Tous les fichiers ont bien été téléchargés!
    </div>
    <div class="section__header">
      <attachment-search-bar :aid="aid"></attachment-search-bar>
      <div class="utils--spacer-semi"></div>
      <div class="d-flex flex-row justify-content-between align-items-center">
        <ul class="list-unstyled">
          <li>
            <ul class="list-unstyled list-inline"><!-- v-if="types.isActive" -->
              <li class="text--upper list-inline-item pointer" v-for="(option, i2) in settings.browse.types" :key="i2"
                @click="types.current = option.mime.join(',');$forceUpdate();filterType();"
                :class="{active: types.current == option.mime.join(',')}">
                <strong>{{ option.label }}</strong>
              </li>
            </ul>
          </li>
        </ul>
        <div class="d-flex flex-row align-items-center">
          {{selectedFiles.length}} fichier(s) selectionné(s)&nbsp;&nbsp;
          <div class="btn-group">

            <!-- FORM DOWNLOAD -->
            <form ref="dform" :action="$store.get(this.aid + '/settings.url')+'attachment/download/files'"
              method="POST">
              <input type="hidden" name="token" v-model="downloadToken">
            </form>

            <!-- SELECT -->
            <button v-if="selectedFiles.length > 0 && settings.groupActions.indexOf('select') != -1"
              @click="confirmSelection" type="button" name="button" class="btn btn--blue mb-0 color--white">
              CHOISIR
            </button>

            <!-- ARCHIVE -->
            <button v-if="selectedFiles.length > 1 && settings.browse.download.multiple && !downloading"
              @click="multiDownload" type="button" name="button" class="btn btn--blue mb-0 color--white">
              TÉLÉCHARGER
            </button>

            <button v-if="downloading" disabled class="btn btn--blue mb-0 color--white">
              TÉLÉCHARGEMENT EN COURS
            </button>

            <!-- EDIT -->
            <button v-if="selectedFiles.length > 0 && settings.groupActions.indexOf('edit') != -1"
              @click="editSelection" type="button" name="button" class="btn btn--orange mb-0 color--white">
              EDITER
            </button>

            <!-- EDIT -->
            <button v-if="selectedFiles.length > 0 && settings.groupActions.indexOf('tinymce') != -1"
              @click="editorOptions" type="button" name="button" class="btn btn--orange mb-0 color--white">
              AJOUTER A L'ÉDITEUR
            </button>

            <!-- DELETE -->
            <button v-if="selectedFiles.length > 0 && settings.groupActions.indexOf('delete') != -1"
              @click="deleteSelection" type="button" name="button" class="btn btn--red mb-0 color--white">
              SUPPRIMER
            </button>

          </div>
        </div>
      </div>
      <div class="utils--spacer-semi"></div>
      <div class="d-flex flex-row justify-content-between align-items-center">
        <div>
          <div v-if="aParams.atags || aParams.filters || aParams.date" class="f-flex flex-row">
            <p class="small color--grey d-inline-block">Filtre(s): </p>
            <span v-if="aParams.atags" class="badge badge-secondary bg-secondary" @click="removeAtag(atag)" :key="atag"
              v-for="atag in aParams.atags.split(',')">{{atag}} <i class="material-icons">close</i></span>
            <span v-if="aParams.filters" class="badge badge-secondary bg-secondary" @click="removeFilter(filter)"
              :key="atag" v-for="filter in aParams.filters.split(',')">{{filter}} <i
                class="material-icons">close</i></span>
            <span v-if="aParams.date" class="badge badge-secondary bg-secondary" @click="removeDate()"> {{
              aParams.date.split(',').length > 1 ? aParams.date.split(',')[0] + ' - ' + aParams.date.split(',')[1] :
              'Derniers fichiers ajoutés' }} <i class="material-icons">close</i></span>
            <div class="utils--spacer-semi"></div>
          </div>
          <div class="section__sort d-flex flex-row align-items-center">
            <p class="small color--grey d-inline-block mb-0">Ordre: &nbsp;&nbsp;</p>
            <select class="no-select-2" v-model="direction" @change="changeOrder">
              <option value="desc">Plus récent en premier</option>
              <option value="asc">Plus ancien en premier</option>
            </select>
          </div>
        </div>
        <div class="section__filter d-flex flex-row">
          <button type="button" @click="mode = 'thumb'" name="button" class="btn btn--white mb-0"
            :class="{active: mode == 'thumb'}"><icon-grid></icon-grid></button>
          <button v-if="types.current == 'image/*'" type="button" @click="mode = 'mosaic'" name="button"
            class="btn btn--white mb-0" :class="{active: mode == 'mosaic'}"><icon-mosaic></icon-mosaic></button>
          <button type="button" @click="mode = 'thumbInfo'" name="button" class="btn btn--white mb-0"
            :class="{active: mode == 'thumbInfo'}"><icon-list></icon-list></button>
        </div>
      </div>
    </div>

    <div class="section__index" v-if="attachments && $parent.loading == false && !downloading">
      <h3 class="text-end">
        <span v-if="pagination && pagination.count">{{pagination.count}}</span>
        <span v-else>0</span>
        Fichiers
        <span v-if="size.length"> &nbsp; | &nbsp; {{ parseInt(size[0].size) | bytesToMegaBytes | decimal(2) }} MB</span>
      </h3>
      <div class="utils--spacer-mini"></div>
      <transition name="fade">
        <div v-if="mode == 'mosaic'" v-images-loaded="imgReady">
          <div id="mosaic">
            <div class="mosaic-img-container" v-for="(attachment, i ) in attachments" :key="i">
              <attachment :index="i" :aid="aid" :mode="mode" :attachment="attachment"></attachment>
            </div>
          </div>
        </div>
        <div v-else-if="mode == 'thumb'">
          <div>
            <div class="row g-4">
              <div v-for="(attachment, i ) in attachments" :key="i" class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                <attachment :index="i" :aid="aid" :mode="mode" :attachment="attachment"></attachment>
              </div>
            </div>
          </div>
        </div>
        <div v-else-if="mode == 'thumbInfo'">
          <table class="table w-100">
            <attachment v-for="(attachment, i ) in attachments" :key="i" :index="i" :aid="aid" :mode="mode"
              :attachment="attachment"></attachment>
          </table>
        </div>
      </transition>
      <div class="clearfix"></div>
      <div class="utils--spacer-semi"></div>
      <div v-if="pagination">
        <attachment-pagination :aid="aid" :pagination="pagination" :settings="settings"></attachment-pagination>
      </div>
    </div>
    <div v-if="$parent.loading == true || downloading" class=" text-center">
      <img src="https://static.wgr.ch/attachment/loading.gif" alt="">
    </div>
    <attachment-preview :aid="aid" :open="false"></attachment-preview>
    <attachment-infos :aid="aid" :open="false"></attachment-infos>
    <!--<attachment-archive :aid="aid" :settings="settings"></attachment-archive >-->

  </section>
</template>
<script>
// npm libs
import { mapActions } from 'vuex'

import { client } from '../http/client.js'

import Attachment from './Attachment.vue'
import Pagination from './Pagination.vue'
import Preview from './Preview.vue'
import Infos from './Infos.vue'

//import Archive from './Archives.vue'

import iconGrid from './icons/viewGrid.vue'
import iconMosaic from './icons/viewMosaic.vue'
import iconList from './icons/viewList.vue'

import SearchBar from './SearchBar.vue'

export default
{
  name:'attachments',
  props: { aid: String, settings: Object },
  data(){
    return {
      sort: this.settings.browse.search.dateField.split('.').pop(),
      direction: 'desc',
      mode: 'thumb',
      types: {
        name: 'Types',
        slug: 'type',
        isActive: false,
        current: '',
      },
      downloading: false,
      hasProcessingArchive: false,
      aarchiveIds: []

    }
  },
  components:
  {
    'attachment': Attachment,
    'attachment-pagination': Pagination,
    'attachment-search-bar': SearchBar,
    'attachment-preview': Preview,
    'attachment-infos': Infos,
    //'attachment-archive': Archive,

    'icon-grid': iconGrid,
    'icon-mosaic': iconMosaic,
    'icon-list': iconList
  },
  mounted() {
  },
  computed:
  {
    aParams()
    {
      return this.$store.get(this.aid + '/aParams')
    },
    attachments()
    {
      return this.$store.get(this.aid + '/attachments/list')
    },
    pagination()
    {
      return this.$store.get(this.aid + '/pagination')
    },
    selectedFiles()
    {
      return this.$store.get(this.aid + '/selection.files')
    },
    downloadToken()
    {
      return this.$store.get(this.aid + '/selection.token')
    },
    size()
    {
      return this.$store.get(this.aid + '/size/list')
    },
  },
  watch: {
    mode: function(){
      if(this.mode == 'mosaic'){
      }else{
      }
    }
  },
  methods:
  {
    ...mapActions({
      createAttachments(dispatch, payload)
      {
        return dispatch(this.aid + '/aarchives/create', payload)
      },
    }),
    removeAtag(atag)
    {
      var list = this.aParams.atags.split(',');
      for(var i = 0 ; i < list.length ; i++) {
        if(list[i] == atag) list.splice(i, 1)
      }
      this.$store.set(this.aid + '/aParams', Object.assign(this.$store.get(this.aid + '/aParams'),{ atags: list.join(','), page: 1 }))
    },
    removeFilter(filter)
    {
      var list = this.aParams.filters.split(',');
      for(var i = 0 ; i < list.length ; i++) {
        if(list[i] == filter) list.splice(i, 1)
      }
      this.$store.set(this.aid + '/aParams', Object.assign(this.$store.get(this.aid + '/aParams'), { filters: list.join(','), page: 1 }))
    },
    removeDate(filter) {
      document.getElementById("date-start").value = "";
      document.getElementById("date-end").value = "";
      this.$store.set(this.aid + '/aParams', Object.assign(this.$store.get(this.aid + '/aParams'), { date: '', page: 1 }))
    },
    filterType()
    {
      if(!this.upload){
        if(!this.types.current.match(/image/g)){
          this.mode = 'thumb'
        }
        this.$store.set(this.aid + '/tParams', Object.assign(this.$store.get(this.aid + '/tParams'), { refresh: new Date().getTime(), type: this.types.current }))
        this.$store.set(this.aid + '/aParams', Object.assign(this.$store.get(this.aid + '/aParams'),{ type: this.types.current, filters: '', atags: '', page: 1 }))
      }
    },
    changeOrder()
    {
      this.$store.set(this.aid + '/aParams', Object.assign(this.$store.get(this.aid + '/aParams'),{ sort: this.sort, direction: this.direction, page: 1 }))
    },
    downloadSelection()
    {
      let token = this.$store.get(this.aid+'/selection.token')
      this.$refs.dform.submit()
      /*let url = this.$store.get(this.aid + '/settings.url')+'attachment/download/files/'+token
      window.open(url)*/
    },
    editSelection()
    {
      this.$parent.mode = 'edit'
    },
    deleteSelection()
    {
      if(confirm('Etes-vous sûr de vouloir supprimer les fichiers séléctionnés?')){
        let params = {
          headers: {'Accept': 'application/json', 'Content-Type': 'multipart/form-data'},
        }
        let formData = new FormData()
        for(let i = 0;i < this.selectedFiles.length;i++) formData.append('id['+i+']', this.selectedFiles[i].id)
        client.post(this.settings.url+'attachment/attachments/deleteAll.json', formData, params)
        .then(this.deleteSuccess, this.deleteError)
      }
    },
    deleteSuccess()
    {
      this.$store.commit(this.aid+'/flushSelection')
      this.$store.set(this.aid + '/aParams', Object.assign(this.$store.get(this.aid + '/aParams'),{ refresh: new Date().getTime() }))
      this.$store.set(this.aid + '/aParams', Object.assign(this.$store.get(this.aid + '/aParams'), { refresh: new Date().getTime() }))
    },
    deleteError()
    {
      alert('Une erreur est survenue veuillez réessayer.')
    },
    multiDownload()
    {
      let selectedFiles = this.$store.get(this.aid + '/selection.files')
      let body = []
      selectedFiles.forEach((a) =>{
        body.push(a.path)
      })

      let params = {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Client': this.settings.browse.download.client,
          'Authorization': 'Bearer '+ this.settings.browse.download.token
        },
      }

      this.downloading = true
      const response = fetch(this.settings.browse.download.url, {
        ...params,
        body: JSON.stringify(body),
      })
      .then(response => response.blob())  // Convert the response stream to a Blob
      .then(blob => {
        this.$store.commit(this.aid + '/flushSelection');
        this.downloading = false
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'download.zip');  // Set the file name for download

        document.body.appendChild(link);
        link.click();

        link.parentNode.removeChild(link);
        window.URL.revokeObjectURL(url);
        
      })
      .catch(error => {
        this.downloading = false
        console.error('Download error:', error);
      });
      

      
      console.log(selectedFiles)
      console.log(this.$store.get(this.aid))

    },
    requestArchive()
    {
      let
      selectedFiles = this.$store.get(this.aid+'/selection.files'),
      aids = []

      // create
      for(let i = 0;i < selectedFiles.length;i++) aids.push(selectedFiles[i].id)
      this.createAttachments({
        data: {aids}
      })

      // flush and redirect
      this.types.current = this.settings.browse.types.other.mime.join(',')
      setTimeout(function () {
        this.filterType()
        this.$store.commit(this.aid+'/flushSelection');
      }.bind(this), 500)
    },
    confirmSelection()
    {
      this.$parent.mode = 'input'
    },
    editorOptions()
    {
      this.$parent.mode = 'editor-options'
    }
  },
}
</script>
