$('[name=qty]').each(function() {
    $(this).on('keyup', function() {
        var qty = parseInt($(this).val());
        var dgrid = $(this).parents('.d-grid');
        var before_qty =parseInt(dgrid.find('[name=before_qty]').val());

        if (qty > before_qty) {
            alert('Quantité trop grande');
            $(this).val(before_qty);
            dgrid.find('[name=after_qty]').val(0);            
        } else {
            dgrid.find('[name=after_qty]').val(before_qty - qty);            
        }
    });
})