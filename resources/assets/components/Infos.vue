<template lang="html">
  <div class="block-attachment--modal" v-if="open && attachment.id">
    <div @click="open = false" class="block-attachment__close">
      <i class="material-icons"> clear </i>
    </div>
    <div class="block-attachment__content">
      <div class="block block-attachment__infos block--white text-left">
        <ul class="list-unstyled mb-0">
          <li><strong>Nom</strong>: {{attachment.name}}</li>
          <li><strong>Type</strong>: {{attachment.type}}/{{attachment.subtype}}</li>
          <li><strong>Poids</strong>: {{attachment.size | bytesToMegaBytes | decimal(2)}}MB</li>
          <li v-if="attachment.width"><strong>Largeur</strong>: {{attachment.width}}</li>
          <li v-if="attachment.height"><strong>Hauteur</strong>: {{attachment.height}}</li>
          <li><strong>ID</strong>: {{attachment.id}}</li>
          <li v-if="attachment.title"><strong>Title</strong>: {{attachment.title}}</li>
          <li v-if="attachment.date"><strong>Date</strong>: {{attachment.date}}</li>
          <li v-if="attachment.date"><strong>Date d'upload</strong>: {{attachment.created}}</li>
          <li v-if="attachment.description"><strong>Description</strong>: {{attachment.description}}</li>
          <li v-if="attachment.author"><strong>Auteur</strong>: {{attachment.author}}</li>
          <li v-if="attachment.copyright"><strong>Copyright</strong>: {{attachment.copyright}}</li>
          <!--<li v-if="attachment.meta"><strong>Meta</strong>: {{attachment.meta}}</li>-->
        </ul>
      </div>
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
      return this.$store.get(this.aid + '/infos')
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
