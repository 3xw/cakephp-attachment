<template lang="html">
  <div class="block-attachment--archive">
    <div class="block-attachment--archive-box" >
      <div class="block-attachment__header">
        Téléchargements
      </div>
      <div class="block-attachment__content">
        <!--<ul class="list-unstyled">
          <li><span><i class="material-icons">folder_open</i>&nbsp; archive-dg3h4f.zip</span> <img src="https://static.wgr.ch/attachment/loading.gif" alt=""></li>
          <li><span><i class="material-icons">folder_open</i>&nbsp; archive-wf842e.zip</span> <i class="material-icons"> cloud_download </i></li>
        </ul>-->
        <ul v-for="archive in archives">
          <span><i class="material-icons">folder_open</i>&nbsp; {{archive.name}}</span> {{archive.status}}</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'attachment-archives',
  props: {
    aid: String,
    settings: Object,
  },
  data()
  {
    return {}
  },
  computed:
  {
    archives()
    {
      return this.$store.get(this.aid + '/aarchives')
    }
  },
  created()
  {
  },
  methods:
  {

  }
}
</script>
