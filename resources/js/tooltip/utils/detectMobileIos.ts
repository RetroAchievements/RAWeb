import { UAParser } from 'ua-parser-js';

export function detectMobileIos() {
  let wasMobileIosDetected = false;

  if ('userAgent' in navigator) {
    const { getOS, getDevice } = new UAParser(navigator.userAgent);

    const { name: osName } = getOS();
    const { type: deviceType } = getDevice();

    if (osName === 'iOS' && deviceType === 'mobile') {
      wasMobileIosDetected = true;
    }
  }

  return wasMobileIosDetected;
}
