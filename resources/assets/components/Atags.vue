<template lang="html">
  <section class="section-attachment--atags">
    <ul class="list-unstyled section-attachment__list" v-if="atagTypes">
      <li v-for="(filter, i) in filters" v-if="!upload && !visibility.hiddenValues.filters.includes(filter.slug)">
        <div class="section-attachment__list-title d-flex flex-row justify-content-between">
          <p class="text--upper mb-0 color--grey-light-text">{{filter.label}}</p>
        </div>
        <ul class="list-unstyled section-attachment__sublist" ><!-- v-if="filter.isActive" -->
          <li v-for="(option, i2) in filter.options" :key="i2" @click="option.isActive = !option.isActive;$forceUpdate();filterOption(option.slug); " class="d-flex flex-row justify-content-between align-items-center" :class="{active: checkFilterActive(i, i2, option.slug)}">
            {{option.label}} <input type="checkbox" :checked="checkFilterActive(i, i2, option.slug)">
          </li>
        </ul>
      </li>
      <li v-for="(atagType, i) in atagTypes" v-if="!visibility.hiddenValues.atagTypes.includes(atagType.slug)">
        <div class="section-attachment__list-title d-flex flex-row justify-content-between">
          <p class="text--upper mb-0 color--grey-light-text">{{atagType.name}}</p>
        </div>
        <ul class="list-unstyled section-attachment__sublist" > <!--v-if="atagType.isActive"-->
          <li
            v-for="(atag, i2) in atagType.atags"
            v-if="!visibility.hiddenValues.atags.includes(atag.slug)"
            @click="toggle(i, i2)"
            class="d-flex flex-row justify-content-between align-items-center"
            :class="{active: ((upload)? atag.isActive : checkActive(i, i2, atag.name))}">
            {{atag.name}} <input type="checkbox" :checked="(upload)? atag.isActive : checkActive(i, i2, atag.name)">
          </li>
        </ul>
      </li>
    </ul>
  </section>
