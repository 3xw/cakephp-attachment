<template>
  <section>
    <div v-if="loading == true" class=" text-center">
      <img src="https://static.wgr.ch/attachment/loading.gif" alt="">
    </div>
    <div v-else>
      <div class="section__files-list">

        <div class="row">
          <div v-for="(attachment, i) in selectedFiles" :key="i" class="col-sm-2">
            <attachment :index="i" :aid="aid" mode="thumb" :attachment="attachment" :settings="settings"></attachment>
          </div>
        </div>
        <div class="utils--spacer-semi"></div>
      </div>

      <!-- File replacement section (only for single file edit) -->
      <div v-if="selectedFiles.length === 1" class="file-replacement-section mb-4">
        <h5>Remplacer le fichier</h5>

        <div class="current-file-info mb-2">
          <small class="text-muted">
            Fichier actuel: {{ selectedFiles[0].name }}
            ({{ (selectedFiles[0].size / 1024 / 1024).toFixed(2) }} MB)
          </small>
        </div>

        <input type="file" ref="fileInput" @change="validateNewFile" style="display: none">

        <div v-if="!fileToUpload">
          <button type="button" class="btn btn-outline-primary" @click="selectNewFile">
            Changer le fichier
          </button>
        </div>

        <div v-else class="new-file-info alert alert-info">
          <strong>Nouveau fichier:</strong> {{ fileToUpload.name }}
          ({{ (fileToUpload.size / 1024 / 1024).toFixed(2) }} MB)
          <button type="button" class="btn btn-sm btn-outline-danger ml-2" @click="unselectFile">
            Annuler
          </button>
        </div>

        <div v-for="(error, i) in fileErrors" :key="i" class="alert alert-danger mt-2">
          {{ error }}
        </div>

        <div v-if="uploading" class="progress mt-2">
          <div class="progress-bar" role="progressbar" :style="{ width: uploadProgress + '%' }">
            {{ uploadProgress }}%
          </div>
        </div>
      </div>

      <!-- inputs -->
      <attachment-inputs :aid="aid" mode="edit"></attachment-inputs>
      <button type="button" name="button" class="btn btn-success" @click="edit">Editer</button>
    </div>
  </section>
