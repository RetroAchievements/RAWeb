import { usePageProps } from '@/common/hooks/usePageProps';
import { asset } from '@/utils/helpers';

export function useResetNavbarUserPic() {
  const { auth } = usePageProps();

  const resetNavbarUserPic = () => {
    // Using document functions to mutate the DOM is very bad.
    // We only do this because the app shell is still a Blade template.

    const userDisplayName = auth?.user.displayName ?? '';
    const fileName = `/UserPic/${userDisplayName}.png`;

    for (const element of document.querySelectorAll<HTMLImageElement>('.userpic')) {
      const now = new Date(); // use a query param to ignore the browser cache
      element.src = `${asset(fileName)}` + '?' + now.getTime();
    }
  };

  return { resetNavbarUserPic };
}
