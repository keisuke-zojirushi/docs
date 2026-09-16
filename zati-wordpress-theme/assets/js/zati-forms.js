document.addEventListener('DOMContentLoaded', function () {

  function moneyToNumber(value) {
    return parseFloat(String(value).replace(/[^0-9.-]/g, '')) || 0;
  }

  function formatMoney(value) {
    return '$' + Number(value).toFixed(2);
  }

  function recalculateClaimTotal() {
    let partsTotal = 0;

    document.querySelectorAll('.zati-parts-table tbody tr').forEach(function (row) {
      const qtyInput = row.querySelector('.zati-part-qty');
      const unitInput = row.querySelector('.zati-part-unit-price');
      const extendedInput = row.querySelector('.zati-part-extended-price');

      const qty = Math.max(1, moneyToNumber(qtyInput ? qtyInput.value : 1));
      const unitPrice = moneyToNumber(unitInput ? unitInput.value : 0);
      const extendedPrice = qty * unitPrice;

      if (qtyInput && !qtyInput.value) {
        qtyInput.value = '1';
      }

      if (extendedInput) {
        extendedInput.value = formatMoney(extendedPrice);
      }

      partsTotal += extendedPrice;
    });

    const labor = moneyToNumber(document.querySelector('[name="labor"]')?.value || '$60.00');
    const shippingIn = moneyToNumber(document.querySelector('[name="shipping_fee_in"]')?.value || '$0.00');
    const shippingOut = moneyToNumber(document.querySelector('[name="shipping_fee_out"]')?.value || '$0.00');

    const stockingFee = partsTotal * 0.10;
    const total = partsTotal + stockingFee + labor + shippingIn + shippingOut;

    document.querySelector('[name="parts_price_total"]').value = formatMoney(partsTotal);
    document.querySelector('[name="labor_total"]').value = formatMoney(labor);
    document.querySelector('[name="stocking_fee_total"]').value = formatMoney(stockingFee);
    document.querySelector('[name="shipping_fee_in_total"]').value = formatMoney(shippingIn);
    document.querySelector('[name="shipping_fee_out_total"]').value = formatMoney(shippingOut);
    document.querySelector('[name="total_payment"]').value = formatMoney(total);
  }

  function zatiUpdatePartRowIndexes() {
    const rows = document.querySelectorAll('.zati-zac-parts-table tbody tr');

    rows.forEach(function (row, index) {
      row.querySelectorAll('input').forEach(function (input) {
        const name = input.getAttribute('name');
        if (!name) return;

        input.setAttribute(
          'name',
          name.replace(/parts\[\d+\]/, 'parts[' + index + ']')
        );
      });

      const removeBtn = row.querySelector('.zati-row-remove');

      if (removeBtn) {
        if (index === 0) {
          removeBtn.classList.add('is-hidden');
        } else {
          removeBtn.classList.remove('is-hidden');
        }
      }
    });
  }

  zatiUpdatePartRowIndexes();
  recalculateClaimTotal();

  document.addEventListener('click', function (event) {
    const lookupBtn = event.target.closest('.zati-part-lookup');
    const clearBtn = event.target.closest('.zati-part-clear');
    const addBtn = event.target.closest('.zati-row-add');
    const removeBtn = event.target.closest('.zati-row-remove');

    if (lookupBtn) {
      const row = lookupBtn.closest('tr');
      const partInput = row.querySelector('.zati-part-number');
      const descInput = row.querySelector('.zati-part-description');
      const priceInput = row.querySelector('.zati-part-unit-price');
      const qtyInput = row.querySelector('.zati-part-qty');

      const partnumber = partInput.value.trim();

      if (!partnumber) {
        alert('Please enter a part number.');
        return;
      }

      const formData = new FormData();
      formData.append('action', 'zati_lookup_part');
      formData.append('nonce', zatiForms.nonce);
      formData.append('partnumber', partnumber);

      fetch(zatiForms.ajaxUrl, {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (!data.success) {
            alert(data.data.message || 'Part was not found.');
            descInput.value = '';
            priceInput.value = '';
            recalculateClaimTotal();
            return;
          }

          descInput.value = data.data.description || '';
          priceInput.value = data.data.unit_price || '$0.00';

          if (qtyInput && !qtyInput.value) {
            qtyInput.value = '1';
          }

          recalculateClaimTotal();
        })
        .catch(() => {
          alert('Lookup failed. Please try again.');
        });

      return;
    }

    if (clearBtn) {
      const row = clearBtn.closest('tr');

      row.querySelector('.zati-part-number').value = '';
      row.querySelector('.zati-part-description').value = '';
      row.querySelector('.zati-part-unit-price').value = '';

      const qtyInput = row.querySelector('.zati-part-qty');
      if (qtyInput) qtyInput.value = '1';

      const extendedInput = row.querySelector('.zati-part-extended-price');
      if (extendedInput) extendedInput.value = '$0.00';

      recalculateClaimTotal();
      return;
    }

    if (addBtn) {
      const currentRow = addBtn.closest('tr');
      const tbody = currentRow.closest('tbody');
      const newRow = currentRow.cloneNode(true);

      newRow.querySelectorAll('input').forEach(function (input) {
        input.value = '';
      });

      const qtyInput = newRow.querySelector('.zati-part-qty');
      if (qtyInput) qtyInput.value = '1';

      const extendedInput = newRow.querySelector('.zati-part-extended-price');
      if (extendedInput) extendedInput.value = '$0.00';

      tbody.appendChild(newRow);
      zatiUpdatePartRowIndexes();
      recalculateClaimTotal();

      return;
    }

    if (removeBtn) {
      const row = removeBtn.closest('tr');
      const tbody = row.closest('tbody');

      if (tbody.querySelectorAll('tr').length > 1) {
        row.remove();
        zatiUpdatePartRowIndexes();
        recalculateClaimTotal();
      }

      return;
    }
  });

  document.addEventListener('input', function (event) {
    if (
      event.target.name === 'shipping_fee_in' ||
      event.target.name === 'shipping_fee_out' ||
      event.target.name === 'labor' ||
      event.target.classList.contains('zati-part-qty')
    ) {
      recalculateClaimTotal();
    }
  });

  document.addEventListener('change', function (event) {
    if (
      event.target.name === 'shipping_fee_in' ||
      event.target.name === 'shipping_fee_out' ||
      event.target.name === 'labor' ||
      event.target.classList.contains('zati-part-qty')
    ) {
      recalculateClaimTotal();
    }
  });

  /* =========================
     Restore Parts After Back
  ========================= */

  window.addEventListener('pageshow', function () {

    let lookupStarted = false;

    document
      .querySelectorAll('.zati-zac-parts-table tbody tr')
      .forEach(function (row) {

        const partInput = row.querySelector(
          '.zati-part-number'
        );

        const descInput = row.querySelector(
          '.zati-part-description'
        );

        const priceInput = row.querySelector(
          '.zati-part-unit-price'
        );

        const lookupButton = row.querySelector(
          '.zati-part-lookup'
        );

        if (
          !partInput ||
          !lookupButton ||
          partInput.value.trim() === ''
        ) {
          return;
        }

        const descriptionMissing =
          !descInput ||
          descInput.value.trim() === '';

        const priceMissing =
          !priceInput ||
          moneyToNumber(priceInput.value) <= 0;

        /*
         * Part NumberはあるがLookup結果がない場合、
         * Parts Masterから再取得する。
         */
        if (descriptionMissing || priceMissing) {
          lookupStarted = true;
          lookupButton.click();
        }
      });

    /*
     * Lookup不要の場合もSummaryを再計算する。
     */
    if (!lookupStarted) {
      recalculateClaimTotal();
    }
  });


});