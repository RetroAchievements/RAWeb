import { UAParser } from 'ua-parser-js';

let wasMobileIosDetected: boolean | null = null;

function detectMobileIos() {
  if ('userAgent' in navigator) {
    const { getOS, getDevice } = new UAParser(navigator.userAgent);

    const { name: osName } = getOS();
    const { type: deviceType } = getDevice();

    if (osName === 'iOS' && deviceType === 'mobile') {
      wasMobileIosDetected = true;
    } else {
      wasMobileIosDetected = false;
    }
  }
}

function handleMouseOver(tipDomContent: string) {
  /**
   * wz_tooltip.js causes issues with touch events on
   * iOS devices. Android devices are okay.
   * @see https://github.com/RetroAchievements/RAWeb/issues/1365
   */
  if (wasMobileIosDetected === null) {
    detectMobileIos();
  }

  if (wasMobileIosDetected === false && window.Tip) {
    window.Tip(tipDomContent);
  }
}

export const mobileSafeTipEvents = {
  mouseOver: handleMouseOver,
};
