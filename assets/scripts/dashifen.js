import SelectWithOther from './select-with-other';

document.addEventListener('DOMContentLoaded', () => {
  const html = document.getElementsByTagName('html')[0];
  html.classList.remove('no-js');
  html.classList.add('js');

  SelectWithOther.initialize();
});
