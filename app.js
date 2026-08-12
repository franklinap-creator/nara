function stepQty(button, amount) {
    const input = button.parentElement.querySelector('input');
    input.value = Math.max(0, Math.min(20, (parseInt(input.value) || 0) + amount));
}

document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.product-tab');
    const panels = document.querySelectorAll('.product-panel');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            const target = tab.dataset.panel;
            tabs.forEach(function (item) {
                item.classList.toggle('active', item === tab);
            });
            panels.forEach(function (panel) {
                panel.classList.toggle('active', panel.dataset.panelContent === target);
            });
        });
    });
});
