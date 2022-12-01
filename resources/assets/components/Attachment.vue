<template>
  <div :is="(mode == 'thumbInfo')? 'tbody' : 'div'" @mouseover="hover = true" @mouseleave="hover = false">

    <!-- input -->
    <div v-if="mode == 'input'">
      <div class="card attachment-input">
        <div class="attachment-input__icon-container" >
          <div>
            <img v-if="$options.filters.isThumbable(attachment, hasCustomThumbService)" v-bind:src="thumbBaseUrl('w678c4-3q90', attachment)" class="card-img-top" />
            <span v-html="$options.filters.icon(attachment)"></span>
            <!-- overlay -->
            <div class="attachment-thumb__hover">
              <div v-if="isSelected(attachment.id)" class="d-flex flex-column justify-content-center align-items-center">
                <icon-check></icon-check>
                <div class="utils--spacer-mini"></div>
                fichier selectionné
              </div>
              <div v-if="hover" class="attachment-thumb__actions d-flex flex-column justify-content-end align-items-end">
                <div class="btn-group">
                  <!-- SELECT -->
                  <div v-if="settings.groupActions.length > 0" @click="toggleFile(attachment)" title="Ajouter à la sélection" alt="Ajouter à la sélection" class="btn btn--blue-dark color--white" >
                    <i v-if="!isSelected(attachment.id)" class="material-icons"> add_circle </i>
                    <i v-else class="material-icons"> remove_circle </i>
                  </div>

                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="card-body">
          <p class="card-text small">
            <span v-if="attachment.title">{{attachment.title}}<br/></span>
            {{attachment.name}}<br/>
          </p>
          <!-- data -->
          <input v-if="settings.relation == 'belongsToMany'" type="hidden" :name="'attachments['+index+'][id]'" :value="attachment.id">
          <input v-if="settings.relation == 'belongsToMany'" type="hidden" :name="'attachments['+index+'][_joinData][order]'" :value="index">
          <input v-if="settings.relation != 'belongsToMany'" type="hidden" :name="settings.field" :value="attachment.id">
        </div>
      </div>
    </div>

    <!-- thumb -->
    <div v-else-if="mode == 'thumb'">
      <div class="card attachment-thumb">
        <div class="attachment-thumb__icon-container" >
          <div>
            <img v-if="$options.filters.isThumbable(attachment, hasCustomThumbService)" alt="" v-bind:src="thumbBaseUrl('w678c4-3q90', attachment)" class="card-img-top" />
            <span v-html="$options.filters.icon(attachment)"></span>
            <!-- overlay -->
            <div @click="previewThumb($event, attachment)" class="attachment-thumb__hover">
              <div v-if="isSelected(attachment.id)" class="d-flex flex-column justify-content-center align-items-center">
                <icon-check></icon-check>
                <div class="utils--spacer-mini"></div>
                fichier selectionné
              </div>
              <div v-if="hover" class="attachment-thumb__actions d-flex flex-column justify-content-end align-items-end">
                <div class="btn-group">

                  <div title="Infos" alt="Infos" class="btn btn--grey color--white" @click="infos(attachment)"><i class="material-icons">info</i></div>

                  <!-- VIEW -->
                  <div @click="preview(attachment)" title="Aperçu" alt="Aperçu" class="btn btn--green color--white">
                    <i class="material-icons"> remove_red_eye</i>
                  </div>

                  <!-- DOWNLOAD -->
                  <div v-if="settings.actions.indexOf('download') != -1" @click="downloadFile(attachment)" title="Télécharger" alt="Télécharger" class="btn btn--blue color--white">
                    <i class="material-icons"> cloud_download </i>
                  </div>

                  <!-- SELECT -->
                  <div v-if="settings.groupActions.length > 0" @click="toggleFile(attachment)" title="Ajouter à la sélection" alt="Ajouter à la sélection" class="btn btn--blue-dark color--white" >
                    <i v-if="!isSelected(attachment.id)" class="material-icons"> add_circle </i>
                    <i v-else class="material-icons"> remove_circle </i>
                  </div>

                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="card-body">
          <p class="card-text small">
            <span v-if="attachment.title">{{attachment.title}}<br/></span>
            {{attachment.name}}<br/>
          </p>
        </div>
      </div>
    </div>

    <!-- thumbInfo -->
    <tr v-else-if="mode == 'thumbInfo'">
      <td>
        <div class="attachment-thumb__icon-container table" >
          <div>
            <img v-if="$options.filters.isThumbable(attachment, hasCustomThumbService)"  alt="" v-bind:src="thumbBaseUrl('w60c1-1q75',attachment)" width="60" class="card-img-top" />
            <span v-html="$options.filters.icon(attachment)"></span>
          </div>
        </div>
      </td>
      <td>
        <span v-if="attachment.title">{{attachment.title}} | </span>
        {{attachment.name}}<br>
        {{attachment.date}}<br>
        {{attachment.size | bytesToMegaBytes | decimal(2) }} MB
      </td>
      <td class="text-right">
        <div class="btn-group attachment__actions">
          <div title="Infos" alt="Infos" class="btn btn--grey color--white" @click="infos(attachment)"><i class="material-icons">info</i></div>
          <div v-if="attachment.type != 'application' || (attachment.type == 'application' && attachment.subtype == 'pdf')" title="Aperçu" alt="Aperçu" class="btn btn--green color--white" @click="preview(attachment)"><i class="material-icons"> remove_red_eye </i></div>
          <div title="Télécharger" alt="Télécharger" class="btn btn--blue color--white" @click="downloadFile(attachment)"><i class="material-icons"> cloud_download </i></div>
          <div title="Ajouter à la sélection" alt="Ajouter à la sélection" class="btn btn--blue-dark color--white" @click="toggleFile(attachment)">
            <i v-if="!isSelected(attachment.id)" class="material-icons"> add_circle </i>
            <i v-else class="material-icons"> remove_circle </i>
          </div>
        </div>
      </td>
    </tr>

    <!-- mosaic -->
    <div v-else-if="mode == 'mosaic'" class="attachment-thumb">
      <img v-if="$options.filters.isThumbable(attachment, hasCustomThumbService)"  alt="" v-bind:src="thumbBaseUrl('w678q90',attachment)" class="img-fluid"  />
      <div class="attachment-thumb__hover">
        <div v-if="isSelected(attachment.id)" class="d-flex flex-column justify-content-center align-items-center">
          <icon-check></icon-check>
          <div class="utils--spacer-mini"></div>
          fichier selectionné
        </div>
        <div v-if="hover" class="attachment-thumb__actions d-flex flex-column justify-content-end align-items-end">
          <div class="btn-group">
            <div title="Infos" alt="Infos" class="btn btn--grey color--white" @click="infos(attachment)"><i class="material-icons">info</i></div>
            <div v-if="attachment.type != 'application' || (attachment.type == 'application' && attachment.subtype == 'pdf')" title="Aperçu" alt="Aperçu" class="btn btn--green color--white" @click="preview(attachment)"><i class="material-icons"> remove_red_eye </i></div>
            <div title="Télécharger" alt="Télécharger" class="btn btn--blue color--white" @click="downloadFile(attachment)"><i class="material-icons"> cloud_download </i></div>
            <div title="Ajouter à la séléction" alt="Ajouter à la séléction" class="btn btn--blue-dark color--white" @click="toggleFile(attachment)">
              <i v-if="!isSelected(attachment.id)" class="material-icons"> add_circle </i>
              <i v-else class="material-icons"> remove_circle </i>
            </div>
          </div>
        </div>
      </div>
    </div>


  </div>
