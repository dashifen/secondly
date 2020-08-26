import Vue from "vue";
import Vuex from "vuex";
import RecordForm from "./components/record-form.vue";

Vue.use(Vuex);

export default {
  initialize() {
    const store = getVuexStore();

    new Vue({
      el: '#vue-root',
      components: {RecordForm},
      store
    });
  }
};

function getVuexStore() {
  return new Vuex.Store({
    state: {
      'project': 0,
      'task': 0,
    },

    getters: {
      project(state) {
        return state.project;
      },

      task(state) {
        return state.task;
      }
    },

    mutations: {
      setProject(state, project) {
        state.project = project
      },

      setTask(state, task) {
        state.task = task;
      }
    }
  });
}

