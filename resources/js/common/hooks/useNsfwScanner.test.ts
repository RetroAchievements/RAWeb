import { __UNSAFE_VERY_DANGEROUS_SLEEP, act, renderHook } from '@/test';

// Mock nsfwjs at the module level. TF.js is imported for side effects
// only, so it doesn't need its own mock.
const mockClassify = vi.fn();
const mockLoad = vi.fn().mockResolvedValue({ classify: mockClassify });
vi.mock('nsfwjs', () => ({ load: (...args: unknown[]) => mockLoad(...args) }));
vi.mock('@tensorflow/tfjs', () => ({}));

function createMockFile(): File {
  return new File(['test'], 'test.png', { type: 'image/png' });
}

/**
 * nsfwjs returns predictions as an array of { className, probability }.
 * This helper builds a full set with sensible defaults.
 */
function buildPredictions(
  overrides: Partial<Record<'Drawing' | 'Hentai' | 'Neutral' | 'Porn' | 'Sexy', number>> = {},
) {
  const defaults = { Drawing: 0.1, Hentai: 0.1, Neutral: 0.6, Porn: 0.1, Sexy: 0.1 };
  const merged = { ...defaults, ...overrides };

  return Object.entries(merged).map(([className, probability]) => ({
    className,
    probability,
  }));
}

