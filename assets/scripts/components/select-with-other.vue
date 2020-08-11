<script>
export default {
  name: 'select-with-other',

  props: ['options', 'name', 'original'],

  data: function () {
    return {
      'value': this.original
    }
  },

  computed: {
    otherId() {
      return 'other-' + this.name;
    },

    otherLabel() {
      return this.ucfirst(this.name) + ' Other';
    }
  },

  methods: {
    ucfirst(string) {
      string = String(string);
      return string.charAt(0).toUpperCase() + string.slice(1);
    },

    hideOther() {
      return this.value !== 'other';
    }
  }
};
</script>

<template>
  <li>
    <label>
      <span v-text="ucfirst(name)"></span>
      <select :id="name" :name="name" v-model="value">
        <option v-for="option in options" :value="option.value" v-text="option.text"></option>
        <option value="other">Other...</option>
      </select>
    </label>
    <label :class='{ "swo-hidden": hideOther }'>
      <span class="screen-reader-text" v-text="otherLabel"></span>
      <input type="text" :id="otherId" :name="otherId" >
    </label>
  </li>
</template>

<style scoped>

</style>
