<template>
  <div>
    <!-- <label class="sr-only" for="inlineFormInputGroup">Username</label> -->
    <div class="d-flex flex-row align-items-center mb-2">
      <!-- Search input group -->
      <div class="input-group" style="flex: 0 0 auto; width: auto;">
        <input v-model="needle" @keydown="enterSearch" type="text" class="form-control"
          id="inlineFormInputGroup" placeholder="RECHERCHE">
        <div type="submit" class="input-group-append bg--blue-light">
          <div @click="search" class="input-group-text bg--blue-light h-100">
            <icon-search></icon-search>
          </div>
        </div>
      </div>

      <!-- Date picker - OUTSIDE input-group -->
      <div class="d-flex flex-row date-picker ml-3">
        <label for="">Du</label>
        <input id="date-start" type="date" v-model="startDate">

        <label for="">au</label>
        <input id="date-end" type="date" v-model="endDate">
      </div>

      <!-- Upload button (when sidebar hidden) -->
      <button v-if="settings && settings.browse && settings.browse.show_sidebar === false && settings.groupActions && settings.groupActions.indexOf('add') != -1"
        type="button"
        class="btn btn--blue color--white d-flex align-items-center ml-auto"
        @click="$parent.$parent.mode = 'upload'">
        <icon-add class="icon-white"></icon-add>&nbsp;&nbsp;Ajouter des fichiers
      </button>

    </div>
  </div>
</template>

<script>
import { mapState, mapGetters, mapActions } from 'vuex'
import iconSearch from './icons/search.vue'
import iconAdd from './icons/add.vue'
import moment from 'moment';

export default
{
  name:'attachment-search-bar',
  components: {
      'icon-search': iconSearch,
      'icon-add': iconAdd
  },
  props: { aid: String, settings: Object },
  data()
  {
    return {
      needle: '',
      endDate: '',
      startDate: ''
    }
  },
  watch:{
    endDate(value){
      this.endDate = moment(value).format('YYYY-MM-DD');
      if(!(this.startDate == '')) { 
        this.daterange();
      }
    },
    startDate(value){
      this.startDate = moment(value).format('YYYY-MM-DD');
      if(!(this.endDate == '')) { 
        this.daterange();
      }
    },
  },
  methods:
  {
    daterange() {
      let value = [this.startDate, this.endDate];
      this.$store.set(this.aid + '/aParams', Object.assign(this.$store.get(this.aid + '/aParams'), { date: value.join(','), page: 1 }))
    },
    enterSearch(e) {
      if (e.keyCode == 13) {
        e.preventDefault()
        e.stopPropagation()
        this.search()
      }
    },
    search()
    {
      this.$store.set(this.aid + '/aParams', Object.assign(this.$store.get(this.aid + '/aParams'),{ search: this.needle, page: 1 }))
    }
  }
}
</script>
