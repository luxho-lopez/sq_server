// ==================== VARIABLES DE ESTADO ====================
let currentPage = 1;
let rowsPerPage = 10;
let currentFilter = "";

// ==================== FILTRADO Y PAGINACIÓN ====================
function filterTable() {
    const input = document.getElementById("tableSearch");
    currentFilter = input.value.toLowerCase();
    currentPage = 1;
    paginateTable();
}

function changeRowsPerPage(select) {
    rowsPerPage = select.value === "all" ? -1 : parseInt(select.value);
    currentPage = 1;
    paginateTable();
}

function paginateTable() {
    const table = document.getElementById("dataTable");
    const tr = table.getElementsByTagName("tr");
    let visibleRows = [];

    for (let i = 1; i < tr.length; i++) {
        const row = tr[i];
        const text = row.textContent || row.innerText;
        const match = text.toLowerCase().includes(currentFilter);

        if (match) {
            visibleRows.push(row);
        }

        // Ocultar todas las filas inicialmente
        row.style.display = "none";
    }

    // Mostrar solo las filas de la página actual
    for (let i = 0; i < visibleRows.length; i++) {
        if (
            rowsPerPage === -1 ||
            (i >= (currentPage - 1) * rowsPerPage && i < currentPage * rowsPerPage)
        ) {
            visibleRows[i].style.display = "";
        }
    }

    renderPaginationControls(visibleRows.length);
}

function renderPaginationControls(totalRows) {
    let container = document.getElementById("pagination");
    if (!container) {
        container = document.createElement("div");
        container.id = "pagination";
        container.className = "pagination-controls";
        document.getElementById("dataTable").parentNode.appendChild(container);
    }
    container.innerHTML = "";

    if (rowsPerPage === -1 || totalRows <= rowsPerPage) return;

    const totalPages = Math.ceil(totalRows / rowsPerPage);

    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement("button");
        btn.textContent = i;
        btn.className = i === currentPage ? "active" : "";
        btn.onclick = function () {
            currentPage = i;
            paginateTable();
        };
        container.appendChild(btn);
    }
}
