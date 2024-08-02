export function useCardTooltip(args: {
  dynamicType: 'user' | 'game' | 'achievement' | 'ticket';
  dynamicId: string | number | null;
  dynamicContext?: string;
}) {
  const { dynamicId, dynamicType } = args;

  // TODO migrate this out of Alpine.js
  const cardTooltipProps = {
    'x-data': `tooltipComponent($el, {dynamicType: '${dynamicType}', dynamicId: '${dynamicId}', dynamicContext: '${args.dynamicContext}'})`,
    'x-on:mouseover': 'showTooltip($event)',
    'x-on:mouseleave': 'hideTooltip',
    'x-on:mousemove': 'trackMouseMovement($event)',
  };

  return { cardTooltipProps };
}
