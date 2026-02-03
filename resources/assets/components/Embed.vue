<template>
  <section class="">

    <!-- progress -->
    <div v-if="uploading" class="progress__info">
      <label>Uploading...</label>
      <div class="progress">
        <div class="progress-bar" role="progressbar" :style="{ width: percent + '%' }" >{{percent}}</div>
      </div>
    </div>

    <!-- selection -->
    <div class="">

      <!-- alerts -->
      <div v-for="(error, i) in errors" class="alert alert-warning" role="alert">
        An error occured: {{error}}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <!-- inputs -->
      <attachment-inputs :aid="aid" mode="upload"></attachment-inputs>

      <!-- Name HERE -->
      <div class="input text required">
        <label for="name">Name</label>
        <input v-model="name"  type="text" name="name" class="form-control" id="name" />
      </div>

      <!-- EMBED CODE HERE -->
      <div class="input text required">
        <label for="embed">Embed code</label>
        <textarea v-model="embed" name="embed" class="form-control" id="embed" rows="5"></textarea>
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
import { client } from '../http/client.js'

// vue components
import AttachmentInputs from './AttachmentInputs.vue'

export default
{
  name: 'attachment-embed',
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
      errors: [],
      name: '',
      embed: ''
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
  methods: {
    upload()
    {
      // check if name
      if(this.name.trim() == '') return this.errors.push('A name is required!')

      // check if embed
      if(this.embed.trim() == '') return this.errors.push('Embed code is required!')

      this.uploading = true

      let formData = new FormData()
      for( let i in this.inputs ) formData.append(i, this.inputs[i])
      for( let t in this.atags ) formData.append('atags['+t+'][name]', this.atags-[t])
      formData.append('name', this.name.trim())
      formData.append('embed', this.embed.trim())
      formData.append('uuid', this.settings.uuid)

      let params = {
        headers: {'Accept': 'application/json', 'Content-Type': 'multipart/form-data'},
        progress: this.progressHandler
      }

      client.post(this.settings.url+'attachment/attachments/add', formData, params)
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
      this.finish()
    },
    errorUploadCb: function(response)
    {
      this.uploading = false
      this.errors.push(response)
      this.finish()
    },
    finish: function()
    {
      if(this.errors.length == 0)
      {
        this.$parent.mode = 'browse'
        this.$store.set(this.aid + '/aParams', Object.assign(this.$store.get(this.aid + '/aParams'),{ refresh: new Date().getTime() }))
      }
    },
  }
}
</script>
