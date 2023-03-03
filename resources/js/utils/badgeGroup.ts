import { setCookie } from './cookie';
import { throttle } from './throttle';

const cachedBadgeGroupButtons: Record<string, HTMLButtonElement> = {};

/**
 * Show the full list of badges and remove the Expand button.
 */
const handleExpandGroupClick = (event: MouseEvent, groupContainerId:string) => {
  const buttonEl = event.target as HTMLButtonElement;
  const groupContainerEl = document.getElementById(groupContainerId);

  if (groupContainerEl) {
    groupContainerEl.style.setProperty('max-height', '100000px');
    groupContainerEl.style.setProperty('-webkit-mask-image', '');
    groupContainerEl.style.setProperty('mask-image', '');
    groupContainerEl.classList.remove('group-fade');

    delete cachedBadgeGroupButtons[buttonEl.id];
    buttonEl.remove();
  }
};

/**
 * @param event The scroll event
 * @param expandButtonId The ID for the expand button of
 * this badges section.
 *
 * Based on the user's scroll position, we will adjust the "shadow"
 * on the top and bottom of the group container div. This provides
 * a helpful contextual clue that scrolling is available in the given
 * direction.
 */
const onGroupScroll = (event: UIEvent, expandButtonId: string) => {
  const groupContainerEl = event.target as HTMLDivElement;
  const minimumContainerScrollPosition = groupContainerEl.offsetHeight;
  const userCurrentScrollPosition = groupContainerEl.scrollTop + groupContainerEl.offsetHeight;

  const newTopFadeOpacity = 1.0 - Math.min((userCurrentScrollPosition - minimumContainerScrollPosition) / 120, 1.0);
  const newBottomFadeOpacity = 1.0 - Math.min((groupContainerEl.scrollHeight - userCurrentScrollPosition) / 120, 1.0);

  // It is better to not be constantly querying the whole DOM tree for this
  // element. Generally, the tree is going to be huge if this is needed
  // in the first place, so repeated queries are quite slow.
  let expandButtonEl = cachedBadgeGroupButtons[expandButtonId];
  if (!expandButtonEl) {
    cachedBadgeGroupButtons[expandButtonId] = document.getElementById(expandButtonId) as HTMLButtonElement;
    expandButtonEl = cachedBadgeGroupButtons[expandButtonId];
  }

  if (userCurrentScrollPosition >= groupContainerEl.scrollHeight - 100) {
    expandButtonEl.style.setProperty('opacity', '0');
  } else {
    expandButtonEl.style.setProperty('opacity', '100');
  }

  // When the button is clicked, it is to be removed from the DOM.
  // We don't want the fade to ever be applied if all badges are in view.
  if (expandButtonEl) {
    const opacityGradient = `linear-gradient(
      to bottom,
      rgba(0, 0, 0, ${newTopFadeOpacity}),
      rgba(0, 0, 0, 1) 120px calc(100% - 120px),
      rgba(0, 0, 0, ${newBottomFadeOpacity})
    )`;
    groupContainerEl.style.setProperty('-webkit-mask-image', opacityGradient);
    groupContainerEl.style.setProperty('mask-image', opacityGradient);
  }
};

/**
 * A user may prefer to always see fully-expanded badge groups.
 * Set a cookie and inform them their preference was saved.
 */
const saveExpandableBadgeGroupsPreference = (
  cookieName: string,
  shouldAlwaysExpand: boolean
) => {
  setCookie(cookieName, String(shouldAlwaysExpand));
  alert('Saved!');
};

/**
 * Determines whether to apply the badge group fade and show the
 * expand button based on the difference between the container's true
 * height and the rendered height in the user's browser. This executes
 * after an optimistic check for this runs on the server. This follow-up
 * check gives us greater precision on when to show the expand and fade.
 */
const shouldApplyBadgeGroupFade = (
  groupContainerId: string,
  groupExpandButtonId: string,
  groupFadeClassName: string
) => {
  const groupContainerEl = document.getElementById(groupContainerId) as HTMLElement;
  const groupExpandButtonEl = document.getElementById(groupExpandButtonId) as HTMLElement;

  const renderedContainerHeight = groupContainerEl.clientHeight;
  const trueContainerHeight = groupContainerEl.scrollHeight;

  if (renderedContainerHeight < trueContainerHeight) {
    groupContainerEl.classList.add(groupFadeClassName);
    groupExpandButtonEl.classList.remove('hidden');
  } else {
    groupContainerEl.classList.remove(groupFadeClassName);
    groupExpandButtonEl.classList.add('hidden');
  }
};

export const badgeGroup = {
  handleExpandGroupClick,
  saveExpandableBadgeGroupsPreference,
  shouldApplyBadgeGroupFade,
  handleBadgeGroupScroll: throttle(onGroupScroll, 25)
};
