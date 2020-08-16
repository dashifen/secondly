import Vue from "vue"
import RecordForm from "./components/record-form.vue";

export default {
  initialize() {
    new Vue({
      components: {RecordForm},
      el: '#vue-root'
    })
  }
}

