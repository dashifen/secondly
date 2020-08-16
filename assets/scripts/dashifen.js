import RecordForm from './record-form.js';

document.addEventListener('DOMContentLoaded', () => {
  const html = document.getElementsByTagName('html')[0];
  html.classList.remove('no-js');
  html.classList.add('js');

  RecordForm.initialize();
});
