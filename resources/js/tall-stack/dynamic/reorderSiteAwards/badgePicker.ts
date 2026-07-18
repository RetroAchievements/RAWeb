import { fetcher } from '../../utils/fetcher';

// TODO migrate to React, use react-query, and don't roll a dialog by hand

interface SelectableBadge {
  sha1: string;
  url: string;
  label: string;
  isCurrent: boolean;
  isSelected: boolean;
}

const DIALOG_ID = 'badge-picker-dialog';

export async function openBadgePicker(gameId: number): Promise<void> {
  try {
    const { badges } = await fetcher<{ badges: SelectableBadge[] }>(
      `/internal-api/user/games/${gameId}/selectable-badges`,
    );

    renderBadgePickerDialog(badges, (badge, dialog) =>
      commitBadgeChoice(gameId, badge.sha1, dialog),
    );
  } catch {
    window.showStatusFailure?.('Could not load badges for this game.');
  }
}

export async function openMediaContributionTierPicker(): Promise<void> {
  try {
    const { badges } = await fetcher<{ badges: SelectableBadge[] }>(
      '/internal-api/user/media-contribution/selectable-tiers',
    );

    renderBadgePickerDialog(badges, (badge, dialog) =>
      commitMediaContributionTierChoice(Number(badge.sha1), dialog),
    );
  } catch {
    window.showStatusFailure?.('Could not load tiers for this award.');
  }
}

type BadgeTileClickHandler = (
  badge: SelectableBadge,
  dialog: HTMLDialogElement,
) => void | Promise<void>;

export function renderBadgePickerDialog(
  badges: SelectableBadge[],
  onTileClick: BadgeTileClickHandler,
): HTMLDialogElement {
  document.getElementById(DIALOG_ID)?.remove();

  const dialog = document.createElement('dialog');
  dialog.id = DIALOG_ID;
  dialog.className =
    'bg-embed text-text border border-embed-highlight rounded p-0 backdrop:bg-black/70';
  dialog.setAttribute('aria-labelledby', 'badge-picker-title');

  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) {
      closeBadgePicker(dialog);
    }
  });

  const content = document.createElement('div');
  content.className = 'p-4';

  const heading = document.createElement('h2');
  heading.id = 'badge-picker-title';
  heading.textContent = 'Choose displayed badge';
  heading.className = 'mb-3';
  content.appendChild(heading);

  const grid = document.createElement('div');
  grid.className = 'grid grid-cols-4 gap-3 max-h-[60vh] overflow-y-auto p-2';

  for (const badge of badges) {
    grid.appendChild(buildBadgeTile(badge, dialog, onTileClick));
  }

  content.appendChild(grid);

  const cancel = document.createElement('button');
  cancel.type = 'button';
  cancel.className = 'btn mt-4';
  cancel.textContent = 'Cancel';
  cancel.addEventListener('click', () => closeBadgePicker(dialog));
  content.appendChild(cancel);

  dialog.appendChild(content);
  document.body.appendChild(dialog);
  dialog.showModal();

  return dialog;
}

function buildBadgeTile(
  badge: SelectableBadge,
  dialog: HTMLDialogElement,
  onTileClick: BadgeTileClickHandler,
): HTMLButtonElement {
  const tile = document.createElement('button');
  tile.type = 'button';
  tile.dataset.sha1 = badge.sha1;
  tile.className = 'flex flex-col items-center gap-1 p-1 rounded';
  tile.setAttribute('aria-pressed', badge.isSelected ? 'true' : 'false');
  tile.setAttribute(
    'aria-label',
    badge.isCurrent ? `Current badge: ${badge.label}` : `Badge: ${badge.label}`,
  );

  if (badge.isSelected) {
    tile.classList.add(
      'outline',
      'outline-1',
      'outline-offset-2',
      'outline-neutral-400',
      'light:outline-neutral-600',
    );
  }

  const img = document.createElement('img');
  img.src = badge.url;
  img.width = 64;
  img.height = 64;
  img.alt = ''; // the button's aria-label already names the tile.
  tile.appendChild(img);

  const label = document.createElement('span');
  label.className = 'text-2xs';
  label.textContent = badge.label;
  tile.appendChild(label);

  tile.addEventListener('click', () => {
    void onTileClick(badge, dialog);
  });

  return tile;
}

export async function commitBadgeChoice(
  gameId: number,
  sha1: string,
  dialog?: HTMLDialogElement,
): Promise<void> {
  try {
    const { url } = await fetcher<{ success: boolean; url: string }>(
      '/internal-api/user/mastery-badge-preference',
      {
        method: 'POST',
        body: new URLSearchParams({ gameId: String(gameId), sha1 }),
      },
    );

    swapGameBadgeImages(gameId, url);
    window.showStatusSuccess?.('Displayed badge updated.');
    closeBadgePicker(dialog);
  } catch {
    window.showStatusFailure?.('Could not update the displayed badge.');
  }
}

export async function commitMediaContributionTierChoice(
  tierIndex: number,
  dialog?: HTMLDialogElement,
): Promise<void> {
  try {
    const { url } = await fetcher<{ success: boolean; url: string }>(
      '/internal-api/user/media-contribution/tier-preference',
      {
        method: 'PUT',
        body: new URLSearchParams({ tierIndex: String(tierIndex) }),
      },
    );

    swapMediaContributionBadgeImages(url);
    window.showStatusSuccess?.('Displayed badge updated.');
    closeBadgePicker(dialog);
  } catch {
    window.showStatusFailure?.('Could not update the displayed badge.');
  }
}

export function swapGameBadgeImages(gameId: number, url: string): void {
  const images = document.querySelectorAll<HTMLImageElement>(`[data-gameid="${gameId}"] img`);

  for (const img of images) {
    img.src = url;
  }
}

export function swapMediaContributionBadgeImages(url: string): void {
  const images = document.querySelectorAll<HTMLImageElement>('[data-media-contribution-badge] img');

  for (const img of images) {
    img.src = url;
  }
}

function closeBadgePicker(dialog?: HTMLDialogElement): void {
  dialog?.close();
  dialog?.remove();
}
