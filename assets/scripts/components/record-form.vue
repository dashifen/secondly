<!--suppress JSUnresolvedVariable -->
<script>
import Axios from "axios";
import SelectWithOther from "./select-with-other.vue";

export default {
  name: "record-form",
  components: {SelectWithOther},
  props: ["recordId", "destination", "action", "jsonValues", "jsonProjects",
    "jsonTasks", "nonce"],

  data() {
    const values = JSON.parse(this.jsonValues);

    return {
      "date": values.date || null,
      "start": values.start || null,
      "end": values.end || null,
      "activity": values.activity || null,
      "project": values.project || null,
      "task": values.task || null
    };
  },

  methods: {
    convertDate() {
      this.convert("?action=convert-date&date=" + this.date, "date");
    },

    convert(qs, property) {
      Axios.get(window.ajaxUrl + qs).then((response) => {
        this[property] = response.data.conversion;
      });
    },

    convertStartTime() {
      this.convert("?action=convert-time&time=" + this.start, "start");
    },

    convertEndTime() {
      this.convert("?action=convert-time&time=" + this.end, "end");
    }/*,

    submitRecord(event) {
      const elements = Array
        .from(event.target.form.elements)
        .filter((element) => !!element.name);

      const data = new FormData();
      elements.forEach((element) => {
        data.append(element.name, element.value);
      });



    }*/
  }
};
</script>

<template>
  <form method="post" :action="destination">
    <fieldset class="add-record">
      <legend>Add Record</legend>
      <ol>
        <li>
          <label for="date" class="required">Date</label>
          <input v-model="date" type="text" id="date" name="date" aria-required="true" required @change="convertDate">
        </li>
        <li>
          <label for="start" class="required">Start</label>
          <input v-model="start" type="text" id="start" name="start" aria-required="true" required
              @change="convertStartTime">
        </li>
        <li>
          <label for="end" class="required">End</label>
          <input v-model="end" type="text" id="end" name="end" aria-required="true" required @change="convertEndTime">
        </li>
        <li>
          <label for="activity" class="required">Activity</label>
          <input v-model="activity" type="text" id="activity" name="activity" aria-required="true" required>
        </li>
        <li is="select-with-other" :options="jsonProjects" name="project" :original="project"></li>
        <li is="select-with-other" :options="jsonTasks" name="task" :original="task"></li>
      </ol>

      <button type="submit">Save Record</button>
      <input type="hidden" name="recordId" v-model="recordId">
      <input type="hidden" name="action" v-model="action">
      <span v-html="nonce"></span>
    </fieldset>
  </form>
</template>
