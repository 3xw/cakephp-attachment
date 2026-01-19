<template>
  <div id="attachment-pagination">
    <nav>
      <ul class="pagination">
        <li class="page-item" v-if="start" v-bind:class="{ 'disabled': !pagination.has_prev_page}">
          <a class="page-link" href="#" aria-label="First" @click.prevent="changePage(1)">
            <span aria-hidden="true">première</span>
          </a>
        </li>
        <li class="page-item" v-if="(pagination.current_page-offset-1) > 1">
          <a class="page-link" href="#" aria-label="More" @click.prevent="changePage(pagination.current_page-offset-1)">
            <span aria-hidden="true">&laquo;{{pagination.current_page-offset-1}}</span>
          </a>
        </li>
        <li class="page-item" v-bind:class="{ 'disabled': !pagination.has_prev_page}">
          <a class="page-link" href="#" aria-label="Previous" @click.prevent="changePage(pagination.current_page - 1)">
            <span aria-hidden="true">&laquo;</span>
          </a>
        </li>
        <li class="page-item" v-for="( num, index ) in array" :class="{'active': num == pagination.current_page}">
          <a class="page-link" href="#" @click.prevent="changePage(num)">{{ num }}</a>
        </li>
        <li class="page-item" v-bind:class="{ 'disabled': !pagination.has_next_page}">
          <a class="page-link" href="#" aria-label="Next" @click.prevent="changePage(pagination.current_page + 1)">
            <span aria-hidden="true">&raquo;</span>
          </a>
        </li>
        <li class="page-item" v-if="(pagination.current_page+offset+1) < pagination.page_count">
          <a class="page-link" href="#" aria-label="More" @click.prevent="changePage(pagination.current_page+offset+1)">
            <span aria-hidden="true">{{pagination.current_page+offset+1}} &raquo;</span>
          </a>
        </li>
        <li class="page-item" v-if="end" v-bind:class="{ 'disabled': !pagination.has_next_page}">
          <a class="page-link" href="#" aria-label="Last" @click.prevent="changePage(pagination.page_count)">
            <span aria-hidden="true">dernière</span>
          </a>
        </li>
      </ul>
    </nav>
    <small>
      Page {{pagination.current_page}} sur {{pagination.page_count}} (total: {{pagination.count}})
    </small>
  </div>
</template>

<script>
export default
{
  name: 'attachment-pagination',
  props: {
    aid: String,
    pagination: {
      type: Object,
      required: true
    },
    settings: Object,
  },
  data: function(){
    return {
      lastPage: 1,
      offset: 4,
      start: false,
      end: false,
      from: 1,
      to: 2,
    };
  },
  computed: {
    array: function () {
      this.offset = this.settings.pagination.offset;
      this.start = this.settings.pagination.start;
      this.end = this.settings.pagination.end;
      if(this.pagination.page_count == 1) {
        return [];
      }

      this.from = this.pagination.current_page - Math.floor(this.offset/2);
      if(this.from + this.offset >= this.pagination.page_count ){
        this.from = this.pagination.current_page - (this.offset - (this.pagination.page_count - this.pagination.current_page));
      }
      if(this.from < 1) {
        this.from = 1;
      }

      this.to = this.from + (this.offset );
      if(this.to >= this.pagination.page_count) {
        this.to = this.pagination.page_count;
      }

      var arr = [];
      var i = this.from;
      while (i <=this.to) {
        arr.push(i);
        i++;
      }

      return arr;
    },
    aParams()
    {
      return this.$store.get(this.aid + '/aParams')
    },
  },
  methods: {
    changePage: function (page) {
      this.lastPage = this.pagination.current_page;
      this.pagination.current_page = page;
      this.$store.set(this.aid + '/aParams', Object.assign(this.$store.get(this.aid + '/aParams'),{ page: this.pagination.current_page }))
    }
  }
}
</script>
