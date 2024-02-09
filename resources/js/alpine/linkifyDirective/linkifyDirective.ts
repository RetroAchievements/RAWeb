import linkifyHtml from 'linkify-html';
import type { DirectiveData, DirectiveUtilities } from 'alpinejs';

export function linkifyDirective(
  el: HTMLElement,
  _: DirectiveData,
  { effect }: DirectiveUtilities,
) {
  effect(() => {
    el.innerHTML = linkifyHtml(el.innerHTML);

    requestAnimationFrame(() => {
      el.classList.add('[&>a]:!text-link');
    });
  });
}
