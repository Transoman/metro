// cf7-partial-admin.js

document.addEventListener("DOMContentLoaded", function () {
  const table = document.querySelector(".widefat");
  const headers = table.querySelectorAll("th.sortable");
  const rows = Array.from(table.querySelector("tbody").rows);
  
  headers.forEach((header) => {
    header.addEventListener("click", () => {
      const column = header.dataset.column;
      const isAscending = header.classList.contains("asc");
      const direction = isAscending ? -1 : 1;
      
      headers.forEach((h) => h.classList.remove("asc", "desc"));
      header.classList.add(isAscending ? "desc" : "asc");
      
      rows.sort((a, b) => {
        const aText = a.querySelector(`td:nth-child(${header.cellIndex + 1})`).textContent.trim();
        const bText = b.querySelector(`td:nth-child(${header.cellIndex + 1})`).textContent.trim();
        
        return aText.localeCompare(bText, undefined, {numeric: true}) * direction;
      });
      
      rows.forEach((row) => table.querySelector("tbody").appendChild(row));
    });
  });
  
  // NEW: Handle "Read More" toggles for truncated messages
  const readMoreButtons = document.querySelectorAll('.read-more-button');
  readMoreButtons.forEach((btn) => {
    btn.addEventListener('click', function () {
      const row = this.closest('tr');
      if (!row) return;
      const shortSpan = row.querySelector('.message-short');
      const fullSpan = row.querySelector('.message-full');
      
      if (shortSpan && fullSpan) {
        // Hide short snippet, show full text
        shortSpan.style.display = 'none';
        fullSpan.style.display = 'inline';
        // Remove the button or rename to "Show Less"
        this.remove();
      }
    });
  });
});