describe('Hook: useNsfwScanner', () => {
  beforeEach(() => {
    // jsdom's Image doesn't fire onload. Stub it so the
    // image promise in performScan resolves immediately.
    vi.stubGlobal(
      'Image',
      class MockImage {
        onload: (() => void) | null = null;
        onerror: ((error: unknown) => void) | null = null;

        set src(_value: string) {
          // Simulate the browser firing onload asynchronously.
          queueMicrotask(() => this.onload?.());
        }
      },
    );

    URL.createObjectURL = vi.fn().mockReturnValue('blob:test');
    URL.revokeObjectURL = vi.fn();
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    mockClassify.mockReset();
    mockLoad.mockClear();

    // Reset the module so the cached modelPromise doesn't leak between tests.
    vi.resetModules();
  });

  it('given a Porn score above the threshold, flags the image as NSFW', async () => {
    // ARRANGE
    mockClassify.mockResolvedValueOnce(buildPredictions({ Porn: 0.85 }));

    const { useNsfwScanner } = await import('./useNsfwScanner');
    const { result } = renderHook(() => useNsfwScanner());

    // ACT
    const scanResult = await result.current.scanImage(createMockFile());

    // ASSERT
    expect(scanResult.isNsfw).toEqual(true);
    expect(scanResult.scores?.['Porn']).toEqual(0.85);
  });

  it('given a Hentai score above the threshold, flags the image as NSFW', async () => {
    // ARRANGE
    mockClassify.mockResolvedValueOnce(buildPredictions({ Hentai: 0.75 }));

    const { useNsfwScanner } = await import('./useNsfwScanner');
    const { result } = renderHook(() => useNsfwScanner());

    // ACT
    const scanResult = await result.current.scanImage(createMockFile());

    // ASSERT
    expect(scanResult.isNsfw).toEqual(true);
    expect(scanResult.scores?.['Hentai']).toEqual(0.75);
  });

  it('given all scores are below the threshold, does not flag the image', async () => {
    // ARRANGE
    mockClassify.mockResolvedValueOnce(buildPredictions({ Neutral: 0.8 }));

    const { useNsfwScanner } = await import('./useNsfwScanner');
    const { result } = renderHook(() => useNsfwScanner());

    // ACT
    const scanResult = await result.current.scanImage(createMockFile());

    // ASSERT
    expect(scanResult.isNsfw).toEqual(false);
  });

  it('given the model returns predictions missing Porn and Hentai keys, does not flag the image', async () => {
    // ARRANGE
    mockClassify.mockResolvedValueOnce([
      { className: 'Neutral', probability: 0.9 },
      { className: 'Drawing', probability: 0.1 },
    ]);

    const { useNsfwScanner } = await import('./useNsfwScanner');
    const { result } = renderHook(() => useNsfwScanner());

    // ACT
    const scanResult = await result.current.scanImage(createMockFile());

    // ASSERT
    expect(scanResult.isNsfw).toEqual(false);
  });

  it('given the model fails to load, fails closed and blocks the upload', async () => {
    // ARRANGE
    mockLoad.mockRejectedValueOnce(new Error('Network error'));

    const { useNsfwScanner } = await import('./useNsfwScanner');
    const { result } = renderHook(() => useNsfwScanner());

    // ACT
    const scanResult = await result.current.scanImage(createMockFile());

    // ASSERT
    expect(scanResult.isNsfw).toEqual(true);
    expect(scanResult.scores).toBeUndefined();
  });

  it('given classification throws, fails closed and blocks the upload', async () => {
    // ARRANGE
    mockClassify.mockRejectedValueOnce(new Error('Classification failed'));

    const { useNsfwScanner } = await import('./useNsfwScanner');
    const { result } = renderHook(() => useNsfwScanner());

    // ACT
    const scanResult = await result.current.scanImage(createMockFile());

    // ASSERT
    expect(scanResult.isNsfw).toEqual(true);
    expect(scanResult.scores).toBeUndefined();
  });

  it('given isEnabled is false, skips scanning and returns not NSFW', async () => {
    // ARRANGE
    const { useNsfwScanner } = await import('./useNsfwScanner');
    const { result } = renderHook(() => useNsfwScanner({ isEnabled: false }));

    // ACT
    const scanResult = await result.current.scanImage(createMockFile());

    // ASSERT
    expect(scanResult.isNsfw).toEqual(false);
    expect(mockClassify).not.toHaveBeenCalled();
  });

  it('given isEnabled is true, preloads the model on mount', async () => {
    // ARRANGE
    const { useNsfwScanner } = await import('./useNsfwScanner');
    mockLoad.mockClear();

    // ACT
    await act(async () => {
      renderHook(() => useNsfwScanner({ isEnabled: true }));
    });

    // ASSERT
    expect(mockLoad).toHaveBeenCalledOnce();
  });

  it('given isEnabled is false, does not preload the model on mount', async () => {
    // ARRANGE
    const { useNsfwScanner } = await import('./useNsfwScanner');
    mockLoad.mockClear();

    // ACT
    await act(async () => {
      renderHook(() => useNsfwScanner({ isEnabled: false }));
    });

    // Allow any pending microtasks to flush.
    await __UNSAFE_VERY_DANGEROUS_SLEEP(10);

    // ASSERT
    expect(mockLoad).not.toHaveBeenCalled();
  });

  it('given a successful scan, revokes the object URL for cleanup', async () => {
    // ARRANGE
    mockClassify.mockResolvedValueOnce(buildPredictions());

    const { useNsfwScanner } = await import('./useNsfwScanner');
    const { result } = renderHook(() => useNsfwScanner());

    // ACT
    await result.current.scanImage(createMockFile());

    // ASSERT
    expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:test');
  });

  it('given the image fails to load, fails closed and blocks the upload', async () => {
    // ARRANGE
    vi.stubGlobal(
      'Image',
      class MockImage {
        onload: (() => void) | null = null;
        onerror: ((error: unknown) => void) | null = null;

        set src(_value: string) {
          queueMicrotask(() => this.onerror?.(new Error('Image load failed')));
        }
      },
    );

    const { useNsfwScanner } = await import('./useNsfwScanner');
    const { result } = renderHook(() => useNsfwScanner());

    // ACT
    const scanResult = await result.current.scanImage(createMockFile());

    // ASSERT
    expect(scanResult.isNsfw).toEqual(true);
    expect(scanResult.scores).toBeUndefined();
  });
});