</template>
<script>
import { client } from '../http/client.js'
import AttachmentInputs from './AttachmentInputs.vue'
import Attachment from './Attachment.vue'
export default {
  name: 'attachment-edit',
  components:
  {
    'attachment': Attachment,
    'attachment-inputs': AttachmentInputs,
  },
  props: { aid: String, settings: Object },
  data() {
    return {
      loading: false,
      fileToUpload: null,
      fileErrors: [],
      uploading: false,
      uploadProgress: 0,
      attachmentInputs: {
        fields: [
          {
            key: 'title',
            value: null,
            isSame: false,
            hasChange: false,
          },
          {
            key: 'date',
            value: null,
            isSame: false,
            hasChange: false,
          },
          {
            key: 'description',
            value: null,
            isSame: false,
            hasChange: false,
          },
          {
            key: 'author',
            value: null,
            isSame: false,
            hasChange: false,
          },
          {
            key: 'copyright',
            value: null,
            isSame: false,
            hasChange: false,
          },
        ],
        atags: [],
        atagsToKeep: []
      }
    }
  },
  created()
  {
    this.mergeDatas()
  },
  computed:
  {
    atagTypes()
    {
      return this.$store.get(this.aid + '/atags/list')
    },
    aParams()
    {
      return this.$store.get(this.aid + '/aParams')
    },
    selectedFiles()
    {
      return this.$store.get(this.aid + '/selection.files')
    },
    uploadSettings()
    {
      return this.$store.get(this.aid + '/settings')
    }
  },
  watch:
  {
    selectedFiles: {
      handler()
      {
        this.mergeDatas()
      },
      deep: true
    },
    aParams: {
      handler()
      {
        this.attachmentInputs.atags = this.aParams.atags.split(',')
      },
      deep: true
    },
  },
  methods:
  {
    mergeDatas()
    {
      for(let i = 0;i < this.selectedFiles.length; i++){
        let attachment = this.selectedFiles[i]
        //FIELDS CHECK FOR SAME VALUES
        for(let y = 0;y < this.attachmentInputs.fields.length;y++){
          let input = this.attachmentInputs.fields[y]
          let field = input.key
          let value = input.value
          if(attachment[field] == value || input.value == null){
            input.isSame = true
            input.value = attachment[field]
          }else{
            input.isSame = false
            input.value = null
          }
        }
        //ATAGS CHECK FOR SAME ATAG
        for(let z = 0;z < attachment.atags.length;z++){
          if(i == 0){
            this.attachmentInputs.atags.push(attachment.atags[z].name)
          }
          //ADD ALL ATAGS TO KEEPING LIST
          if(this.attachmentInputs.atagsToKeep.findIndex(a => a == attachment.atags[z].name) == -1){
            this.attachmentInputs.atagsToKeep.push(attachment.atags[z].name)
          }
          for(let x = 0;x < this.attachmentInputs.atags.length;x++){
            if(attachment.atags.findIndex(a => a.name === this.attachmentInputs.atags[x]) == -1){
              this.attachmentInputs.atags.splice(x, 1)
            }
          }
        }
      }
      //REMOVE GOOD ATAGS FOR NO KEEPING
      for(let w = 0;w < this.attachmentInputs.atagsToKeep.length;w++){
        if(this.attachmentInputs.atags.findIndex(a => a === this.attachmentInputs.atagsToKeep[w]) !== -1){
          this.attachmentInputs.atagsToKeep.splice(w, 1)
        }
      }
      //NO KEEP IF SINGLE ATTACHMENT
      if(this.selectedFiles.length == 1) this.attachmentInputs.atagsToKeep = []
      //UDPATE ATAGS LIST
      this.$store.set(this.aid + '/aParams', Object.assign(this.$store.get(this.aid + '/aParams'),{ atags: this.attachmentInputs.atags.join(','), page: 1 }))
    },
    selectNewFile()
    {
      this.$refs.fileInput.click()
    },
    validateNewFile(event)
    {
      this.fileErrors = []
      const file = event.target.files[0]
      if (!file) return

      // Validate file size
      const sizeMB = file.size / 1024 / 1024
      if (sizeMB > this.uploadSettings.maxsize) {
        this.fileErrors.push(`Ce fichier est trop lourd (${sizeMB.toFixed(2)} MB). La taille max est de ${this.uploadSettings.maxsize} MB.`)
        return
      }

      // Validate file type
      if (this.uploadSettings.types && this.uploadSettings.types.length > 0) {
        if (this.uploadSettings.types.indexOf(file.type) === -1) {
          this.fileErrors.push(`Ce type de fichier (${file.type}) n'est pas supporté.`)
          return
        }
      }

      // Validate image dimensions if applicable
      if (file.type && file.type.startsWith('image/') && this.uploadSettings.minwidth > 0) {
        const _URL = window.URL || window.webkitURL
        const img = new Image()
        const objectUrl = _URL.createObjectURL(file)
        img.onload = () => {
          if (img.naturalWidth < this.uploadSettings.minwidth) {
            this.fileErrors.push(`La taille de l'image est trop petite (min. ${this.uploadSettings.minwidth}px).`)
            this.fileToUpload = null
          } else {
            this.fileToUpload = file
          }
          _URL.revokeObjectURL(objectUrl)
        }
        img.onerror = () => {
          this.fileErrors.push(`Impossible de lire l'image.`)
          _URL.revokeObjectURL(objectUrl)
        }
        img.src = objectUrl
      } else {
        this.fileToUpload = file
      }
    },
    unselectFile()
    {
      this.fileToUpload = null
      this.fileErrors = []
      this.$refs.fileInput.value = ''
    },
    edit()
    {
      // Single file with file replacement
      if (this.selectedFiles.length === 1 && this.fileToUpload) {
        return this.editSingleWithFile()
      }
      // Bulk edit or single edit without file replacement
      return this.editBulk()
    },
    editSingleWithFile()
    {
      this.uploading = true
      this.uploadProgress = 0
      const attachment = { ...this.selectedFiles[0] }

      // Apply metadata changes
      for (let y = 0; y < this.attachmentInputs.fields.length; y++) {
        const input = this.attachmentInputs.fields[y]
        if (input.hasChange) attachment[input.key] = input.value
      }

      // Process atags
      const atags = []
      for (let z = 0; z < attachment.atags.length; z++) {
        const atag = attachment.atags[z]
        // Keep atag if it's in the checked list or in keep list
        if (this.attachmentInputs.atags.findIndex(a => a == atag.name) !== -1 ||
            this.attachmentInputs.atagsToKeep.findIndex(a => a == atag.name) !== -1) {
          atags.push(atag)
        }
      }
      // Add new tags
      for (let x = 0; x < this.attachmentInputs.atags.length; x++) {
        if (atags.findIndex(a => a.name == this.attachmentInputs.atags[x]) == -1) {
          for (let z = 0; z < this.atagTypes.length; z++) {
            const atag = this.atagTypes[z].atags.find(a => a.name == this.attachmentInputs.atags[x])
            if (atag) {
              atags.push(atag)
              break
            }
          }
        }
      }

      // Build FormData
      const formData = new FormData()
      formData.append('path', this.fileToUpload)
      formData.append('uuid', this.aid)

      // Add metadata fields
      const metadataFields = ['title', 'description', 'author', 'copyright', 'date']
      for (const field of metadataFields) {
        if (attachment[field] != null) {
          formData.append(field, attachment[field])
        }
      }

      // Add atags
      atags.forEach((atag, i) => {
        formData.append(`atags[${i}][name]`, atag.name)
      })

      const params = {
        headers: { 'Accept': 'application/json', 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (e) => {
          if (e.lengthComputable) {
            this.uploadProgress = Math.floor((e.loaded / e.total) * 100)
          }
        }
      }

      client.post(
        `${this.settings.url}attachment/attachments/edit/${attachment.id}.json`,
        formData,
        params
      )
      .then(this.editSuccess, this.editError)
    },
    editBulk()
    {
      this.loading = true
      for(let i = 0;i < this.selectedFiles.length; i++){
        let attachment = this.selectedFiles[i]
        for(let y = 0;y < this.attachmentInputs.fields.length;y++){
          let input = this.attachmentInputs.fields[y]
          if(input.hasChange) attachment[input.key] = input.value
        }
        for(let z = 0;z < attachment.atags.length;z++){
          let atag = attachment.atags[z]
          //REMOVE UNCHECKED ATAGS
          if(this.attachmentInputs.atags.findIndex(a => a == atag.name) == -1 && this.attachmentInputs.atagsToKeep.findIndex(a => a == atag.name) == -1 )
          {
            attachment.atags.splice(attachment.atags.findIndex(b => b.name == atag.name), 1)
          }
        }
        //ADD NEWS TAGS
        for(let x = 0;x < this.attachmentInputs.atags.length;x++){
          if(attachment.atags.findIndex(a => a.name == this.attachmentInputs.atags[x]) == -1){
            for(let z = 0;z < this.atagTypes.length;z++){
              let atag = this.atagTypes[z].atags.find(a => a.name == this.attachmentInputs.atags[x])
              if(atag){
                attachment.atags.push(atag)
                break
              }
            }
          }
        }
      }
      let params = {
        headers: {'Accept': 'application/json', 'Content-Type': 'multipart/form-data'},
      }
      client.post(this.settings.url+'attachment/attachments/edit-all.json', this.selectedFiles, params)
      .then(this.editSuccess, this.editError)
    },
    editSuccess(){
      this.$store.commit(this.aid+'/flushSelection')
      this.$store.set(this.aid + '/aParams', Object.assign(this.$store.get(this.aid + '/aParams'),{atags: '', refresh: new Date().getTime() }))
      this.loading = false
      this.uploading = false
      this.fileToUpload = null
      this.uploadProgress = 0
      this.$parent.mode = 'browse'
    },
    editError(error){
      this.loading = false
      this.uploading = false
      const message = error.response?.data?.message || 'Une erreur est survenue veuillez réessayer.'
      alert(message)
    }
  }
}
</script>
