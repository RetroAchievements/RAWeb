import { setCookie } from './cookie';
import { throttle } from './throttle';

const cachedAwardsExpandButtons: Record<string, HTMLButtonElement> = {};

/**
 * Show the full list of awards and remove the Expand button.
 */
const handleExpandAwardsClick = (event: MouseEvent) => {
  const buttonEl = event.target as HTMLButtonElement;
  const awardsContainerEl = buttonEl.parentElement?.querySelector('.component') as HTMLElement;

  awardsContainerEl.style.setProperty('max-height', '100000px');
  awardsContainerEl.style.setProperty('-webkit-mask-image', '');
  awardsContainerEl.style.setProperty('mask-image', '');
  awardsContainerEl.classList.remove('awards-fade');

  delete cachedAwardsExpandButtons[buttonEl.id];
  buttonEl.remove();
};

/**
 * @param event The scroll event
 * @param expandButtonId The ID for the expand button of
 * this awards section.
 *
 * Based on the user's scroll position, we will adjust the "shadow"
 * on the top and bottom of the awards container div. This provides
 * a helpful contextual clue that scrolling is available in the given
 * direction.
 */
const onAwardsScroll = (event: UIEvent, expandButtonId: string) => {
  const awardsContainerEl = event.target as HTMLDivElement;
  const minimumContainerScrollPosition = awardsContainerEl.offsetHeight;
  const userCurrentScrollPosition = awardsContainerEl.scrollTop + awardsContainerEl.offsetHeight;

  const newTopFadeOpacity = 1.0 - Math.min((userCurrentScrollPosition - minimumContainerScrollPosition) / 120, 1.0);
  const newBottomFadeOpacity = 1.0 - Math.min((awardsContainerEl.scrollHeight - userCurrentScrollPosition) / 120, 1.0);

  // It is better to not be constantly querying the whole DOM tree for this
  // element. Generally, the tree is going to be huge if this is needed
  // in the first place, so repeated queries are quite slow.
  let expandButtonEl = cachedAwardsExpandButtons[expandButtonId];
  if (!expandButtonEl) {
    cachedAwardsExpandButtons[expandButtonId] = document.getElementById(expandButtonId) as HTMLButtonElement;
    expandButtonEl = cachedAwardsExpandButtons[expandButtonId];
  }

  if (userCurrentScrollPosition >= awardsContainerEl.scrollHeight - 100) {
    expandButtonEl.style.setProperty('opacity', '0');
  } else {
    expandButtonEl.style.setProperty('opacity', '100');
  }

  // When the button is clicked, it is to be removed from the DOM.
  // We don't want the fade to ever be applied if all awards are in view.
  if (expandButtonEl) {
    const opacityGradient = `linear-gradient(
      to bottom,
      rgba(0, 0, 0, ${newTopFadeOpacity}),
      rgba(0, 0, 0, 1) 120px calc(100% - 120px),
      rgba(0, 0, 0, ${newBottomFadeOpacity})
    )`;
    awardsContainerEl.style.setProperty('-webkit-mask-image', opacityGradient);
    awardsContainerEl.style.setProperty('mask-image', opacityGradient);
  }
};

/**
 * A user may prefer to always see fully-expanded awards.
 * Set a cookie and inform them their preference was saved.
 */
const saveExpandableAwardsPreference = (
  cookieName: string,
  shouldAlwaysExpand: boolean
) => {
  setCookie(cookieName, String(shouldAlwaysExpand));
  alert('Saved!');
};

/**
 * Determines whether to apply the awards group fade and show the
 * expand button based on the difference between the container's true
 * height and the rendered height in the user's browser. This executes
 * after an optimistic check for this runs on the server. This follow-up
 * check gives us greater precision on when to show the expand and fade.
 */
const shouldApplyAwardsGroupFade = (
  awardsContainerId: string,
  awardsExpandButtonId: string,
  awardsFadeClassName: string
) => {
  const awardsContainerEl = document.getElementById(awardsContainerId) as HTMLElement;
  const awardsExpandButtonEl = document.getElementById(awardsExpandButtonId) as HTMLElement;

  const renderedContainerHeight = awardsContainerEl.clientHeight;
  const trueContainerHeight = awardsContainerEl.scrollHeight;

  if (renderedContainerHeight < trueContainerHeight) {
    awardsContainerEl.classList.add(awardsFadeClassName);
    awardsExpandButtonEl.classList.remove('hidden');
  } else {
    awardsContainerEl.classList.remove(awardsFadeClassName);
    awardsExpandButtonEl.classList.add('hidden');
  }
};

export const expandableAwards = {
  handleExpandAwardsClick,
  saveExpandableAwardsPreference,
  shouldApplyAwardsGroupFade,
  handleAwardsScroll: throttle(onAwardsScroll, 25)
};
