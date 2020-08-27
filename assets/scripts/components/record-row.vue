<script>
import RecordForm from "./record-form.vue";

export default {
  name: "record-row",
  props: ["row", "jsonRecord", "jsonProjects", "jsonTasks", "nonce"],
  components: {RecordForm},

  data() {
    return {
      record: JSON.parse(this.jsonRecord),
      formInvisible: false
    }
  },

  computed: {
    rowId() {
      return 'row-' + this.row;
    }
  }
};
</script>

<template>
  <tbody>
  <tr class="data-row" :class="{ hidden: formInvisible }">
    <td headers="col-date" :headers="rowId" v-text="record.date"></td>
    <td headers="col-start" :headers="rowId" v-text="record.start"></td>
    <td headers="col-end" :headers="rowId" v-text="record.end"></td>
    <th headers="col-activity" :id="rowId" scope="row" v-text="record.activity"></th>
    <td headers="col-project" :headers="rowId" v-text="record.project"></td>
    <td headers="col-task" :headers="rowId" v-text="record.task"></td>
    <td headers="col-controls" :headers="rowId">
      <button type="button" @click.prevent="formInvisible = !formInvisible">Edit</button>
    </td>
  </tr>
  <tr class="form-row" :class="{ hidden: !formInvisible }">
    <td colspan="7">
      <record-form
          ajax="true"
          :record-id="record.id"
          :json-projects='jsonProjects'
          :json-tasks='jsonTasks'
          :json-values="jsonRecord"
          :nonce='nonce'
      ></record-form>
    </td>
  </tr>
  </tbody>
</template>
