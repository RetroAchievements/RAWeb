/**
 * Creates an eased gradient with many stops to reduce visual banding.
 * The stops follow a curve that provides a smooth visual transition.
 */
export function buildEasedGradient(
  direction: 'to bottom' | 'to top',
  color: 'black' | 'white',
  maxOpacity: number,
): string {
  const rgb = color === 'black' ? '0,0,0' : '255,255,255';
  const stops = direction === 'to bottom' ? topGradientStops : bottomGradientStops;

  const gradientStops = stops
    .map(([opacity, position]) => `rgba(${rgb},${opacity * maxOpacity}) ${position}%`)
    .join(',\n            ');

  return `linear-gradient(${direction},
            ${gradientStops}
          )`;
}

// Concentrated at the top so the navbar blends smoothly into the banner.
const topGradientStops: Array<[number, number]> = [
  [1, 0],
  [0.9, 5],
  [0.77, 10],
  [0.63, 15],
  [0.5, 20],
  [0.37, 25],
  [0.25, 30],
  [0.15, 36],
  [0.07, 43],
  [0.02, 50],
  [0, 58],
];

// Spread across the full height so overlaid text remains readable at any position.
const bottomGradientStops: Array<[number, number]> = [
  [1, 0],
  [0.9, 5],
  [0.78, 10],
  [0.67, 16],
  [0.55, 23],
  [0.43, 31],
  [0.32, 40],
  [0.22, 50],
  [0.13, 62],
  [0.07, 75],
  [0.02, 88],
  [0, 100],
];
