export function config(key) {
  return window.cfg && window.cfg[key];
}

export function asset(uri) {
  return `${config('assetsUrl')}${uri}`;
}

export function clipboard(text) {
  const proxy = document.createElement('input');
  document.body.appendChild(proxy);
  proxy.value = text;
  proxy.select();
  document.execCommand('copy', false);
  proxy.remove();
  showStatusSuccess('Copied!');
}

export function scrollToGridTop() {
  const scrollTarget = document.querySelector('[data-scroll-target]');
  scrollTo({ top: scrollTarget ? scrollTarget.offsetTop - 90 : 0, behavior: 'smooth' });
}

/*
 * TODO: replace with alpine / livewire
 */
// $('[data-delete]').click(e => {
//   e.preventDefault();
//   const $button = $(e.currentTarget);
//   if ($button.data('url') && confirm($button.data('delete'))) {
//     formSubmit($button.data('url'), 'delete');
//   }
// });
//
// $('[data-submit]').click(e => {
//   e.preventDefault();
//   const $button = $(e.currentTarget);
//   if ($button.data('url') && confirm($button.data('submit'))) {
//     formSubmit($button.data('url'), 'delete');
//   }
// });
