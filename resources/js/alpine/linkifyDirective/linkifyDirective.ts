import linkifyHtml from 'linkify-html';
import type { DirectiveData, DirectiveUtilities } from 'alpinejs';

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

      validate: {
        url: (text) => {
          const sanitized = text.toLowerCase();

          if (sanitized.endsWith('.mp') || sanitized.endsWith('.ar')) {
            return false;
          }

          return true;
        },
      },
    });

    requestAnimationFrame(() => {
      el.classList.add('[&>a]:!text-link');
    });
  });
}
