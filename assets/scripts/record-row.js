import Vue from "vue";
import Vuex from "vuex";
import { getVuexStore } from "./get-vuex-store.js";
import RecordRow from "./components/record-row.vue"

Vue.use(Vuex);

export default {
  initialize() {
    const store = getVuexStore();

    new Vue({
      el: '#records',
      components: {RecordRow},
      store
    });
  }
};
