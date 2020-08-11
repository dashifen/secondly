import Vue from "vue"
import SelectWithOther from "./components/select-with-other.vue";

export default {
  initialize() {
    new Vue({
      components: {SelectWithOther},
      el: '#vue-root'
    })
  }
}

