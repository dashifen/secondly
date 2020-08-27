import Vuex from "vuex";

export function getVuexStore() {
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
