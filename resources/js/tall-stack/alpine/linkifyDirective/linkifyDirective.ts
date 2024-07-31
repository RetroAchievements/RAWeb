import type { DirectiveData, DirectiveUtilities } from 'alpinejs';
import linkifyHtml from 'linkify-html';

export function linkifyDirective(
  el: HTMLElement,
  _: DirectiveData,
  { effect }: DirectiveUtilities,
) {
  effect(() => {
    el.innerHTML = linkifyHtml(el.innerHTML, {
      formatHref: {
        url: (href) => {
          if (href.toLowerCase().includes('retroachievements.org')) {
            return href;
          }

          return `/redirect?url=${href}`;
        },
      },
    });

    requestAnimationFrame(() => {
      el.classList.add('[&>a]:!text-link');
    });
  });
}
