import type { FC } from 'react';

export const LegalNotice: FC = () => {
  return (
    <div>
      <p role="heading" aria-level={3} className="text-lg font-medium">
        Disclaimer about ROMs
      </p>

      <p className="font-bold">
        RetroAchievements.org does not condone or supply any copyright-protected ROMs to be used in
        conjunction with the supplied emulators.
      </p>

      <p>There are no copyright-protected ROMs available for download on RetroAchievements.org.</p>
    </div>
  );
};
