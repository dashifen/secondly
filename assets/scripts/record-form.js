import Vue from "vue";
import Vuex from "vuex";
import { getVuexStore } from "./get-vuex-store.js";
import RecordForm from "./components/record-form.vue";

Vue.use(Vuex);

export default {
  initialize() {
    const store = getVuexStore();

    new Vue({
      el: '#record-form',
      components: {RecordForm},
      store
    });
  }
};

