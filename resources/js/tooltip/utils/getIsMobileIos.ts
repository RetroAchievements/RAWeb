import { UAParser } from 'ua-parser-js';

export function getIsMobileIos(userAgent?: string) {
  let wasMobileIosDetected = false;

  // This conditional is what keeps the function from blowing up in a test setting.
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
