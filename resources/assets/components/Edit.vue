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
    edit()
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
      this.$parent.mode = 'browse'
    },
    editError(){
      alert('Une erreur est survenue veuillez réessayer.')
    }
  }
}
</script>
