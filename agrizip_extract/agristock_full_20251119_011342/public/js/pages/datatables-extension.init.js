$(document).ready(function(){
    var a;
    document.getElementById("datatable-buttons")
    &&
    $("#datatable-buttons").DataTable({
        lengthChange:!1,
        buttons: [
            {
                extend: "copy",
                text: "<i class='mdi mdi-content-copy'></i>",
                titleAttr: "Copier",
                className: "btn-secondary"
            },
            {
                extend: "excel",
                text: "<i class='mdi mdi-file-excel'></i>",
                titleAttr: "Exporter en Excel",
                className: "btn-success"
            },
            {
                extend: "pdf",
                text: "<i class='mdi mdi-file-pdf'></i>",
                titleAttr: "Exporter en PDF",
                className: "btn-danger"
            },
            {
                extend: "colvis",
                text: "<i class='mdi mdi-eye'></i>",
                titleAttr: "Afficher/Masquer colonnes",
                className: "btn-info"
            }
        ],
        language:{
            paginate:{
                previous:"<i class='mdi mdi-arrow-left'>",next:"<i class='mdi mdi-arrow-right'>"
            }
        },
        drawCallback:function() {
            $(".dataTables_paginate > .pagination").addClass("pagination")
        }
    })
    .buttons()
    .container()
    .appendTo("#datatable-buttons_wrapper .col-md-6:eq(0)"),

    $("#datatable-autofill").DataTable({
        autoFill:!0,
        language:{
            paginate:{
                previous:"<i class='mdi mdi-chevron-left'>",next:"<i class='mdi mdi-chevron-right'>"
            }
        },
        drawCallback:function() {
            $(".dataTables_paginate > .pagination").addClass("pagination")
        }
    }),
    $(".dataTables_length select").addClass("form-select form-select-sm"),
    
    $("#datatable-colReorder").DataTable({
        colReorder:!0,
        language:{
            paginate:{
                previous:"<i class='mdi mdi-chevron-left'>",next:"<i class='mdi mdi-chevron-right'>"
            }
        },
        drawCallback:function() {
            $(".dataTables_paginate > .pagination").addClass("pagination")
        }
    }),
    $("#datatable-fixedheader").DataTable({
        fixedHeader:!0,
        language:{
            paginate:{
                previous:"<i class='mdi mdi-chevron-left'>",next:"<i class='mdi mdi-chevron-right'>"
            }
        },
        drawCallback:function() {
            $(".dataTables_paginate > .pagination").addClass("pagination")
        }
    }),
    $("#datatable-fixedcolumn").DataTable({
        scrollY:"300px",
        scrollX:!0,
        scrollCollapse:!0,
        paging:!1,
        fixedColumns:!0,
        language:{
            paginate:{
                previous:"<i class='mdi mdi-chevron-left'>",
                next:"<i class='mdi mdi-chevron-right'>"
            }
        },
        drawCallback:function() {
            $(".dataTables_paginate > .pagination").addClass("pagination")
        }
    }),
    $("#datatable-keytable").DataTable({
        keys:!0,
        language:{
            paginate:{
                previous:"<i class='mdi mdi-chevron-left'>",next:"<i class='mdi mdi-chevron-right'>"
            }
        },
        drawCallback:function() {
            $(".dataTables_paginate > .pagination").addClass("pagination")
        }
    }),
    $("#datatable-row-group").DataTable({
        order:[[2,"asc"]],
        rowGroup:{dataSrc:2},
        drawCallback:function() {
            $(".dataTables_paginate > .pagination").addClass("pagination")
        }
    }),
    $("#datatable-rowReorder").DataTable({
        rowReorder:!0,
        language:{
            paginate:{
                previous:"<i class='mdi mdi-chevron-left'>",next:"<i class='mdi mdi-chevron-right'>"
            }
        },
        drawCallback:function() {
            $(".dataTables_paginate > .pagination").addClass("pagination")
        }
    }),
    $("#datatable-keytable").DataTable({
        keys:!0,
        language:{
            paginate:{
                previous:"<i class='mdi mdi-chevron-left'>",next:"<i class='mdi mdi-chevron-right'>"
            }
        },
        drawCallback:function() {
            $(".dataTables_paginate > .pagination").addClass("pagination")
        }
    }),
    $("#datatable-scrollable").DataTable({
        deferRender:!0,
        scrollY:200,
        scrollCollapse:!0,
        scroller:!0,
        language:{
            paginate:{
                previous:"<i class='mdi mdi-chevron-left'>",
                next:"<i class='mdi mdi-chevron-right'>"
            }
        },
        drawCallback:function() {
            $(".dataTables_paginate > .pagination").addClass("pagination")
        }
    }),
    document.getElementById("datatable-searchpanes")
    &&
    ((a=$("#datatable-searchpanes").DataTable({
        searchPanes:!0,
        language:{
            paginate:{
                previous:"<i class='mdi mdi-chevron-left'>",next:"<i class='mdi mdi-chevron-right'>"
            }
        },
        drawCallback:function() {
            $(".dataTables_paginate > .pagination").addClass("pagination")
        }
    }))
    .searchPanes.container()
    .prependTo(a.table().container()),
    a.searchPanes.resizePanes()),
    
    $("#datatable-select").DataTable({
        select:!0,
        language:{
            paginate:{
                previous:"<i class='mdi mdi-chevron-left'>",next:"<i class='mdi mdi-chevron-right'>"
            }
        },
        drawCallback:function() {
            $(".dataTables_paginate > .pagination").addClass("pagination")
        }
    })
});