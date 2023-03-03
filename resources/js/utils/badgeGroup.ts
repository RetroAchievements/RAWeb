import { setCookie } from './cookie';
import { throttle } from './throttle';

const cachedBadgeGroupButtons: Record<string, HTMLButtonElement> = {};

const handleSizeToggleButtonClick = (
  event: MouseEvent,
  groupContainerId: string,
  visibleAwardsCount: number
) => {
  const buttonEl = event.target as HTMLButtonElement;
  const isAlreadyExpanded = buttonEl.innerHTML.includes('Collapse');

  if (isAlreadyExpanded) {
    collapse(event, groupContainerId, visibleAwardsCount);
  } else {
    expand(event, groupContainerId);
  }
};

/**
 * Show the full list of badges and remove the Expand button.
 */
const expand = (event: MouseEvent, groupContainerId: string) => {
  const buttonEl = event.target as HTMLButtonElement;
  const groupContainerEl = document.getElementById(groupContainerId);

  // NOTE: You can dynamically remove Tailwind classes, but you cannot dynamically add them.
  if (groupContainerEl) {
    groupContainerEl.classList.remove('max-h-[64vh]', 'lg:max-h-[76vh]', 'group-fade', '!gap-[.5em]');
    groupContainerEl.style.setProperty('margin-bottom', '2.5rem');
    groupContainerEl.style.setProperty('mask-image', '');
    groupContainerEl.style.setProperty('webkit-mask-image', '');

    buttonEl.classList.remove('bottom-4', 'transform', 'hover:-translate-y-1', 'hover:scale-105', 'active:scale-100');
    buttonEl.style.setProperty('bottom', '-0.1rem');
    buttonEl.style.setProperty('right', '10%');
    buttonEl.innerHTML = 'Collapse';
  }

  saveExpandableBadgeGroupsPreference(true);
};

const collapse = (
  event: MouseEvent,
  groupContainerId: string,
  visibleAwardsCount: number
) => {
  const buttonEl = event.target as HTMLButtonElement;
  const groupContainerEl = document.getElementById(groupContainerId);

  // NOTE: You can dynamically remove Tailwind classes, but you cannot dynamically add them.
  if (groupContainerEl) {
    groupContainerEl.classList.add('max-h-[64vh]', 'lg:max-h-[76vh]', 'group-fade', '!gap-[.5em]');
    groupContainerEl.style.setProperty('margin-bottom', '');

    buttonEl.classList.add('bottom-4', 'transform', 'hover:-translate-y-1', 'hover:scale-105', 'active:scale-100');
    buttonEl.style.setProperty('bottom', '');
    buttonEl.style.setProperty('right', '');
    buttonEl.style.setProperty('opacity', '100');
    buttonEl.innerHTML = `Expand (${visibleAwardsCount})`;
  }

  saveExpandableBadgeGroupsPreference(false);
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
 * On expand (or collapse), persist the preference to the user's machine.
 */
const saveExpandableBadgeGroupsPreference = (
  shouldAlwaysExpand: boolean,
  cookieName = 'prefers_always_expanded_badge_groups',
) => {
  setCookie(cookieName, String(shouldAlwaysExpand));
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
  handleSizeToggleButtonClick,
  saveExpandableBadgeGroupsPreference,
  shouldApplyBadgeGroupFade,
  handleBadgeGroupScroll: throttle(onGroupScroll, 25)
};
