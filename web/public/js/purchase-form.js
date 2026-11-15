(function () {
    var products = window.productsData || [];
    var table = document.querySelector('#items-table tbody');
    var totalEl = document.getElementById('grand-total');
    var rowCount = 0;

    function money(n) { return (Math.round(n * 100) / 100).toFixed(2); }

    function recalc() {
        var total = 0;
        table.querySelectorAll('tr').forEach(function (row) {
            var qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            var cost = parseFloat(row.querySelector('.item-cost').value) || 0;
            var line = qty * cost;
            row.querySelector('.item-total').textContent = money(line);
            total += line;
        });
        var discount = parseFloat(document.getElementById('discount').value) || 0;
        totalEl.textContent = money(Math.max(0, total - discount));
    }

    document.getElementById('add-row').addEventListener('click', function () {
        rowCount++;
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><select class="input item-product" name="items[' + rowCount + '][product_id]" required>' +
            '<option value="">— اختر الصنف —</option>' +
            products.map(function (p) { return '<option value="' + p.id + '" data-cost="' + p.cost_price + '">' + p.name + '</option>'; }).join('') +
            '</select></td>' +
            '<td><input class="input item-qty num" name="items[' + rowCount + '][qty]" type="number" step="0.001" min="0.001" required style="width:100px"></td>' +
            '<td><input class="input item-cost num" name="items[' + rowCount + '][unit_cost]" type="number" step="0.01" min="0" required style="width:110px"></td>' +
            '<td class="num item-total">0.00</td>' +
            '<td><button class="btn btn-danger btn-sm" type="button" onclick="this.closest(\'tr\').remove(); recalc()">حذف</button></td>';
        table.appendChild(tr);
        tr.querySelector('.item-product').addEventListener('change', function () {
            tr.querySelector('.item-cost').value = this.selectedOptions[0].dataset.cost || 0;
            recalc();
        });
        tr.querySelector('.item-qty').addEventListener('input', recalc);
        tr.querySelector('.item-cost').addEventListener('input', recalc);
    });

    document.getElementById('discount').addEventListener('input', recalc);

    document.getElementById('purchase-form').addEventListener('submit', function (e) {
        if (table.querySelectorAll('tr').length === 0) { e.preventDefault(); alert('أضف صنفًا واحدًا على الأقل'); }
    });

    document.getElementById('add-row').click();
})();
