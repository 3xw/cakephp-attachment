<template>
  <div>
    <!-- <label class="sr-only" for="inlineFormInputGroup">Username</label> -->
    <div class="input-group mb-2">
      <input v-model="needle" @keydown="enterSearch" type="text" class="form-control"
        id="inlineFormInputGroup" placeholder="RECHERCHE">
      <div type="submit" class="input-group-append bg--blue-light">
        <div @click="search" class="input-group-text bg--blue-light h-100">
          <icon-search></icon-search>
        </div>
      </div>

      <div class="d-flex flex-row date-picker">
        <label for="">Du</label>
        <input id="date-start" type="date" v-model="startDate">

        <label for="">au</label>
        <input id="date-end" type="date" v-model="endDate">
      </div>

    </div>
  </div>
  </div>
</template>

<script>
import { mapState, mapGetters, mapActions } from 'vuex'
import iconSearch from './icons/search.vue'
import moment from 'moment';

export default
{
  name:'attachment-search-bar',
  components: {
      'icon-search': iconSearch
  },
  props: { aid: String },
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
