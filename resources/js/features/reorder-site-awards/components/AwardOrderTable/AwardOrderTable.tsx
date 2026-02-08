import React from 'react';

import type { AwardProps } from '@/features/reorder-site-awards/components/+root';
import { ManualMoveButtons } from '@/features/reorder-site-awards/components/ManualMoveButtons';

interface AwardOrderTableProps {
  title: string;
  awards: AwardProps[];
  awardOwnerUsername: string;
  awardCounterStart: number;
  renderedSectionCount: number;
  prefersSeeingSavedHiddenRows: boolean;
  initialSectionOrder: number;
  eventData: any;
  reorderSiteAwards: any; // your drag + checkbox handlers
  RenderAward: React.ComponentType<any>;
}

export const AwardOrderTable: React.FC<AwardOrderTableProps> = ({
  title,
  awards,
  awardOwnerUsername,
  awardCounterStart,
  renderedSectionCount,
  prefersSeeingSavedHiddenRows,
  initialSectionOrder,
  eventData,
  reorderSiteAwards,
  RenderAward,
}) => {
  const humanReadableAwardKind = title.split(' ')[0].toLowerCase();

  let awardCounter = awardCounterStart;

  const renderAwardTitle = (award: AwardProps) => {
    switch (award.AwardType) {
      case 1: // Mastery (replace with enum if desired)
        return <span className="game-title">{award.Title}</span>;

      case 2:
        return 'Achievements Earned by Others';

      case 3:
        return 'Achievement Points Earned by Others';

      case 4:
        return 'Patreon Supporter';

      case 5:
        return 'Certified Legend';

      default:
        return award.Title;
    }
  };

  return (
    <>
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
            const awardDisplayOrder = award.DisplayOrder;
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
                data-award-date={award.AwardedAt}
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
                  <RenderAward
                    award={award}
                    size={32}
                    awardOwnerUsername={awardOwnerUsername}
                    eventData={eventData}
                    someFlag={false}
                  />
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
                    onChange={(e) =>
                      reorderSiteAwards.handleRowHiddenCheckedChange(e, currentCounter)
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
                        awardCounter={currentCounter}
                        moveValue={1}
                        orientation={'horizontal'}
                        isHiddenPreChecked={isHiddenPreChecked}
                      />
                    )}
                  </div>
                </td>

                {/* Hidden inputs */}
                <input type="hidden" name="type" value={award.AwardType} />
                <input type="hidden" name="data" value={award.AwardData} />
                <input type="hidden" name="extra" value={award.AwardDataExtra} />
              </tr>
            );
          })}
        </tbody>
      </table>
    </>
  );
};
