import { buildTrackingClassNames } from './buildTrackingClassNames';

// It isn't necessary log the warning for an empty customEventName.
global.console.warn = vi.fn();

describe('Util: buildTrackingClassNames', () => {
  it('is defined', () => {
    // ASSERT
    expect(buildTrackingClassNames).toBeDefined();
  });

  it('given only an event name is provided, returns a single classname', () => {
    // ACT
    const result = buildTrackingClassNames('Download Patch File Click');

    // ASSERT
    expect(result).toEqual('plausible-event-name=Download+Patch+File+Click');
  });

  it('given an event name and a property are provided, returns multiple classnames', () => {
    // ACT
    const result = buildTrackingClassNames('Download Patch File Click', { md5: 'abc123' });

    // ASSERT
    expect(result).toEqual(
      'plausible-event-name=Download+Patch+File+Click plausible-event-md5=abc123',
    );
  });

  it('correctly handles multiple properties with different types', () => {
    // ACT
    const result = buildTrackingClassNames('Submit Form', {
      id: 123,
      success: true,
      description: 'User Submitted Form',
    });

    // ASSERT
    expect(result).toEqual(
      'plausible-event-name=Submit+Form plausible-event-id=123 plausible-event-success=true plausible-event-description=User+Submitted+Form',
    );
  });

  it('returns an empty string when neither event name nor properties are provided', () => {
    // ACT
    const result = buildTrackingClassNames('');

    // ASSERT
    expect(result).toEqual('');
  });
});
