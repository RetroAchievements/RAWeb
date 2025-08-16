export function handleSortByAwardDate(
    direction: "asc" | "desc",
    awardKind: string
): void {
    // Determine the correct table by awardKind
    const tableId = `${awardKind}-reorder-table`;
    const table = document.getElementById(tableId) as HTMLTableElement | null;
    const tbody = table ? table.querySelector("tbody") : null;
    if (!tbody) return;

    // Only select rows for this awardKind in this table
    const rows = Array.from(
        tbody.querySelectorAll<HTMLTableRowElement>(".award-table-row")
    ).filter(
        (row) => row.getAttribute("data-award-kind") === awardKind
    );

    // Sort rows by data-completion-date (descending or ascending)
    rows.sort((a, b) => {
        const dateA = a.getAttribute("data-award-date") ?? "";
        const dateB = b.getAttribute("data-award-date") ?? "";

        let numA: number;
        let numB: number;

        if (!isNaN(Number(dateA)) && !isNaN(Number(dateB))) {
            numA = parseInt(dateA, 10);
            numB = parseInt(dateB, 10);
        } else {
            numA = Date.parse(dateA) || 0;
            numB = Date.parse(dateB) || 0;
        }

        return direction === "asc" ? numA - numB : numB - numA;
    });

    for (const row of rows) tbody.appendChild(row);
}
