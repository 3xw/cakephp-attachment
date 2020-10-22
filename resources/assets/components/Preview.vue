<template lang="html">
  <div class="block-attachment--modal" v-if="open && attachment.id">
    <div @click="open = false" class="block-attachment__close">
      <i class="material-icons"> clear </i>
    </div>
    <div class="block-attachment__content">
      <img v-if="attachment.type == 'image'" :src="attachment.url" class="img-fluid">
      <video v-else-if="attachment.type == 'video'" :src="attachment.url" controls class="img-fluid"></video>
      <div v-else-if="attachment.type == 'embed'">{{attachment.embed}}</div>
      <iframe frameborder="0" v-else-if="attachment.subtype == 'pdf'" :src="attachment.url" class="w-100"></iframe>
    </div>
  </div>
</template>
<script>
export default {
  name: 'preview',
  props: {aid: String},
  data()
  {
    return {
      open: false
    }
  },
  computed:
  {
    attachment()
    {
      return this.$store.get(this.aid + '/preview')
    }
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
    close()
    {
      this.open = false
      this.$store.set(this.aid + '/infos', {})
    }
  }
}
</script>
