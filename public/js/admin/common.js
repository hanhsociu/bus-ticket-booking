function confirmAction(message = 'Bạn chắc chắn?') {
    return confirm(message);
}

function renderTableLoading(tbodyId) {
    const tb = document.getElementById(tbodyId);
    if (tb) tb.innerHTML = `<tr><td colspan="20" class="text-center py-4"><div class="spinner-border spinner-border-sm"></div></td></tr>`;
}

function paginateLinks(paginated, onPage) {
    if (!paginated?.last_page || paginated.last_page <= 1) return '';
    let html = '<ul class="pagination pagination-sm justify-content-end">';
    for (let i = 1; i <= paginated.last_page; i++) {
        html += `<li class="page-item ${i === paginated.current_page ? 'active' : ''}">
            <a class="page-link" href="#" data-p="${i}">${i}</a></li>`;
    }
    html += '</ul>';
    setTimeout(() => {
        document.querySelectorAll('[data-p]').forEach(a => a.addEventListener('click', e => {
            e.preventDefault();
            onPage(parseInt(a.dataset.p));
        }));
    }, 0);
    return html;
}

window.confirmAction = confirmAction;
window.renderTableLoading = renderTableLoading;
window.paginateLinks = paginateLinks;
