<template lang="html">
  <div id="attachment-trumbowyg-options" class="modal-mask" v-if="show" transition="modal">
    <div class="modal-wrapper">
      <div class="modal-container container">
        <div class="custom-modal-header">
          Choose options
        </div>
        <div class="custom-modal-body">

          <div class="row">

            <!-- col 1 -->
            <div class="col-md-6">
              <attachment :aid="aid" mode="thumb" :attachment="file" ></attachment>
            </div>

            <!-- ACTION -->
            <div class="col-md-6">
              <select v-if="file.type == 'image'" v-model="selection.displayAs" class="form-control">
                <option value="Link">Link</option>
                <option value="Image">Image</option>
              </select>


              <!-- LINK -->
              <div v-if="selection.displayAs == 'Link'">
                <!-- link title -->
                <div class="input">
                  <div class="utils--spacer-mini"></div>
                  <label>Target</label>
                  <select v-model="selection.target" class="form-control">
                    <option value="_blank">New window</option>
                    <option value="_self">Default</option>
                  </select>
                  <div class="utils--spacer-mini"></div>
                  <label>Title</label>
                  <input type="text" class="form-control" v-model="selection.title" placeholder="Title">
                </div>
              </div>

              <!-- IMAGE -->
              <div v-if="selection.displayAs == 'Image'">
                <div class="row">

                  <div class="col-md-6">
                    <!-- align -->
                    <div class="input select">
                      <label>Align</label>
                      <select v-model="selection.align" class="form-control">
                        <option value="normal">Normal</option>
                        <option value="left">Left</option>
                        <option value="center">Center</option>
                        <option value="right">Right</option>
                      </select>
                    </div>
                    <!-- classes -->
                    <div class="input">
                      <label>Extra styles</label>
                      <input type="text" class="form-control" v-model="selection.classes" placeholder="classes">
                    </div>
                  </div>

                  <div class="col-md-6">
                    <!-- width -->
                    <div class="input">
                      <label>Width</label>
                      <input type="number" class="form-control" v-model="selection.width" min="0" step="5" :max="(file.width < 1200)? file.width: 1200" >
                    </div>
                    <!-- crop -->
                    <div v-if="selection.width" class="input">
                      <label>Crop</label>
                      <div class="clearfix"></div>
                      <input type="checkbox" class="" v-model="selection.crop" value="0">
                      Yes / no
                    </div>
                    <!-- crop settings-->
                    <div v-if="selection.width && selection.crop" class="input">
                      <label>Ratio</label>
                      <div class="clearfix"></div>
                      <input type="number" v-model="selection.cropWidth" min="1" step="1" max="32" value="16">:<input type="number" v-model="selection.cropHeight" min="1" step="1" max="32" value="9">
                    </div>
                  </div>

                </div>
              </div>

            </div>


          </div>

        </div>
        <p></p>
        <div class="custom-modal-footer">
          <div class="btn-group">
            <button type="button" class="modal-default-button btn btn-fill btn-warning" @click="close()">
              Close
            </button>
            <button type="button" class="modal-default-button btn btn-fill btn-success" @click="success()">
              Insert
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Attachment from './Attachment.vue'

export default
{
  name: 'attachment-inline-options',
  components:{ Attachment },
  props: {
    file: Object,
    settings: Object,
    aid: String
  },
  data: function(){ return {
    show: (this.$parent.mode == 'editor-options'),
    options: {
      crop: [true,false],
      align: ['normal','left','center','right'],
      displayAs: ['Image', 'Link'],
      target: ['_blank', '_self']
    },
    selection:{}
  }},
  created: function()
  {
    //YO
  },
  watch:
  {
    file: function (val)
    {
      if(!val) return
      this.selection = {
        displayAs:'Link',
        target: '_blank',
        title: val.title? val.title: val.name,
        align: 'normal',
        width: null,
        crop: 0
      }
    },
    show: function()
    {
      if(this.show){
        this.addEventListeners()
      }else{
        this.removeEventListeners()
      }
    }
  },
  methods:
  {
    addEventListeners : function()
    {
      $(document).bind('keypress', this.preventEnter)
      $('form').bind('submit', this.preventSubmit)
    },
    removeEventListeners : function()
    {
      $(document).unbind('keypress', this.preventEnter)
      $('form').unbind('submit', this.preventSubmit)
    },
    preventEnter: function(e)
    {
      if(e.which == 13) this.preventSubmit(e)
    },
    preventSubmit: function(e)
    {
      e.preventDefault()
      e.stopPropagation()
    },
    close: function()
    {
      this.removeEventListeners()
      this.$parent.mode = 'hidden'
    },
    success: function()
    {
      this.close()
      this.$parent.$emit('options-success', this.file, this.selection)
    }
  }
}
</script>
