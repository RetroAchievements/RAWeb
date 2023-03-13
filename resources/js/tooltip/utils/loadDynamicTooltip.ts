import { asset, fetcher } from '../../utils';

import { tooltipStore as store } from '../state/tooltipStore';
import { renderTooltip } from './renderTooltip';
import { pinTooltipToCursorPosition } from './pinTooltipToCursorPosition';

export async function loadDynamicTooltip(anchorEl: HTMLElement, type: string, id: string, context?: unknown) {
  const cacheKey = `${type}_${id}`;

  if (!store.dynamicContentCache[cacheKey]) {
    // Temporarily show a loading spinner.
    const genericLoadingTemplate = /* html */ `
      <div>
        <div class="flex justify-center items-center w-8 h-8 p-5">
          <img src="${asset('/assets/images/icon/loading.gif')}" alt="Loading">
        </div>
      </div>
    `;
    renderTooltip(anchorEl, genericLoadingTemplate);

    store.dynamicTimeoutId = setTimeout(async () => {
      const cardResponse = await fetcher<{ html: string }>('/request/card.php', {
        method: 'POST',
        body: `type=${type}&id=${id}&context=${context}`,
      });

      if (cardResponse.html) {
        store.dynamicContentCache[cacheKey] = cardResponse.html;

        // We don't want to continue on with displaying this dynamic tooltip
        // if a static tooltip is opened while we're fetching data.
        const wasTimeoutCleared = !store.dynamicTimeoutId;
        if (!wasTimeoutCleared) {
          renderTooltip(anchorEl, cardResponse.html);
          pinTooltipToCursorPosition(
            anchorEl,
            store.tooltipEl,
            store.trackedMouseX,
            store.trackedMouseY
          );
        }
      }
    }, 200);
  } else {
    renderTooltip(anchorEl, store.dynamicContentCache[cacheKey]);
    pinTooltipToCursorPosition(anchorEl, store.tooltipEl, store.trackedMouseX, store.trackedMouseY);
  }
}
