import { UAParser } from 'ua-parser-js';

export function getIsMobileIos(userAgent?: string) {
  let wasMobileIosDetected = false;

  if (userAgent || 'userAgent' in navigator) {
    const { getOS, getDevice } = new UAParser(userAgent ?? navigator.userAgent);

    const { name: osName } = getOS();
    const { type: deviceType } = getDevice();

    if (osName === 'iOS' && deviceType === 'mobile') {
      wasMobileIosDetected = true;
    }
  }

  return wasMobileIosDetected;
}
