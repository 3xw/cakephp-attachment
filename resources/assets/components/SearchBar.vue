<template>
    <div>
      <label class="sr-only" for="inlineFormInputGroup">Username</label>
      <div class="input-group mb-2">
        <input v-model="needle" @keyup.enter="search" type="text" class="form-control" id="inlineFormInputGroup" placeholder="RECHERCHE">
        <div type="submit" class="input-group-append bg--blue-light">
          <div @click="search" class="input-group-text bg--blue-light">
            <icon-search></icon-search>
          </div>
        </div>

        <div class="">
          <el-date-picker
            v-model="daterange"
            type="daterange"
            align="right"
            start-placeholder="Start Date"
            end-placeholder="End Date">
          </el-date-picker>
        </div>

      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapGetters, mapActions } from 'vuex'
import iconSearch from '@/components/icons/search.vue'
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
      daterange: ''
    }
  },
  watch:{
    daterange(value)
    {
      if(!Array.isArray(value)) this.$store.set(this.aid + '/aParams', Object.assign(this.$store.get(this.aid + '/aParams'),{ date: '', page: 1 }))
      else
      {
        for(let i in value) value[i] = moment(value[i]).format('YYYY-MM-DD')
        this.$store.set(this.aid + '/aParams', Object.assign(this.$store.get(this.aid + '/aParams'),{ date: value.join(','), page: 1 }))
      }
    }
  },
  methods:
  {
    search()
    {
      this.$store.set(this.aid + '/aParams', Object.assign(this.$store.get(this.aid + '/aParams'),{ search: this.needle, page: 1 }))
    }
  }
}
</script>
