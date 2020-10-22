<template>
  <component :is="componentInstance" v-bind="attributes" />
</template>
<script>
export default {
  name: 'attachment-loader',
  props: {
    name: { type: String, default: 'null' },
    props: { type: String }
  },
  computed:
  {
    componentInstance ()
    {
      if (this.name == 'null') {
        return null
      }
      const name = this.camelize(this.name.substring(this.name.indexOf('-') + 1))
      return () => import(/* webpackChunkName: "[request]" */ `./${name}.vue`)
    },
    attributes()
    {
      let baseObj = JSON.parse(this.props);
      let obj = {}
      for(let i in baseObj)
      {
        if(i.substr(0,1) == ':')
        {
          let prop = i.substr(1)
          obj[prop] = baseObj[i]
        }
        else obj[i] = baseObj[i]
      }
      console.log(obj);
      return obj
    }
  },
  methods:
  {
    camelize(str) {
      return str.replace(/(?:^\w|[A-Z]|-|\b\w)/g, function(word, index)
      {
        return word.toUpperCase();
      }).replace(/\s+/g, '');
    }
  }
}
</script>
