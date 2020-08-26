<script>
export default {
  name: 'select-with-other',

  props: ['options', 'groups', 'name', 'original'],

  data: function () {
    return {
      'value': this.original,
      'optArray': JSON.parse(this.options),
    }
  },

  computed: {
    selectName() {
      return this.name + '[id]';
    },

    otherId() {
      return 'other-' + this.name;
    },

    otherName() {
      return this.name + '[other]';
    },

    otherLabel() {
      return this.ucfirst(this.name) + ' Other';
    },

    otherPlaceholder() {
      return 'Enter other ' + this.name;
    },

    hideOther() {
      return this.value !== 'other';
    }
  },

  methods: {
    ucfirst(string) {
      return String(string).charAt(0).toUpperCase() + string.slice(1);
    },

    selected() {
      const mutation = 'set' + this.ucfirst(this.name);
      this.$store.commit(mutation, this.value || 0);
    },

    showGroup(project) {
      const projectState = this.$store.state.project;
      return projectState === 0 || projectState === project;
    }
  }
};
</script>

<template>
  <li class="select-with-other">
    <label :for="name" v-text="ucfirst(name)" class="required"></label>
    <!--suppress HtmlFormInputWithoutLabel -->
    <select v-if="groups" @change.prevent="selected" :name="selectName" :aria-controls="otherId" v-model="value" aria-required="true" required>
      <option value=""></option>
      <optgroup v-for="(tasks, projectId) in optArray" v-show="showGroup(projectId)" :label="tasks.project" :data-project="projectId">
        <option v-for="(option, optionValue) in tasks.tasks" :value="optionValue" v-text="option" :selected="optionValue = value"></option>
      </optgroup>
      <option value="other">Other...</option>
    </select>

    <!--suppress HtmlFormInputWithoutLabel -->
    <select v-else :id="name" @change.prevent="selected" :name="selectName" :aria-controls="otherId" v-model="value" aria-required="true" required>
      <option value=""></option>
      <option v-for="(option, optionValue) in optArray" :value="optionValue" v-text="option" :selected="optionValue = value"></option>
      <option value="other">Other...</option>
    </select>

    <label :class='{ "visually-hidden": hideOther }' aria-live="polite">
      <span class="screen-reader-text" v-text="otherLabel"></span>
      <input type="text" :id="otherId" :name="otherName" :placeholder="otherPlaceholder" :aria-required="hideOther ? 'false' : 'true'" :required="!hideOther">
    </label>
  </li>
</template>

<style scoped>
  optgroup {
    padding-bottom: 5rem;
  }
</style>
