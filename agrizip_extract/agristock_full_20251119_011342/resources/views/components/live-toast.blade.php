<div class="position-fixed top-0 end-0 p-3 bg-label-primary" style="z-index: 1111">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <img src="assets/images/logo-sm-dark.png" alt="" class="me-2" height="18">
            <strong class="me-auto">{{ $title }}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">{{ $message }}</div>
    </div>
</div>

<script>
    var toastLiveExample = document.getElementById("liveToast");

    new bootstrap.Toast(toastLiveExample).show();
</script>