<template>
  <div  id="attachment-options">
    <div class="row optional-fields">

      <!-- _translations[en_GB][name] -->
      <div v-if="settings.i18n.enable" class="col-md-6 attachment-locale-area">
        <ul class="nav nav-tabs" role="tablist">
          <li v-for="(language, index) in settings.i18n.languages" :class="{ 'active': language ==  settings.i18n.defaultLocale}" role="presentation">
            <a :href="'#a-'+language" :aria-controls="'a-'+language" role="tab" data-toggle="tab" >
              {{language}}
            </a>
          </li>
        </ul>
        <div class="tab-content">

          <!-- defaultLocale -->
          <div role="tabpanel" class="tab-pane active" :id="'a-'+settings.i18n.defaultLocale">
            <div class="input text">
              <label for="title">Title</label>
              <input @change="update" v-model="file.title" type="text" name="title" class="form-control" id="title">
            </div>
            <div class="input text">
              <label for="date" class="mb-0">Date de prise / création de l’image (si connue)</label>
              <label for="date" class="small mt-0">(format: 2020-12-12, 12:12)</label>
              <input @change="update" v-model="file.date" type="datetime-local" name="date" class="form-control" id="date">
            </div>
            <div class="input text">
              <label for="description">Description</label>
              <textarea @change="update" v-model="file.description" name="description" class="form-control" id="description" rows="5"></textarea>
            </div>
          </div>

          <!-- other locales -->
          <div v-for="(language, index) in settings.i18n.languages"  v-if="language != settings.i18n.defaultLocale" role="tabpanel" class="tab-pane active" :id="'a-'+language">
            <div class="input text">
              <label :for="'_translations['+language+'][title]'">Title {{language}}</label>
              <input @change="update" v-model="file['_translations'][language].title" type="text" :name="'_translations['+language+'][title]'" class="form-control" :id="'a-'+language+'-title'">
            </div>
            <div class="input text">
              <label :for="'_translations['+language+'][description]'">Description{{language}}</label>
              <textarea @change="update" v-model="file['_translations'][language].description" :name="'_translations['+language+'][description]'" class="form-control" :id="'a-'+language+'-description'" rows="5"></textarea>
            </div>
          </div>

        </div>
      </div>

      <!-- no translate -->
      <div v-if="!settings.i18n.enable" class="col-md-6">
        <div class="input text">
          <label for="title">Title</label>
          <input @change="update" v-model="file.title" type="text" name="title" class="form-control" id="title">
        </div>
        <div class="input text">
          <label for="date" class="mb-0">Date de prise / création de l’image (si connue)</label>
          <label for="date" class="small mt-0">(format: 2020-12-12, 12:12)</label>
          <input @change="update" v-model="file.date" type="datetime-local" name="date" class="form-control" id="date">
        </div>
        <div class="input text">
          <label for="description">Description</label>
          <textarea @change="update" v-model="file.description" name="description" class="form-control" id="description" rows="5"></textarea>
        </div>
      </div>

      <div class="col-md-6">
        <div class="input text">
          <label for="author">Author</label>
          <input @change="update" v-model="file.author" type="text" name="author" class="form-control" id="author">
        </div>
        <div class="input text">
          <label for="copyright">Copyright</label>
          <input @change="update" v-model="file.copyright" type="text" name="copyright" class="form-control" id="copyright">
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default
{
  name:'attachment-inputs',
  props: { aid: String, attachment: Object, mode: String },
  data()
  {
    return {
      inputs: {title: '', date:'', author: '', description: '', copyright: ''},
      file: {}
    }
  },
  computed:
  {
    settings()
    {
      return this.$store.get(this.aid+'/settings')
    },
    upload()
    {
      return this.$store.get(this.aid+'/upload')
    }
  },
  created()
  {
    // init simple object
    if(this.mode == 'upload'){
      Object.assign(this.file, this.inputs)
    }else{
      for(let i = 0;i < this.$parent.attachmentInputs.fields.length;i++){
        let input = this.$parent.attachmentInputs.fields[i]
        let key = input.key
        if(key == 'date'){
          this.file[key] = input.value.split('+')[0]
        }else{
          this.file[key] = input.value
        }
      }
    }

    // init a i18n object
    if(this.settings.i18n.enable)
    {
      this.file['_translations'] = {}
      for(let i in this.settings.i18n.languages) this.file._translations[this.settings.i18n.languages[i]] = this.inputs
    }

    // update if data present
    if(this.attachment) Object.assign(this.file, this.attachment)

    this.update()
  },
  methods:
  {
    update(event)
    {
      if(this.mode == 'edit'){
        if(event !== undefined){
          let input = this.$parent.attachmentInputs.fields.find(field => field.key == event.target.name)
          input.value = event.target.value
          input.hasChange = true
        }
      }else{
        if(!this.attachment) this.$store.set(this.aid + '/upload', Object.assign(this.$store.get(this.aid + '/upload'),{ inputs: this.file }))
      }
    },
  }
}
</script>
