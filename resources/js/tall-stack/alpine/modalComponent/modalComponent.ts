import { fetcher } from '@/utils';

export function modalComponent(
  resourceApiRoute?: string,
  resourceId?: string | number,
  resourceContext?: string,
) {
  return {
    isModalOpen: false,
    dynamicHtmlContent: '',
    dynamicHtmlStatus: 'idle',
    dynamicResourceApiRoute: resourceApiRoute,

    openModal() {
      this.isModalOpen = true;

      if (this.dynamicResourceApiRoute) {
        this.fetchDynamicHtmlContent();
      }
    },

    closeModal() {
      this.isModalOpen = false;
    },

    async fetchDynamicHtmlContent() {
      if (window.cachedDialogHtmlContent) {
        this.dynamicHtmlContent = window.cachedDialogHtmlContent;

        return;
      }

      if (this.dynamicHtmlStatus !== 'idle') {
        return;
      }

      if (this.dynamicResourceApiRoute) {
        this.dynamicHtmlStatus = 'loading';

        const { html } = await fetcher<{ html: string }>(this.dynamicResourceApiRoute, {
          method: 'POST',
          body: `id=${resourceId}&context=${resourceContext}`,
        });

        this.dynamicHtmlContent = html;
        this.dynamicHtmlStatus = 'success';

        window.cachedDialogHtmlContent = html;
      }
    },
  };
}
