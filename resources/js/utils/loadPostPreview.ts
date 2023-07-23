import { fetcher } from './fetcher';

let mostRecentPreviewContent = '';

export const loadPostPreview = async (
  textareaElId = 'commentTextarea',
  previewElId = 'post-preview',
  loadingElId = 'preview-loading-icon'
) => {
  // Locate the element on the page where we'll be dumping preview content.
  // If the element isn't on the page, we can prematurely bail.
  const previewEl = document.getElementById(previewElId) as HTMLDivElement | null;
  if (!previewEl) {
    return;
  }

  // Locate the user input element and retrieve its current input value.
  const textareaEl = document.getElementById(textareaElId) as HTMLTextAreaElement | null;
  const postContent = textareaEl?.value ?? '';

  // Don't send duplicate calls to the back-end.
  if (postContent === mostRecentPreviewContent) {
    return;
  }

  mostRecentPreviewContent = postContent;

  try {
    setLoadingIconVisibility(loadingElId, { isVisible: true });
    const { postPreviewHtml } = await fetcher<{ message: string; postPreviewHtml: string }>(
      '/request/forum-topic-comment/preview.php',
      {
        method: 'POST',
        body: `body=${encodeURIComponent(postContent)}`,
      }
    );

    if (postPreviewHtml) {
      previewEl.innerHTML = postPreviewHtml;
    }

    setLoadingIconVisibility(loadingElId, { isVisible: false });
  } catch (error) {
    // Legacy from all.js.
    (window as any).showStatusFailure(error);
    console.error(error);

    setLoadingIconVisibility(loadingElId, { isVisible: false });
  }
};

const setLoadingIconVisibility = (elementId: string, options: { isVisible: boolean }): void => {
  const loadingImageEl = document.getElementById('preview-loading-icon') as HTMLImageElement | null;

  if (loadingImageEl) {
    loadingImageEl.style.opacity = options.isVisible ? '100' : '0';
  }
};
