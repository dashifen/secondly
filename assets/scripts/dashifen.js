import RecordForm from "./record-form.js";
import RecordRow from "./record-row.js";

document.addEventListener('DOMContentLoaded', () => {
  const html = document.getElementsByTagName('html')[0];
  html.classList.remove('no-js');
  html.classList.add('js');

  RecordForm.initialize();
  RecordRow.initialize();
});
