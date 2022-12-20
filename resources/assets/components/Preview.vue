<template lang="html">
  <div class="block-attachment--modal" v-if="open && attachment.id" @click="closePreview">
    <div @click="open = false" class="block-attachment__close">
      <i class="material-icons"> clear </i>
    </div>
    <div class="block-attachment__content">
      <div @click="slide('prev')" class="arrow-left"><i class="material-icons"> chevron_left </i></div>
      <div @click="slide('next')" class="arrow-right"><i class="material-icons"> chevron_right </i></div>
      <div class="attachment-preview">
        <div class="attachment-preview-image" v-if="attachment.type == 'image'">
          <img :src="thumbBaseUrl(`w${(attachment.width < 1200) ? attachment.width : 1200}q95`,attachment)" class="img-fluid" :key="attachment.id">
        </div>
        <video v-else-if="attachment.type == 'video'" :src="attachment.url" controls class="img-fluid"></video>
        <div v-else-if="attachment.type == 'embed'">{{attachment.embed}}</div>
        <iframe frameborder="0" v-else-if="attachment.subtype == 'pdf'" :src="attachment.url" class="w-100"></iframe>
        <div v-if="attachment.type != 'application'" class="info-display" style="color: white;">
          <strong v-if="attachment.title">
            Titre : {{attachment.title}} <br>
          </strong>
          <strong v-else>
            Nom : {{ attachment.name }} <br>
          </strong>
          <strong v-if="attachment.date">
            Date : {{ new Date(attachment.date).toLocaleDateString('fr-CH') }} {{ new
            Date(attachment.created).toLocaleTimeString('fr-CH', { hour: "2-digit", minute: "2-digit" }) }}
          </strong>
          <strong v-else>
            Date : {{ new Date(attachment.created).toLocaleDateString('fr-CH') }} {{ new
            Date(attachment.created).toLocaleTimeString('fr-CH', { hour: "2-digit", minute: "2-digit" }) }}
          </strong>
          <div class="attachment-preview-actions">
            <div class="btn-group">
              <!-- DOWNLOAD -->
              <div v-if="settings.actions.indexOf('download') != -1" @click="downloadFile(attachment)" title="Télécharger"
                alt="Télécharger" class="btn btn--blue color--white">
                <i class="material-icons"> cloud_download </i>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="info-display" style="color: white;">
          <div v-if="attachment.subtype != 'pdf'" class="file-logo">
            <i class="material-icons" style="color: white;font-size: 84px;">insert_drive_file</i>
          </div>
          <strong v-if="attachment.title">
            Titre : {{attachment.title}} <br>
          </strong>
          <strong v-else>
            Nom : {{ attachment.name }} <br>
          </strong>
          <strong>Type : {{attachment.type}}/{{ attachment.subtype }} <br></strong>
          <strong>Poids : {{ attachment.size | bytesToMegaBytes | decimal(2)}}MB <br></strong>
          <strong v-if="attachment.date">
            Date : {{ new Date(attachment.date).toLocaleDateString('fr-CH') }} {{ new
            Date(attachment.created).toLocaleTimeString('fr-CH', { hour: "2-digit", minute: "2-digit" }) }}
          </strong>
          <strong v-else>
            Date : {{ new Date(attachment.created).toLocaleDateString('fr-CH') }} {{ new
            Date(attachment.created).toLocaleTimeString('fr-CH', { hour: "2-digit", minute: "2-digit" }) }}
          </strong>
          <div class="attachment-preview-actions">
            <div class="btn-group">
              <!-- DOWNLOAD -->
              <div v-if="settings.actions.indexOf('download') != -1" @click="downloadFile(attachment)" title="Télécharger"
                alt="Télécharger" class="btn btn--blue color--white">
                <i class="material-icons"> cloud_download </i>
              </div>
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

export default {
  name: 'preview',
  props: {aid: String},
  data()
  {
    return {
      open: false,
      slideCounter: 1,
    }
  },
  computed:
  {
    attachment()
    {
      return this.$store.get(this.aid + '/preview')
    },
    settings() {
      return this.$store.get(this.aid + '/settings')
    },
  },
  watch:
  {
    attachment: function(){
      this.open = true
    }
  },
  created(){
  },
  methods: {
    closePreview(event) {
      if (event.target.className === 'block-attachment__content' || event.target.className === 'block-attachment--modal') {
        this.open = false
      }
    },
    close()
    {
      this.open = false
      this.$store.set(this.aid + '/infos', {})
    },
    downloadFile(attachment) {
      client.get(attachment.url, { responseType: 'arraybuffer' })
        .then(response => {
          this.forceFileDownload(response, attachment)
        })
        .catch((response) => console.log(response))
    },
    forceFileDownload(response, attachment) {
      if (navigator.appVersion.toString().indexOf('.NET') > 0) {
        window.navigator.msSaveBlob(new Blob([response.data], { type: response.headers['content-type'] }), attachment.name);
      } else {
        const url = window.URL.createObjectURL(new Blob([response.data], { type: response.headers['content-type'] }))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', attachment.filename) //or any other extension
        document.body.appendChild(link)
        link.click()
      }
    },
    infos(attachment) {
      return this.$store.get(this.aid + '/infos', attachment)
    },
    thumbBaseUrl(modifs, attachment) {
      let baseUrl = typeof attachment == 'string' ?
          __WEBROOT__ + 'thumbnails/' :
          this.$store.get(this.aid + '/settings.baseUrls.' + attachment.profile + '.thumbnails'),
        mbP = (attachment.thumb_params) ? attachment.thumb_params : '',
        url = typeof attachment == 'string' ?
          'default/' + modifs + '/' + attachment :
          attachment.profile + '/' + modifs + '/' + attachment.path + mbP

      return baseUrl + url
    },
    slide(direction){
      let index = this.$store.get(this.aid + '/attachments/list').indexOf(this.attachment)
      let authorizedTypes = ['image', 'video', 'application']
      let attachment = this.$store.get(this.aid + '/attachments/list')[index + this.slideCounter]
      if(direction == 'next'){
        if((index + this.slideCounter) < this.$store.get(this.aid + '/attachments/list').length){
          if(authorizedTypes.includes(attachment.type)){
            this.$store.set(this.aid + '/preview', this.$store.get(this.aid + '/attachments/list')[index + this.slideCounter]);
            this.slideCounter = 1;
          }
          else{
            this.slideCounter++;
            this.slide('next');
          }
        }
      }else{
        if((index - this.slideCounter) >= 0){
          if (authorizedTypes.includes(attachment.type)) {
            this.$store.set(this.aid + '/preview', this.$store.get(this.aid + '/attachments/list')[index - this.slideCounter]);
            this.slideCounter = 1;
          }
          else {
            this.slideCounter++;
            this.slide('prev');
          }
        }
      }
    },
  },
  mounted() {
    document.addEventListener('keydown', (event) => {
      if (this.open) {
        if (event.key === 'ArrowLeft') {
          this.slide('prev')
        }
        if (event.key === 'ArrowRight') {
          this.slide('next')
        }
      }
    })
  },
  beforeDestroy() {
    document.removeEventListener('keydown', (event) => {
      if (this.open) {
        if (event.key === 'ArrowLeft') {
          this.slide('prev')
        }
        if (event.key === 'ArrowRight') {
          this.slide('next')
        }
      }
    })
  },
}
</script>
