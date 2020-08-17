<script>
export default {
  name: 'select-with-other',

  props: ['options', 'name', 'original'],

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
    }
  }
};
</script>

<template>
  <li class="select-with-other">
    <label :for="name" v-text="ucfirst(name)" class="required"></label>
    <!--suppress HtmlFormInputWithoutLabel -->
    <select :id="name" :name="selectName" :aria-controls="otherId" v-model="value" aria-required="true" required>
      <option value=""></option>
      <option v-for="option in optArray" :value="option.value" v-text="option.text" :selected="option.value = value"></option>
      <option value="other">Other...</option>
    </select>
    <label :class='{ "visually-hidden": hideOther }' aria-live="polite">
      <span class="screen-reader-text" v-text="otherLabel"></span>
      <input type="text" :id="otherId" :name="otherName" :placeholder="otherPlaceholder" :aria-required="hideOther ? 'false' : 'true'" :required="!hideOther">
    </label>
  </li>
</template>
