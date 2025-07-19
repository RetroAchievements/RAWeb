import { createRoot } from 'react-dom/client';

import { AppProviders } from './common/components/AppProviders';
import { loadDayjsLocale } from './common/utils/l10n/loadDayjsLocale';
import i18n from './i18n-client';

/**
 * This function mounts the React global search component on
 * non-Inertia (Blade PHP) pages. Use this pattern sparingly.
 * This should eventually be deleted when the navbar is converted
 * to React.
 */
async function initializeGlobalSearch() {
  /**
   * Get the user locale from the meta tag if authenticated, otherwise fall back to en_US.
   * @see head.blade.php
   */
  const userLocaleMeta = document.querySelector('meta[name="user-locale"]');
  let userLocale = 'en_US';

  if (userLocaleMeta) {
    userLocale = userLocaleMeta.getAttribute('content') || 'en_US';
  }

  // Initialize i18n and dayjs locale.
  await Promise.all([i18n.changeLanguage(userLocale), loadDayjsLocale(userLocale)]);

  // Create a container for the global search if it doesn't exist.
  let searchContainer = document.getElementById('global-search-standalone');
  if (!searchContainer) {
    searchContainer = document.createElement('div');
    searchContainer.id = 'global-search-standalone';

    document.body.appendChild(searchContainer);
  }

  const root = createRoot(searchContainer);
  root.render(
    <AppProviders i18n={i18n}>
      <span />
    </AppProviders>,
  );
}

// Initialize when the DOM is ready.
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeGlobalSearch);
} else {
  initializeGlobalSearch();
}
