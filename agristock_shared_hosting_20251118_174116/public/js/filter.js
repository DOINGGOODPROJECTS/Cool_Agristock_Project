$(function () {
    const selectionData = window.dashboardSelectionData || [];
    const $customer = $('#customerId');
    const $storage = $('#storageId');
    const $viewBtn = $('#viewDetailsBtn');
    const $detailsCard = $('#selection-details');
    const $detailsBody = $detailsCard.find('.details-body');
    const $detailsEmpty = $detailsCard.find('.details-empty');
    const $detailsCount = $('#selection-details-count');
    const $detailsSubtitle = $('#selection-details-subtitle');

    const updateButtonState = () => {
        const enabled = Boolean($customer.val() && $storage.val());
        $viewBtn.prop('disabled', !enabled);
    };

    const renderDetails = (entries, headerText) => {
        $detailsCard.removeClass('d-none');
        $detailsCount.text(entries.length);
        $detailsSubtitle.text(headerText);

        if (!entries.length) {
            $detailsBody.addClass('d-none').empty();
            $detailsEmpty.removeClass('d-none')
                .text(window.dashboardTranslations?.noResults || 'No matching records found for this selection.');
            return;
        }

        const markup = entries.map((entry) => `
            <div class="detail-item">
                <div>
                    <p class="detail-label">Ref</p>
                    <div class="detail-value">${entry.ref || '—'}</div>
                </div>
                <div>
                    <p class="detail-label">Bill</p>
                    <div class="detail-value">${entry.billing || '—'}</div>
                </div>
                <div>
                    <p class="detail-label">Qty</p>
                    <div class="detail-value">${entry.qty ?? '0'} kg</div>
                </div>
                <div>
                    <p class="detail-label">Expires</p>
                    <div class="detail-value">${entry.expires_on || '—'} (${entry.expires_in} days)</div>
                </div>
                <div>
                    <p class="detail-label">Status</p>
                    <div class="detail-value">${entry.status}</div>
                </div>
            </div>
        `).join('');

        $detailsBody.removeClass('d-none').html(markup);
        $detailsEmpty.addClass('d-none');
    };

    $viewBtn.on('click', function () {
        if ($viewBtn.prop('disabled')) return;
        const customerId = $customer.val();
        const storageId = $storage.val();

        const customerLabel = $customer.find('option:selected').text();
        const storageLabel = $storage.find('option:selected').text();

        const matches = selectionData.filter((item) => {
            return String(item.customer_id) === String(customerId) &&
                   String(item.storage_id) === String(storageId);
        });

        renderDetails(matches, `${customerLabel} • ${storageLabel}`);
        $detailsCard[0]?.scrollIntoView({ behavior: 'smooth', block: 'start' });

        if (matches.length) {
            setTimeout(() => {
                const firstItem = $detailsBody.find('.detail-item').first();
                firstItem.addClass('highlighted');
                setTimeout(() => firstItem.removeClass('highlighted'), 2000);
            }, 100);
        }
    });

    $customer.on('change', updateButtonState);
    $storage.on('change', updateButtonState);
    updateButtonState();
});
