import { usePageProps } from '@/common/hooks/usePageProps';
import { dangerouslyGetClientSideAssetUrl } from '@/common/utils/dangerouslyGetClientSideAssetUrl';

export function useResetNavbarUserPic() {
  const { auth } = usePageProps();

  const resetNavbarUserPic = () => {
    // Using document functions to mutate the DOM is very bad.
    // We only do this because the app shell is still a Blade template.

    const fileName = `/UserPic/${auth!.user.username}.png`;
    for (const element of document.querySelectorAll<HTMLImageElement>('.userpic')) {
      // Use a query param to ignore the browser cache.
      element.src = `${dangerouslyGetClientSideAssetUrl(fileName)}?${Date.now()}`;
    }
  };

  return { resetNavbarUserPic };
}
