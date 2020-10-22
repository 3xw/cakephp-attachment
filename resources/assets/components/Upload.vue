<template>
  <section class="">

    <!-- progress -->
    <div v-if="uploading" class="progress__info">
      <label>Uploading file: "{{fileName}}"</label>
      <div class="progress">
        <div class="progress-bar" role="progressbar" :style="{ width: percent + '%' }" >{{percent}}</div>
      </div>
    </div>

    <!-- selection -->
    <div class="">

      <!-- files -->
      <div v-for="(file, i) in files" class="alert alert-info">
        <div v-if="file.preview" class="alert__preview-img">
          <img :src="file.preview" >
        </div>
        File: {{file.name}} ready to upload!
      </div>

      <!-- alerts -->
      <div v-for="(error, i) in errors" class="alert alert-warning" role="alert">
        {{error}}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <!-- inputs -->
      <attachment-inputs :aid="aid" mode="upload"></attachment-inputs>

      <!-- file input -->
      <div class="input">
        <label>Files
          <input type="file" id="files" ref="files" multiple @change="validate">
        </label>
      </div>



      <!-- submit -->
      <div class="input">
        <button type="button" name="button" class="btn btn-success" @click="upload">Upload</button>
      </div>



    </div>


  </section>
</template>

<script>
// js scripts
import { client } from '@/http/client.js'

// vue components
import AttachmentInputs from '@/components/AttachmentInputs.vue'

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
      hasUploaded: 0
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
        // test
        if(this.settings.types.indexOf(file.type) === false || file.type == "" )
        {
          errors.push(file.name +  ' ce type de fichier n\' est pas supporté.')
        }
        this.errors = errors;
        if(this.errors.length == 0) this.files.push(file)
      }
    },
    upload: function()
    {
      if(this.files.length != 0) return this.uploadFile(this.files.shift())
      else
      {
        if(this.errors.length == 0)
        {
          this.$parent.mode = 'browse'
          this.$store.set(this.aid + '/aParams', Object.assign(this.$store.get(this.aid + '/aParams'),{ refresh: new Date().getTime() }))
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
