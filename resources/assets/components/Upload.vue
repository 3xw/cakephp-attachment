<template>
  <section class="">

    <!-- selection -->
    <div class="">
      <!-- inputs -->
      <attachment-inputs :aid="aid" mode="upload"></attachment-inputs>

      <!-- file input -->
      <div class="input">
        <label>Fichiers
          <input type="file" id="files" ref="files" multiple @change="validate">
        </label>
      </div>

      <!-- progress -->
      <div v-if="uploading" class="progress__info">
        <div v-if="filesToUpload < 2">
          <label>Téléchargement du fichier : "{{fileName}}"</label>
          <div class="progress">
            <div class="progress-bar" role="progressbar" :style="{ width: percent + '%' }">{{percent}}</div>
          </div>
        </div>
        <div v-else >
          <label>{{ (filesToUpload - files.length) }} fichiers sur {{ filesToUpload }}.</label>
          <div class="progress">
            <div class="progress-bar" role="progressbar" :style="{ width: ((filesToUpload - files.length) * (100 / filesToUpload)) + '%' }">&nbsp;</div>
          </div>
        </div>
      </div>

      <!-- submit -->
      <div class="input">
        <button type="button" name="button" class="btn btn-success" @click="preUpload">Télécharger</button>
      </div>

      <!-- alerts -->
      <div v-for="(error, i) in errors" :key="i" class="alert alert-warning" role="alert">
        {{error}}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

    </div>


  </section>
</template>

<script>
// js scripts
import { client } from '../http/client.js'

// vue components
import AttachmentInputs from './AttachmentInputs.vue'

export default
{
  name:'attachment-upload',
  components:
  {
    'attachment-inputs':AttachmentInputs
  },
  props: { aid: String },
  data()
  {
    return {
      progress: 0,
      uploading: false,
      fileName: '',
      files: [],
      errors: [],
      hasUploaded: 0,
      filesToUpload: 0,
      firstUploadTime: 0,
      finishUpload: false
    }
  },
  computed:
  {
    percent()
    {
      return Math.floor(this.progress * 100)
    },
    settings()
    {
      return this.$store.get(this.aid+'/settings')
    },
    atags()
    {
      return this.$store.get(this.aid+'/upload.atags')
    },
    inputs()
    {
      return this.$store.get(this.aid+'/upload.inputs')
    }
  },
  created()
  {
    this.firstUploadTime = 0
    this.finishUpload = false
    this.$store.commit(this.aid+'/flushUploadedFiles')
  },
  watch:
  {
    files()
    {
      for(let i in this.files)
      {
        if (this.files[i].type && this.files[i].type.match('image/*'))
        {
           let reader = new FileReader()
           reader.onload = (event) =>
           {
             this.files[i].preview = event.target.result
             this.$forceUpdate()
           }
           reader.readAsDataURL(this.files[i])
        }
      }
    }
  },
  methods:
  {
    validate: function()
    {
      this.errors = []
      let errors = []
      for(let i in this.$refs.files.files)
      {
        let file = this.$refs.files.files[i]
        
        // test size
        let size = file.size / 1024 / 1024 ;
        if(!size) return
        if ( !(size > 0) || !(size <= this.settings.maxsize))
        {
          errors.push(file.name +  ' ce fichier est trop lourd. La taille max est de ' + this.settings.maxsize + 'MB.')
        }

        function waitForImage(imgElem) {
          return new Promise(res => {
            if (imgElem.complete) {
              return res();
            }
            imgElem.onload = () => res();
            imgElem.onerror = () => res();
          });
        }

        //test width
        if (file.type.split('/')[0] == 'image') {
          var _URL = window.URL || window.webkitURL;
          var img = new Image();
          var objectUrl = _URL.createObjectURL(file);
          var minwidth = this.settings.minwidth
          img.src = objectUrl;
        }

        // test
        if(this.settings.types.indexOf(file.type) === false || file.type == "" )
        {
          errors.push(file.name +  ' ce type de fichier n\' est pas supporté.')
        }
        

        (async () => {
          if (file.type.split('/')[0] == 'image'){
            await waitForImage(img);
            if (img.naturalWidth < minwidth) {
              errors.push('La taille de l\'image "' + file.name + '" est trop petite (min. ' + minwidth + 'px).');
            }
            _URL.revokeObjectURL(objectUrl);
            this.errors = errors;
            if (this.errors.length == 0) { this.files.push(file) } 
          }else{
            this.errors = errors;
            if (this.errors.length == 0) { this.files.push(file) } 
          }
        })();
      }
    },
    preUpload: function()
    {
      if(this.files.length > 1) this.filesToUpload = this.files.length
      this.upload()
    },
    upload: function()
    {
      if(this.files.length != 0){
        if(this.settings.mandatory_tag){
          if(this.atags.length == 0){
            alert('Vous devez choisir au moins un tag.')
            return
          }
        }
        return this.uploadFile(this.files.shift())
      } 
      else
      {
        if(this.errors.length == 0)
        {
          this.$parent.mode = 'browse'
          this.finishUpload = true
          this.$store.set(this.aid + '/aParams', Object.assign(this.$store.get(this.aid + '/aParams'), { refresh: new Date().getTime(), date: this.firstUploadTime }))
        }
      }
    },
    uploadFile: function(file)
    {
      this.uploading = true
      this.fileName = file.name

      //DATE FOR UPLOAD
      if(this.inputs.date.length > 0){
        this.inputs.date = (this.inputs.date.indexOf('T') != -1)? this.inputs.date.replace('T', ' ')+':00' : this.inputs.date
      }

      let formData = new FormData()
      for( let i in this.inputs ) formData.append(i, this.inputs[i])
      for( let t in this.atags ) formData.append('atags['+t+'][name]', this.atags[t])
      formData.append('path', file)
      formData.append('uuid', this.aid)

      let params = {
        headers: {'Accept': 'application/json', 'Content-Type': 'multipart/form-data'},
        onUploadProgress: this.progressHandler
      }
      if (this.firstUploadTime === 0) this.firstUploadTime = new Date().toISOString().slice(0, 19).replace('T', ' ');
      client.post(this.settings.url+'attachment/attachments/add.json', formData, params)
      .then(this.uploadSuccessCb, this.errorUploadCb)
    },
    progressHandler: function(e)
    {
      if (!e.lengthComputable) return
      this.progress = e.loaded / e.total
    },
    uploadSuccessCb: function(response)
    {
      this.uploading = false
      this.hasUploaded = true
      this.$store.commit(this.aid+'/addUploadedFile', response.data.data )
      this.upload()
    },
    errorUploadCb: function(error)
    {
      this.uploading = false
      this.errors.push(error.response.data.message? error.response.data.message: error)
      this.upload()
    },
  }
}
</script>