</template>
<script>
import { mapState, mapGetters, mapActions } from 'vuex'
export default
{
  name:'attachment-atags',
  props: { aid: String, upload:Boolean, filters: Array, options: Object },
  data(){
    return {
      visibility: {
        default: {
          visible: true,
          model: '*',
          slug: '*',
          atags: '*',
          types: '*',
          filters: '*'
        },
        hiddenValues: {
          filters: [],
          atagTypes: [],
          atags: []
        }
      }
    }
  },
  computed:
  {
    atagTypes()
    {
      return this.$store.get(this.aid + '/atags/list').map((v, idx) => {
        let atags = v.atags.map((v2, idx2) => Object.assign({index: idx2}, v2))
        return Object.assign({index: idx}, v, {atags: atags, isActive: false})
      })
    },
    aParams()
    {
      return this.$store.get(this.aid + '/aParams')
    },
    visibilityParams()
    {
        let visibilityParams = [this.visibility.default]
        for(let i = 0;i < this.options.visibility.length;i++){
          let defaultParam = Object.assign({}, this.visibility.default)
          visibilityParams.push(Object.assign(defaultParam, this.options.visibility[i]))
        }
        return visibilityParams
    },
  },
  watch: {
    aParams:
    {
      handler(){
        this.checkForHiddenOptions()
        this.removeTagsActived()
        this.checkAllActived()
      },
      deep: true
    }
  },
  created()
  {

  },
  mounted()
  {
    this.checkForHiddenOptions()
  },
  methods:
  {
    toggle(index1,index2)
    {
      // test exclusive
      if(this.atagTypes[index1].exclusive && this.upload) for(let i in this.atagTypes[index1].atags) if(i != index2) this.atagTypes[index1].atags[i].isActive = false

      // toogle
      if(!this.atagTypes[index1].atags[index2].isActive) this.atagTypes[index1].atags[index2].isActive = true
      else this.atagTypes[index1].atags[index2].isActive = false

      // force render
      this.$forceUpdate()

      // loop
      let atags = []
      for(let i1 in this.atagTypes)for(let i2 in this.atagTypes[i1].atags) if(this.atagTypes[i1].atags[i2].isActive) atags.push(this.atagTypes[i1].atags[i2].name)

      // set upload tags OR fetch attachment by mutating aParams
      this.$store.set(this.aid + '/aParams', Object.assign(this.$store.get(this.aid + '/aParams'),{ atags: atags.join(','), page: 1 }))
      if(this.upload){
        this.$store.set(this.aid + '/upload', Object.assign(this.$store.get(this.aid + '/upload'),{ atags: atags }))
        this.checkForHiddenOptions()
      }
    },
    filterOption(key)
    {
      if(!this.upload){
        let filters = []
        for(let i1 in this.filters)for(let i2 in this.filters[i1].options) if(this.filters[i1].options[i2].isActive) filters.push(this.filters[i1].options[i2].slug)
        this.$store.set(this.aid + '/aParams', Object.assign(this.$store.get(this.aid + '/aParams'),{ filters: filters.join(','), page: 1 }))
      }
    },
    checkActive(index1, index2, atag){
      this.atagTypes[index1].atags[index2].isActive = (this.aParams.atags.split(',').includes(atag) == true)
      return (this.aParams.atags.split(',').includes(atag) == true)
    },
    checkAllActived(){
      for(let index1 = 0;index1 < this.atagTypes.length;index1++){
        for(let index2 = 0;index2 < this.atagTypes[index1].atags.length;index2++){
          this.atagTypes[index1].atags[index2].isActive = (this.aParams.atags.split(',').includes(this.atagTypes[index1].atags[index2].name) == true)
        }
      }
    },
    checkFilterActive(index1, index2, filter){
      this.filters[index1].options[index2].isActive = (this.aParams.filters.indexOf(filter) !== -1)
      return this.aParams.filters.indexOf(filter) !== -1
    },
    removeTagsActived(){
      let hasChange = false
      if(this.visibility.hiddenValues.atagTypes.length > 0){
        hasChange = true
        for(let i = 0;i < this.visibility.hiddenValues.atagTypes.length;i++){
          let hiddenTagType = this.visibility.hiddenValues.atagTypes[i];
          let atagType = this.atagTypes.find( ({ slug }) => slug === hiddenTagType);
          if(atagType){
            for(let y = 0;y < atagType.atags.length;y++){
              atagType.atags[y].isActive = false
            }
          }
        }
      }
      if(hasChange){
        let atags = []
        let comp = this
        setTimeout(function(){
          for(let i1 in comp.atagTypes)for(let i2 in comp.atagTypes[i1].atags) if(comp.atagTypes[i1].atags[i2].isActive) atags.push(comp.atagTypes[i1].atags[i2].name)
          comp.$store.set(comp.aid + '/aParams', Object.assign(comp.$store.get(comp.aid + '/aParams'),{ atags: atags.join(','), page: 1 }))
        }, 0)
      }
    },
    checkForHiddenOptions(){
      if(this.$parent.mode == 'browse'){
        let hiddenValues = this.visibility.hiddenValues
        for(var i = 0;i < this.visibilityParams.length;i++){
          let condition = this.visibilityParams[i]
          let isComplete = 1
          if(condition.atags != '*'){
            for(let y = 0; y < condition.atags.length;y++){
              if(!this.aParams.atags.split(',').includes(condition.atags[y])){
                isComplete = 0
                break;
              }
            }
          }
          if(condition.types != '*' && isComplete){
            for(let y = 0; y < condition.types.length;y++){
              if(!this.aParams.type.split(',').includes(condition.types[y])){
                isComplete = 0
                break;
              }
            }
          }
          if(condition.filters != '*' && isComplete){
            for(let y = 0; y < condition.filters.length;y++){
              if(!this.aParams.filters.split(',').includes(condition.filters[y])){
                isComplete = 0
                break;
              }
            }
          }
          if(isComplete){
            switch(condition.model){
              case '*':
                hiddenValues.filters = []
                hiddenValues.atagTypes = []
                hiddenValues.atags = []
                break;
              case 'Filters':
                if(condition.visible){
                  hiddenValues.filters = hiddenValues.filters.filter(e => e != condition.slug)
                }else{
                  if(hiddenValues.filters.indexOf(condition.slug) == -1) hiddenValues.filters.push(condition.slug)
                }
                break;
              case 'AtagTypes':
                if(condition.visible){
                  hiddenValues.atagTypes = hiddenValues.atagTypes.filter(e => e != condition.slug)
                }else{
                  if(hiddenValues.atagTypes.indexOf(condition.slug) == -1) hiddenValues.atagTypes.push(condition.slug)
                }
                break;
              case 'Atags':
                if(condition.visible){
                  hiddenValues.atags = hiddenValues.atags.filter(e => e != condition.slug)
                }else{
                  for(let i = 0;i < condition.slug.length;i++){
                    if(hiddenValues.atags.indexOf(condition.slug[i]) == -1) hiddenValues.atags.push(condition.slug[i])
                  }
                }
                break;
            }
          }
        }
      }
      this.$forceUpdate()
    }
  }
}
</script>
