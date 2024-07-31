import { asset, fetcher } from '@/utils';

import { tooltipStore as store } from '../state/tooltipStore';
import { pinTooltipToCursorPosition } from './pinTooltipToCursorPosition';
import { renderTooltip } from './renderTooltip';

/**
 * Fetch the HTML content for a dynamic tooltip and then display it
 * attached to the given anchor element.
 *
 * For performance reasons, this function also manages the caching of
 * fetched content to avoid refetching the same content multiple times on
 * the same page. If the content for a given tooltip is already in the cache,
 * it is displayed immediately. If not, a loading spinner is displayed until the
 * content is successfully fetched.
 *
 * @param anchorEl The HTML element to anchor the tooltip to.
 * @param type The type of the dynamic tooltip.
 * @param id The ID of the dynamic tooltip.
 * @param context Optional additional context passed to the server when fetching the dynamic tooltip content.
 * @param offsetX Optional X-coordinate for the tooltip's initial position.
 * @param offsetY Optional Y-coordinate for the tooltip's initial position.
 */
export async function loadDynamicTooltip(
  anchorEl: HTMLElement,
  type: string,
  id: string,
  context?: unknown,
  offsetX?: number,
  offsetY?: number,
): Promise<void> {
  const cacheKey = `${type}_${id}`;

  // Store the current anchorEl. This helps us avoid potential race
  // conditions if the user quickly moves over multiple dynamic tooltips.
  store.activeAnchorEl = anchorEl;

  // If we already have cached HTML content for this tooltip available,
  // don't refetch the content. Display the cached content instead.
  if (store.dynamicContentCache[cacheKey]) {
    displayDynamicTooltip(anchorEl, store.dynamicContentCache[cacheKey]);
    return;
  }

  // Temporarily show a loading spinner while we're fetching the content.
  const genericLoadingTemplate = /** @html */ `
    <div>
      <div class="flex justify-center items-center w-8 h-8 p-5">
        <img src="${asset('/assets/images/icon/loading.gif')}" alt="Loading">
      </div>
    </div>
  `;
  renderTooltip(anchorEl, genericLoadingTemplate, (offsetX ?? 0) + 12, (offsetY ?? 0) + 12, {
    isBorderless: true,
  });

  // Fetch the content and display it. We add a small timeout to ensure the user
  // isn't just skimming their cursor over the anchor element.
  store.dynamicTimeoutId = setTimeout(async () => {
    const fetchedDynamicContent = await fetchDynamicTooltipContent(type, id, context);

    if (fetchedDynamicContent) {
      store.dynamicContentCache[cacheKey] = fetchedDynamicContent;

      // We don't want to continue on with displaying this dynamic tooltip
      // if a static tooltip is opened while we're fetching data.
      const wasTimeoutCleared = !store.dynamicTimeoutId;
      if (anchorEl === store.activeAnchorEl && !wasTimeoutCleared && store.isHoveringOverAnchorEl) {
        renderTooltip(anchorEl, fetchedDynamicContent, offsetX, offsetY);
        pinTooltipToCursorPosition(
          anchorEl,
          store.tooltipEl,
          store.trackedMouseX,
          (store.trackedMouseY ?? 0) - 12, // The tooltip appears to jump if we don't do this subtraction.
        );
      }
    }
  }, 200);
}

/**
 * Fetch dynamic tooltip content from the server.
 *
 * This function sends a POST request to the server with the provided
 * type, ID, and optional context, and returns the tooltip content as an HTML string.
 *
 * @param type The type of the dynamic tooltip.
 * @param id The ID of the dynamic tooltip.
 * @param context Optional additional context passed to the server when fetching the dynamic tooltip content.
 * @returns A promise resolving to an HTML string containing the tooltip content.
 */
async function fetchDynamicTooltipContent(type: string, id: string, context?: unknown) {
  let bodyString = `type=${type}&id=${id}`;
  if (context) {
    bodyString += `&context=${context}`;
  }

  const contentResponse = await fetcher<{ html: string }>('/request/card.php', {
    method: 'POST',
    body: bodyString,
  });

  return contentResponse.html;
}

/**
 * Display a dynamic tooltip for the given anchor element with the provided HTML content.
 *
 * @param anchorEl The HTML element to anchor the tooltip to.
 * @param htmlContent The HTML content to be displayed in the tooltip.
 */
function displayDynamicTooltip(anchorEl: HTMLElement, htmlContent: string) {
  renderTooltip(
    anchorEl,
    htmlContent,
    store.trackedMouseX ? 10 : undefined,
    store.trackedMouseY ? 8 : undefined,
  );
}