</template>

<script>
import { mapActions } from 'vuex'
import { client } from '../http/client.js'

import iconCheck from './icons/check.vue'

export default
{
  name:'attachment',
  components: {
    'icon-check': iconCheck,
  },
  props:{attachment: Object, index: Number, aid: String, mode: String},
  data()
  {
    return {
      hover: false,
      requestArchive: 0,
      hasCustomThumbService: (this.$store.get(this.aid+'/settings.profiles.'+this.attachment.profile).thumbnails !== 'thumbnails')
    }
  },
  computed:
  {
    settings()
    {
      return this.$store.get(this.aid+'/settings')
    },
    selectedFiles()
    {
      return this.$store.get(this.aid + '/selection.files')
    }
  },
  mounted: function()
  {
    this.checkForArchiveProcessing()
  },
  methods:
  {
    ...mapActions({
      fetchAttachment(dispatch, payload)
      {
        return dispatch(this.aid + '/attachments/fetchSingle', payload)
      },
    }),
    thumbBaseUrl(modifs, attachment)
    {
      let
      baseUrl = typeof attachment == 'string'?
      __WEBROOT__ + 'thumbnails/' :
      this.$store.get(this.aid+'/settings.baseUrls.'+attachment.profile+'.thumbnails'),
      mbP = (attachment.thumb_params)? attachment.thumb_params : '',
      url = typeof attachment == 'string'?
      'default/'+modifs+'/'+ attachment:
      attachment.profile+'/'+modifs+'/'+attachment.path+mbP

      return baseUrl + url
    },
    toggleFile(attachment)
    {
      if(this.selectedFiles.findIndex(f => f.id === attachment.id) == -1){
        this.$store.commit(this.aid+'/addFileToSelection', attachment)
      }else{
        this.$store.commit(this.aid+'/removeFileFromSelection', attachment)
      }
    },
    isSelected(id)
    {
      return (this.selectedFiles.findIndex(f => f.id === id) !== -1)
    },
    forceFileDownload(response, attachment){
      if (navigator.appVersion.toString().indexOf('.NET') > 0){
        window.navigator.msSaveBlob(new Blob([response.data], { type: response.headers['content-type']}), attachment.name);
      }else {
        const url = window.URL.createObjectURL(new Blob([response.data], { type: response.headers['content-type'] }))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', attachment.name) //or any other extension
        document.body.appendChild(link)
        link.click()
      }
    },
    downloadFile(attachment){
      client.get(attachment.url, {responseType: 'arraybuffer'})
      .then(response => {
        this.forceFileDownload(response, attachment)
      })
      .catch((response) => console.log(response))
    },
    previewThumb(event, attachment) {
      if (event.target.tagName !== 'DIV') {
        return;
      }
      this.$store.set(this.aid + '/preview', '');
      this.$store.set(this.aid + '/preview', attachment)
      this.$forceUpdate()
    },
    preview(attachment){
      this.$store.set(this.aid + '/preview', '');
      this.$store.set(this.aid + '/preview', attachment)
      this.$forceUpdate()
    },
    infos(attachment){
      this.$store.set(this.aid + '/infos', '')
      this.$store.set(this.aid + '/infos', attachment)
      this.$forceUpdate()
    },
    checkForArchiveProcessing(){
      if(this.attachment.aarchive && this.attachment.aarchive.state == 'PROCESSING'){
        setTimeout(function () {
          this.checkStatus()
        }.bind(this), 5000)
      }
    },
    checkStatus(){
      this.requestArchive++
      if(this.requestArchive <= 30){
        this.fetchAttachment({
          customUrl:  this.settings.url+'attachment/attachments/view/'+this.attachment.id
        }).then(response => {
          let attachment = response.data.data
          if(attachment.aarchive.state == 'PROCESSING'){
            setTimeout(function () {
              this.checkStatus()
            }.bind(this), 5000)
          }else{
            this.attachment = attachment
            this.$forceUpdate()
          }
        })
        .catch(() => console.log('error occured'))
      }
    }
  }
}
</script>
