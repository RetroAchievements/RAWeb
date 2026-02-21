import React, { useRef, useState } from 'react';

import { ManualMoveButtons } from '@/features/reorder-site-awards/components/ManualMoveButtons';
import UserAwardData = App.Community.Data.UserAwardData;
import { DragDropProvider } from '@dnd-kit/react';
import { useTranslation } from 'react-i18next';

import { UserAward } from '@/common/components/UserAward';

interface AwardOrderTableProps {
  title: string;
  awards: UserAwardData[];
  awardCounterStart: number;
  renderedSectionCount: number;
  prefersSeeingSavedHiddenRows: boolean;
  initialSectionOrder: number;
  reorderSiteAwards: any; // your drag + checkbox handlers
}

const initialColumns: { id: string; name: string }[] = [
  { id: 'imageUrl', name: 'Badge' },
  { id: 'title', name: 'Site Award' },
  { id: 'hidden', name: 'Hidden' },
  { id: 'manualMove', name: 'Manual Move' },
];

export const AwardOrderTableOld: React.FC<AwardOrderTableProps> = ({
  title,
  awards,
  awardCounterStart,
  renderedSectionCount,
  prefersSeeingSavedHiddenRows,
  initialSectionOrder,
  reorderSiteAwards,
}) => {
  const humanReadableAwardKind = title.split(' ')[0].toLowerCase();

  const { t } = useTranslation();

  let awardCounter = awardCounterStart;

  const renderAwardTitle = (award: UserAwardData) => {
    switch (award.awardType) {
      case 'mastery': // Mastery (replace with enum if desired)
        return <span className="game-title">{award.awardSection}</span>;

      case 'achievement_unlocks_yield':
        return 'Achievements Earned by Others';

      case 'achievement_points_yield':
        return 'Achievement Points Earned by Others';

      case 'patreon_supporter':
        return 'Patreon Supporter';

      case 'certified_legend':
        return 'Certified Legend';

      default:
        return award.tooltip;
    }
  };

  const [rows, setRows] = useState(awards);
  const [columns, setColumns] = useState(initialColumns);
  const initialOrder = useRef({
    columns,
    rows,
  });

  return (
    <DragDropProvider>
      <div className="flex w-full items-center justify-between">
        <h4>{title}</h4>

        <select data-award-kind={humanReadableAwardKind}>
          {Array.from({ length: renderedSectionCount }, (_, i) => {
            const value = i + 1;

            return (
              <option key={value} value={value} selected={initialSectionOrder === value}>
                {value}
              </option>
            );
          })}
        </select>
      </div>

      <table id={`${humanReadableAwardKind}-reorder-table`} className="mb-8">
        <thead>
          <tr className="do-not-highlight">
            <th></th>
            <th>Badge</th>
            <th width="60%">Site Award</th>
            <th className="text-center">Hidden</th>
            <th className="text-right" width="20%">
              Manual Move
            </th>
          </tr>
        </thead>

        <tbody>
          {awards.map((award, index) => {
            const awardDisplayOrder = award.displayOrder;
            const isHiddenPreChecked = awardDisplayOrder === -1;

            const subduedOpacityClassName = isHiddenPreChecked ? 'opacity-40' : '';

            const cursorGrabClass = isHiddenPreChecked ? '' : 'cursor-grab';

            const savedHiddenClass = isHiddenPreChecked ? 'saved-hidden' : '';

            const hiddenClass = !prefersSeeingSavedHiddenRows && isHiddenPreChecked ? 'hidden' : '';

            const rowClassNames = `
              award-table-row
              select-none
              transition
              ${cursorGrabClass}
              ${savedHiddenClass}
              ${hiddenClass}
            `;

            const currentCounter = awardCounter++;

            return (
              <tr
                key={currentCounter}
                data-row-index={currentCounter}
                data-award-kind={humanReadableAwardKind}
                data-award-date={award.dateAwarded}
                draggable={!isHiddenPreChecked}
                className={rowClassNames}
                /* onDragStart={reorderSiteAwards.handleRowDragStart}
                onDragEnter={reorderSiteAwards.handleRowDragEnter}
                onDragLeave={reorderSiteAwards.handleRowDragLeave}
                onDragOver={reorderSiteAwards.handleRowDragOver}
                onDragEnd={reorderSiteAwards.handleRowDragEnd}
                onDrop={reorderSiteAwards.handleRowDrop} */
              >
                {/* Badge */}
                <td className={`${subduedOpacityClassName} transition`}>
                  <UserAward award={award} size={32} />
                </td>

                {/* Title */}
                <td className={`${subduedOpacityClassName} transition`}>
                  <span>{renderAwardTitle(award)}</span>
                </td>

                {/* Hidden checkbox */}
                <td className="text-center !opacity-100">
                  <input
                    name={`${currentCounter}-is-hidden`}
                    type="checkbox"
                    defaultChecked={isHiddenPreChecked}
                    onChange={
                      (e) => false
                      // reorderSiteAwards.handleRowHiddenCheckedChange(e, currentCounter)
                    }
                  />
                </td>

                {/* Manual Move */}
                <td>
                  <div
                    className={`award-movement-buttons flex justify-end transition ${
                      isHiddenPreChecked ? 'opacity-0' : 'opacity-100'
                    }`}
                  >
                    {awards.length > 50 && (
                      <>
                        <ManualMoveButtons
                          awardCounter={currentCounter}
                          moveValue={99999}
                          upLabel={' Top'}
                          downLabel={' Bottom'}
                          autoScroll={true}
                          orientation={'vertical'}
                          isHiddenPreChecked={isHiddenPreChecked}
                        />
                        <ManualMoveButtons
                          awardCounter={currentCounter}
                          moveValue={50}
                          upLabel={'50'}
                          downLabel={'50'}
                          autoScroll={true}
                          isHiddenPreChecked={isHiddenPreChecked}
                        />
                        <ManualMoveButtons
                          awardCounter={currentCounter}
                          moveValue={1}
                          isHiddenPreChecked={isHiddenPreChecked}
                        />
                      </>
                    )}

                    {awards.length > 15 && awards.length <= 50 && (
                      <>
                        <ManualMoveButtons
                          awardCounter={currentCounter}
                          moveValue={10}
                          upLabel={'10'}
                          downLabel={'10'}
                          autoScroll={true}
                          isHiddenPreChecked={isHiddenPreChecked}
                        />
                        <ManualMoveButtons
                          awardCounter={currentCounter}
                          moveValue={1}
                          isHiddenPreChecked={isHiddenPreChecked}
                        />
                      </>
                    )}

                    {awards.length <= 15 && (
                      <ManualMoveButtons
                        award={award}
                        awardCounter={currentCounter}
                        moveValue={1}
                        orientation={'horizontal'}
                        isHiddenPreChecked={isHiddenPreChecked}
                      />
                    )}
                  </div>
                </td>

                {/* Hidden inputs */}
                <input type="hidden" name="type" value={award.awardType} />
                {/*<input type="hidden" name="data" value={award.AwardData} />*/}
                {/*<input type="hidden" name="extra" value={award.AwardDataExtra} />*/}
              </tr>
            );
          })}
        </tbody>
      </table>
    </DragDropProvider>
  );
};
