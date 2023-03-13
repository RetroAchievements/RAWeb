import { asset, fetcher } from '../../utils';

import { tooltipStore as store } from '../state/tooltipStore';
import { renderTooltip } from './renderTooltip';
import { pinTooltipToCursorPosition } from './pinTooltipToCursorPosition';

export async function loadDynamicTooltip(
  anchorEl: HTMLElement,
  type: string,
  id: string,
  context?: unknown
): Promise<void> {
  const cacheKey = `${type}_${id}`;

  if (store.dynamicContentCache[cacheKey]) {
    displayDynamicTooltip(anchorEl, store.dynamicContentCache[cacheKey]);
    return;
  }

  // Temporarily show a loading spinner.
  const genericLoadingTemplate = /** @html */`
    <div>
      <div class="flex justify-center items-center w-8 h-8 p-5">
        <img src="${asset('/assets/images/icon/loading.gif')}" alt="Loading">
      </div>
    </div>
  `;
  renderTooltip(anchorEl, genericLoadingTemplate);

  store.dynamicTimeoutId = setTimeout(async () => {
    const fetchedDynamicContent = await fetchDynamicTooltipContent(type, id, context);

    if (fetchedDynamicContent) {
      store.dynamicContentCache[cacheKey] = fetchedDynamicContent;

      // We don't want to continue on with displaying this dynamic tooltip
      // if a static tooltip is opened while we're fetching data.
      const wasTimeoutCleared = !store.dynamicTimeoutId;
      if (!wasTimeoutCleared) {
        renderTooltip(anchorEl, fetchedDynamicContent);
        pinTooltipToCursorPosition(
          anchorEl,
          store.tooltipEl,
          store.trackedMouseX,
          store.trackedMouseY
        );
      }
    }
  }, 200);
}

async function fetchDynamicTooltipContent(type: string, id: string, context?: unknown) {
  const contentResponse = await fetcher<{ html: string }>('/request/card.php', {
    method: 'POST',
    body: `type=${type}&id=${id}&context=${context}`,
  });

  return contentResponse.html;
}

function displayDynamicTooltip(anchorEl: HTMLElement, htmlContent: string) {
  renderTooltip(anchorEl, htmlContent);
  pinTooltipToCursorPosition(anchorEl, store.tooltipEl, store.trackedMouseX, store.trackedMouseY);
}
