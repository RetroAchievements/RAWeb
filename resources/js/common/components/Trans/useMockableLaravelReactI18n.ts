import { useLaravelReactI18n } from 'laravel-react-i18n';

/**
 * This just re-exports useLaravelReactI18n with no modifications made.
 * This allows us to mock this export's implementation in tests. We can
 * also spy on what the <Trans /> component calls this hook with.
 *
 * This is useful, because we're not actually interested in the implementation
 * details of `useLaravelReactI18n()`. We're interested in how the component
 * interacts with this hook. The hook is vendor code; it's safe to assume the
 * hook itself works.
 */
export const useMockableLaravelReactI18n = useLaravelReactI18n;
