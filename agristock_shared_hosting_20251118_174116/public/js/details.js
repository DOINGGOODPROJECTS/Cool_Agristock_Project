$('#form').on('submit', function() {
    if($('#tbody').children().length == 0) {
        alert('Veuillez ajouter au moins un produit');
        return false;
    }

    let total = parseInt(localStorage.getItem('total')),
        stock = parseInt($('#qty').val()),
        available = parseInt($('#storageId').children('option:selected').attr('accesskey'));

    if(stock != total) {
        alert('Attention, la quantité totale des produits ne correspond pas à la quantité du stock');
        return false;
    }

    if(stock > available) {
        alert('La zone de stockage ne peut pas contenir cette quantité de produits');
        return false;
    }
})

var deleter = function(item) {
    let qty = $(item).closest('tr').find('[name="qtys[]"]').val();
    let total = parseInt(localStorage.getItem('total')) - qty;
    localStorage.setItem('total', total);
    $(item).closest('tr').remove(); 
} 

$('#new-row').on('click', function() {
    let product = $('#product_name').children('option:selected').text(),
        id = $('#product_name').children('option:selected').val(),
        containerid = $('#container').children('option:selected').val(),
        container = $('#container').children('option:selected').text(),
        expired = $('#product_name').children('option:selected').attr('title'),
        qty = parseInt($('#product_qty').val()),
        stock = parseInt($('#qty').val()),
        total = parseInt(localStorage.getItem('total'));

    if(qty != undefined && qty > 0 && id != undefined && id != '') {
        total += qty;
        if(total <= stock) {
            localStorage.setItem('total', total);
            $('#product_qty').val('');
            let products = {'id':id, 'product':product, 'qty':qty, 'expired':expired, 'containerid':containerid, 'container':container};
            $('#tbody').append('<tr class="mg-0"><td>'+ products.product +'</td><td>'+products.qty+'<input type="hidden" class="form-control" style="text-align: center" name="qtys[]" value="'+products.qty+'" /></td><td>'+products.container+'<input type="hidden" class="form-control" style="text-align: center" name="containers[]" value="'+products.containerid+'" /></td><td>'+products.expired+' Jours</td><td class="text-center"><i class="fa fa-trash text-danger" onclick="deleter(this)" title="'+products.qty+'"></i></td><input type="hidden" name="product_id[]" value="'+products.id+'" /></tr>');                                               
        } else {
            alert('La quantité totale des produits ne doit pas dépasser la quantité du stock');
        }  
    }
})