/**
 * Track a custom Plausible event on click. Optionally accepts custom properties to decorate
 * the event with more specificity (see example).
 *
 * @example
 * className={buildTrackingClassNames('Download Patch File', { md5: hash.md5 })}
 * className={`px-3 py-4 ${buildTrackingClassNames('Download Patch File', { md5: hash.md5 })}`}
 */
export function buildTrackingClassNames(
  customEventName: string,
  customProperties?: Record<string, string | number | boolean>,
) {
  // Something has gone wrong. Bail.
  if (customEventName.trim() === '') {
    console.warn('buildTrackingClassNames() was called with an empty customEventName.');

    return '';
  }

  const classNames: string[] = [];

  // Format the custom event name how Plausible expects.
  // "My Custom Event" --> "My+Custom+Event"
  const formattedEventName = `plausible-event-name=${customEventName.replace(/\s+/g, '+')}`;
  classNames.push(formattedEventName);

  // Add each custom property. Spaces here must be replaced with plus signs, too.
  if (customProperties) {
    for (const [key, value] of Object.entries(customProperties)) {
      const formattedValue = `${value}`.replace(/\s+/g, '+');
      classNames.push(`plausible-event-${key}=${formattedValue}`);
    }
  }

  return classNames.join(' ');
}
